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
	<?php

	import();
}

function import() {
	$xml = file_get_contents( "/home/nikola/public_html/pibackup_1041_2016-07-05.xml" );
	$xml = simplexml_load_string( $xml, 'SimpleXMLElement', LIBXML_NOCDATA );
	if ( $xml->attributes()->type{0} != "pibackup" ) {
		die( "Not a Preacher backup file" );
	}

	/*
	 * FOR REPLACING:
	 * [root] => get_home_path(); (/home/user/www/website...)
	 *
	 * XML STRUCTURE:
	 * root
	 * |-mptable (Media player table)
	 *   |- id                  (int)       ID of the media item (1)
	 *   |- playername          (string)    Video player name (JWplayer Video)
	 *   |- playertype          (int)       Video player type id? (2)
	 *   |- playercode          (string)    HTML embed code
	 *   |- playerscript        (string)    Location of video player script (media/preachit/mediaplayers/jwplayer.js)
	 *   |- playerurl           (string)    Location of some file of video player ([root]/media/preachit/mediaplayers/player.swf)
	 *   |- published           (bool)      Published|Unpublished|Archived|Deleted (1|0|-1|-2)
	 *   |- html5               (bool)      HTML5 player or not (1|0)
	 *   |- image               (bool)      ??? (0|1)
	 *   \- facebook            (bool)      ??? (0|1)
	 * |-sharetable (Share engines table)
	 *   |- id                  (int)       ID of the sharing engine (1)
	 *   |- name                (string)    Name of the sharing engine (Addthis)
	 *   |- code                (string)    HTML code to put where the sharer should be
	 *   |- published           (int)       Published|Unpublished|Archived|Deleted (1|0|-1|-2)
	 *   \- ordering            (int)       Ordering of the sharing engine
	 * |-filepathtable (File path table)
	 *   |- id                  (int)       ID of the ??? (1)
	 *   |- name                (string)    Name of the ???
	 *   |- server              (string)    URL of the ???
	 *   |- folder              (string)
	 *   |- published           (int)       Published|Unpublished|Archived|Deleted (1|0|-1|-2)
	 *   |- ftpport             (???)
	 *   |- type                (???)
	 *   |- aws_key             (string)    Amazon key
	 *   \- aws_secret          (string)    Amazon secret
	 * |-mintable (Ministry table)
	 *   |- id                  (int)       ID of the Ministry (1)
	 *   |- ministry_name       (string)    Ministry name (Morning Service)
	 *   |- ministry_alias      (string)    Ministry alias (morning-service)
	 *   |- image_folderlrg     (???)
	 *   |- ministry_image_lrg  (???)
	 *   |- published           (int)       Published|Unpublished|Archived|Deleted (1|0|-1|-2)
	 *   |- ordering            (int)       Ordering of the Ministry (1)
	 *   |- access              (int)       What kind of user can view that ministry? (Guest|Registered|Admin...)
	 *   |- language            (string)    Joomla language parameter for multi-language implementations (en-US)
	 *   |- metakey             (???)
	 *   \- metadesc            (???)
	 * |-sertable (Sermons series table)
	 *   |- id                  (int)       ID of the sermon (1)
	 *   |- series_name         (string)    Series name (Worship and Wealth)
	 *   |- series_alias        (string)    Series alias (worship-and-wealth)
	 *   |- image_folderlrg     (???)
	 *   |- series_image_lrg    (???)
	 *   |- published           (int)       Published|Unpublished|Archived|Deleted (1|0|-1|-2)
	 *   |- ministry            (string)    JSON string of assigned ministry ({"0":""})
	 *   |- ordering            (int)       Ordering of the sermon (1)
	 *   |- access              (int)       What kind of user can view that ministry? (Guest|Registered|Admin...)
	 *   |- language            (string)    Joomla language parameter for multi-language implementations (en-US)
	 *   |- introvideo          (???)
	 *   |- videofolder         (???)
	 *   |- videofolder_text    (???)
	 *   |- vheight             (int)       Video height (300)
	 *   |- vwidth              (int)       Video width (400)
	 *   |- metakey             (???)
	 *   \- metadesc            (???)
	 * |-teachtable (Teacher table - teacher info)
	 *   |- id                  (int)       ID of the teacher
	 *   |- teacher_name        (string)    Teacher name (John)
	 *   |- lastname            (string)    Teacher last_name (Bloggs)
	 *   |- teacher_alias       (string)    Teacher alias (john-bloggs)
	 *   |- image_folderlrg     (???)
	 *   |- teacher_image_lrg   (???)
	 *   |- published           (int)       Published|Unpublished|Archived|Deleted (1|0|-1|-2)
	 *   |- ordering            (int)       Ordering of the teacher (1)
	 *   |- teacher_view        (???)
	 *   |- language            (string)    Joomla language parameter for multi-language implementations (en-US)
	 *   |- metakey             (???)
	 *   \- metadesc            (???)
	 * |-mestable (Message table)
	 *   |- id                  (int)       ID of the message (1)
	 *   |- study_date          (string)    Date of the message (2014-11-09 02:02:00)
	 *   |- study_name          (string)    Name of the message (How Does Baptism Help Us)
	 *   |- study_alias         (string)    Alias of the message (how-does-baptism-help-us)
	 *   |- study_description   (string)    Description of the message (AM Service)
	 *   |- study_book          (int)       ID of the book (51)
	 *   |- ref_ch_beg          (int)       Chapter beginning (2)
	 *   |- ref_ch_end          (int)       Chapter end (2)
	 *   |- ref_vs_beg          (int)       Bible verse beginning (11)
	 *   |- ref_vs_end          (int)       Bible verse end (15)
	 *   |- study_book2         (int)       ID of the second study book (0)
	 * 	 |- ref_ch_beg2         (int)       Second chapter beginning (2)
	 *   |- ref_ch_end2         (int)       Second chapter end (2)
	 *   |- ref_vs_beg2         (int)       Second bible verse beginning (11)
	 *   |- ref_vs_end2         (int)       Second bible verse end (15)
	 *   |- series              (int)       ID of the series (87)
	 *   |- ministry            (string)    JSON string of assigned ministry(ies) ({"0":"1"})
	 *   |- teacher             (string)    JSON string of assigned teacher(s) ({"0":"7"})
	 *   |- series_text         (string)
	 *   |- dur_hrs             (int)       Duration - hours (1)
	 *   |- dur_mins            (int)       Duration - minutes (23)
	 *   |- dur_secs            (int)       Duration - seconds (48)
	 *   |- video               (???){obj}
	 *   |- video_type          (???){obj}
	 *   |- video_download      (bool)      Allow video download (0|1)
	 *   |- audio               (???){obj}
	 *   |- audio_type          (???){obj}
	 *   |- audio_download      (bool)      Allow audio download (0|1)
	 *   |- audio_link          (string)    URL to the audio stream (http://example.com/audio.mp3)
	 *   |- published           (int)       Published|Unpublished|Archived|Deleted (1|0|-1|-2)
	 *   |- comments            (bool)      Allow comments (0|1)
	 *   |- study_text          (???){obj}
	 *   |- text                (???){obj}
	 *   |- studylist           (???)
	 *   |- hits                (int)       Number of hits (132)
	 *   |- downloads           (int)       Number of downloads (46)
	 *   |- audio_folder        (???)
	 *   |- video_folder        (???)
	 *   |- publish_up          (string)    Publish up ({date})
	 *   |- publish_down        (string)    Publish down ({date})
	 *   |- podpublish_up       (string)    ??? (date)
	 *   |- podpublish_down     (string)    ??? (date)
	 *   |- image_folderlrg     (???)
	 *   |- notes_folder        (???)
	 *   |- notes               (???)
	 *   |- slides_folder       (???)
	 *   |- slides              (???)
	 *   |- add_downloadvid     (???)
	 *   |- downloadvid_folder  (???)
	 *   |- add_downloadaud     (???)
	 *   |- downloadaud_folder  (???)
	 *   |- access              (int)       What kind of user can view that message? (Guest|Registered|Admin...)
	 *   |- minaccess           (???)
	 *   |- saccess             (???)
	 *   |- language            (string)    Joomla language parameter for multi-language implementations (en-US)
	 *   |- audpurchase         (bool)      Allow audio purchase (1|0)
	 *   |- audpurchase_folder  (string)    Audio purchase folder location (media/audio/test.mp3)
	 *   |- vidpurchase         (bool)      Allow video purchase (1|0)
	 *   |- vidpurchase_folder  (string)    Video purchase folder location (media/audio/test.mp4)
	 *   |- audiofs             (int)       Audio file size (545433)
	 *   |- adaudiofs           (int)       Additional audio file size (545433)
	 *   |- videofs             (int)       Video file size (545433)
	 *   |- advideofs           (int)       Additional video file size (545433)
	 *   |- notesfs             (int)       Notes file size (545433)
	 *   |- slidesfs            (int)       Slides file size (545433)
	 *   |- audioprice          (???)       Price of the audio (???)
	 *   |- videoprice          (???)       Price of the video (???)
	 *   |- metakey             (???)
	 *   |- metadesc            (???)
	 *   \- extrafields         (???)
	 * |-podtable (Podcast table)
	 *   |- id                  (int)       ID of the podcast (1)
	 *   |- name                (string)    Name of the podcast (My awesome podcast)
	 *   |- image               (string)    URL of podcast image (http://example.com/image.jpg)
	 *   |- records             (???)
	 *   |- published           (int)       Published|Unpublished|Archived|Deleted (1|0|-1|-2)
	 *   |- description         (string)    Podcast description
	 *   |- imagehgt            (int)       Image height (300)
	 *   |- imagewth            (int)       Image width (400)
	 *   |- author              (string)    Podcast author (John Bloggs)
	 *   |- search              (string)    Tags?
	 *   |- filename            (string)
	 *   |- menuitem            (???)
	 *   |- language            (string)    Joomla language parameter for multi-language implementations (en-US)
	 *   |- editor              (string)    Podcast editor (Jane Bloggs)
	 *   |- email               (string)    Editor's email (jane@bloggs.com)
	 *   |- ordering            (int)       Ordering of the podcast (1)
	 *   |- itunestitle         (string)    Title on the iTunes ([title])
	 *   |- itunessub           (string)    ??? ([description])
	 *   |- itunesdesc          (string)    Description on the iTunes ([description], [teacher], [scripture])
	 *   |- series              (int)       ID of the series (1)
	 *   |- series_list         (string)    JSON of? ({"0":""})
	 *   |- ministry            (int)       ID of the ministry (1)
	 *   |- ministry_list       (string)    JSON of? ({"0":""})
	 *   |- teacher             (int)       ID of the teacher (1)
	 *   |- teacher_list        (string)    JSON of? ({"0":""})
	 *   |- media               (???)
	 *   |- media_list          (string)    JSON of? ({"0":"audio"})
	 *   \- languagesel         (???)
	 */

	$series = array();

	echo "<xmp>";
	foreach ( $xml->sertable as $sermon ) {
		$series[ (int) $sermon->id ]["name"]    = (string) $sermon->series_name;
		$series[ (int) $sermon->id ]["alias"]   = (string) $sermon->series_alias;
		$series[ (int) $sermon->id ]["sermons"] = array();
	}

	foreach ( $xml->mestable as $message ) {
		$series[ (int) $message->series ]["sermons"][ (int) $message->id ]["title"]             = (string) $message->study_name;
		$series[ (int) $message->series ]["sermons"][ (int) $message->id ]["teacher"]           = (string) $message->teacher;
		$series[ (int) $message->series ]["sermons"][ (int) $message->id ]["study_description"] = (string) $message->study_description;
		$series[ (int) $message->series ]["sermons"][ (int) $message->id ]["study_text"]        = (string) $message->study_text;
		$series[ (int) $message->series ]["sermons"][ (int) $message->id ]["text"]              = (string) $message->text;
		$series[ (int) $message->series ]["sermons"][ (int) $message->id ]["ministry"]          = (string) $message->ministry;
		$series[ (int) $message->series ]["sermons"][ (int) $message->id ]["date"]              = (string) $message->publish_up;
		// TODO
		// Chapter start/end
		// Bible verse start/end
		// Book
	}
	print_r( $series );
	echo "</xmp>";

}