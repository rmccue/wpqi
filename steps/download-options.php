<?php

$errors = array();

$query = array(
	'locale' => 'en_US',
	'php' => phpversion()
);

$api = wp_remote_get('http://api.wordpress.org/core/version-check/1.6/?' . http_build_query($query, null, '&'), array('timeout' => 10));
if ( ! is_wp_error($api) && $api && !empty($api['body']) && 200 == $api['response']['code'] ) {
	$api = @unserialize($api['body']);
	$api = $api['offers'][0];
}

if ( !$api || is_wp_error($api) || (isset($api['response']['code']) && $api['response']['code'] !== 200) ) {
	$api = array(
		'locale' => 'en_US',
		'download' => 'http://wordpress.org/latest.zip',
		'current' => 'unknown'
	);
}

the_header('download-options');
?><h1>Almost done</h1>
<p>Alright &ndash; Nearly there, Lets just choose some defaults for your WordPress Installation</p>

<form method="post" action="?step=download">
<p><strong>Currently Installing WordPress <span id="wordpress-install-version"><?php 
	echo $api['current'];
	echo ' ';
	echo $api['locale'];
			?></span>. <a href="?step=download-options&select-release=true" onclick="show_language_options(); return false;">(change)</a></strong>
</p>

<input type="hidden" name="package" value="<?php echo $api['download'] ?>" />
<p id="release-options" class="<?php if ( ! isset($_GET['select-release']) ) echo 'hidden' ?>"><label for="lang" class="hidden">Release:</label>

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
<p class="step"><input name="submit" type="submit" value="Continue" class="button" /></p>
</form>
<?php
the_footer();
