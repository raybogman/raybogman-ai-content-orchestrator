<?php
/**
 * Instagram integration via Meta Graph API.
 *
 * Handles OAuth, token management, and publishing posts to Instagram
 * Business/Creator accounts via the Instagram Graph API.
 *
 * @package Raybogman_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class RBCO_Instagram {

	const AUTH_URL    = 'https://www.facebook.com/v21.0/dialog/oauth';
	const TOKEN_URL   = 'https://graph.facebook.com/v21.0/oauth/access_token';
	const GRAPH_URL   = 'https://graph.facebook.com/v21.0';

	/**
	 * Get Instagram OAuth authorization URL.
	 *
	 * @return string Authorization URL.
	 */
	public static function get_auth_url() {
		$client_id    = self::get_client_id();
		$redirect_uri = admin_url( 'admin.php?page=rbco-settings&tab=instagram' );

		$state = wp_create_nonce( 'rbco_instagram_oauth' );
		set_transient( 'rbco_instagram_oauth_state', $state, 600 );

		$params = array(
			'client_id'     => $client_id,
			'redirect_uri'  => $redirect_uri,
			'scope'         => 'instagram_basic,instagram_content_publish,pages_show_list,pages_read_engagement',
			'response_type' => 'code',
			'state'         => $state,
		);

		return self::AUTH_URL . '?' . http_build_query( $params );
	}

	/**
	 * Handle OAuth callback — exchange code for tokens and find Instagram account.
	 *
	 * @param string $code  Authorization code.
	 * @param string $state State parameter for CSRF validation.
	 * @return true|WP_Error
	 */
	public static function handle_callback( $code, $state ) {
		$stored_state = get_transient( 'rbco_instagram_oauth_state' );
		if ( ! $stored_state || $state !== $stored_state ) {
			return new \WP_Error( 'invalid_state', 'OAuth state mismatch. Please try again.' );
		}
		delete_transient( 'rbco_instagram_oauth_state' );

		$client_id     = self::get_client_id();
		$client_secret = self::get_client_secret();
		$redirect_uri  = admin_url( 'admin.php?page=rbco-settings&tab=instagram' );

		// Exchange code for access token.
		$response = wp_remote_get( add_query_arg( array(
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'redirect_uri'  => $redirect_uri,
			'code'          => $code,
			'grant_type'    => 'authorization_code',
		), self::TOKEN_URL ) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) {
			$error = isset( $body['error']['message'] ) ? $body['error']['message'] : 'No access token received.';
			return new \WP_Error( 'token_error', $error );
		}

		$short_token = $body['access_token'];

		// Exchange for long-lived token (60 days).
		$long_response = wp_remote_get( add_query_arg( array(
			'grant_type'    => 'fb_exchange_token',
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'fb_exchange_token' => $short_token,
		), self::TOKEN_URL ) );

		if ( ! is_wp_error( $long_response ) ) {
			$long_body = json_decode( wp_remote_retrieve_body( $long_response ), true );
			if ( ! empty( $long_body['access_token'] ) ) {
				$short_token = $long_body['access_token'];
				$expires_in  = isset( $long_body['expires_in'] ) ? (int) $long_body['expires_in'] : 5184000;
			}
		}

		$expires_at = time() + ( isset( $expires_in ) ? $expires_in : 3600 );

		// Find Instagram Business Account via Facebook Pages.
		$ig_account = self::find_instagram_account( $short_token );
		if ( is_wp_error( $ig_account ) ) {
			return $ig_account;
		}

		// Store tokens and profile.
		update_option( 'rbco_instagram_tokens', array(
			'access_token' => $short_token,
			'expires_at'   => $expires_at,
		) );

		update_option( 'rbco_instagram_profile', array(
			'ig_user_id' => $ig_account['id'],
			'username'   => $ig_account['username'],
			'name'       => isset( $ig_account['name'] ) ? $ig_account['name'] : $ig_account['username'],
		) );

		return true;
	}

	/**
	 * Find the Instagram Business Account connected to a Facebook Page.
	 *
	 * @param string $access_token Facebook access token.
	 * @return array|WP_Error Instagram account data.
	 */
	private static function find_instagram_account( $access_token ) {
		// Get user's Facebook Pages.
		$pages_response = wp_remote_get( self::GRAPH_URL . '/me/accounts?' . http_build_query( array(
			'access_token' => $access_token,
			'fields'       => 'id,name,instagram_business_account',
		) ) );

		if ( is_wp_error( $pages_response ) ) {
			return $pages_response;
		}

		$pages = json_decode( wp_remote_retrieve_body( $pages_response ), true );
		if ( empty( $pages['data'] ) ) {
			return new \WP_Error( 'no_pages', 'No Facebook Pages found. You need a Facebook Page connected to an Instagram Business account.' );
		}

		// Find first page with an Instagram Business Account.
		foreach ( $pages['data'] as $page ) {
			if ( ! empty( $page['instagram_business_account']['id'] ) ) {
				$ig_id = $page['instagram_business_account']['id'];

				// Get Instagram username.
				$ig_response = wp_remote_get( self::GRAPH_URL . '/' . $ig_id . '?' . http_build_query( array(
					'access_token' => $access_token,
					'fields'       => 'id,username,name,profile_picture_url',
				) ) );

				if ( ! is_wp_error( $ig_response ) ) {
					$ig_data = json_decode( wp_remote_retrieve_body( $ig_response ), true );
					if ( ! empty( $ig_data['id'] ) ) {
						return $ig_data;
					}
				}

				return array( 'id' => $ig_id, 'username' => $page['name'] );
			}
		}

		return new \WP_Error( 'no_instagram', 'No Instagram Business account found connected to your Facebook Pages. Make sure your Instagram account is a Business or Creator account and is linked to a Facebook Page.' );
	}

	/**
	 * Share a post to Instagram.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return true|WP_Error
	 */
	public static function share_post( $post_id ) {
		$access_token = self::get_access_token();
		if ( ! $access_token ) {
			return new \WP_Error( 'not_connected', 'Instagram is not connected.' );
		}

		$profile = self::get_profile();
		if ( empty( $profile['ig_user_id'] ) ) {
			return new \WP_Error( 'no_profile', 'Instagram profile not found.' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new \WP_Error( 'no_post', 'Post not found.' );
		}

		// Get featured image URL (required for Instagram).
		$image_url = '';
		$thumbnail_id = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			$image_data = wp_get_attachment_image_src( $thumbnail_id, 'full' );
			if ( $image_data ) {
				$image_url = $image_data[0];
			}
		}

		if ( empty( $image_url ) ) {
			return new \WP_Error( 'no_image', 'Instagram requires a featured image. Set a featured image on the post first.' );
		}

		// Get caption — use stored Instagram caption or generate from excerpt.
		$caption = get_post_meta( $post_id, '_rbco_instagram_caption', true );
		if ( empty( $caption ) ) {
			$caption = wp_strip_all_tags( $post->post_excerpt );
			if ( empty( $caption ) ) {
				$caption = wp_trim_words( wp_strip_all_tags( $post->post_content ), 50 );
			}
			$caption .= "\n\n" . get_permalink( $post_id );
		}

		// Step 1: Create media container.
		$container_response = wp_remote_post( self::GRAPH_URL . '/' . $profile['ig_user_id'] . '/media', array(
			'timeout' => 30,
			'body'    => array(
				'access_token' => $access_token,
				'image_url'    => $image_url,
				'caption'      => $caption,
			),
		) );

		if ( is_wp_error( $container_response ) ) {
			return $container_response;
		}

		$container = json_decode( wp_remote_retrieve_body( $container_response ), true );
		if ( empty( $container['id'] ) ) {
			$error = isset( $container['error']['message'] ) ? $container['error']['message'] : 'Failed to create media container.';
			return new \WP_Error( 'container_error', $error );
		}

		// Step 2: Publish the container.
		$publish_response = wp_remote_post( self::GRAPH_URL . '/' . $profile['ig_user_id'] . '/media_publish', array(
			'timeout' => 30,
			'body'    => array(
				'access_token' => $access_token,
				'creation_id'  => $container['id'],
			),
		) );

		if ( is_wp_error( $publish_response ) ) {
			return $publish_response;
		}

		$publish = json_decode( wp_remote_retrieve_body( $publish_response ), true );
		if ( empty( $publish['id'] ) ) {
			$error = isset( $publish['error']['message'] ) ? $publish['error']['message'] : 'Failed to publish to Instagram.';
			return new \WP_Error( 'publish_error', $error );
		}

		// Mark as shared.
		update_post_meta( $post_id, '_rbco_instagram_shared', time() );
		update_post_meta( $post_id, '_rbco_instagram_media_id', $publish['id'] );
		delete_post_meta( $post_id, '_rbco_instagram_error' );

		return true;
	}

	/**
	 * Get a valid access token.
	 *
	 * @return string|false Access token or false if not available/expired.
	 */
	public static function get_access_token() {
		$tokens = get_option( 'rbco_instagram_tokens', array() );
		if ( empty( $tokens['access_token'] ) ) {
			return false;
		}

		// Check expiration with 5-min buffer.
		if ( ! empty( $tokens['expires_at'] ) && $tokens['expires_at'] < ( time() + 300 ) ) {
			return false;
		}

		return $tokens['access_token'];
	}

	/**
	 * Check if Instagram is connected.
	 *
	 * @return bool
	 */
	public static function is_connected() {
		return (bool) self::get_access_token();
	}

	/**
	 * Get stored Instagram profile.
	 *
	 * @return array Profile data.
	 */
	public static function get_profile() {
		return get_option( 'rbco_instagram_profile', array() );
	}

	/**
	 * Disconnect Instagram.
	 */
	public static function disconnect() {
		delete_option( 'rbco_instagram_tokens' );
		delete_option( 'rbco_instagram_profile' );
	}

	/**
	 * Get Instagram App ID.
	 *
	 * @return string
	 */
	public static function get_client_id() {
		return get_option( 'rbco_instagram_app_id', '' );
	}

	/**
	 * Get Instagram App Secret.
	 *
	 * @return string
	 */
	public static function get_client_secret() {
		return get_option( 'rbco_instagram_app_secret', '' );
	}
}
