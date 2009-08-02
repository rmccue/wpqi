<?php

$credentials = array( 'hostname' => 'localhost', 'port' => '21', 'username' => $the_guessed_user, 'password' => '', 'public_key' => '', 'private_key' => '', 'connection_type' => 'ftp');

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

if ( strpos($credentials['hostname'], ':') )
	list( $credentials['hostname'], $credentials['port'] ) = explode(':', $credentials['hostname'], 2);
else
	unset($credentials['port']);
	
$errors = array();


/* ( !empty($credentials['password']) && !empty($credentials['username']) && !empty($credentials['hostname']) ) ||
				( 'ssh' == $credentials['connection_type'] && !empty($credentials['public_key']) && !empty($credentials['private_key']) ) */

if ( 'ftp-detail-check' == $step || 'direct' == get_filesystem_method() ) {
	
	$result = WP_Filesystem($credentials, ABSPATH);
	if ( true === $result ) {
		$config['fs'] = $credentials;

		write_config();
		header("Location: {$PHP_SELF}?step=db-details");
		exit;
	}
	$errors[] = 'credentials';
}
the_header();

extract($credentials);

?><h1>Filesystem Details</h1>
<p>In order to continue, WordPress needs to know your FTP details to copy the files to your server. Please note that these are NOT stored once the installation is complete and that you'll need to re-enter them when you wish to Install or Upgrade new Plugins and Themes.</p>
<form method="post" action="?step=ftp-detail-check">
	<?php if ( ! empty($errors) ) echo '<p><strong>There are some errors with your input. Please check the highlighted fields below.</strong></p>'; ?>

	<p>
	<label for="fs-connection-type">Connection Type</label>
	<fieldset id="fs-connection-type" class="no-border">
	<?php
	$types = array();
	if ( extension_loaded('ftp') || extension_loaded('sockets') || function_exists('fsockopen') )
		$types[ 'ftp' ] = 'FTP';
	if ( extension_loaded('ftp') ) //Only this supports FTPS :)
		$types[ 'ftps' ] = 'FTPS (SSL)';
	if ( extension_loaded('ssh2') && function_exists('stream_get_contents') )
		$types[ 'ssh' ] = 'SSH2';

	foreach ( $types as $name => $text ) :
	?>
	<input type="radio" name="connection_type" id="<?php echo $name ?>" value="<?php echo $name ?>" <?php checked($name, $connection_type); ?>/>
		<label for="<?php echo $name ?>"><?php echo $text ?></label>&nbsp;
	<?php endforeach; ?>
	</fieldset>
	</p>

	<p<?php if ( in_array('credentials', $errors) ) echo ' class="error"' ?>>
		<label for="username">FTP Username</label>
		<input name="username" id="username" type="text" size="25" value="<?php echo $username ?>" />
		Your FTP username
	</p>
	<p<?php if ( in_array('credentials', $errors) ) echo ' class="error"' ?>>
		<label for="password">FTP Password</label>
		<input name="password" id="password" type="text" size="25" value="<?php echo $password ?>" />
		Your FTP password.
	</p>

<div id="advanced-options" class="<?php if ( !empty($errors) ) echo 'force-show-block' ?>">
	<p<?php if ( in_array('credentials', $errors) ) echo ' class="error"' ?>>
		<label for="hostname">FTP Hostname</label>
		<input name="hostname" id="hostname" type="text" size="25" value="<?php echo $hostname ?>" />
		99% chance you won't need to change this value.
	</p>
<?php if ( in_array('ssh', $types) ) : ?>
	<p>
		<label for="public_key">SSH Public Key (Optional)</label>
		<input type="text" name="public_key" id="public_key" value="<?php echo esc_attr($public_key) ?>" />
		
		<label for="private_key">SSH Private Key (Optional)</label>
		<input name="private_key" type="text" id="private_key" value="<?php echo esc_attr($private_key) ?>" />
		Enter the location on the server where the keys are located. If a passphrase is needed, enter that in the password field above.
	</p>
<?php endif; /*ssh*/ ?>
</div>
	<p class="step"><input name="submit" type="submit" value="Continue" class="button" /></p>
	<p><input type="checkbox" name="advanced-options" id="advanced-options-toggle" <?php if ( isset($_REQUEST['advanced-options']) ) echo ' checked="checked"' ?>  /><label for="advanced-options-toggle">Show Advanced Options</label></p>
</form>

<?php
the_footer();