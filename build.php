<?php

/**
 * This file is the main Builder for the Compressed/Minified/Combined Builds.
 * It can be daunting at first, But it really works. Certain PHP functionalities may not be supported, but i've done my best.
 * Various PHP comments can be used to control the process of the builder, Eg: (Ignore any spaces in the closing Block quote.)
 *  /*BuildIgnore* / - If placed DIRECTLY before a include/require, the builder will not attempt to place it inline.
 *  /*BuildCompressSplit* / - If used, In the Compressed build, The code will be split into eval blocks eitherside of it. Useful for delaying function definitions until the previous block is finished running.
 */

set_time_limit(0);

function _strip_php_openers($file) {
	$file = preg_replace('#^\s*<\?php\s*#is', '', $file);
	return preg_replace('#\s*\?>\s*$#is', '', $file);
}

function _replace_include($matches) {
    $filename = isset($matches[5]) ? $matches[5] : $matches[4];

    $known_missing_includes = array('class-ftp-".($mod_sockets?"sockets":"pure").".php');

    if ( !file_exists($filename) ) {
        if ( ! isset($_REQUEST['quiet']) )
            if ( !in_array($filename, $known_missing_includes) )
                echo '<p><strong>Warning:</strong> <code>' . $filename . '</code> does not exist</p>';
        return '';
    }
    $file = _strip_php_openers(file_get_contents($filename));
    //Next, Replace WP_DEBUG with QI_DEBUG in WORDPRESS FILES ONLY
    if ( strpos($filename, 'wp-files') !== false )
        $file = str_replace('WP_DEBUG', 'QI_DEBUG', $file);
    $file = preg_replace_callback('#(?<!/\*BuildIgnoreInclude\*/)(include|require|include_once|require_once)((\(.*?["\'](.+)["\']\s*\))|\s*["\'](.+)["\']);#i', '_replace_include', $file);
    return "\n" . $file . "\n";
}

function _get_resources() {
    $all = glob('resources/*');
    $reses = '$resources = array();' . "\n";
    foreach ( $all as $res ) {
        $res = str_replace('resources/', '', $res);
        $reses .= '$resources["' . $res . '"] = "' . base64_encode(gzcompress( file_get_contents('resources/' . $res), 9 )) . '";' . "\n";
    }
    return $reses;
}

function _get_requirements_notice() {
    return "\nif(!function_exists('gzuncompress')||!function_exists('base64_decode'))die('This script requires for the functions <code>gzuncompress()</code>, <code>base64_decode</code> and <code>eval()</code> to be available. Your current hosting does not allow one or more of these functions. Please try a Minified Build instead.');\n"; //TODO: Eval doesnt seem to play nice here.
}


echo 'Building QI...' . PHP_EOL;

$in = 'installer.php';
$out_contents = 'define("COMPRESSED_BUILD", true);' . "\n" . _strip_php_openers(file_get_contents($in));


echo 'Replacing includes' . PHP_EOL;

$out_contents = preg_replace_callback('#(?<!/\*BuildIgnoreInclude\*/)(include|require|include_once|require_once)((\(.*?["\'](.+?)["\']\s*\))|\s*["\'](.+)["\']);#i', '_replace_include', $out_contents);

//file_put_contents('release/installer-nonminimised.php', '<?php ' . _get_resources() . $out_contents);

//echo 'Non-minimised build created, Exiting.';
//die();

//Time to set the Build Date and Revision.
$date = date('Y-m-d');
$revision = '';
if ( file_exists(dirname(__FILE__) . '/.git/HEAD') ) {
    $revision = trim(exec('git rev-parse --short HEAD'));
    $out_contents = preg_replace('#\$wpqi_version = \'([^\']+)\';#', '$wpqi_version = \'$1-' . $revision . '\';', $out_contents);
}
$out_contents = str_replace('/*BuildDate*/', $date, $out_contents);

//Remove any 'RemoveMe' blocks
$out_contents = preg_replace('!(/\*BuildRemoveStart\*/.+?/\*BuildRemoveEnd\*/)!is', '', $out_contents);

//Remove non-needed Build* markers.
$out_contents = str_replace( array('/*BuildIgnoreInclude*/'), '', $out_contents);


echo 'Compressing whitespace and stripping comments' . PHP_EOL;

$tokens = token_get_all('<?php ' . $out_contents);
$result = '';
foreach ($tokens as $token) {
    if (is_string($token)) {
        $result .= $token;
        continue;
    }

    switch ($token[0]) {
        // Strip comments and PHPDoc comments
        case T_DOC_COMMENT:
        case T_COMMENT:
            if (strpos($token[1], 'BuildCompressSplit') !== false) {
                $result .= $token[1];
            }
            break;

        case T_WHITESPACE:
            $result .= ' ';
            break;

        case T_INLINE_HTML:
            $token[1] = preg_replace('|>\s+<|m', '><', $token[1]);
            // fall-through
        default:
            $result .= $token[1];
            break;
    }
}
$out_contents = substr($result, 6);

file_put_contents('release/installer-uncompressed.php', '<?php ' . _get_resources() . $out_contents);

//Final touches.. This is done to allow for functions to be defined in a later eval() block to allow for WP inclusion directly by compressed content.
echo 'Building compressed' . PHP_EOL;
$chunks = explode('/*BuildCompressSplit*/', $out_contents);
$out_contents = '<?php ' . _get_requirements_notice() . _get_resources();

foreach ( $chunks as $chunk ) {
	$out_contents .= "\n" . 'eval(gzuncompress(base64_decode("' . base64_encode(gzcompress($chunk, 9)) . '")));';
}

file_put_contents('release/installer.php', $out_contents);
echo 'Done!' . PHP_EOL;