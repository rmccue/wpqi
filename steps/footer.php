<?php
function the_footer() {
	global $wpqi_version;
?>
<div id="footer">
<p><a href="http://wpquickinstall.com/">WordPress QI</a> by <a href="http://dd32.id.au/">Dion Hulse</a> and <a href="http://ryanmccue.info/">Ryan McCue</a></p>
<p>Version <?php echo $wpqi_version ?> <?php echo "/*BuildDate*/"; ?></p>
</div>
</body>
</html>
<?php
}