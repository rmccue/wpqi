<?php
function the_header( $id = '', $resources = array() ) {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>WordPress QI - The Single file WordPress Quick Installer</title>
<?php
$resources[] = 'install.css';
$resources[] = 'common.js';
foreach ( (array) $resources as $file ) {
	if ( 'css' == substr( $file, -3 ) )
		echo '<link rel="stylesheet" type="text/css" href="' . res_url( $file ) . '" />' . "\n";
	elseif ( 'js' == substr( $file, -2 ) )
		echo '<script type="text/javascript" src="' . res_url( $file ) . '"></script>' . "\n";
}
?>
</head>
<body<?php if ( !empty( $id ) ) echo ' id="' . $id . '"' ?>>
<h1 id="logo"><img alt="WordPress" src="<?php echo res_url( 'wordpressqi.png' ) ?>" /></h1>
<?php
}