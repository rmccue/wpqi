<?php

@set_magic_quotes_runtime( 0 );
@ini_set( 'magic_quotes_sybase', 0 );

if ( ini_get( 'register_globals' ) ) {
	if ( isset( $_REQUEST['GLOBALS'] ) )
		die( 'GLOBALS overwrite attempt detected' );

	// Variables that shouldn't be unset
	$noUnset = array( 'GLOBALS', '_GET', '_POST', '_COOKIE', '_REQUEST', '_SERVER', '_ENV', '_FILES', 'table_prefix', 'step' );

	$input = array_merge( $_GET, $_POST, $_COOKIE, $_SERVER, $_ENV, $_FILES, isset( $_SESSION ) && is_array( $_SESSION ) ? $_SESSION : array() );
	foreach ( $input as $k => $v ) {
		if ( !in_array( $k, $noUnset ) && isset( $GLOBALS[$k] ) ) {
			$GLOBALS[$k] = NULL;
			unset( $GLOBALS[$k] );
		}
	}
	unset( $input, $noUnset, $k, $v );
}

// If already slashed, strip, Expect to ALWAYS be striped in this App. None of this silly pre-escaped stuff!
if ( get_magic_quotes_gpc() ) {
	$_GET    = _stripslashes_deep( $_GET    );
	$_POST   = _stripslashes_deep( $_POST   );
	$_COOKIE = _stripslashes_deep( $_COOKIE );
}

// Force REQUEST to be GET + POST.  If SERVER, COOKIE, or ENV are needed, use those superglobals directly.
$_REQUEST = array_merge( $_GET, $_POST );

$default_server_values = array(
	'SERVER_SOFTWARE' => '',
	'REQUEST_URI' => '',
);

$_SERVER = array_merge( $default_server_values, $_SERVER );

// Fix for IIS when running with PHP ISAPI
if ( empty( $_SERVER['REQUEST_URI'] ) || ( php_sapi_name() != 'cgi-fcgi' && preg_match( '/^Microsoft-IIS\//', $_SERVER['SERVER_SOFTWARE'] ) ) ) {

	// IIS Mod-Rewrite
	if ( isset( $_SERVER['HTTP_X_ORIGINAL_URL'] ) ) {
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_ORIGINAL_URL'];
	} elseif ( isset( $_SERVER['HTTP_X_REWRITE_URL'] ) ) {
		// IIS Isapi_Rewrite
		$_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
	} else {
		// Use ORIG_PATH_INFO if there is no PATH_INFO
		if ( !isset( $_SERVER['PATH_INFO'] ) && isset( $_SERVER['ORIG_PATH_INFO'] ) )
			$_SERVER['PATH_INFO'] = $_SERVER['ORIG_PATH_INFO'];

		// Some IIS + PHP configurations puts the script-name in the path-info (No need to append it twice)
		if ( isset( $_SERVER['PATH_INFO'] ) ) {
			if ( $_SERVER['PATH_INFO'] == $_SERVER['SCRIPT_NAME'] )
				$_SERVER['REQUEST_URI'] = $_SERVER['PATH_INFO'];
			else
				$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . $_SERVER['PATH_INFO'];
		}

		// Append the query string if it exists and isn't null
		if ( ! empty( $_SERVER['QUERY_STRING'] ) )
			$_SERVER['REQUEST_URI'] .= '?' . $_SERVER['QUERY_STRING'];
	}
}

// Fix for PHP as CGI hosts that set SCRIPT_FILENAME to something ending in php.cgi for all requests
if ( isset( $_SERVER['SCRIPT_FILENAME'] ) && ( strpos( $_SERVER['SCRIPT_FILENAME'], 'php.cgi' ) == strlen( $_SERVER['SCRIPT_FILENAME'] ) - 7 ) )
	$_SERVER['SCRIPT_FILENAME'] = $_SERVER['PATH_TRANSLATED'];

// Fix for Dreamhost and other PHP as CGI hosts
if ( strpos( $_SERVER['SCRIPT_NAME'], 'php.cgi' ) !== false )
	unset( $_SERVER['PATH_INFO'] );

// Fix empty PHP_SELF
$PHP_SELF = $_SERVER['PHP_SELF'];
if ( empty( $PHP_SELF ) )
	$_SERVER['PHP_SELF'] = $PHP_SELF = preg_replace( '/(\?.*)?$/', '', $_SERVER["REQUEST_URI"] );

$is_apache = ( strpos( $_SERVER['SERVER_SOFTWARE'], 'Apache' ) !== false || strpos( $_SERVER['SERVER_SOFTWARE'], 'LiteSpeed' ) !== false );

$installer_file = defined( 'COMPRESSED_BUILD' ) && COMPRESSED_BUILD ? preg_replace( '|\(\d+.*$|', '', __FILE__ ) : dirname( __FILE__ ) . '/installer.php';

if ( function_exists( 'posix_getpwuid' ) && $userinfo = posix_getpwuid( @fileowner( $installer_file ) ) )
	$the_guessed_user = $userinfo['name'];
else if ( preg_match( '|^/home/([^/]+?)/|i', $installer_file, $mat ) )
	$the_guessed_user = $mat[1];
else
	$the_guessed_user = 'username';

$the_guessed_language = 'en_US';
if ( !empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) && preg_match( '|(\w\w[\-_]\w\w)|i', $_SERVER['HTTP_ACCEPT_LANGUAGE'], $mat ) )
	$the_guessed_language = str_replace( '-', '_', $mat[1] );

$wpqi_version = '1.0-bleeding';