<?php
$errors = false;

the_header();

if ( isset( $_POST['checking'] ) && $_POST['checking'] === 'fs' ) {
	$errors = true;
}

extract( $credentials );

?><h1>Filesystem Details</h1>
<p>In order to continue, WordPress needs to know your FTP details to copy the files to your server. Please note that these are NOT stored once the installation is complete and that you'll need to re-enter them when you wish to Install or Upgrade new Plugins and Themes.</p>
<form method="post" action="">
	<?php if ( $errors ) echo '<p><strong>There are some errors with your input. Please check the highlighted fields below.</strong></p>'; ?>

	<input type="hidden" name="step" value="download" />
	<input type="hidden" name="path" value="<?php echo $_REQUEST['path'] ?>" />
	<input type="hidden" name="checking" value="fs" />

	<p>
	<label for="fs-connection-type">Connection Type</label>
	<fieldset id="fs-connection-type" class="no-border">
	<?php
	$types = array();
	if ( extension_loaded( 'ftp' ) || extension_loaded( 'sockets' ) || function_exists( 'fsockopen' ) )
		$types[ 'ftp' ] = 'FTP';
	if ( extension_loaded( 'ftp' ) ) //Only this supports FTPS :)
		$types[ 'ftps' ] = 'FTPS (SSL)';

	foreach ( $types as $name => $text ) :
	?>
	<input type="radio" name="connection_type" id="<?php echo $name ?>" value="<?php echo $name ?>" <?php checked( $name, $connection_type ); ?>/>
		<label for="<?php echo $name ?>"><?php echo $text ?></label>&nbsp;
	<?php endforeach; ?>
	</fieldset>
	</p>

	<p<?php if ( $errors ) echo 'class="error"'; ?>>
		<label for="username">FTP Username</label>
		<input name="username" id="username" type="text" size="25" value="<?php echo $username ?>" />
		Your FTP username
	</p>

	<p<?php if ( $errors ) echo 'class="error"'; ?>>
		<label for="password">FTP Password</label>
		<input name="password" id="password" type="text" size="25" value="<?php echo $password ?>" />
		Your FTP password.
	</p>

	<p<?php if ( $errors ) echo 'class="error"'; ?>>
		<label for="hostname">FTP Hostname</label>
		<input name="hostname" id="hostname" type="text" size="25" value="<?php echo $hostname ?>" />
		99% chance you won't need to change this value.
	</p>
	<p class="step"><input name="submit" type="submit" value="Continue" class="button" /></p>
</form>

<?php
the_footer();