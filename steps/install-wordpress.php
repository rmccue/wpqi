<?php

//Note that we have the full power of WordPress in this file, All translate function are available (in the install language) as well as all helper functions.

if ( defined( 'E_RECOVERABLE_ERROR' ) )
	error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING | E_RECOVERABLE_ERROR );
else
	error_reporting( E_CORE_ERROR | E_CORE_WARNING | E_COMPILE_ERROR | E_ERROR | E_WARNING | E_PARSE | E_USER_ERROR | E_USER_WARNING );

/*BuildIgnore*/include_once ABSPATH . 'wp-admin/includes/upgrade.php';

function _wp_install_defaults_cats_only() {
	global $wpdb;

	// Default category
	$cat_name = __('Uncategorized');
	/* translators: Default category slug */
	$cat_slug = sanitize_title(_x('Uncategorized', 'Default category slug'));

	$wpdb->insert( $wpdb->terms, array('name' => $cat_name, 'slug' => $cat_slug, 'term_group' => 0) );
	$wpdb->insert( $wpdb->term_taxonomy, array('term_id' => '1', 'taxonomy' => 'category', 'description' => '', 'parent' => 0, 'count' => 1));

	// Default link category
	$cat_name = __('Blogroll');
	/* translators: Default link category slug */
	$cat_slug = sanitize_title(_x('Blogroll', 'Default link category slug'));

	$wpdb->insert( $wpdb->terms, array('name' => $cat_name, 'slug' => $cat_slug, 'term_group' => 0) );
	$wpdb->insert( $wpdb->term_taxonomy, array('term_id' => '2', 'taxonomy' => 'link_category', 'description' => '', 'parent' => 0, 'count' => 7));
}

$public_blog = in_array('search-engines', $config['options']);
$create_defaults = in_array('create-default-objects', $config['options']);
$pretty_permalinks = in_array('pretty-permalinks', $config['options']);

$username = !empty($config['username']) ? $config['username'] : 'admin';

wp_check_mysql_version();
wp_cache_flush();
make_db_current_silent();
populate_options();
populate_roles();

update_option('blogname', $config['title']);
update_option('blogdescription', $config['tagline']);
update_option('admin_email', $config['email']);
update_option('blog_public', $public_blog );

$guessurl = ( !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$guessurl = trailingslashit( url_join($guessurl, $config['destination']) );

update_option('siteurl', $guessurl);
update_option('home', $guessurl);

// If not a public blog, don't ping.
update_option('default_pingback_flag', $public_blog ? "1" : "0");

//If installer is to enable permalinks, enable the base permalink structure. - Note, That the single-file installer also creates the .htaccess file, and chmod 777's it.
if ( $pretty_permalinks )
	update_option('permalink_structure', '/%year%/%monthnum%/%day%/%postname%/');

// Create default user.  If the user already exists, the user tables are
// being shared among blogs.  Just set the role in that case.
if ( 'admin' != $username && username_exists('admin') )
	$username = 'admin';
$user_id = username_exists($username);
if ( !$user_id ) {
	$random_password = wp_generate_password();
	$user_id = wp_create_user($username, $random_password, $config['email']);
	update_usermeta($user_id, 'default_password_nag', true);
} else {
	$random_password = '';
}

$user = new WP_User($user_id);
$user->set_role('administrator');

if ( $create_defaults )
	wp_install_defaults($user_id);
else
	_wp_install_defaults_cats_only();

if ( $is_apache ) 
	$wp_rewrite->flush_rules(false); //false = soft flush
else
	$wp_rewrite->flush_rules(true); //true = hard flush, redo the files.

wp_new_blog_notification($config['title'], $guessurl, $user_id, $random_password);

wp_cache_flush();

ob_end_clean(); //Clean out any caught error-data

echo serialize( array('url' => $guessurl, 'user_id' => $user_id, 'username' => $username, 'password' => $random_password ) );