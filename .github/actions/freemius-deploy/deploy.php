<?php
/**
 * Upload a plugin zip to Freemius and set its release mode.
 *
 * Inputs arrive as INPUT_* env vars; credentials as DEV_ID, PLUGIN_ID,
 * PUBLIC_KEY, SECRET_KEY. The workspace is the working directory, so the
 * relative file_name resolves to the zip built earlier in the job.
 */

$file_name    = getenv( 'INPUT_FILE_NAME' );
$version      = getenv( 'INPUT_VERSION' );
$sandbox      = 'true' === getenv( 'INPUT_SANDBOX' );
$release_mode = getenv( 'INPUT_RELEASE_MODE' ) ? getenv( 'INPUT_RELEASE_MODE' ) : 'pending';
$plugin_id    = getenv( 'PLUGIN_ID' );

if ( ! is_file( $file_name ) ) {
	fwrite( STDERR, "Zip not found: {$file_name}\n" );
	exit( 1 );
}

require_once '/freemius-php-api/freemius/FreemiusBase.php';
require_once '/freemius-php-api/freemius/Freemius.php';

echo "Deploying {$file_name} as {$version} (release_mode={$release_mode}, sandbox=" . ( $sandbox ? 'yes' : 'no' ) . ")\n";

$api = new Freemius_Api( 'developer', getenv( 'DEV_ID' ), getenv( 'PUBLIC_KEY' ), getenv( 'SECRET_KEY' ), $sandbox );

$existing = $api->Api( "plugins/{$plugin_id}/tags.json?count=5", 'GET' );
if ( isset( $existing->tags ) ) {
	foreach ( $existing->tags as $tag ) {
		if ( $tag->version === $version ) {
			echo "Version {$version} already exists on Freemius (tag {$tag->id}); nothing to upload.\n";
			exit( 0 );
		}
	}
}

$tag = $api->Api( "plugins/{$plugin_id}/tags.json", 'POST', array( 'add_contributor' => false ), array( 'file' => $file_name ) );
if ( ! is_object( $tag ) || ! property_exists( $tag, 'id' ) ) {
	fwrite( STDERR, "Upload failed:\n" . print_r( $tag, true ) . "\n" );
	exit( 1 );
}
echo "Uploaded: tag {$tag->id}, version {$tag->version}\n";

$updated = $api->Api( "plugins/{$plugin_id}/tags/{$tag->id}.json", 'PUT', array( 'release_mode' => $release_mode ), array() );
if ( ! is_object( $updated ) || ! property_exists( $updated, 'release_mode' ) || $updated->release_mode !== $release_mode ) {
	fwrite( STDERR, "Could not set release_mode={$release_mode}:\n" . print_r( $updated, true ) . "\n" );
	exit( 1 );
}
echo "Release mode set to {$updated->release_mode}.\n";
