<?php
function the_header($id = '') {
/*$total_steps = 5;
if ( get_filesystem_method() == 'direct' ) {
	$total_steps--;
$steps = array('first' => 0, 'db-details' => 1, 'db-detail-check' => 1, 'install-options' => 2, 'install-options-check' => 2);
} else {
	$steps = array('first' => 0, 'fs-details' => 1, 'fs-detail-check' => 1, 'db-details' => 2, 'db-detail-check' => 2, 'install-options' => 3, 'install-options-check' => 3);
}
$step = $steps[ $GLOBALS['step'] ];
$percentage_width = ceil($step*(700/$total_steps))*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head profile="http://gmpg.org/xfn/11">
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>WP Auto Installer</title>
<link rel="stylesheet" type="text/css" href="<?php echo res_url('install.css') ?>" />
<?php /* <script src="http://www.google.com/jsapi"></script>
<script type="text/javascript">
<!--
google.load("jquery", "1.3.2");
-->
</script>
<style type="text/css">
.process { position:relative; width: 100%; border: 1px solid #000; text-align:center; font-weight: bold; }
.process span { position:absolute; width: 100%;}
.percentage { position:absolute; width: <?php echo $percentage_width ?>px; background-color:#0CF;}
</style>
*/ ?>
</head>
<body<?php if ( !empty($id) ) echo ' id="' . $id . '"' ?>>
<h1 id="logo"><img alt="WordPress" src="<?php echo res_url('wordpress-logo.png') ?>" /></h1>
<?php /*if (false &&  $step > 0 ) : ?>
<div class="process">
<div class="percentage">&nbsp;</div>
<span><?php echo "Step $step of $total_steps"; ?></span>
<br />
</div>
<?php endif; */ ?>
<?php
}