<?php
function the_footer() {
	global $wpa_verrsion;
?>
<div id="footer">
<a href="http://wpautoinstall.com/">The WordPress Single-file Installer</a> by <a href="http://dd32.id.au/">Dion Hulse</a>. Version <?php echo $wpa_verrsion ?>
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