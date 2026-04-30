<?php
/**
 * LinkedIn integration — OAuth 2.0 and posting.
 *
 * @package RayAI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RAYAI_LinkedIn
 *
 * Handles LinkedIn OAuth 2.0 authentication, token management, and posting.
 */
class RAYAI_LinkedIn {

	const AUTH_URL  = 'https://www.linkedin.com/oauth/v2/authorization';
	const TOKEN_URL = 'https://www.linkedin.com/oauth/v2/accessToken';
	const USERINFO_URL = 'https://api.linkedin.com/v2/userinfo';
	// Use the /v2/ugcPosts endpoint which works with the self-serve
	// "Share on LinkedIn" product and w_member_social scope.
	// The newer /rest/posts endpoint requires Partner API approval.
	const POSTS_URL = 'https://api.linkedin.com/v2/ugcPosts';
	const ASSETS_URL = 'https://api.linkedin.com/v2/assets?action=registerUpload';
	// LinkedIn-Version header required even for /v2/ugcPosts.
	const API_VERSION = '202603';
	const SCOPES = 'openid profile email w_member_social';
	const SCOPES_SIGNIN_ONLY = 'openid profile email';
	const SCOPES_SHARE_ONLY = 'w_member_social';

	/**
	 * Get the OAuth redirect URI (points back to WP admin).
	 *
	 * @return string
	 */
	public static function get_redirect_uri() {
		return admin_url( 'admin.php?page=rayai-settings&rayai_linkedin_callback=1' );
	}

	/**
	 * Build the OAuth authorization URL.
	 *
	 * @return string|false URL or false if not configured.
	 */
	public static function get_auth_url( $scopes = null ) {
		$client_id = self::get_client_id();
		if ( empty( $client_id ) ) {
			return false;
		}

		if ( null === $scopes ) {
			$scopes = self::SCOPES;
		}

		$state = wp_create_nonce( 'rayai_linkedin_oauth' );
		set_transient( 'rayai_linkedin_oauth_state', $state, 600 );

		// Build URL manually — LinkedIn requires %20 for scope separators
		// and add_query_arg may encode spaces as + which LinkedIn rejects.
		return sprintf(
			'%s?response_type=code&client_id=%s&redirect_uri=%s&scope=%s&state=%s',
			self::AUTH_URL,
			rawurlencode( $client_id ),
			rawurlencode( self::get_redirect_uri() ),
			rawurlencode( $scopes ),
			rawurlencode( $state )
		);
	}

	/**
	 * Handle the OAuth callback — exchange code for tokens.
	 *
	 * @param string $code  Authorization code.
	 * @param string $state State parameter for CSRF validation.
	 * @return true|WP_Error
	 */
	public static function handle_callback( $code, $state ) {
		// Validate state.
		$saved_state = get_transient( 'rayai_linkedin_oauth_state' );
		if ( empty( $saved_state ) || $state !== $saved_state ) {
			return new WP_Error( 'invalid_state', __( 'Invalid OAuth state. Please try again.', 'rayai-content-orchestrator' ) );
		}
		delete_transient( 'rayai_linkedin_oauth_state' );

		// Exchange code for tokens.
		$response = wp_remote_post( self::TOKEN_URL, array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
			'body'    => array(
				'grant_type'    => 'authorization_code',
				'code'          => $code,
				'redirect_uri'  => self::get_redirect_uri(),
				'client_id'     => self::get_client_id(),
				'client_secret' => self::get_client_secret(),
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code_http = wp_remote_retrieve_response_code( $response );
		$body      = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code_http < 200 || $code_http >= 300 || empty( $body['access_token'] ) ) {
			$msg = isset( $body['error_description'] ) ? $body['error_description'] : 'Token exchange failed.';
			return new WP_Error( 'token_error', $msg );
		}

		// Store tokens.
		$token_data = array(
			'access_token'  => $body['access_token'],
			'expires_at'    => time() + intval( $body['expires_in'] ),
		);
		if ( ! empty( $body['refresh_token'] ) ) {
			$token_data['refresh_token']            = $body['refresh_token'];
			$token_data['refresh_token_expires_at'] = time() + intval( $body['refresh_token_expires_in'] );
		}

		update_option( 'rayai_linkedin_tokens', $token_data );

		// Store granted scopes for diagnostics (LinkedIn returns this in the response).
		if ( ! empty( $body['scope'] ) ) {
			update_option( 'rayai_linkedin_scopes', $body['scope'] );
		}

		// Fetch and store user profile.
		$profile = self::fetch_profile( $body['access_token'] );
		if ( ! is_wp_error( $profile ) ) {
			update_option( 'rayai_linkedin_profile', $profile );
		}

		return true;
	}

	/**
	 * Fetch the authenticated user's LinkedIn profile.
	 *
	 * @param string $access_token Access token.
	 * @return array|WP_Error Profile data or error.
	 */
	private static function fetch_profile( $access_token ) {
		$response = wp_remote_get( self::USERINFO_URL, array(
			'timeout' => 15,
			'headers' => array( 'Authorization' => 'Bearer ' . $access_token ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['sub'] ) ) {
			return new WP_Error( 'profile_error', 'Could not retrieve LinkedIn profile.' );
		}

		return array(
			'person_urn' => 'urn:li:person:' . $body['sub'],
			'name'       => isset( $body['name'] ) ? $body['name'] : '',
			'email'      => isset( $body['email'] ) ? $body['email'] : '',
			'picture'    => isset( $body['picture'] ) ? $body['picture'] : '',
		);
	}

	/**
	 * Get a valid access token, refreshing if needed.
	 *
	 * @return string|false Access token or false if unavailable.
	 */
	public static function get_access_token() {
		$tokens = get_option( 'rayai_linkedin_tokens', array() );
		if ( empty( $tokens['access_token'] ) ) {
			return false;
		}

		// Check if access token is still valid (with 5 min buffer).
		if ( isset( $tokens['expires_at'] ) && $tokens['expires_at'] > ( time() + 300 ) ) {
			return $tokens['access_token'];
		}

		// Try refresh.
		if ( ! empty( $tokens['refresh_token'] ) ) {
			$refreshed = self::refresh_token( $tokens['refresh_token'] );
			if ( ! is_wp_error( $refreshed ) ) {
				return $refreshed;
			}
		}

		return false;
	}

	/**
	 * Refresh the access token.
	 *
	 * @param string $refresh_token Refresh token.
	 * @return string|WP_Error New access token or error.
	 */
	private static function refresh_token( $refresh_token ) {
		$response = wp_remote_post( self::TOKEN_URL, array(
			'timeout' => 30,
			'headers' => array( 'Content-Type' => 'application/x-www-form-urlencoded' ),
			'body'    => array(
				'grant_type'    => 'refresh_token',
				'refresh_token' => $refresh_token,
				'client_id'     => self::get_client_id(),
				'client_secret' => self::get_client_secret(),
			),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $body['access_token'] ) ) {
			// Clear stored tokens — refresh failed, user needs to re-auth.
			delete_option( 'rayai_linkedin_tokens' );
			delete_option( 'rayai_linkedin_profile' );
			return new WP_Error( 'refresh_failed', 'LinkedIn token refresh failed. Please reconnect.' );
		}

		$token_data = array(
			'access_token' => $body['access_token'],
			'expires_at'   => time() + intval( $body['expires_in'] ),
		);
		if ( ! empty( $body['refresh_token'] ) ) {
			$token_data['refresh_token']            = $body['refresh_token'];
			$token_data['refresh_token_expires_at'] = time() + intval( $body['refresh_token_expires_in'] );
		}

		update_option( 'rayai_linkedin_tokens', $token_data );

		return $body['access_token'];
	}

	/**
	 * Post an article link to LinkedIn.
	 *
	 * @param int $post_id WordPress post ID.
	 * @return true|WP_Error
	 */
	public static function share_post( $post_id ) {
		$access_token = self::get_access_token();
		if ( ! $access_token ) {
			return new WP_Error( 'not_connected', 'LinkedIn not connected or token expired.' );
		}

		$profile = get_option( 'rayai_linkedin_profile', array() );
		if ( empty( $profile['person_urn'] ) ) {
			return new WP_Error( 'no_profile', 'LinkedIn profile not found. Please reconnect.' );
		}

		$post  = get_post( $post_id );
		$title = $post->post_title;
		$url   = get_permalink( $post_id );
		$desc  = $post->post_excerpt;

		// Build commentary: use AI-generated LinkedIn summary if available,
		// otherwise fall back to meta description, otherwise a default.
		$ai_commentary = get_post_meta( $post_id, '_rayai_linkedin_commentary', true );
		if ( ! empty( $ai_commentary ) ) {
			$commentary = $ai_commentary;
		} elseif ( ! empty( $desc ) ) {
			$commentary = $desc;
		} else {
			$commentary = sprintf( 'Check out our latest post: %s', $title );
		}

		// Try to upload the featured image to LinkedIn so it appears in the post.
		// LinkedIn's link preview scraper is unreliable, so we upload the image directly.
		$image_asset_urn = null;
		$thumbnail_id    = get_post_thumbnail_id( $post_id );
		if ( $thumbnail_id ) {
			$image_path = get_attached_file( $thumbnail_id );
			if ( $image_path && file_exists( $image_path ) ) {
				$upload_result = self::upload_image( $access_token, $profile['person_urn'], $image_path );
				if ( ! is_wp_error( $upload_result ) ) {
					$image_asset_urn = $upload_result;
				}
			}
		}

		// If we have an image asset, share as IMAGE with link in commentary.
		// Otherwise fall back to ARTICLE share (LinkedIn will scrape OG tags).
		if ( $image_asset_urn ) {
			// Append the URL to the commentary so users can click through.
			// LinkedIn auto-detects URLs in text and makes them clickable.
			$commentary_with_url = $commentary . "\n\n" . $url;

			$body = array(
				'author'         => $profile['person_urn'],
				'lifecycleState' => 'PUBLISHED',
				'specificContent' => array(
					'com.linkedin.ugc.ShareContent' => array(
						'shareCommentary' => array(
							'text' => $commentary_with_url,
						),
						'shareMediaCategory' => 'IMAGE',
						'media' => array(
							array(
								'status'      => 'READY',
								'description' => array(
									'text' => ! empty( $desc ) ? $desc : $title,
								),
								'media'       => $image_asset_urn,
								'title'       => array(
									'text' => $title,
								),
							),
						),
					),
				),
				'visibility' => array(
					'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
				),
			);
		} else {
			// Fallback: ARTICLE share that scrapes the URL's OG tags.
			$body = array(
				'author'         => $profile['person_urn'],
				'lifecycleState' => 'PUBLISHED',
				'specificContent' => array(
					'com.linkedin.ugc.ShareContent' => array(
						'shareCommentary' => array(
							'text' => $commentary,
						),
						'shareMediaCategory' => 'ARTICLE',
						'media' => array(
							array(
								'status'      => 'READY',
								'description' => array(
									'text' => ! empty( $desc ) ? $desc : $title,
								),
								'originalUrl' => $url,
								'title'       => array(
									'text' => $title,
								),
							),
						),
					),
				),
				'visibility' => array(
					'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
				),
			);
		}

		// Build headers. Note: LinkedIn's docs use "LinkedIn-Version" — include it.
		$headers = array(
			'Authorization'             => 'Bearer ' . $access_token,
			'Content-Type'              => 'application/json',
			'X-Restli-Protocol-Version' => '2.0.0',
			'LinkedIn-Version'          => self::API_VERSION,
		);

		// Store the granted scopes for debugging.
		$granted_scopes = get_option( 'rayai_linkedin_scopes', '' );

		$response = wp_remote_post( self::POSTS_URL, array(
			'timeout' => 30,
			'headers' => $headers,
			'body'    => wp_json_encode( $body ),
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$http_code = wp_remote_retrieve_response_code( $response );
		if ( $http_code < 200 || $http_code >= 300 ) {
			$resp_body  = wp_remote_retrieve_body( $response );
			$error_body = json_decode( $resp_body, true );
			$msg        = isset( $error_body['message'] ) ? $error_body['message'] : $resp_body;

			$hint = '';
			if ( false !== stripos( $msg, 'NO_VERSION' ) || false !== stripos( $msg, 'permission' ) ) {
				$hint = sprintf(
					' | Granted scopes: %s | Try disconnecting and reconnecting LinkedIn in Settings to refresh the access token.',
					! empty( $granted_scopes ) ? $granted_scopes : 'unknown'
				);
			}

			return new WP_Error( 'linkedin_post_failed', sprintf( 'LinkedIn API error (HTTP %d): %s%s', $http_code, $msg, $hint ) );
		}

		// Store that this post was shared to LinkedIn.
		update_post_meta( $post_id, '_rayai_linkedin_shared', time() );

		return true;
	}

	/**
	 * Upload an image to LinkedIn via the assets API.
	 *
	 * Two-step flow:
	 * 1. POST /v2/assets?action=registerUpload — get an upload URL + asset URN
	 * 2. PUT the binary image to that upload URL
	 *
	 * @param string $access_token LinkedIn access token.
	 * @param string $person_urn   Author person URN (urn:li:person:xxx).
	 * @param string $image_path   Local file path of the image.
	 * @return string|WP_Error Asset URN on success, WP_Error on failure.
	 */
	private static function upload_image( $access_token, $person_urn, $image_path ) {
		// Step 1: Register upload.
		$register_body = array(
			'registerUploadRequest' => array(
				'recipes'              => array( 'urn:li:digitalmediaRecipe:feedshare-image' ),
				'owner'                => $person_urn,
				'serviceRelationships' => array(
					array(
						'relationshipType' => 'OWNER',
						'identifier'       => 'urn:li:userGeneratedContent',
					),
				),
			),
		);

		$register_response = wp_remote_post( self::ASSETS_URL, array(
			'timeout' => 30,
			'headers' => array(
				'Authorization'             => 'Bearer ' . $access_token,
				'Content-Type'              => 'application/json',
				'X-Restli-Protocol-Version' => '2.0.0',
			),
			'body' => wp_json_encode( $register_body ),
		) );

		if ( is_wp_error( $register_response ) ) {
			return $register_response;
		}

		$register_code = wp_remote_retrieve_response_code( $register_response );
		$register_data = json_decode( wp_remote_retrieve_body( $register_response ), true );

		if ( $register_code < 200 || $register_code >= 300 ) {
			$msg = isset( $register_data['message'] ) ? $register_data['message'] : 'Unknown error';
			return new WP_Error( 'register_upload_failed', sprintf( 'LinkedIn register upload failed (HTTP %d): %s', $register_code, $msg ) );
		}

		if ( empty( $register_data['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'] ) ||
		     empty( $register_data['value']['asset'] ) ) {
			return new WP_Error( 'register_upload_invalid', 'LinkedIn register upload returned unexpected response.' );
		}

		$upload_url = $register_data['value']['uploadMechanism']['com.linkedin.digitalmedia.uploading.MediaUploadHttpRequest']['uploadUrl'];
		$asset_urn  = $register_data['value']['asset'];

		// Step 2: Upload the binary image.
		$image_binary = file_get_contents( $image_path );
		if ( false === $image_binary ) {
			return new WP_Error( 'image_read_failed', 'Could not read image file.' );
		}

		// Determine MIME type.
		$mime_type = function_exists( 'mime_content_type' ) ? mime_content_type( $image_path ) : 'image/png';

		$upload_response = wp_remote_request( $upload_url, array(
			'method'  => 'PUT',
			'timeout' => 60,
			'headers' => array(
				'Authorization' => 'Bearer ' . $access_token,
				'Content-Type'  => $mime_type,
			),
			'body' => $image_binary,
		) );

		if ( is_wp_error( $upload_response ) ) {
			return $upload_response;
		}

		$upload_code = wp_remote_retrieve_response_code( $upload_response );
		if ( $upload_code < 200 || $upload_code >= 300 ) {
			return new WP_Error( 'upload_failed', sprintf( 'LinkedIn image upload failed (HTTP %d).', $upload_code ) );
		}

		return $asset_urn;
	}

	/**
	 * Check if LinkedIn is connected with a valid token.
	 *
	 * @return bool
	 */
	public static function is_connected() {
		return false !== self::get_access_token();
	}

	/**
	 * Get the stored LinkedIn profile.
	 *
	 * @return array
	 */
	public static function get_profile() {
		return get_option( 'rayai_linkedin_profile', array() );
	}

	/**
	 * Disconnect LinkedIn — remove tokens and profile.
	 */
	public static function disconnect() {
		delete_option( 'rayai_linkedin_tokens' );
		delete_option( 'rayai_linkedin_profile' );
		delete_option( 'rayai_linkedin_scopes' );
	}

	/**
	 * Get LinkedIn Client ID from settings.
	 *
	 * @return string
	 */
	private static function get_client_id() {
		return get_option( 'rayai_linkedin_client_id', '' );
	}

	/**
	 * Get LinkedIn Client Secret from settings.
	 *
	 * @return string
	 */
	private static function get_client_secret() {
		return get_option( 'rayai_linkedin_client_secret', '' );
	}
}
