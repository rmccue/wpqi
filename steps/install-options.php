<?php
if ( empty($config['db']) || empty($config['fs']) ) {
	header("Location: {$PHP_SELF}");
	exit;
}

foreach ( array('title' => '', 'email' => '', 'tagline' => '', 'release' => '', 'lang' => $the_guessed_language) as $field => $default )
	$$field = isset($_REQUEST[$field]) ? $_REQUEST[$field] : $default;

$selected_options = ('install-options-check' == $step) ? (isset($_POST['options']) ? array_keys($_POST['options']) : array()) : array('create-default-objects', 'allow-search-engines','pretty-permalinks');

$errors = array();
if ( 'install-options-check' == $step ) {
	$config['package'] = $release;
	$config['title'] = $title;
	$config['email'] = $email;
	$config['tagline'] = $tagline;

	if ( empty($config['package']) )
		$errors[] = 'package';
	if ( empty($title) )
		$errors[] = 'title';
	if ( !preg_match('|.+@.+|', $email) )
		$errors[] = 'email';
	
	$config['destination'] = ''; //Compressed Release build will install to CWD
	if ( ! defined('COMPRESSED_BUILD') || !COMPRESSED_BUILD )
		$config['destination']  .= 'wordpress/'; //Non-compressed build, Install to sub directory.
	$config['lang'] = $lang;
	
	$config['options'] = $selected_options;

	if ( empty($errors) ) {
		write_config();
		header("Location: {$PHP_SELF}?step=install");
		exit;
	}
}

$api = wp_remote_get('http://api2.wpquickinstall.com/version-api/api.php', array('timeout' => 10));
if ( ! is_wp_error($api) && $api && !empty($api['body']) && 200 == $api['response']['code'] ) {
	$api = @unserialize($api['body']);
}

if ( !$api || is_wp_error($api) )
	$api = array('en_US' => array( 'version' => 'Latest', 'language' => 'English', 'language_code' => 'en_US', 'stable' => true, 'download_url' => 'http://wordpress.org/latest.zip') );

if ( ! isset($api[$lang]) )
	$lang = 'en_US';

$version = isset($_REQUEST['version']) && !empty($api[$lang][$_REQUEST['version']]) ? $_REQUEST['version'] : key($api[$lang]);

the_header('install-options');
?><h1>Almost done</h1>
<p>Alright &ndash; Nearly there, Lets just choose some defaults for your WordPress Installation</p>

<script type="text/javascript">
function show_language_options() {
	document.getElementById('release-options').style.display = 'block';
}
function update_release(selectid) {
	document.getElementById('release-options').style.display = 'hidden';

	for ( var i=0; i <= selectid.length; i++) {
		if ( selectid.value == selectid.options[i].value) {
			document.getElementById('wordpress-install-version').innerHTML = selectid.options[i].text;
			break;
		}
	}
}
</script>
<form method="post" action="?step=install-options-check">
<p><strong>Currently Installing WordPress <span id="wordpress-install-version"><?php 
	echo $version;
	echo ' ';
	echo $api[$lang][$version]->language;
			?></span>. <a href="?step=install-options&select-release=true" onclick="show_language_options(); return false;">(change)</a></strong>
</p>
<p id="release-options" class="<?php if ( ! isset($_GET['select-release']) ) echo 'hidden' ?>"><label for="lang" class="hidden">Release:</label>
<select name="release" id="release-select" onchange="update_release(this)">

	<?php
		foreach ( (array)$api as $the_lang => $versions ) :
			foreach ( $versions as $the_version ) :
		?>
		<option value="<?php echo $the_version->download_url ?>" <?php if ( $the_lang == $lang && $the_version->version == $version ) echo 'selected="selected"'; ?>><?php echo $the_version->language . ' - ' . $the_version->version; ?></option>

		<?php
			endforeach;
		endforeach;
		unset($the_lang, $versions, $version);
	?>
</select>
</p>

<p<?php if ( in_array('title', $errors) ) echo ' class="error"' ?>><label for="title">Title:</label>
<input type="text" class="large<?php if ( in_array('title', $errors) ) echo '  error' ?>" name="title" id="title" value="<?php if ( isset($_REQUEST['title']) ) echo $_REQUEST['title'] ?>" />
</p>
<p<?php if ( in_array('tagline', $errors) ) echo ' class="error"' ?>><label for="tagline">Tagline:</label>
<input type="text" class="large" name="tagline" id="tagline" value="<?php if ( isset($_REQUEST['tagline']) ) echo $_REQUEST['tagline'] ?>" />
</p>
<p<?php if ( in_array('email', $errors) ) echo ' class="error"' ?>><label for="email">Admin Email:</label>
<input type="text" class="large<?php if ( in_array('email', $errors) ) echo ' error"' ?>" name="email" id="email" value="<?php if ( isset($_REQUEST['email']) ) echo $_REQUEST['email'] ?>" />
</p>
<p<?php if ( in_array('url', $errors) ) echo ' class="error"' ?>><label for="email">WordPress URL:</label>
<?php
$url = current_url();
if ( ! defined('COMPRESSED_BUILD') || !COMPRESSED_BUILD )
	$url .= 'wordpress/'; //Non-compressed build, Install to current directory.
?>
<input type="text" class="large" name="url" id="url" value="<?php echo $url ?>" disabled="disabled" />
</p>
<div id="advanced-options">
<fieldset>
	<legend>Install Options</legend>
<?php
	$options = array(
				'create-default-objects' => 'Create Example Posts, Pages, Links and Comments',
				'allow-search-engines' => 'Allow this Installation to appear in Search Engines.',
				'pretty-permalinks' => 'Enable <em>Pretty Permalinks</em> by default.',
				'debug-install' => 'Enable Development mode on the WordPress Install.',
				'enable-multisite' => 'Enable Multisite Installation support'
				);
	foreach ( $options as $option => $text ) :
?>
	<input type="checkbox" name="options[<?php echo $option ?>]" id="<?php echo $option ?>" <?php if ( in_array($option, $selected_options) ) echo ' checked="checked"'; ?> /> <label for="<?php echo $option ?>"><?php echo $text ?></label><br />
<?php endforeach; ?>
</fieldset>
</div>
<p class="step"><input name="submit" type="submit" value="Continue" class="button" /></p>
<p><input type="checkbox" name="advanced-options" id="advanced-options-toggle" <?php if ( isset($_REQUEST['advanced-options']) ) echo ' checked="checked"' ?>  /><label for="advanced-options-toggle">Show Advanced Options</label></p>
</form>
<?php
the_footer();
