<?php
/*
Plugin Name: Sermons Importer
Plugin URI: http://gospelpowered.com
Description: A plugin used to import sermons from Joomla's Preachit plugin
Version: 1.0
Author: Gospel Powered
Author URI: http://gospelpowered.com
License: MIT
*/

add_action( 'admin_menu', 'si_menu_item' );

function si_menu_item() {
	add_options_page( "Sermons Importer", "Sermons Importer", "manage_options", "sermons-importer", "si_init" );
}

function si_init() { ?>
	<div class="wrap">
		<h2>Sermons Importer</h2>
		<div id="postbox">
			<p>Select XML file to import:</p>
			<input type="file" name="data"><br><br>
			<div class="button button-primary">Import</div>
		</div>
	</div>
<?php }