<?php
$database = isset($_REQUEST['dbname']) ? $_REQUEST['dbname'] : 'wordpress';
$username = isset($_REQUEST['uname']) ? $_REQUEST['uname'] : $the_guessed_user;
$password = isset($_REQUEST['pwd']) ? $_REQUEST['pwd'] : 'password';
$hostname = isset($_REQUEST['dbhost']) ? $_REQUEST['dbhost'] : 'localhost';
$prefix = isset($_REQUEST['prefix']) ? $_REQUEST['prefix'] : 'wp_';
$errors = array();
$installed = $prefix_test = false;
if ( 'db-detail-check' == $step ) {
	$wpdb = new wpdb($username, $password, $database, $hostname);
	
	$prefix_test = $wpdb->set_prefix($prefix);
	$installed = $wpdb->get_var( "SELECT option_value FROM $wpdb->options WHERE option_name = 'siteurl'" );
	$installed = !empty( $installed );

	if ( isset($wpdb->error) && is_wp_error($wpdb->error) && 'db_connect_fail' == $wpdb->error->get_error_code() )
		$errors[] = 'credentials';
	elseif ( isset($wpdb->error) && is_wp_error($wpdb->error) && 'db_select_fail' == $wpdb->error->get_error_code() )
		$errors[] = 'database';
	elseif ( is_wp_error($prefix_test) || $installed )
		$errors[] = 'prefix';

	if ( empty($errors) ) {	
		$config['db'] = compact('database', 'username', 'password', 'hostname', 'prefix');
		write_config();
		header("Location: {$PHP_SELF}?step=install-options");
		exit;
	}
}
the_header() ?><h1>Database Details</h1>
<form method="post" action="?step=db-detail-check">
	<p>Below you should enter your database connection details. If you're not sure about these, contact your host. </p>
	<?php if ( ! empty($errors) ) echo '<p><strong>There are some errors with your input. Please check the highlighted fields below.</strong></p>'; ?>

	<p<?php if ( in_array('credentials', $errors) ) echo ' class="error"' ?>>
		<label for="uname">MySQL Username</label>
		<input name="uname" id="uname" type="text" size="25" value="<?php echo $username ?>" />
		Your MySQL username
	</p>
	<p<?php if ( in_array('credentials', $errors) ) echo ' class="error"' ?>>
		<label for="pwd">MySQL Password</label>
		<input name="pwd" id="pwd" type="text" size="25" value="<?php echo $password ?>" />
		Your MySQL password.
	</p>
	<p<?php if ( in_array('database', $errors) ) echo ' class="error"' ?>>
		<label for="dbname">MySQL Database Name:</label>
		<input name="dbname" id="dbname" type="text" size="25" value="<?php echo $database ?>" />
		The name of the database you want to run WP in.
		<!-- <a href="">(List Databases)</a> -->
	</p>
	<div id="advanced-options" class="<?php if ( !empty($errors) ) echo 'force-show-block' ?>">
	<p<?php if ( in_array('credentials', $errors) ) echo ' class="error"' ?>>
		<label for="dbhost">Database Host</label>
		<input name="dbhost" id="dbhost" type="text" size="25" value="<?php echo $hostname ?>" />
		99% chance you won't need to change this value.
	</p>
	<p<?php if ( in_array('prefix', $errors) ) echo ' class="error"' ?>>
		<label for="prefix">Table Prefix</label>
		<input name="prefix" id="prefix" type="text" id="prefix" value="<?php echo $prefix ?>" size="25" />
	<?php if ( $installed ) echo '<strong>It appears there is already a WordPress instance installed with this Prefix, Please choose another.</strong><br />'; ?>
		If you want to run multiple WordPress installations in a single database, change this.
	</p>
	</div>
	<p class="step"><input name="submit" type="submit" value="Continue" class="button" /></p>
	<p><input type="checkbox" name="advanced-options" id="advanced-options-toggle" <?php if ( isset($_REQUEST['advanced-options']) ) echo ' checked="checked"' ?>  /><label for="advanced-options-toggle">Show Advanced Options</label></p>
</form>
<?php
the_footer();