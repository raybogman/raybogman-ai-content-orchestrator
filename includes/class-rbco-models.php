<?php
/**
 * Live model lists for Claude (Anthropic) and OpenAI.
 *
 * Fetches the provider's own /models endpoint with the saved API key, caches
 * the result for 12 hours (keyed on the API key, so a new key refreshes it),
 * and falls back to a short built-in list when no key is set or the request
 * fails. The currently saved model is always kept selectable.
 *
 * @package RayBogman_AI_Content_Orchestrator
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class RBCO_Models
 */
class RBCO_Models {

	const CACHE_TTL         = 12 * HOUR_IN_SECONDS;
	const CACHE_TTL_FAILURE = 10 * MINUTE_IN_SECONDS;
	const ANTHROPIC_MODELS  = 'https://api.anthropic.com/v1/models?limit=100';
	const OPENAI_MODELS     = 'https://api.openai.com/v1/models';

	/**
	 * Built-in fallback lists (newest first). Only used without a key or when
	 * the provider API is unreachable.
	 *
	 * @var array
	 */
	private static $fallback = array(
		'claude' => array(
			'claude-sonnet-5-5' => 'Claude Sonnet 5.5',
			'claude-opus-5-5'   => 'Claude Opus 5.5',
			'claude-sonnet-4-6' => 'Claude Sonnet 4.6',
			'claude-opus-4-6'   => 'Claude Opus 4.6',
			'claude-haiku-4-5'  => 'Claude Haiku 4.5',
		),
		'openai' => array(
			'gpt-5.5'      => 'GPT-5.5',
			'gpt-5.4'      => 'GPT-5.4',
			'gpt-5.4-mini' => 'GPT-5.4 Mini',
			'gpt-5.4-nano' => 'GPT-5.4 Nano',
			'gpt-5.1'      => 'GPT-5.1',
			'gpt-5'        => 'GPT-5',
			'gpt-4.1'      => 'GPT-4.1',
			'gpt-4o'       => 'GPT-4o',
		),
	);

	/**
	 * Get select options for a provider.
	 *
	 * @param string $provider 'claude' or 'openai'.
	 * @param string $selected Currently saved model id (kept in the list even if the provider no longer lists it).
	 * @return array Model id => label.
	 */
	public static function get_options( $provider, $selected = '' ) {
		$info   = self::get_list( $provider );
		$models = $info['models'];

		if ( '' !== $selected && ! isset( $models[ $selected ] ) ) {
			$models = array( $selected => self::prettify( $selected ) . ' ' . __( '(current)', 'raybogman-ai-content-orchestrator' ) ) + $models;
		}

		// Mark the recommended default: newest Sonnet for Claude, newest full-size GPT for OpenAI.
		$recommended = self::recommended( $provider, $models );
		if ( $recommended && isset( $models[ $recommended ] ) ) {
			$models[ $recommended ] .= ' ' . __( '(recommended)', 'raybogman-ai-content-orchestrator' );
		}

		return $models;
	}

	/**
	 * Recommended model id for a provider, given the available list.
	 *
	 * @param string $provider 'claude' or 'openai'.
	 * @param array  $models   Model id => label (newest first).
	 * @return string
	 */
	public static function recommended( $provider, $models = null ) {
		if ( null === $models ) {
			$models = self::get_list( $provider )['models'];
		}
		foreach ( array_keys( $models ) as $id ) {
			if ( 'claude' === $provider && preg_match( '/^claude-sonnet-/', $id ) ) {
				return $id;
			}
			if ( 'openai' === $provider && preg_match( '/^gpt-\d+(\.\d+)?$/', $id ) ) {
				return $id;
			}
		}
		return (string) key( $models );
	}

	/**
	 * Human-readable source note for the settings page.
	 *
	 * @param string $provider 'claude' or 'openai'.
	 * @return string
	 */
	public static function source_note( $provider ) {
		$info = self::get_list( $provider );
		if ( 'live' === $info['source'] ) {
			return sprintf(
				/* translators: 1: provider name, 2: human time diff */
				__( 'Model list fetched live from %1$s %2$s ago.', 'raybogman-ai-content-orchestrator' ),
				'claude' === $provider ? 'Anthropic' : 'OpenAI',
				human_time_diff( $info['fetched'] )
			);
		}
		if ( 'no-key' === $info['source'] ) {
			return __( 'Built-in list. Save an API key to load the latest models from the provider.', 'raybogman-ai-content-orchestrator' );
		}
		return __( 'Built-in list. The provider could not be reached; click Refresh to try again.', 'raybogman-ai-content-orchestrator' );
	}

	/**
	 * Clear the cached lists for both providers.
	 */
	public static function clear_cache() {
		foreach ( array( 'claude', 'openai' ) as $provider ) {
			delete_transient( self::cache_key( $provider ) );
		}
	}

	/**
	 * Admin-post handler: clear cache and return to the settings page.
	 */
	public static function handle_refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'raybogman-ai-content-orchestrator' ) );
		}
		check_admin_referer( 'rbco_refresh_models' );
		self::clear_cache();
		wp_safe_redirect( admin_url( 'admin.php?page=rbco-settings&tab=general' ) );
		exit;
	}

	/**
	 * Nonce-protected refresh URL.
	 *
	 * @return string
	 */
	public static function refresh_url() {
		return wp_nonce_url( admin_url( 'admin-post.php?action=rbco_refresh_models' ), 'rbco_refresh_models' );
	}

	/**
	 * Get the (cached) model list with its origin.
	 *
	 * @param string $provider 'claude' or 'openai'.
	 * @return array { models: array, source: 'live'|'no-key'|'error', fetched: int }
	 */
	private static function get_list( $provider ) {
		$api_key = 'claude' === $provider ? RBCO_Settings::get_anthropic_api_key() : RBCO_Settings::get_openai_api_key();
		if ( empty( $api_key ) ) {
			return array(
				'models'  => self::$fallback[ $provider ],
				'source'  => 'no-key',
				'fetched' => time(),
			);
		}

		$key    = self::cache_key( $provider );
		$cached = get_transient( $key );
		if ( is_array( $cached ) && isset( $cached['models'] ) ) {
			return $cached;
		}

		$models = 'claude' === $provider ? self::fetch_claude( $api_key ) : self::fetch_openai( $api_key );
		if ( empty( $models ) ) {
			$result = array(
				'models'  => self::$fallback[ $provider ],
				'source'  => 'error',
				'fetched' => time(),
			);
			set_transient( $key, $result, self::CACHE_TTL_FAILURE );
			return $result;
		}

		$result = array(
			'models'  => $models,
			'source'  => 'live',
			'fetched' => time(),
		);
		set_transient( $key, $result, self::CACHE_TTL );
		return $result;
	}

	/**
	 * Transient name, scoped to the current API key so a new key refreshes the list.
	 *
	 * @param string $provider 'claude' or 'openai'.
	 * @return string
	 */
	private static function cache_key( $provider ) {
		$api_key = 'claude' === $provider ? RBCO_Settings::get_anthropic_api_key() : RBCO_Settings::get_openai_api_key();
		return 'rbco_models_' . $provider . '_' . substr( md5( (string) $api_key ), 0, 8 );
	}

	/**
	 * Fetch Claude models from the Anthropic Models API.
	 *
	 * @param string $api_key Anthropic API key.
	 * @return array Model id => display name, newest first. Empty on failure.
	 */
	private static function fetch_claude( $api_key ) {
		$response = wp_remote_get(
			self::ANTHROPIC_MODELS,
			array(
				'timeout' => 15,
				'headers' => array(
					'x-api-key'         => $api_key,
					'anthropic-version' => '2023-06-01',
				),
			)
		);
		$data = self::decode( $response );
		if ( empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return array();
		}

		$rows = array();
		foreach ( $data['data'] as $model ) {
			if ( empty( $model['id'] ) || 0 !== strpos( $model['id'], 'claude-' ) ) {
				continue;
			}
			$rows[] = array(
				'id'      => $model['id'],
				'label'   => ! empty( $model['display_name'] ) ? $model['display_name'] : self::prettify( $model['id'] ),
				'created' => ! empty( $model['created_at'] ) ? strtotime( $model['created_at'] ) : 0,
			);
		}
		return self::to_options( $rows );
	}

	/**
	 * Fetch chat-capable GPT models from the OpenAI Models API.
	 *
	 * The endpoint lists every model (audio, image, embeddings, dated snapshots,
	 * codex, ...). Keep only the current chat families and drop variants that
	 * cannot be used with the Chat Completions call this plugin makes.
	 *
	 * @param string $api_key OpenAI API key.
	 * @return array Model id => label, newest first. Empty on failure.
	 */
	private static function fetch_openai( $api_key ) {
		$response = wp_remote_get(
			self::OPENAI_MODELS,
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'Bearer ' . $api_key ),
			)
		);
		$data = self::decode( $response );
		if ( empty( $data['data'] ) || ! is_array( $data['data'] ) ) {
			return array();
		}

		$keep = '/^(gpt-(4o|4\.1|5)|o[1-9])/';
		$drop = '/(realtime|audio|tts|transcribe|search|image|instruct|embedding|moderation|codex|chat-latest|preview|deep-research|-pro\b|computer-use|-\d{4}-\d{2}-\d{2}$|-\d{4}$)/';

		$rows = array();
		foreach ( $data['data'] as $model ) {
			if ( empty( $model['id'] ) || ! preg_match( $keep, $model['id'] ) || preg_match( $drop, $model['id'] ) ) {
				continue;
			}
			$rows[] = array(
				'id'      => $model['id'],
				'label'   => self::prettify( $model['id'] ),
				'created' => ! empty( $model['created'] ) ? (int) $model['created'] : 0,
			);
		}
		return self::to_options( $rows );
	}

	/**
	 * Sort rows newest first and flatten to id => label.
	 *
	 * @param array $rows Rows with id, label, created.
	 * @return array
	 */
	private static function to_options( $rows ) {
		usort(
			$rows,
			function ( $a, $b ) {
				return $b['created'] <=> $a['created'];
			}
		);
		$options = array();
		foreach ( $rows as $row ) {
			$options[ $row['id'] ] = $row['label'];
		}
		return $options;
	}

	/**
	 * Decode a wp_remote_* response body as JSON, or return an empty array.
	 *
	 * @param array|WP_Error $response HTTP response.
	 * @return array
	 */
	private static function decode( $response ) {
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		return is_array( $data ) ? $data : array();
	}

	/**
	 * "gpt-4.1-mini" => "GPT-4.1 Mini", "claude-sonnet-5-5" => "Claude Sonnet 5 5".
	 *
	 * @param string $id Model id.
	 * @return string
	 */
	private static function prettify( $id ) {
		$label = ucwords( str_replace( '-', ' ', $id ) );
		$label = preg_replace( '/^Gpt /', 'GPT-', $label );
		return preg_replace( '/^(Claude \w+) (\d) (\d)\b/', '$1 $2.$3', $label );
	}
}

add_action( 'admin_post_rbco_refresh_models', array( 'RBCO_Models', 'handle_refresh' ) );
