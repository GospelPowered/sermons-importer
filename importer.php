<?php
/*
Plugin Name: Sermons Importer
Plugin URI: http://gospelpowered.com
Description: A plugin used to import sermons from Joomla's Preachit plugin
Version: 1.0.4
Author: Gospel Powered
Author URI: http://gospelpowered.com
License: MIT
*/

add_action( 'admin_menu', 'si_menu_item' );

function si_menu_item() {
	add_management_page( "Sermons Importer", "Sermons Importer", "manage_options", "sermons-importer", "si_init" );
}

function si_init() { ?>
    <div class="wrap">
        <h2>Sermons Importer</h2>
        <form id="postbox" method="post" enctype="multipart/form-data">
            <p>Select XML file to import:</p>
            <input type="file" name="data"><br><br>
            <input class="button button-primary" type="submit" value="Import">
        </form>
    </div>
	<?php

	if ( empty( $_FILES ) ) {
		return;
	}

	if ( is_uploaded_file( $_FILES['data']['tmp_name'] ) && $_FILES['data']['error'] == 0 ) {
		import( $_FILES["data"]["tmp_name"] );
	} else {
		if ( $_FILES['data']['error'] != 0 ) {
			echo "Problem with file upload. Error message: " . $_FILES['data']['error'];
		}
	}
}

function import( $file ) {
	$xml = file_get_contents( $file );
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

	$series = array(
		'series'   => array(),
		'teacher'  => array(),
		'ministry' => array(),
	);
	global $wpdb;

	foreach ( $xml->sertable as $sermon ) {
		$series["series"][ (int) $sermon->id ]["name"]  = ! empty( (string) $sermon->series_name ) ? (string) $sermon->series_name : (string) $sermon->name;
		$series["series"][ (int) $sermon->id ]["alias"] = ! empty( (string) $sermon->series_alias ) ? (string) $sermon->series_alias : (string) $sermon->alias;

		// WORKS
		$wpdb->insert( $wpdb->prefix . 'terms', array(
			'name' => $series["series"][ (int) $sermon->id ]["name"],
			'slug' => $series["series"][ (int) $sermon->id ]["alias"]
		), array(
			'%s',
			'%s'
		) );

		$series["series"][ (int) $sermon->id ]["newid"] = (int) $wpdb->insert_id;

		$wpdb->insert( $wpdb->prefix . 'term_taxonomy', array(
			'term_taxonomy_id' => (int) $wpdb->insert_id,
			'term_id'          => (int) $wpdb->insert_id,
			'taxonomy'         => 'wpfc_sermon_series',
			'description'      => '',
			'parent'           => 0,
			'count'            => 0
		), array(
			'%d',
			'%d',
			'%s',
			'%s',
			'%d',
			'%d'
		) );
	}

	foreach ( $xml->teachtable as $teacher ) {
		$series["teacher"][ (int) $teacher->id ]["name"]     = ! empty( (string) $teacher->teacher_name ) ? (string) $teacher->teacher_name : (string) $teacher->name;
		$series["teacher"][ (int) $teacher->id ]["lastname"] = (string) $teacher->lastname;
		$series["teacher"][ (int) $teacher->id ]["alias"]    = ! empty( (string) $teacher->teacher_alias ) ? (string) $teacher->teacher_alias : (string) $teacher->alias;
		$series["teacher"][ (int) $teacher->id ]["image"]    = (string) $teacher->teacher_image_lrg;

		// WORKS
		$wpdb->insert( $wpdb->prefix . 'terms', array(
			'name' => $series["teacher"][ (int) $teacher->id ]["name"] . ' ' . (string) $teacher->lastname,
			'slug' => $series["teacher"][ (int) $teacher->id ]["alias"]
		), array(
			'%s',
			'%s'
		) );

		$series["teacher"][ (int) $teacher->id ]["newid"] = (int) $wpdb->insert_id;

		$wpdb->insert( $wpdb->prefix . 'term_taxonomy', array(
			'term_taxonomy_id' => (int) $wpdb->insert_id,
			'term_id'          => (int) $wpdb->insert_id,
			'taxonomy'         => 'wpfc_preacher',
			'description'      => '',
			'parent'           => 0,
			'count'            => 0
		), array(
			'%d',
			'%d',
			'%s',
			'%s',
			'%d',
			'%d'
		) );
	}

	foreach ( $xml->mintable as $ministry ) {
		$series["ministry"][ (int) $ministry->id ]["name"]  = ! empty( (string) $ministry->ministry_name ) ? (string) $ministry->ministry_name : (string) $ministry->name;
		$series["ministry"][ (int) $ministry->id ]["alias"] = ! empty( (string) $ministry->ministry_alias ) ? (string) $ministry->ministry_alias : (string) $ministry->alias;

		// WORKS
		$wpdb->insert( $wpdb->prefix . 'terms', array(
			'name' => $series["ministry"][ (int) $ministry->id ]["name"],
			'slug' => $series["ministry"][ (int) $ministry->id ]["alias"]
		), array(
			'%s',
			'%s'
		) );

		$series["ministry"][ (int) $ministry->id ]["newid"] = (int) $wpdb->insert_id;

		$wpdb->insert( $wpdb->prefix . 'term_taxonomy', array(
			'term_taxonomy_id' => (int) $wpdb->insert_id,
			'term_id'          => (int) $wpdb->insert_id,
			'taxonomy'         => 'wpfc_service_type',
			'description'      => '',
			'parent'           => 0,
			'count'            => 0
		), array(
			'%d',
			'%d',
			'%s',
			'%s',
			'%d',
			'%d'
		) );
	}

	$series['study_book'] = array(
		array(
			'name' => 'Genesis',
		),
		array(
			'name' => 'Exodus',
		),
		array(
			'name' => 'Leviticus',
		),
		array(
			'name' => 'Numbers',
		),
		array(
			'name' => 'Deuteronomy',
		),
		array(
			'name' => 'Joshua',
		),
		array(
			'name' => 'Judges',
		),
		array(
			'name' => 'Ruth',
		),
		array(
			'name' => '1 Samuel',
		),
		array(
			'name' => '2 Samuel',
		),
		array(
			'name' => '1 Kings',
		),
		array(
			'name' => '2 Kings',
		),
		array(
			'name' => '1 Chronicles',
		),
		array(
			'name' => '2 Chronicles',
		),
		array(
			'name' => 'Ezra',
		),
		array(
			'name' => 'Nehemiah',
		),
		array(
			'name' => 'Esther',
		),
		array(
			'name' => 'Job',
		),
		array(
			'name' => 'Psalm',
		),
		array(
			'name' => 'Proverbs',
		),
		array(
			'name' => 'Ecclesiastes',
		),
		array(
			'name' => 'Song of Songs',
		),
		array(
			'name' => 'Isaiah',
		),
		array(
			'name' => 'Jeremiah',
		),
		array(
			'name' => 'Lamentations',
		),
		array(
			'name' => 'Ezekiel',
		),
		array(
			'name' => 'Daniel',
		),
		array(
			'name' => 'Hosea',
		),
		array(
			'name' => 'Joel',
		),
		array(
			'name' => 'Amos',
		),
		array(
			'name' => 'Obadiah',
		),
		array(
			'name' => 'Jonah',
		),
		array(
			'name' => 'Micah',
		),
		array(
			'name' => 'Nahum',
		),
		array(
			'name' => 'Habakkuk',
		),
		array(
			'name' => 'Zephaniah',
		),
		array(
			'name' => 'Haggai',
		),
		array(
			'name' => 'Zechariah',
		),
		array(
			'name' => 'Malachi',
		),
		array(
			'name' => 'Matthew',
		),
		array(
			'name' => 'Mark',
		),
		array(
			'name' => 'Luke',
		),
		array(
			'name' => 'John',
		),
		array(
			'name' => 'Acts',
		),
		array(
			'name' => 'Romans',
		),
		array(
			'name' => '1 Corinthians',
		),
		array(
			'name' => '2 Corinthians',
		),
		array(
			'name' => 'Galatians',
		),
		array(
			'name' => 'Ephesians',
		),
		array(
			'name' => 'Philippians',
		),
		array(
			'name' => 'Colossians',
		),
		array(
			'name' => '1 Thessalonians',
		),
		array(
			'name' => '2 Thessalonians',
		),
		array(
			'name' => '1 Timothy',
		),
		array(
			'name' => '2 Timothy',
		),
		array(
			'name' => 'Titus',
		),
		array(
			'name' => 'Philemon',
		),
		array(
			'name' => 'Hebrews',
		),
		array(
			'name' => 'James',
		),
		array(
			'name' => '1 Peter',
		),
		array(
			'name' => '2 Peter',
		),
		array(
			'name' => '1 John',
		),
		array(
			'name' => '2 John',
		),
		array(
			'name' => '3 John',
		),
		array(
			'name' => 'Jude',
		),
		array(
			'name' => 'Revelation',
		),
		array(
			'name' => 'Topical',
		),
	);

	foreach ( $series['study_book'] as $key => $book ) {

		$wpdb->insert( $wpdb->prefix . 'terms', array(
			'name' => $book['name'],
			'slug' => strtolower( str_replace( ' ', '-', $book['name'] ) ),
		), array(
			'%s',
			'%s'
		) );

		$series['study_book'][ $key ]['newid'] = (int) $wpdb->insert_id;

		$wpdb->insert( $wpdb->prefix . 'term_taxonomy', array(
			'term_taxonomy_id' => (int) $wpdb->insert_id,
			'term_id'          => (int) $wpdb->insert_id,
			'taxonomy'         => 'wpfc_bible_book',
			'description'      => '',
			'parent'           => 0,
			'count'            => 0
		), array(
			'%d',
			'%d',
			'%s',
			'%s',
			'%d',
			'%d'
		) );
	}

	foreach ( $xml->mestable as $message ) {
		$series["message"][ (int) $message->id ]["title"]             = ! empty( (string) $message->study_name ) ? (string) $message->study_name : (string) $message->name;
		$series["message"][ (int) $message->id ]["series"]            = (string) $message->series;
		$series["message"][ (int) $message->id ]["teacher"]           = (string) $message->teacher;
		$series["message"][ (int) $message->id ]["study_description"] = ! empty( (string) $message->study_description ) ? (string) $message->study_description : (string) $message->description;
		$series["message"][ (int) $message->id ]["study_text"]        = ! empty( (string) $message->study_text ) ? (string) $message->study_text : '';
		$series["message"][ (int) $message->id ]["study_alias"]       = ! empty( (string) $message->study_alias ) ? (string) $message->study_alias : (string) $message->alias;
		$series["message"][ (int) $message->id ]["text"]              = (string) $message->text;
		$series["message"][ (int) $message->id ]["ministry"]          = (string) $message->ministry;
		$series["message"][ (int) $message->id ]["date"]              = ! empty( (string) $message->study_date ) ? (string) $message->study_date : (string) $message->date;
		$series["message"][ (int) $message->id ]["study_book"]        = (string) $message->study_book;
		$series["message"][ (int) $message->id ]["ref_ch_beg"]        = (string) $message->ref_ch_beg;
		$series["message"][ (int) $message->id ]["ref_ch_end"]        = (string) $message->ref_ch_end;
		$series["message"][ (int) $message->id ]["ref_vs_beg"]        = (string) $message->ref_vs_beg;
		$series["message"][ (int) $message->id ]["ref_vs_end"]        = (string) $message->ref_vs_end;
		$series["message"][ (int) $message->id ]["audio_link"]        = (string) $message->audio_link;

		$message->series     = '{"0": "' . $message->series . '"}';
		$message->study_book = '{"0": "' . $message->study_book . '"}';

		// get the latest post id
		$post_id = $wpdb->get_var( 'SELECT `ID` FROM ' . $wpdb->prefix . 'posts ORDER BY `ID` DESC LIMIT 1' );
		$post_id = $post_id === null ? 1 : (int) $post_id + 1;

		$wpdb->insert( $wpdb->prefix . 'posts', array(
			'post_author'           => get_current_user_id(),
			'post_date'             => $series["message"][ (int) $message->id ]["date"],
			'post_date_gmt'         => $series["message"][ (int) $message->id ]["date"],
			'post_content'          => '',
			'post_title'            => $series["message"][ (int) $message->id ]["title"],
			'post_excerpt'          => '',
			'post_status'           => ( (string) $message->published == 1 ) ? 'publish' : 'draft',
			'comment_status'        => ( (string) $message->comments == 1 ) ? 'open' : 'closed',
			'ping_status'           => 'closed',
			'post_password'         => '',
			'post_name'             => $series["message"][ (int) $message->id ]["study_alias"],
			'to_ping'               => '',
			'pinged'                => '',
			'post_modified'         => $series["message"][ (int) $message->id ]["date"],
			'post_modified_gmt'     => $series["message"][ (int) $message->id ]["date"],
			'post_content_filtered' => '',
			'post_parent'           => 0,
			'guid'                  => get_site_url() . '/?post_type=wpfc_sermon&#038;p=' . $post_id,
			'menu_order'            => 0,
			'post_type'             => 'wpfc_sermon',
			'post_mime_type'        => '',
			'comment_count'         => 0
		), array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%d',
			'%s',
			'%s',
			'%d',
		) );

		$postdb_id = $wpdb->insert_id;

		foreach (
			array(
				'sermon_date'              => strtotime( $series["message"][ (int) $message->id ]["date"] ),
				'wpfc_service_type_select' => 'a:0:{}',
				'bible_passage'            => (string) $series['study_book'][ json_decode( $message->study_book )->{'0'} == 0 ? json_decode( $message->study_book )->{'0'} : json_decode( $message->study_book )->{'0'} - 1 ]["name"] . ' ' . (string) $message->ref_ch_beg . ':' . (string) $message->ref_vs_beg . '-' . (string) $message->ref_vs_end . ( (string) $message->ref_ch_beg != (string) $message->ref_ch_end ? ':' . (string) $message->ref_ch_end : '' ),
				'sermon_description'       => $series["message"][ (int) $message->id ]["study_description"],
				'sermon_audio'             => (string) $message->audio_link
			) as $key => $value
		) {
			$wpdb->insert( $wpdb->prefix . 'postmeta', array(
				'post_id'    => $postdb_id,
				'meta_key'   => $key,
				'meta_value' => $value
			), array(
				'%d',
				'%s',
				( $key == 'sermon date' ? '%d' : '%s' )
			) );
		}

		foreach ( array( 'teacher', 'series', 'study_book', 'ministry' ) as $data ) {
			foreach ( json_decode( $message->$data ) as $key => $data_id ) {
				if ( $data_id == '' ) {
					continue;
				}
				$wpdb->insert( $wpdb->prefix . 'term_relationships', array(
					'object_id'        => $postdb_id,
					'term_taxonomy_id' => $series[ $data ][ $data === 'study_book' && $data_id != 0 ? $data_id - 1 : $data_id ]['newid'],
					'term_order'       => $key
				), array(
					'%d',
					'%d',
					'%d'
				) );

				$count = $wpdb->get_var( '
						SELECT `count`+1 
						FROM `' . $wpdb->prefix . 'term_taxonomy`
						WHERE `term_id` = ' . $series[ $data ][ $data_id ]['newid']
				);

				$wpdb->update( $wpdb->prefix . 'term_taxonomy', array(
					'count' => $count
				), array(
					'term_id' => $series[ $data ][ $data_id ]['newid']
				), array(
					'%d'
				), array(
					'%d'
				) );

				$wpdb->flush();

			}
		}
	}

	echo "done.";
}
