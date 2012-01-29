<?php
echo 'download';

// Replace me!
$credentials = array( 'hostname' => 'localhost', 'port' => '21', 'username' => $the_guessed_user, 'password' => '', 'public_key' => '', 'private_key' => '', 'connection_type' => 'ftp');

the_header('download');
echo '<h2>Installing...</h2>';

$requested_url  = ( !empty($_SERVER['HTTPS'] ) && strtolower($_SERVER['HTTPS']) == 'on' ) ? 'https://' : 'http://';
$requested_url .= $_SERVER['HTTP_HOST'];
$requested_url .= $_SERVER['REQUEST_URI'];

set_time_limit(0); //We may need it...

echo '<p>Connecting to Filesystem.. ';
$fs = WP_Filesystem($credentials, ABSPATH);
echo '<strong>Success!</strong></p>';

echo '<p>Downloading package from <code>' . $_REQUEST['package'] . '</code>.. ';
@ob_end_flush(); flush();
$download_file = download_url($_REQUEST['package']);
if ( is_wp_error($download_file) )
	die( '<strong>Failure</strong> - ' . $download_file->get_error_code() . ': ' . $download_file->get_error_message() );
else
	echo '<strong>Success!</strong></p>';

echo '<p>Uncompressing WordPress files to Filesystem.. <strong><span id="progress">0%</span></strong></p>';
@ob_end_flush(); flush();
function _install_tick($args) {
	static $last = 0;
	if ( ! $args['process'] ) return;
	$percent = round($args['process'] / $args['count'] * 100, 0);
	if ( time() > $last + 1 || $percent >= 100 ) { //Once per 2 second.. or ended.
		$last = time();
		echo "<script type='text/javascript'>document.getElementById('progress').innerHTML = '{$percent}%';</script>";
		@ob_end_flush(); flush();
	}
}

$res = unzip_file($download_file, ABSPATH . '/wordpress', '_install_tick');
if ( is_wp_error($res) ) {
	$error = $res->get_error_message();
	$data = $res->get_error_data();
	if ( !empty($data) )
		$error .= $res->get_error_data();
	echo "<script type='text/javascript'>document.getElementById('progress').innerHTML = '<strong>Failed</strong> - Installation Halted, Error: " . $error . "';</script>";
	echo "<noscript><strong>Failed</strong> - Installation Halted, Error: {$error}</noscript>";
	exit;
} else {
	echo "<script type='text/javascript'>document.getElementById('progress').innerHTML = '<strong>Success!</strong>';</script>";
}

echo '<p>Removing Temporary Download files.. ';
if ( unlink($download_file) )
	echo '<strong>Success!</strong>';
else
	echo '<strong>Failure</strong>.. Please ensure that <code>' . $download_file . '</code> file has been removed';
echo '</p>';

//Finally.. Delete ourselves..
if ( defined('COMPRESSED_BUILD') && COMPRESSED_BUILD && !file_exists('./build.php') ) { //as long as he build file doesnt exist.. (ie. dev install)
	//Lets hope like he.. that someone hasnt uploaded it as a filename which WP has created in the current dir.. ie. index.php
	echo '<p>Removing Installer file... ';
	if ( $wp_filesystem->delete( $installer_file ) )
		echo '<strong>Success!</strong>.</p>';
	else
		echo '<strong>Failed</strong> - You should remove <code>' . basename($installer_file) . '</code> manually.</p>';
}

//Ok, We've got all the details now..

/*
if ( $details ) {
	echo '<form method="post" action="' . $details['url']  . 'wp-login.php">
<input type="hidden" name="log" value="' . $details['username'] . '" />
<input type="hidden" name="pwd" value="' . $details['password'] . '" />
<p><strong>Success!</strong> Your WordPress installation is now complete, You may now login with the default username <strong>' . $details['username'] . '</strong>, with the password <strong>' . $details['password'] . '</strong>. Note this password as it will be required to login with in the future if you do not change the password after logging in. For your convience it has also been emailed to your selected email address.</p>

<p class="step"><input name="submit" type="submit" value="Login Instantly" class="button" /></p>
</form>';
} else {
	echo '<p>An error occured during install, Its unknown if the installation was completed successfully. Please browse to the installation folder and complete the install if nesecary.(TODO Spellings)</p>';
}
*/

//Its been a long night :)