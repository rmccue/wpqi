<?php

/**
 * Note: Most of these functions/constants are only used for stubful purpose. More full functions later in the file however.
 */

function is_multisite() { return false; }
function __( $s ) { return $s; }
function _e( $s ) { echo $s; }
function do_action() {}
function do_action_ref_array() {}
function apply_filters( $a, $v ) { return $v; }
function has_filter() { return false; }
function has_action() { return false; }
function get_bloginfo() { return current_url(); } //TODO Could add a if() to only return the URL on URL's.. but 'eh, Its only used by the HTTP API's Local-request checker..
function get_status_header_desc() { return false; }

if ( ! defined( 'WP_CONTENT_DIR' ) )
	define( 'WP_CONTENT_DIR', rtrim( ABSPATH, '/' ) );
if ( ! defined( 'WP_PLUGIN_DIR' ) )
	define( 'WP_PLUGIN_DIR', rtrim( ABSPATH, '/' ) );

function untrailingslashit( $s ) { return rtrim( $s, '/' ); }
function trailingslashit( $s ) { return untrailingslashit( $s ) . '/'; }

function get_option( $opt, $default = false ) { return $default; }
function update_option() { return false; }
function screen_icon() {}
function esc_attr( $s ) { return $s; }
function esc_attr_e( $s ) { echo $s; }

function wp_die( $e ) { die( $e ); }

function checked( $a, $b ) { if ( $a == $b ) echo ' checked="checked"'; }

function wp_parse_args( $args, $defaults = '' ) {
	if ( is_object( $args ) )
		$r = get_object_vars( $args );
	elseif ( is_array( $args ) )
		$r =& $args;
	else
		parse_str( $args, $r );

	if ( is_array( $defaults ) )
		return array_merge( $defaults, $r );
	return $r;
}

function wp_load_translations_early() {}

function wp_debug_backtrace_summary( $ignore_class = null, $skip_frames = 0, $pretty = true ) {
	$trace  = debug_backtrace( false );
	$caller = array();
	$check_class = ! is_null( $ignore_class );
	$skip_frames++; // skip this function

	foreach ( $trace as $call ) {
		if ( $skip_frames > 0 ) {
			$skip_frames--;
		} elseif ( isset( $call['class'] ) ) {
			if ( $check_class && $ignore_class == $call['class'] )
				continue; // Filter out calls

			$caller[] = "{$call['class']}{$call['type']}{$call['function']}";
		} else {
			if ( in_array( $call['function'], array( 'do_action', 'apply_filters' ) ) ) {
				$caller[] = "{$call['function']}('{$call['args'][0]}')";
			} elseif ( in_array( $call['function'], array( 'include', 'include_once', 'require', 'require_once' ) ) ) {
				$caller[] = $call['function'] . "('" . str_replace( array( WP_CONTENT_DIR, ABSPATH ) , '', $call['args'][0] ) . "')";
			} else {
				$caller[] = $call['function'];
			}
		}
	}
	if ( $pretty )
		return join( ', ', array_reverse( $caller ) );
	else
		return $caller;
}


/**
 * Generates a random password drawn from the defined set of characters.
 *
 * @since 2.5
 *
 * @param int $length The length of password to generate
 * @param bool $special_chars Whether to include standard special characters
 * @return string The random password
 **/
function wp_generate_password( $length = 12, $special_chars = true ) {
	$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
	if ( $special_chars )
		$chars .= '!@#$%^&*()';

	$password = '';
	for ( $i = 0; $i < $length; $i++ )
		$password .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
	return $password;
}

 /**
 * Generates a random number - MODIFIED $seed + statics
 *
 * @since 2.6.2
 *
 * @param int $min Lower limit for the generated number (optional, default is 0)
 * @param int $max Upper limit for the generated number (optional, default is 4294967295)
 * @return int A random number between min and max
 */
function wp_rand( $min = 0, $max = 0 ) {
	static $rnd_value = '';
	static $seed = null;
	if ( $seed == null )
		$seed = md5( time() );

	// Reset $rnd_value after 14 uses
	// 32(md5) + 40(sha1) + 40(sha1) / 8 = 14 random numbers from $rnd_value
	if ( strlen( $rnd_value ) < 8 ) {
		$rnd_value = md5( uniqid( microtime() . mt_rand(), true ) . $seed );
		$rnd_value .= sha1( $rnd_value );
		$rnd_value .= sha1( $rnd_value . $seed );
		$seed = md5( $seed . $rnd_value );
	}

	// Take the first 8 digits for our value
	$value = substr( $rnd_value, 0, 8 );

	// Strip the first eight, leaving the remainder for the next call to wp_rand().
	$rnd_value = substr( $rnd_value, 8 );

	$value = abs( hexdec( $value ) );

	// Reduce the value to be within the min - max range
	// 4294967295 = 0xffffffff = max random number
	if ( $max != 0 )
		$value = $min + ( ( $max - $min + 1 ) * ( $value / ( 4294967295 + 1 ) ) );

	return abs( intval( $value ) );
}

function validate_file( $file, $allowed_files = '' ) {
	if ( false !== strpos( $file, '..' ) )
		return 1;

	if ( false !== strpos( $file, './' ) )
		return 1;

	if ( ! empty( $allowed_files ) && ! in_array( $file, $allowed_files ) )
		return 3;

	if ( ':' == substr( $file, 1, 1 ) )
		return 2;

	return 0;
}
