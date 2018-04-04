<?php
/**
 * Plugin Name: Sermons Importer
 * Plugin URI: http://gospelpowered.com
 * Description: Tool for importing Joomla's Preachit plugin data into Sermon Manager
 * Version: 1.1.0
 * Author: Gospel Powered
 * Author URI: http://gospelpowered.com
 * License: MIT
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
		si_import( $_FILES['data']['tmp_name'] );
	} else {
		if ( $_FILES['data']['error'] != 0 ) {
			echo 'Problem with file upload. Error message: ' . $_FILES['data']['error'];
		}
	}
}

function si_import( $file ) {
	$xml = file_get_contents( $file );
	$xml = simplexml_load_string( $xml, 'SimpleXMLElement', LIBXML_NOCDATA );
	if ( $xml->attributes()->type{0} != "pibackup" ) {
		die( 'Not a Preachit export file.' );
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

	$mapping = array(
		'series'   => array(),
		'teacher'  => array(),
		'ministry' => array(),
	);
	global $wpdb;

	// import series
	foreach ( $xml->sertable as $ser ) {
		$mapping["series"][ (int) $ser->id ]["name"]  = ! empty( (string) $ser->series_name ) ? (string) $ser->series_name : (string) $ser->name;
		$mapping["series"][ (int) $ser->id ]["alias"] = ! empty( (string) $ser->series_alias ) ? (string) $ser->series_alias : (string) $ser->alias;

		if ( ! $id = term_exists( $mapping["series"][ (int) $ser->id ]["name"], 'wpfc_sermon_series' ) ) {
			$id = wp_insert_term(
				$mapping["series"][ (int) $ser->id ]["name"],
				'wpfc_sermon_series'
			);

			if ( $id instanceof WP_Error ) {
				var_dump( $id );
				exit;
			}

			$id = $id['term_id'];
		} else {
			$id = intval( $id['term_id'] );
		}

		$mapping["series"][ (int) $ser->id ]["newid"] = (int) $id;
	}

	// import preachers
	foreach ( $xml->teachtable as $teacher ) {
		$mapping["teacher"][ (int) $teacher->id ]["name"]     = ! empty( (string) $teacher->teacher_name ) ? (string) $teacher->teacher_name : (string) $teacher->name;
		$mapping["teacher"][ (int) $teacher->id ]["lastname"] = (string) $teacher->lastname;
		$mapping["teacher"][ (int) $teacher->id ]["alias"]    = ! empty( (string) $teacher->teacher_alias ) ? (string) $teacher->teacher_alias : (string) $teacher->alias;
		$mapping["teacher"][ (int) $teacher->id ]["image"]    = (string) $teacher->teacher_image_lrg;

		if ( ! $id = term_exists( $mapping["teacher"][ (int) $teacher->id ]["name"] . ' ' . (string) $teacher->lastname, 'wpfc_preacher' ) ) {
			$id = wp_insert_term(
				$mapping["teacher"][ (int) $teacher->id ]["name"] . ' ' . (string) $teacher->lastname,
				'wpfc_preacher'
			);

			if ( $id instanceof WP_Error ) {
				var_dump( $id );
				exit;
			}

			$id = $id['term_id'];
		} else {
			$id = intval( $id['term_id'] );
		}

		$mapping["teacher"][ (int) $teacher->id ]["newid"] = (int) $id;
	}

	// import ministries (service types)
	foreach ( $xml->mintable as $ministry ) {
		$mapping["ministry"][ (int) $ministry->id ]["name"]  = ! empty( (string) $ministry->ministry_name ) ? (string) $ministry->ministry_name : (string) $ministry->name;
		$mapping["ministry"][ (int) $ministry->id ]["alias"] = ! empty( (string) $ministry->ministry_alias ) ? (string) $ministry->ministry_alias : (string) $ministry->alias;

		if ( ! $id = term_exists( $mapping["ministry"][ (int) $ministry->id ]["name"], 'wpfc_service_type' ) ) {
			$id = wp_insert_term(
				$mapping["ministry"][ (int) $ministry->id ]["name"],
				'wpfc_service_type'
			);

			if ( $id instanceof WP_Error ) {
				var_dump( $id );
				exit;
			}

			$id = $id['term_id'];
		} else {
			$id = intval( $id['term_id'] );
		}

		$mapping["ministry"][ (int) $ministry->id ]["newid"] = (int) $id;
	}

	// recreate Preachit book ordering
	$mapping['study_book'] = array(
		array( // books start at 1
			'name' => null
		),
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

	// import books
	foreach ( $mapping['study_book'] as $key => $book ) {
		if ( $book['name'] === null ) { // start from 1, not 0
			continue;
		}

		if ( ! $id = term_exists( $book['name'], 'wpfc_bible_book' ) ) {
			$id = wp_insert_term(
				$book['name'],
				'wpfc_bible_book'
			);

			if ( $id instanceof WP_Error ) {
				var_dump( $id );
				exit;
			}

			$id = $id['term_id'];
		} else {
			$id = intval( $id['term_id'] );
		}

		$mapping['study_book'][ $key ]['newid'] = (int) $id;
	}

	// import sermons
	foreach ( $xml->mestable as $message ) {
		$mapping["message"][ (int) $message->id ]["title"]             = ! empty( (string) $message->study_name ) ? (string) $message->study_name : (string) $message->name;
		$mapping["message"][ (int) $message->id ]["series"]            = (string) $message->series;
		$mapping["message"][ (int) $message->id ]["teacher"]           = (string) $message->teacher;
		$mapping["message"][ (int) $message->id ]["study_description"] = ! empty( (string) $message->study_description ) ? (string) $message->study_description : (string) $message->description;
		$mapping["message"][ (int) $message->id ]["study_text"]        = ! empty( (string) $message->study_text ) ? (string) $message->study_text : '';
		$mapping["message"][ (int) $message->id ]["study_alias"]       = ! empty( (string) $message->study_alias ) ? (string) $message->study_alias : (string) $message->alias;
		$mapping["message"][ (int) $message->id ]["text"]              = (string) $message->text;
		$mapping["message"][ (int) $message->id ]["ministry"]          = (string) $message->ministry;
		$mapping["message"][ (int) $message->id ]["date"]              = ! empty( (string) $message->study_date ) ? (string) $message->study_date : (string) $message->date;
		$mapping["message"][ (int) $message->id ]["study_book"]        = (string) $message->study_book;
		$mapping["message"][ (int) $message->id ]["ref_ch_beg"]        = (string) $message->ref_ch_beg;
		$mapping["message"][ (int) $message->id ]["ref_ch_end"]        = (string) $message->ref_ch_end;
		$mapping["message"][ (int) $message->id ]["ref_vs_beg"]        = (string) $message->ref_vs_beg;
		$mapping["message"][ (int) $message->id ]["ref_vs_end"]        = (string) $message->ref_vs_end;
		$mapping["message"][ (int) $message->id ]["audio_link"]        = (string) $message->audio_link;

		if ( ! $id = post_exists( $mapping["message"][ (int) $message->id ]["title"], '', $mapping["message"][ (int) $message->id ]["date"] ) ) {
			$id = wp_insert_post( array(
				'post_date'         => $mapping["message"][ (int) $message->id ]["date"],
				'post_content'      => '&nbsp;',
				'post_title'        => $mapping["message"][ (int) $message->id ]["title"],
				'post_status'       => ( (string) $message->published == 1 ) ? 'publish' : 'draft',
				'post_type'         => 'wpfc_sermon',
				'comment_status'    => ( (string) $message->comments == 1 ) ? 'open' : 'closed',
				'ping_status'       => 'closed',
				'post_modified'     => $mapping["message"][ (int) $message->id ]["date"],
				'post_modified_gmt' => $mapping["message"][ (int) $message->id ]["date"],
			) );
		}

		$message->series     = '{"0": "' . $message->series . '"}';
		$message->study_book = '{"0": "' . $message->study_book . '"}';

		foreach (
			array(
				'sermon_date'              => strtotime( $mapping["message"][ (int) $message->id ]["date"] ),
				'wpfc_service_type_select' => 'a:0:{}',
				'bible_passage'            => (string) $mapping['study_book'][ json_decode( $message->study_book )->{'0'} ]["name"] . ' ' . (string) $message->ref_ch_beg . ':' . (string) $message->ref_vs_beg . '-' . (string) $message->ref_vs_end . ( (string) $message->ref_ch_beg != (string) $message->ref_ch_end ? ':' . (string) $message->ref_ch_end : '' ),
				'sermon_description'       => $mapping["message"][ (int) $message->id ]["study_description"],
				'sermon_audio'             => (string) $message->audio_link,
				'Views'                    => (int) $message->hits,
			) as $key => $value
		) {
			update_post_meta( $id, $key, $value );
		}

		foreach ( array( 'teacher', 'series', 'study_book', 'ministry' ) as $data ) {
			foreach ( json_decode( $message->$data ) as $data_id ) {
				if ( ! $data_id ) {
					continue;
				}

				switch ( $data ) {
					case 'teacher':
						$taxonomy = 'wpfc_preacher';
						break;
					case 'series':
						$taxonomy = 'wpfc_sermon_series';
						break;
					case 'study_book':
						$taxonomy = 'wpfc_bible_book';
						break;
					case 'ministry':
						$taxonomy = 'wpfc_service_type';
						break;
					default:
						$taxonomy = null;
				}

				wp_set_object_terms( $id, $mapping[ $data ][ $data_id ]['newid'], $taxonomy );
			}
		}
	}

	echo "done.";
}
