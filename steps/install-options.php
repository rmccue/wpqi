<?php
//Note, this file has unset()'s after most loops, this was mainly done during development to ween out the use of variables in loops that are used elsewhere, the unset() makes it much more obvious about the variable use to me.
if ( empty($config['db']) || empty($config['fs']) ) {
	header("Location: {$PHP_SELF}");
	exit;
}

if ( isset($config['api']) && !empty($config['api']) ) {
	$api = $config['api'];
} else {
	$api = wp_remote_get('http://api.wpquickinstall.com/version-api/', array('timeout' => 10));
	if ( ! is_wp_error($api) && $api && !empty($api['body']) && 200 == $api['response']['code'] ) {
		$api = @unserialize($api['body']);
		if ( $api ) {
			$config['api'] = $api;
		}
	}
}

if ( !$api || is_wp_error($api) )
	$api = array('versions' => array(), 'langs' => array( 'en_US' => array('latest' => array('language' => 'API Down: Latest English Release Only.', 'lang' => 'en_US', 'download' => 'http://wordpress.org/latest.zip', 'homepage' => 'http://wordpress.org/') ) ) );

foreach ( array('title' => '', 'email' => '', 'tagline' => '', 'lang' => $the_guessed_language) as $field => $default )
	$$field = isset($_REQUEST[$field]) ? $_REQUEST[$field] : $default;

if ( ! isset($api['langs'][ $lang ]) )
	$lang = 'en_US';

//TODO: I believe theres a sorting bug below somewhere..
function _stable_versions_filter($a) { //Callback filter for below filter.
	return !preg_match("|[^0-9\.]|", $a);
}
$tmp_vers = array_keys($api['langs'][$lang]);
$tmp_vers = array_filter($tmp_vers, '_stable_versions_filter' ); //Figure out which ones are stable releases, and lets use the latest :)

$version = isset($_REQUEST['version']) && !empty($api['langs'][$lang][$_REQUEST['version']]) ? $_REQUEST['version'] : (count($tmp_vers) ? max($tmp_vers) : '');

$selected_options = ('install-options-check' == $step) ? (isset($_POST['options']) ? array_keys($_POST['options']) : array()) : array('create-default-objects', 'allow-search-engines','pretty-permalinks');

unset($tmp_vers);

$errors = array();
if ( 'install-options-check' == $step ) {
	if ( !empty($api['langs'][$lang][$version]['package']) )
		$config['package'] = $api['langs'][$lang][$version]['package'];
	$config['title'] = $title;
	$config['email'] = $email;
	$config['tagline'] = $tagline;

	if ( empty($config['package']) ) $errors[] = 'package';
	if ( empty($title) ) $errors[] = 'title';
	if ( !preg_match('|.+@.+|', $email) ) $errors[] = 'email';
	
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

the_header('install-options');
?><h1>Almost done</h1>
<p>Alright &ndash; Nearly there, Lets just choose some defaults for your WordPress Installation</p>

<script type="text/javascript">
<!--
var versions = [];
<?php
foreach ( $api['versions'] as $the_version => $text )
	echo "versions['$the_version'] = '$text';\n";
unset($the_version, $text);
?>
var lang_to_version = [];
<?php
foreach ( $api['langs'] as $the_lang => $lang_versions ) {
	echo "lang_to_version['$the_lang'] = [";
	$keys = array_keys($lang_versions);
	foreach ( $keys as $item )
		echo "'$item'" . (end($keys) != $item ? ',' : '');
	echo "];\n";
}
unset($the_lang, $lang_versions);
?>
function switch_lang(lang_select) {
	var v = lang_select.value;
	var sel = document.getElementById('version-select');
	while ( sel.options.length > 0 ) //empty it first.
		sel.remove(0);
	for (var i = 0; i < lang_to_version[v].length; i++)
		sel.options[sel.options.length] = new Option(versions[ lang_to_version[v][i] ], lang_to_version[v][i], false, false);
	
	sel.disabled = ( lang_to_version[v].length == 1 );
	
}
function show_language_options() {
	if ( document.getElementById('language-nag') )
		document.getElementById('language-nag').parentNode.removeChild(document.getElementById('language-nag'));
	document.getElementById('language-options').style.display = 'block';
}
-->
</script>
<form method="post" action="?step=install-options-check">
<p><strong>Currently Installing WordPress <?php 
	echo 	!empty($api['langs'][ $lang ][ $version ]['language']) ? $api['langs'][ $lang ][ $version ]['language'] : $lang, ' ',
			isset($api['versions'][$version]) ? $api['versions'][$version] : $version ?>. <a href="?step=install-options&select-version=true" onclick="show_language_options(); return false;">(change)</a></strong>
<?php if ( ! isset($_GET['select-version']) && count($api['langs']) > 1 && $lang == 'en_US' ) echo '<span id="language-nag"><br /><small>A total of ' . count($api['langs']) . ' different languages available, Try WordPress in <em>your</em> language <strong>now!</strong></small></span>' ?>
</p>
<p id="language-options" class="<?php if ( ! isset($_GET['select-version']) ) echo 'hidden' ?>"><label for="lang" class="hidden">Language:</label>
<select name="lang" id="lang-select" class="half-width" onchange="switch_lang(this)">
	<?php
		foreach ( (array)$api['langs'] as $the_lang => $item ) :
			$vers = array_keys($item);
			$title = !empty($item[ $vers[0] ]['language']) ? $item[ $vers[0] ]['language'] : '(Unknown) ' . $item[ $vers[0] ]['lang'];
			$title .= ' ';
			$title .= '(' . implode(', ', $vers) . ')';
	?>
		<option value="<?php echo $the_lang ?>" <?php if ( $lang == $the_lang ) echo 'selected="selected"' ?>><?php echo $title  ?></option>
	<?php
		endforeach;
		unset($the_lang, $item, $vers, $title);
	?>
</select>
<label for="version" class="hidden">Version:</label>
<select name="version" class="half-width" id="version-select" <?php if ( count($api['langs'][$lang]) <= 1 ) echo 'disabled="disabled"' ?> <?php if ( in_array('package', $errors) ) echo '  class="error"' ?> >
	<?php 
		foreach ( array_keys($api['langs'][$lang]) as $the_version ) :
			$text = isset($api['versions'][$the_version]) ? $api['versions'][$the_version] : $the_version;
	?>
	<option value="<?php echo $the_version ?>" <?php if ( $version == $the_version ) echo 'selected="selected"' ?>><?php echo $text ?></option>
	<?php
		endforeach;
		unset($text, $the_version);
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
				'create-default-objects' => 'Create Example Posts, Posts, Links and Comments',
				'allow-search-engines' => 'Allow this Installation to appear in Search Engines.',
				'pretty-permalinks' => 'Enable <em>Pretty Permalinks</em> by default.',
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
