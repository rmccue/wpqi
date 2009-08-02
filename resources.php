<?php
function res_url($res) {
	return $_SERVER['PHP_SELF'] . '?resource=' . $res;
}
function get_res($res) {
	if ( file_exists('resources/' . $res) )
		return file_get_contents('resources/' . $res);
	else
		return !isset($GLOBALS['resources'][ $res ]) ? false : gzuncompress( base64_decode( $GLOBALS['resources'][ $res ] ) );
}
if ( !empty($_REQUEST['resource']) ) {

	$type = preg_replace('|.+\.(\w+)$|', '$1', $_REQUEST['resource']);
	if ( 'css' == $type )
		$type = 'text/css';
	elseif ( 'js' == $type )
		$type = 'text/javascript';
	elseif ( in_array( $type, array('jpg', 'png') ) )
		$type = 'image/' . $type;

	header("Content-Type: $type");

	echo get_res($_REQUEST['resource']);
	exit;
}