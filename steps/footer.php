<?php
function the_footer() {
	global $wpa_version;
?>
<div id="footer">
<a href="http://wpquickinstall.com/">WordPress QI - The Single-file WordPress Quick Installer</a> by <a href="http://dd32.id.au/">Dion Hulse</a>. Version <?php echo $wpa_version ?>&nbsp;<?php
	/*BuildRemoveStart*/
	if ( defined('COMPRESSED_BUILD') && COMPRESSED_BUILD ) {
	/*BuildRemoveEnd*/
		echo 'Build Date: /*BuildDate*/ /*BuildRevision*/';
	/*BuildRemoveStart*/
	} else {
		$revision = '';
		if ( file_exists(dirname(dirname(__FILE__)) . '/.svn/entries') ) {
			$r_parts = file(dirname(dirname(__FILE__)) . '/.svn/entries');
			$revision = 'r' . intval($r_parts[3]);
			unset($r_parts);
		}
		echo "Development Build " . $revision;
	} /*BuildRemoveEnd*/ ?>
</div>
<script type="text/javascript">
function advanced_options() {
	var div = document.getElementById('advanced-options');
	var toggle = document.getElementById('advanced-options-toggle');
	if ( ! div || ! toggle )
		return;
	if ( div.className && div.className.indexOf('force-show-block') )
		return;
	if ( ! toggle.onchange )
		toggle.onchange = function() { advanced_options(); };
	div.style.display = toggle.checked ? 'block' : 'none';
}
advanced_options();
</script>
</body>
</html>
<?php
}