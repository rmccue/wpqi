<?php
$errors = array();
if ( 'packages-check' == $step ) {

	if ( empty($errors) ) {	
		write_config();
		header("Location: {$PHP_SELF}?step=install");
		exit;
	}
}
the_header('', array('listexpander.css', 'listexpander.js')) ?>
<h1>Select Packages</h1>
<form method="post" action="?step=db-detail-check">
	<p>Below you can select extra packages to install along side WordPress.<br />Please note that you may install Plugins and Themes from your WordPress administration panel at any time.</p>

		<ul class="listexpander">
			<li>Plugins
				<ul>
					<li>WordPress Default Plugins
						<ul>
							<li> <input type="checkbox" name="plugin[akismet]" checked="checked" /> Akismet
								<ul>
									<li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur tincidunt turpis sed nunc. Pellentesque porttitor sagittis nisl. Etiam dictum purus vitae lorem. Ut ipsum orci, feugiat sit amet, luctus vitae, sodales quis, nibh. Sed vel mi. Ut vehicula nisi quis pede. Duis porta, lacus a pellentesque sodales, ligula sem tempor magna, ac mattis tortor mi vel odio.</li>
								</ul>
							</li>
							<li> <input type="checkbox" name="plugin[hello-dolly]" checked="checked" /> Hello Dolly
								<ul>
									<li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur tincidunt turpis sed nunc. Pellentesque porttitor sagittis nisl. Etiam dictum purus vitae lorem. Ut ipsum orci, feugiat sit amet, luctus vitae, sodales quis, nibh. Sed vel mi. Ut vehicula nisi quis pede. Duis porta, lacus a pellentesque sodales, ligula sem tempor magna, ac mattis tortor mi vel odio.</li>
								</ul>
							</li>														
						</ul>							
					</li>
					<li>Anti Spam
						<ul>
							<li> <input type="checkbox" name="plugin[test1]" /> Title 1
								<ul>
									<li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur tincidunt turpis sed nunc. Pellentesque porttitor sagittis nisl. Etiam dictum purus vitae lorem. Ut ipsum orci, feugiat sit amet, luctus vitae, sodales quis, nibh. Sed vel mi. Ut vehicula nisi quis pede. Duis porta, lacus a pellentesque sodales, ligula sem tempor magna, ac mattis tortor mi vel odio.</li>
								</ul>
							</li>											
						</ul>					
					</li>
				</ul>				
			</li>
			<li>Themes
				<ul>
					<li>WordPress Default Themes
						<ul>
							<li> <input type="checkbox" name="theme[default]" checked="checked" /> WordPress Default
								<ul>
									<li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur tincidunt turpis sed nunc. Pellentesque porttitor sagittis nisl. Etiam dictum purus vitae lorem. Ut ipsum orci, feugiat sit amet, luctus vitae, sodales quis, nibh. Sed vel mi. Ut vehicula nisi quis pede. Duis porta, lacus a pellentesque sodales, ligula sem tempor magna, ac mattis tortor mi vel odio.</li>
								</ul>
							</li>
							<li> <input type="checkbox" name="theme[classic]" checked="checked" /> WordPress 1.5 Classic
								<ul>
									<li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur tincidunt turpis sed nunc. Pellentesque porttitor sagittis nisl. Etiam dictum purus vitae lorem. Ut ipsum orci, feugiat sit amet, luctus vitae, sodales quis, nibh. Sed vel mi. Ut vehicula nisi quis pede. Duis porta, lacus a pellentesque sodales, ligula sem tempor magna, ac mattis tortor mi vel odio.</li>
								</ul>
							</li>													
						</ul>							
					</li>
					<li>Basic Examples
						<ul>
							<li>Title 1
								<ul>
									<li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur tincidunt turpis sed nunc. Pellentesque porttitor sagittis nisl. Etiam dictum purus vitae lorem. Ut ipsum orci, feugiat sit amet, luctus vitae, sodales quis, nibh. Sed vel mi. Ut vehicula nisi quis pede. Duis porta, lacus a pellentesque sodales, ligula sem tempor magna, ac mattis tortor mi vel odio.</li>
								</ul>
							</li>													
						</ul>					
					</li>
				</ul>				
			</li>	
			<li>Other Addons
				<ul>
					<li>Object Cache's
						<ul>
							<li> <input type="checkbox" name="addon[memcached]" /> MemCached
								<ul>
									<li>Lorem ipsum dolor sit amet, consectetuer adipiscing elit. Curabitur tincidunt turpis sed nunc. Pellentesque porttitor sagittis nisl. Etiam dictum purus vitae lorem. Ut ipsum orci, feugiat sit amet, luctus vitae, sodales quis, nibh. Sed vel mi. Ut vehicula nisi quis pede. Duis porta, lacus a pellentesque sodales, ligula sem tempor magna, ac mattis tortor mi vel odio.</li>
								</ul>
							</li>													
						</ul>							
					</li>
				</ul>				
			</li>					
		</ul>

	<p class="step"><input name="submit" type="submit" value="Install" class="button" /></p>
	<!-- <p><input type="checkbox" name="advanced-options" id="advanced-options-toggle" <?php if ( isset($_REQUEST['advanced-options']) ) echo ' checked="checked"' ?>  /><label for="advanced-options-toggle">Show Advanced Options</label></p> -->
</form>
<?php
the_footer();