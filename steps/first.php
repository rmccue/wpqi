<?php the_header() ?>

<p>If you're reading this message, congratulations! WordPress QI is ready to start setting up your new WordPress site.</p>
<p>Before we hand off to WordPress to begin installation, we're going to download the latest version of WordPress. In order to do so, we'll need access to your server directory.</p>
<?php if ( 'direct' != get_filesystem_method() ) :?>
<p>We can't access the files directly, so on the next step, you'll be prompted for your FTP details (or if available, your SSH details).</p>
<?php else: ?>
<p>We can access the files directly, so we're almost ready to go. Just hit the button below to begin!</p>
<?php endif; ?>

<form action="" method="POST">
	<input type="hidden" name="step" value="download" />

	<p>
		<label for="path">Path to WordPress</label>
		<input type="text" name="path" value="wordpress/" size="25" id="path" />
	</p>
	<p>If you want to install WordPress into a subdirectory, specify that here. Leave blank to install to this directory.</p>

<?php if ( 'direct' != get_filesystem_method() ) :?>
	<p class="step"><input type="submit" class="button" value="Continue" /></p>
<?php else: ?>
	<p class="step"><input type="submit" class="button" value="Let&#8217;s go!" /></p>
<?php endif; ?>
</form>

<?php
the_footer();

