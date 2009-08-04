<?php

/**
 * This file is the main Builder for the Compressed/Minified/Combined Builds.
 * It can be daunting at first, But it really works. Certain PHP functionalities may not be supported, but i've done my best.
 * Various PHP comments can be used to control the process of the builder, Eg: (Ignore any spaces in the closing Block quote.)
 *  /*BuildIgnore* / - If placed DIRECTLY before a include/require, the builder will not attempt to place it inline.
 *  /*BuildCompressSplit* / - If used, In the Compressed build, The code will be split into eval blocks eitherside of it. Useful for delaying function definitions until the previous block is finished running.
 */

set_time_limit(0);
$in = 'installer.php';
$out = 'release/installer.php';

$out_contents = 'define("COMPRESSED_BUILD", true); ' . _strip_php_openers(file_get_contents($in));

function _strip_php_openers($file) {
	$file = preg_replace('#^\s*<\?php\s*#is', '', $file);
	return preg_replace('#\s*\?>\s*$#is', '', $file);
}

//var_dump( preg_match('#(include|require|include_once|require_once)\s*\(?\s*(["\'])(.+)\\2;#', $out_contents, $matches), $matches);

//First, Replace includes.
$out_contents = preg_replace_callback('#(?<!/\*BuildIgnore\*/)(include|require|include_once|require_once)((\(.*?["\'](.+)["\']\s*\))|\s*["\'](.+)["\']);#i', '_replace_include', $out_contents);

function _replace_include($matches) {
	$filename = isset($matches[5]) ? $matches[5] : $matches[4];

	//if ( strpos($matches[3], 'ABSPATH') !== false )
	//	$filename = 'wp-files/' . $filename;
	if ( !file_exists($filename) ) {
		echo '<p><strong>Warning:</strong> <code>' . $filename . '</code> does not exist</p>';
		return '';
	}
	$file = _strip_php_openers(file_get_contents($filename));
	$file = preg_replace_callback('#(?<!/\*BuildIgnore\*/)(include|require|include_once|require_once)((\(.*?["\'](.+)["\']\s*\))|\s*["\'](.+)["\']);#i', '_replace_include', $file);
	return "\n" . $file . "\n";
}

function _get_resources() {
	$all = glob('resources/*');
	$reses = '$resources = array();';
	foreach ( $all as $res ) {
		$res = str_replace('resources/', '', $res);
		$reses .= '$resources["' . $res . '"] = "' . base64_encode(gzcompress( file_get_contents('resources/' . $res), 9 )) . '";';
	}
	return $reses;
}

file_put_contents($out . '.nonminimised.php', '<?php ' . _get_resources() . $out_contents);

//Remove any comments
$out_contents = preg_replace('!(/\*Build\S+?\*/)|(/\*.+?\*/)!is', '$1', $out_contents); //Remove Multiline comments, Leaving  special Build commands.
$out_contents = preg_replace('#^\s*//.+#im', '', $out_contents);
$out_contents = preg_replace('#([;{}(),])\s*//.+#i', '$1', $out_contents);
$out_contents = preg_replace('#((case|default)\s*.*?\s*:)\s*//.+#i', '$1', $out_contents);
$out_contents = preg_replace('#(else)\s*//.+#i', '$1', $out_contents); //Separate from the above to protect it from eating your babies.

//Time to set the Build Date and Revision.
$date = date('d/M/Y');
$revision = '';
if ( file_exists(dirname(__FILE__) . '/.svn/entries') ) {
	$r_parts = file(dirname(__FILE__) . '/.svn/entries');
	$revision = intval($r_parts[3]);
	if ( $revision )
		$revision = '<abbr title="Revision ' . $revision . '">r' . $revision . '</abbr>';
	unset($r_parts);
}
$out_contents = str_replace('/*BuildDate*/', $date, $out_contents);
$out_contents = str_replace('/*BuildRevision*/', $revision, $out_contents);

//Remove any 'RemoveMe' blocks
$out_contents = preg_replace('!(/\*BuildRemoveStart\*/.+?/\*BuildRemoveEnd\*/)!is', '', $out_contents);

//Next, Remove any whitespace thats not needed from within PHP code
$in_field = false;
$in_php = true;
$oca = preg_split('||', $out_contents, -1, PREG_SPLIT_NO_EMPTY);
$count = count($oca);
$unset = array();

for ( $i = 0; $i < $count; $i++) { 
    $value = $oca[$i]; 

    //PHP Block is starting again. 
    if ( true !== $in_php && substr($out_contents, $i, strlen('<?php')) == '<?php' ) { 
        $i += 4; 
        $in_php = true; 
    } 
    //PHP Block has ended. 
    if ( ! $in_field && true === $in_php && $value == '?' &&  $i < $count && $oca[$i+1] == '>' ) { 
        $i++; 
        $in_php = $i; 
        continue; 
    } 

    if ( true !== $in_php ) 
        continue; 

    //End of an enclosed string 
    if ( $value === $in_field && ($oca[$i-1] != '\\' || $oca[$i-2] == '\\') ) { 
		//TODO well we bettter remove any whitespace around any html tags in the values IMO..... 
        $in_field = false; 
        continue; 
    } 
    if ( $in_field ) 
        continue; 
    //detect start of enclosed string. 
    if ( in_array($value, array('"', "'")) ) { 
        $in_field = $value; 
        continue; 
    } 

    $is_whitespace = $in_php && preg_match('|^\s+$|', $value); 

    if ( $is_whitespace ) { 
        //Make sure theres some whitespace after PHP lang items. 
        foreach ( array('<?php', 'function', 'class', 'var', 'return', 'else', 'case', 'echo', 'new', 'and', 'or', 'global', 'include', 'require', 'include_once', 'require_once') as $item ) { 
            if ( $i < strlen($item) ) 
                continue; 
            if ( $item == substr($out_contents, $i-strlen($item), strlen($item)) ) { 
                $oca[$i] = ' '; //Make sure its a space.. not just whitespace :) 
				//$i += strlen($item);
				if ( $item == 'and' || $item == 'or' )
					array_pop($unset);
                continue 2; 
            } 
        } 
        //Need whitespace around 'as' in foreach construct 
        if ( preg_match('|foreach.+ as$|', substr($out_contents, $i - ($i>50?50:$i), 50)) ) { 
            $i++; 
            array_pop($unset); 
            continue; 
        } 
        //Need whitespace around 'extends' in class declaration 
        if ( preg_match('|class.+ extends$|', substr($out_contents, $i - ($i>50?50:$i), 50)) ) { 
            $i += 1; 
            array_pop($unset); 
            continue; 
        } 
    } 

    if ( $is_whitespace ) 
        $unset[] = $i; 

} 
foreach ( $unset as $i )  
    unset($oca[$i]); 
$out_contents = implode('', $oca); 
unset($in_field, $oca, $count, $unset);

//Next, Strip Whitespace from the HTML in the file:

$in_html = false;
$count = strlen($out_contents);
for ( $i = 0; $i < $count; $i++) {
	//PHP Block is starting again.
	if ( $in_html && substr($out_contents, $i, 5) == '<?php' ) {
		$test_string1 = substr($out_contents, $in_html, $i-$in_html+1);
		$test_string2 = preg_replace('|>\s+<|m', '><', $test_string1);
		if ( $test_string1 != $test_string2 ) {
			$out_contents = substr($out_contents, 0, $in_html) . $test_string2 . substr($out_contents, $i+1);
			$count = strlen($out_contents);
			$i = $in_html; //Reset back to start.
		}
		$in_html = false;
	}
	//HTML Block has ended.
	if ( !$in_html && $out_contents{$i} == '?' && $out_contents{$i+1} == '>' ) {
		$i++;
		$in_html = $i;
	}
	//TODO Add a branch here to strip it out when its inside a PHP string..
}

highlight_string('<?php ' . _get_resources() . $out_contents);

file_put_contents($out . '.uncompressed.php', '<?php ' . _get_resources() . $out_contents);

$warning = true ? '' : '/* Warning: This file is Compressed, Which is why eval+base64_decode are used. Its nothing to worry about in this case, Please visit http://.../ for more information on the WordPress Single-file Installer. */';

//Final touches.. This is done to allow for functions to be defined in a later eval() block to allow for WP inclusion directly by compressed content.
$chunks = explode('/*BuildCompressSplit*/', $out_contents);
$out_contents = '<?php ' . $warning . _get_resources();
foreach ( $chunks as $chunk )
	$out_contents .= "\n" . 'eval(gzuncompress(base64_decode("' . base64_encode(gzcompress($chunk, 9)) . '")));';

file_put_contents($out, $out_contents);