<?php
function url_join($one, $two){
	//defaults:
	$port = "80";
	$scheme = $host = $path = '';
	$url = parse_url($one);
	extract($url);
	if( ! empty($query) )
		$query = "?$query";

	$port = ($port != 80) ? ':' . $port : '';

	if ( substr($path, -1) != '/') //If path is to a file, direct back to a folder.
		$path = trailingslashit(rtrim(dirname($path),'\\'));

	if ( strpos($two, '://') > -1 ) // http:// .../...jpg
		return $two;

	if ( !empty($two) && $two{0} == '/' ) //   /file.jpg
		return "{$scheme}://{$host}{$port}{$two}";

	if ( substr($two,0, 3) == '../' ){
		$path = trailingslashit(rtrim(dirname($path),'\\'));
		$two = substr($two, 3);
		return url_join("{$scheme}://{$host}{$port}{$path}{$query}", $two);
	}
	return "{$scheme}://{$host}{$port}{$path}{$two}"; // simply file.jpg
}

function cookie_array_to_cookie_http_objects($cookie) {
	$return = array();
	foreach ( (array)$cookie as $field => $value ) {
		$cookie = new WP_Http_Cookie( array() );
		$cookie->name = $field;
		$cookie->value = $value;
		$return[] = $cookie;
	}
	return $return;
}

function current_url() {
	return (isset($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) == 'on' ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . trailingslashit(dirname($_SERVER['REQUEST_URI']));
}

/**
 * Navigates through an array and removes slashes from the values.
 * Identical to WordPress's stripslashes_deep other than the name to prevent conflict.
 *
 * If an array is passed, the array_map() function causes a callback to pass the
 * value back to the function. The slashes from this value will removed.
 *
 * @since 2.0.0
 *
 * @param array|string $value The array or string to be striped.
 * @return array|string Stripped array (or string in the callback).
 */
function _stripslashes_deep($value) {
	$value = is_array($value) ? array_map('_stripslashes_deep', $value) : stripslashes($value);
	return $value;
}