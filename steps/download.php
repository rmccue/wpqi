<?php

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

if (validate_file($path) !== 0) {
?>
	<p><strong>Failed</strong> - Your path <code><?php echo htmlspecialchars($path) ?></code> looks invalid. Make sure that it's a valid path relative to this directory (<code><?php echo htmlspecialchars(ABSPATH) ?></code>)</p>
<?php
	the_footer();
	die();
}

?>

<p>Beginning download</p>

<?php
$requested_url  = ( !empty($_SERVER['HTTPS'] ) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
$requested_url .= $_SERVER['HTTP_HOST'];
$requested_url .= $_SERVER['REQUEST_URI'];

set_time_limit(0); //We may need it...
?>
<p>Connecting to your server&hellip;
<?php
$fs = WP_Filesystem($credentials, ABSPATH);
?>
<strong>Success!</strong></p>

<p>Downloading WordPress from <code><?php echo $api['download'] ?></code>&hellip;

<?php
@ob_end_flush();
flush();

$download_file = download_url($api['download']);
if ( is_wp_error($download_file) ) {
	echo '<strong>Failure</strong> - ' . $download_file->get_error_code() . ': ' . $download_file->get_error_message() . '</p>';

	the_footer();
	die();
}
?><strong>Success!</strong></p>

<?php if ( $api['download'] != 'http://wordpress.org/latest.zip' ) : ?>
<p>Downloading MD5 checksum to verify download from <code><?php echo $api['download'] ?>.md5</code>&hellip;
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
?>

<p>Verifing the download using the MD5 checksum&hellip;
<?php
@ob_end_flush();
flush();

if ( trim( wp_remote_retrieve_body( $md5_response ) ) !== md5_file( $download_file ) ) {
	echo '<strong>Failure</strong> - MD5 checksum failed to verify the download</p>';

	the_footer();
	die();
}
?><strong>Success!</strong></p>
<?php endif; ?>

<p>Uncompressing WordPress files to <?php echo $path ?>&hellip; <strong><span id="progress">0%</span></strong></p>

<?php
@ob_end_flush();
flush();

function _install_tick($args) {
	static $last = 0;
	if ( ! $args['process'] ) return;
	$percent = round($args['process'] / $args['count'] * 100, 0);
	if ( time() > $last + 1 || $percent >= 100 ) { //Once per 2 second.. or ended.
		$last = time();
		echo "<script type='text/javascript'>document.getElementById('progress').innerHTML = '{$percent}%';</script>";
		@ob_end_flush();
		flush();
	}
}

$res = unzip_file($download_file, ABSPATH . '/' . $path, '_install_tick');
if ( is_wp_error($res) ) {
	$error = $res->get_error_message();
	$data = $res->get_error_data();
	if ( !empty($data) )
		$error .= $res->get_error_data();
	echo "<script type='text/javascript'>document.getElementById('progress').innerHTML = '<strong>Failed</strong> - Uh oh, we had an error: " . $error . "';</script>";
	echo "<noscript><strong>Failed</strong> - Uh oh, we had an error: {$error}</noscript>";
	exit;
} else {
	echo "<script type='text/javascript'>document.getElementById('progress').innerHTML = '<strong>Success!</strong>';</script>";
}
?>

<p>Removing temporary files&hellip;
<?php
if ( unlink($download_file) )
	echo '<strong>Success!</strong>';
else
	echo '<strong>Failure</strong> &mdash; Please remove <code>' . $download_file . '</code> manually.';
?></p>

<?php
//Finally.. Delete ourselves..
if ( defined('COMPRESSED_BUILD') && COMPRESSED_BUILD && !file_exists('./build.php') ) { //as long as he build file doesnt exist.. (ie. dev install)
	//Lets hope like he.. that someone hasnt uploaded it as a filename which WP has created in the current dir.. ie. index.php
	echo '<p>Removing installer&hellip; ';
	if ( $wp_filesystem->delete( $installer_file ) )
		echo '<strong>Success!</strong>.</p>';
	else
		echo '<strong>Failed</strong> &mdash; Please remove <code>' . basename($installer_file) . '</code> manually.</p>';
}

?>
<p><strong>Success!</strong> WordPress has been downloaded!</p>
<p class="step"><a href="<?php echo $path ?>wp-admin/setup-config.php" class="button">Begin installation</a></p>

<?php
the_footer();
