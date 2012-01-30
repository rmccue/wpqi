<?php

function _cleanup($download_file) {
?>
<p>Removing temporary files&hellip;
<?php
if ( unlink( $download_file ) )
	echo '<strong>Success!</strong>';
else
	echo '<strong>Failure</strong> &mdash; Please remove <code>' . htmlspecialchars( $download_file ) . '</code> manually.';
?></p>
<?php
}

$query = array(
	'locale' => 'en_US',
	'php' => phpversion()
);

$api = wp_remote_get( 'http://api.wordpress.org/core/version-check/1.6/?' . http_build_query( $query, null, '&' ), array( 'timeout' => 10 ) );

if ( $api && ! is_wp_error( $api ) && ! empty( $api['body'] ) && 200 === wp_remote_retrieve_response_code( $api ) ) {
	$api = @unserialize( wp_remote_retrieve_body( $api ) );
	$api = $api['offers'][0];
} else {
	$api = array(
		'locale' => 'en_US',
		'download' => 'http://wordpress.org/latest.zip',
		'current' => 'unknown'
	);
}

$path = isset( $_REQUEST['path'] ) ? $_REQUEST['path'] : 'wordpress';

the_header( 'download' );

if ( validate_file( $path ) !== 0 ) {
?>
	<p><strong>Failed</strong> - Your path <code><?php echo htmlspecialchars( $path ) ?></code> looks invalid. Make sure that it's a valid path relative to this directory (<code><?php echo htmlspecialchars( ABSPATH ) ?></code>)</p>
<?php
	the_footer();
	die();
}

?>

<p>Beginning download</p>

<?php
$requested_url  = ( !empty( $_SERVER['HTTPS'] ) && strtolower( $_SERVER['HTTPS'] ) == 'on' ) ? 'https://' : 'http://';
$requested_url .= $_SERVER['HTTP_HOST'];
$requested_url .= $_SERVER['REQUEST_URI'];

set_time_limit( 0 ); //We may need it...
?>
<p>Connecting to your server&hellip;
<?php
$fs = WP_Filesystem( $credentials, ABSPATH );
?>
<strong>Success!</strong></p>

<p>Downloading WordPress from <code><?php echo htmlspecialchars( $api['download'] ) ?></code>&hellip;

<?php
@ob_end_flush();
flush();

$download_file = wpqi_download_url( $api['download'] );
if ( is_wp_error( $download_file ) ) {
	echo '<strong>Failure</strong> - ' . $download_file->get_error_code() . ': ' . $download_file->get_error_message() . '</p>';

	the_footer();
	die();
}

list( $download_file, $response ) = $download_file;

?><strong>Success!</strong></p>

<?php
if ( ! empty( $response['headers']['content-md5'] ) ) {
	// WordPress.org should give us the MD5 in the headers, thanks to nacin!
	$md5 = trim( $response['headers']['content-md5'] );
}
else {
?>
	<p>Downloading MD5 checksum to verify download from <code><?php echo htmlspecialchars( $api['download'] ) ?>.md5</code>&hellip;
<?php
	@ob_end_flush();
	flush();

	$md5_response = wp_remote_get( $api['download'] . '.md5', array( 'timeout' => 10 ) );

	if ( $md5_response && ! is_wp_error( $md5_response ) && 200 === wp_remote_retrieve_response_code( $md5_response ) ) {
		echo '<strong>Success!</strong></p>';
	} else {
		echo '<strong>Failure</strong> - Unable to download MD5 checksum to verify the download</p>';

		the_footer();
		die();
	}

	$md5 = trim( wp_remote_retrieve_body( $md5_response ) );
}
?>

<p>Verifying the download&hellip;
<?php
@ob_end_flush();
flush();

$our_md5 = md5_file( $download_file );
if ( $md5 !== $our_md5 ) {
?>
	<strong>Failure</strong> - MD5 checksums did not match.<br />
	<small>(We have <abbr title="<?php echo $our_md5 ?>"><code><?php echo substr($our_md5, 0, 8) ?></code></abbr>,
		but server reports <abbr title="<?php echo htmlspecialchars($md5) ?>"><code><?php echo htmlspecialchars(substr($md5, 0, 8)) ?></code>)
	</small>
	</p>
<?php
	_cleanup($download_file);

	the_footer();
	die();
}
?><strong>Success!</strong></p>

<p>Uncompressing WordPress files to <code><?php echo htmlspecialchars( $path ) ?></code>&hellip; <strong><span id="progress">0%</span></strong></p>

<?php
@ob_end_flush();
flush();

function _install_tick( $args ) {
	static $last = 0;
	if ( ! $args['process'] ) return;
	$percent = round( $args['process'] / $args['count'] * 100, 0 );
	if ( time() > $last + 1 || $percent >= 100 ) { //Once per 2 second.. or ended.
		$last = time();
		echo "<script type='text/javascript'>document.getElementById('progress').innerHTML = '{$percent}%';</script>";
		@ob_end_flush();
		flush();
	}
}

$res = unzip_file( $download_file, ABSPATH . '/' . $path, '_install_tick' );
if ( is_wp_error( $res ) ) {
	$error = $res->get_error_message();
	$data = $res->get_error_data();
	if ( !empty( $data ) )
		$error .= $res->get_error_data();
	echo "<script type='text/javascript'>document.getElementById('progress').innerHTML = '<strong>Failed</strong> - Uh oh, we had an error: " . $error . "';</script>";
	echo "<noscript><strong>Failed</strong> - Uh oh, we had an error: {$error}</noscript>";

	_cleanup($download_file);

	the_footer();
	exit;
} else {
	echo "<script type='text/javascript'>document.getElementById('progress').innerHTML = '<strong>Success!</strong>';</script>";
}

_cleanup($download_file);

//Finally.. Delete ourselves..
if ( defined( 'COMPRESSED_BUILD' ) && COMPRESSED_BUILD && !file_exists( './build.php' ) ) { //as long as he build file doesnt exist.. (ie. dev install)
	//Lets hope like he.. that someone hasnt uploaded it as a filename which WP has created in the current dir.. ie. index.php
	echo '<p>Removing installer&hellip; ';
	if ( $wp_filesystem->delete( $installer_file ) )
		echo '<strong>Success!</strong>.</p>';
	else
		echo '<strong>Failed</strong> &mdash; Please remove <code>' . htmlspecialchars( basename( $installer_file ) ) . '</code> manually.</p>';
}

?>
<p><strong>Success!</strong> WordPress has been downloaded!</p>
<p class="step"><a href="<?php echo htmlspecialchars( $path ) ?>wp-admin/setup-config.php" class="button">Begin installation</a></p>

<?php
the_footer();
