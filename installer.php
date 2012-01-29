<?php

$step = isset($_REQUEST['step']) ? $_REQUEST['step'] : 'first';

include 'resources.php';
include 'configuration.php';
include 'functions.php';
include 'servervars.php';

/*BuildCompressSplit*/
define('ABSPATH', dirname(__FILE__) . '/');
if ( !defined('WP_MEMORY_LIMIT') )
	define('WP_MEMORY_LIMIT', '64M');

if ( function_exists('memory_get_usage') && ( (int) @ini_get('memory_limit') < abs(intval(WP_MEMORY_LIMIT)) ) )
	@ini_set('memory_limit', WP_MEMORY_LIMIT);

if ( defined( 'E_DEPRECATED' ) )
	error_reporting( E_ALL & ~E_DEPRECATED & ~E_STRICT );
else
	error_reporting( E_ALL );
@ini_set('display_errors', 1);
define('QI_DEBUG', false);

include 'steps/header.php';
include 'steps/footer.php';
include 'wp-error.php';
include 'file.php';

$wpdb = true; //Hack to stop auto-loading of the DB
if ( file_exists('./db.php') ) {
	/*BuildIgnoreInclude*/include 'db.php';
} else {
	include 'wp-files/wp-includes/wp-db.php';
}
include 'wordpress-functions.php';
include 'wp-files/wp-includes/class-http.php';
include 'wp-files/wp-includes/http.php';
include 'wp-files/wp-admin/includes/class-wp-filesystem-base.php';
include 'wp-files/wp-admin/includes/class-wp-filesystem-direct.php';
include 'wp-files/wp-admin/includes/class-wp-filesystem-ssh2.php';
include 'wp-files/wp-admin/includes/class-wp-filesystem-ftpext.php';
include 'wp-files/wp-admin/includes/class-ftp.php';
if ( defined('COMPRESSED_BUILD') && COMPRESSED_BUILD ) { //class-ftp includes it in normal operation..
	if ( $mod_sockets ) {
		include 'wp-files/wp-admin/includes/class-ftp-sockets.php';
	} else {
		include 'wp-files/wp-admin/includes/class-ftp-pure.php';
	}
}
include 'wp-files/wp-admin/includes/class-wp-filesystem-ftpsockets.php';

$credentials = array(
	'hostname' => 'localhost',
	'port' => '21',
	'username' => $the_guessed_user,
	'password' => '',
	'public_key' => '',
	'private_key' => '',
	'connection_type' => 'ftp'
);

$credentials['connection_type'] = !empty($_POST['connection_type']) ? $_POST['connection_type']  : $credentials['connection_type'];

// If defined, set it to that, Else, If POST'd, set it to that, If not, Set it to whatever it previously was(saved details in option)
$credentials['hostname'] = !empty($_POST['hostname']) ? $_POST['hostname'] : $credentials['hostname'];
$credentials['username'] = !empty($_POST['username']) ? $_POST['username'] : $credentials['username'];
$credentials['password'] = !empty($_POST['password']) ? $_POST['password'] : $credentials['password'];

if ( 'ssh' == $credentials['connection_type'] ) {
	// Check to see if we are setting the public/private keys for ssh
	$credentials['public_key'] = !empty($_POST['public_key']) ? $_POST['public_key'] : $credentials['public_key'];
	$credentials['private_key'] = !empty($_POST['private_key']) ? $_POST['private_key'] : $credentials['private_key'];
}

//sanitize the hostname, Some people might pass in odd-data:
$credentials['hostname'] = preg_replace('|\w+://|', '', $credentials['hostname']); //Strip any schemes off

if ( strpos($credentials['hostname'], ':') !== false )
	list( $credentials['hostname'], $credentials['port'] ) = explode(':', $credentials['hostname'], 2);
else
	unset($credentials['port']);

if ($step !== 'first' && ($step === 'download' || 'directa' == get_filesystem_method())) {
	$result = WP_Filesystem($credentials, ABSPATH);
	if ( true === $result ) {
		$step = 'download';
	}
	else {
		$step = 'fs-error';
	}
}
switch ( $step ) {
	default:
	case 'first':
		include 'steps/first.php';
		break;
	case 'fs-error':
		include 'steps/fs.php';
		break;
	case 'download':
		include 'steps/download.php';
		break;
}
