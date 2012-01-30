<?php

//If configuration exists, Load it if it was last touched in the last 5 hours. //TODO reduce to 45mins for release.
if ( file_exists('./config.php') && filemtime('./config.php') + 5*15*60 > time() )
	/*BuildIgnoreInclude*/include './config.php';

if ( ! isset($config) )
	$config = array();

if ( isset($config['user']) ) {

	if ( $config['user']['time'] < time() ) { //timeout.
		//Overwrite sensitive details as the current session has timed out, Retain the user settings which have already been saved however.
		$config['db'] = array();
		$config['fs'] = array();
		write_config();
		if ( $step != 'first' ) {
			header("Location: {$PHP_SELF}");
			exit;
		}
	} else {
		//Still have a valid user logged in.. possibly..
		if ( ! isset($_COOKIE['wpqi']) || $config['user']['cookie'] != $_COOKIE['wpqi'] ) { //If not actually logged in.. Or doesnt have the right cookie..
			the_header();
			echo '<p>Your Cookie is Fail. If this is incorrect, Please ensure that cookies are enabled in your browser. If you\'ve attempted this install already, You may delete the <code>config.php</code> file which has been created in the same folder as this to restart the installation.</p>';
			echo '</body></html>';
			exit;
		}
	}
}
$hash = isset($config['user']['cookie']) ? $config['user']['cookie'] : md5(time() . microtime());
setcookie('wpqi', $hash, time() + 5*15*60);

$config['user'] = array('time' => time() + 5*15*60, 'cookie' => $hash); //TODO decrease timeout to 45m instead of 5h for release.

function write_config() {
	global $config, $wp_filesystem;
	if ( empty($config) || empty($config['fs']) )
		return false;

	$_config = '<?php $config = ' . var_export($config, true) . ';';

	if ( ! is_object($wp_filesystem) && ! WP_Filesystem($config['fs'], ABSPATH) )
		return false;

	$res = $wp_filesystem->put_contents( ABSPATH . 'config.php', $_config);
	$wp_filesystem->chmod(ABSPATH . 'config.php', FS_CHMOD_FILE);
	return $res;
}

function delete_config() {
	global $config, $wp_filesystem;
	if ( ! file_exists('./config.php') )
		return false;

	if ( ! is_object($wp_filesystem) && ! WP_Filesystem($config['fs'], ABSPATH) )
		return false;

	return ( $wp_filesystem->delete( ABSPATH . 'config.php' ) ) ||  @unlink(ABSPATH . 'config.php');
}