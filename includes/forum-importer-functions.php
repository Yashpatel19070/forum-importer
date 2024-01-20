<?php
/**
 * Core functions and definations.
 *
 * @package    Forum_Importer
 * @subpackage Forum_Importer/includes
 */

// Check if function exists.
if ( ! function_exists( 'sonopath_connect_database' ) ) {
	/**
	 * Function for fetch forums from drupal database.
	 */
	function sonopath_connect_database() {
		$dbhost = 'localhost';
		$dbname = 'woocomme_drupal';
		$dbuser = 'root';
		$dbpwd  = 'root';
		$conn   = new mysqli( $dbhost, $dbuser, $dbpwd, $dbname ); // phpcs:ignore

		if ( $conn->connect_error ) {
			return $conn->connect_error;
		}

		return $conn;
	}
}


// Check if function exists.
if ( ! function_exists( 'sonopath_fetch_forums' ) ) {
	/**
	 * Function for fetch forums from drupal database.
	 */
	function sonopath_fetch_forums() {
		global $wpdb;
		$conn        = sonopath_connect_database();
		$query       = "SELECT n.*,nr.body,nr.teaser FROM node as n,node_revisions as nr WHERE n.type LIKE 'blog' and n.nid=nr.nid and nr.uid!=0 GROUP BY n.nid";
		$fetch_forum = $conn->query( $query );
		$forums      = array();
		if ( $fetch_forum->num_rows ) {
			while ( $rows = $fetch_forum->fetch_assoc() ) { // phpcs:ignore
				$forums[] = $rows;
			} 
		}
		// debug($forums);
		return $forums;
	}
}

/**
 * Check, if the function exists.
 */
if ( ! function_exists( 'fi_write_import_log' ) ) {
	/**
	 * Write log to the log file.
	 *
	 * @param string  $message Holds the log message.
	 * @param string  $log_file Log file path.
	 * @param boolean $include_date_time Include date time in the message.
	 * @return void
	 */
	function fi_write_import_log( $message = '', $log_file, $include_date_time = false ) {
		global $wp_filesystem;

		// Return, if the message is empty.
		if ( empty( $message ) ) {
			return;
		}

		require_once ABSPATH . '/wp-admin/includes/file.php';
		WP_Filesystem();

		// Check if the file is created.
		if ( ! $wp_filesystem->exists( $log_file ) ) {
			$wp_filesystem->put_contents( $log_file, '', FS_CHMOD_FILE ); // Create the file.
		}

		// Fetch the old content.
		$content  = $wp_filesystem->get_contents( $log_file );
		$content .= ( $include_date_time ) ? "\n" . fi_get_current_datetime( 'Y-m-d H:i:s' ) . ' :: ' . $message : "\n" . $message;

		// Put the updated content.
		$wp_filesystem->put_contents(
			$log_file,
			$content,
			FS_CHMOD_FILE // predefined mode settings for WP files.
		);
	}
}

/**
 * Check, if the function exists.
 */
if ( ! function_exists( 'fi_get_current_datetime' ) ) {
	/**
	 * Return the current date according to local time.
	 *
	 * @param string $format Holds the format string.
	 * @return string
	 */
	function fi_get_current_datetime( $format = 'Y-m-d' ) {
		$timezone_format = _x( $format, 'timezone date format' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- Format is a dynamic value.

		return date_i18n( $timezone_format );
	}
}

/**
 * Check, if the function exists.
 */
if ( ! function_exists( 'fi_forum_exists' ) ) {
	/**
	 * Check if the forum exists by forum title received from drupal database.
	 *
	 * @param string $forum_title forum title received from drupal database.
	 * @return boolean|int
	 */
	function fi_forum_exists( $forum_title ) {
		global $wpdb;
		if ( strpos( $forum_title, "'") !== false ) {
			$forum_title = str_replace( "'", "\'", $forum_title );
		}
		$forum_id_query = "SELECT ID FROM " .$wpdb->prefix. "posts WHERE post_title LIKE '" . $forum_title . "' AND post_status LIKE 'publish' AND post_type LIKE 'post'";
		$forum_id       = $wpdb->get_results( $forum_id_query, ARRAY_A ); // phpcs:ignore
		return ( ! empty( $forum_id[0]['ID'] ) ) ? (int) $forum_id[0]['ID'] : false;
	}
}

/**
 * Check, if the function exists.
 */
if ( ! function_exists( 'fi_create_forum' ) ) {
	/**
	 * Create new forum and insert that into the database.
	 *
	 * @param array $forum_data Details fetched from drupal.
	 * @param int   $forum_id ID of selected forum to import topic.
	 * @return int
	 */
	function fi_create_forum( $forum_data, $forum_id ) {
		$conn = sonopath_connect_database();
		$fetch_user  = "SELECT * FROM users WHERE uid = {$forum_data['uid']}";
		$user        = $conn->query( $fetch_user );
		$wp_user_id = get_current_user_id();
		if ( $user->num_rows ) {
			$user_data   = $user->fetch_assoc();
			$get_wp_user = get_user_by( 'email', $user_data['mail'] );
			$wp_user_id  = ! empty( $get_wp_user ) ? $get_wp_user->ID : get_current_user_id();
		}
		$content = extract_image_tags_from_string( $forum_data['body'] );
		// $content = $forum_data['body'];
		$post_arr = array(
			'post_title'    => $forum_data['title'],
			'post_content'  => $content,
			'post_status'   => 'publish',
			'post_type'     => 'post',
			'post_author'   => $wp_user_id,
			// 'post_parent'   => $forum_id,
			'post_date'     => gmdate( 'Y-m-d H:i:s', $forum_data['created'] ),
			// 'meta_input'    => array(
			// 	'blog_teaser'   => $forum_data['teaser'],
			// 	'_bbp_forum_id' => $forum_id,
			// ),
		);
		$post_id = wp_insert_post( $post_arr );
		update_post_meta( $post_id, '_bbp_forum_id', $post_id );
		update_post_meta( $forum_id, '_bbp_last_topic_id', $post_id );
		$topic_count = get_post_meta( $forum_id, '_bbp_topic_count', true );
		$topic_count = $topic_count + 1;
		update_post_meta( $forum_id, '_bbp_topic_count', $topic_count );
		update_post_meta( $forum_id, '_bbp_total_topic_count', $topic_count );
		$time = gmdate( 'Y-m-d H:i:s', time() );
		update_post_meta( $post_id, '_bbp_last_active_time', $time );
		
		// Import videos.
		fi_import_forum_videos( $forum_data['nid'], $post_id );

		// Import comments.
		fi_import_forum_comments( $forum_data['nid'], $post_id, $forum_id );

		return $post_id;
	}
}

/**
 * Check if function exists.
 */
if ( ! function_exists( 'fi_import_forum_videos' ) ) {
	/**
	 * Function for import videos from forum.
	 * 
	 * @param $forum_id forum's ID from drupal database.
	 * @param $post_id forum_id of wordpress database.
	 */
	function fi_import_forum_videos( $forum_id, $post_id ) {
		global $wpdb;
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$conn = sonopath_connect_database();
		$query2 = "SELECT data FROM video_zencoder where nid=" . $forum_id;
		$result = $conn->query( $query2 );
		if ( $result->num_rows ) {
			while( $video_data = $result->fetch_assoc() ) {
				if ( ! empty( $video_data['data'] ) ) {
					$vdata = (array) unserialize( $video_data['data'] );
					foreach ( $vdata as $data ) {
						$video_url  = $data->url;
						$tmp_file   = download_url( $video_url );
						$upload_dir = wp_upload_dir();
						$filename   = basename( $video_url );
						if ( wp_mkdir_p( $upload_dir['path'] ) ) {
							$file = $upload_dir['path'] . '/' . $filename;
						} else {
							$file = $upload_dir['basedir'] . '/' . $filename;
						}
						copy( $tmp_file, $file );
						@unlink( $tmp_file );
						$wp_filetype = wp_check_filetype( $filename, null );
						$attachment  = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title'     => sanitize_file_name( $filename ),
							'post_content'   => '',
							'post_status'    => 'inherit',
						);
						$attach_id   = wp_insert_attachment( $attachment, $file, $post_id );
						$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
						wp_update_attachment_metadata( $attach_id, $attach_data );

						$url          = wp_get_attachment_url( $attach_id );
						$post_content = get_post( $post_id )->post_content;
						
						$content = '<!-- wp:video {"id": ' . $attach_id . '} --><figure class="wp-block-video"><video controls src="' . $url . '"></video></figure><!-- /wp:video -->';
						wp_update_post(
							array(
								'ID'           => $post_id,
								'post_content' => $post_content . $content,
							)
						);
					}
				}
			}
		}
	}
}

/**
 * Check if function exists.
 */
if ( ! function_exists( 'fi_import_forum_comments' ) ) {
	/**
	 * Function for import forums comment from drupal database.
	 */
	function fi_import_forum_comments( $forum_id, $post_id, $sopath_forum_id ) {
		global $wpdb;
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		require_once ABSPATH . 'wp-admin/includes/image.php';
		$conn = sonopath_connect_database();
		$user_ids = array();
		$comments       = "SELECT * FROM comments where nid = {$forum_id}";
		$fetch_comments = $conn->query( $comments );
		$reply_cnt      = $fetch_comments->num_rows;
		if ( $reply_cnt < 1 ) {
			update_post_meta( $post_id, '_bbp_last_active_id', $post_id );
		}
		update_post_meta( $post_id, '_bbp_reply_count', $reply_cnt );
		if ( $fetch_comments->num_rows ) {
			while ( $comment_data = $fetch_comments->fetch_assoc() ) {
				$fetch_user  = "SELECT * FROM users WHERE uid = {$comment_data['uid']}";
				$user        = $conn->query( $fetch_user );
				$wp_user_id = get_current_user_id();
				if ( $user->num_rows ) {
					$user_data   = $user->fetch_assoc();
					$get_wp_user = get_user_by( 'email', $user_data['mail'] );
					$wp_user_id  = ! empty( $get_wp_user ) ? $get_wp_user->ID : get_current_user_id();
				}
				$reply_arr   = array(
					'post_title'   => $comment_data['subject'],
					'post_content' => $comment_data['comment'],
					'post_status'  => 'publish',
					'post_type'    => 'reply',
					'post_author'  => $wp_user_id,
					'post_parent'  => $post_id,
					'post_date'     => gmdate( 'Y-m-d H:i:s', $comment_data['timestamp'] ),
					// 'meta_input'   => array(
					// 	'reply_person'    => $comment_data['name'],
					// 	'reply_mail_id'   => $comment_data['mail'],
					// 	'_bbp_author_ip	' => $comment_data['hostname'],
					// 	'_bbp_forum_id'   => $forum_id,
					// 	'_bbp_topic_id'   => $post_id,
					// ),
				);

				$reply_id = wp_insert_post( $reply_arr );
				update_post_meta( $sopath_forum_id, '_bbp_last_reply_id', $reply_id );
				update_post_meta( $post_id, '_bbp_last_active_id', $reply_id );
				update_user_meta( $wp_user_id, 'wp__bbp_last_posted', $comment_data['timestamp'] );
				$user_reply_cnt = get_user_meta( $wp_user_id, 'wp__bbp_reply_count', true );
				$user_reply_cnt = ! empty( $user_reply_cnt  ) ? ( $user_reply_cnt + 1 ) : 1;
				update_user_meta( $wp_user_id, 'wp__bbp_reply_count', $user_reply_cnt );
				update_post_meta( $reply_id, '_bbp_author_ip', $comment_data['hostname'] );
				$user_ids[] = $wp_user_id;
				// Import comment attachments.
				$comment_attchments = "SELECT description FROM comment_upload WHERE cid = {$comment_data['cid']}";
				$fetch_attachments  = $conn->query( $comment_attchments );
				if ( $fetch_attachments->num_rows ) {
					while ( $attchment = $fetch_attachments->fetch_assoc() ) {
						$url        = 'https://oldsite.sonopath.com/sites/default/files/imagecache/blog_main_image_thumb/';
						$image_url  = $url . $attchment['description'];
						$tmp_file   = download_url( $image_url );
						$upload_dir = wp_upload_dir();
						$filename   = basename( $image_url );
						if ( wp_mkdir_p( $upload_dir['path'] ) ) {
							$file = $upload_dir['path'] . '/' . $filename;
						} else {
							$file = $upload_dir['basedir'] . '/' . $filename;
						}
						copy( $tmp_file, $file );
						@unlink( $tmp_file ); // phpcs:ignore
						$wp_filetype        = wp_check_filetype( $filename, null );
						$comment_attachment = array(
							'post_mime_type' => $wp_filetype['type'],
							'post_title'     => sanitize_file_name( $filename ),
							'post_content'   => '',
							'post_status'    => 'inherit',
						);
						$attach_id          = wp_insert_attachment( $comment_attachment, $file, $reply_id );
						$attach_data        = wp_generate_attachment_metadata( $attach_id, $file );
						wp_update_attachment_metadata( $attach_id, $attach_data );

						$attch_url    = wp_get_attachment_url( $attach_id );
						$post_content = get_post( $reply_id )->post_content;
						$content      = '<img class="alignnone size-full wp-image-" ' . $reply_id . ' " src=" ' . $attch_url . ' " alt="" />';
						wp_update_post(
							array(
								'ID'           => $reply_id,
								'post_content' => $post_content . $content,
							)
						);
					}
				}
			}
		}
		$voices     = array_unique( $user_ids );
		update_post_meta( $post_id, '_bbp_voice_count', count( $voices ) );
		$total_reply = get_post_meta( $sopath_forum_id, '_bbp_reply_count', true );
		$total_reply = $reply_cnt + $total_reply;
		$time        = gmdate( 'Y-m-d H:i:s', time() );
		update_post_meta( $sopath_forum_id, '_bbp_reply_count', $total_reply );
		update_post_meta( $sopath_forum_id, '_bbp_total_reply_count', $total_reply );
		update_post_meta( $sopath_forum_id, '_bbp_last_active_time', $time );
	}
}

/**
 * Check if function exists.
 */
if ( ! function_exists( 'extract_image_tags_from_string' ) ) {
	/**]
	 * function for extract image from content and add it into media.
	 *
	 * @param string $html Holds html content.
	 */
	function extract_image_tags_from_string( $html ) {
		$doc  = new DOMDocument();
		$doc->loadHTML( $html );
		$img_tags = $doc->getElementsByTagName( 'img' );

		// Check if not images are available.
		if ( empty( $img_tags ) ) {
			return $html;
		}
	
		foreach ( $img_tags as $img_tag ) {
			$current_src = $img_tag->getAttribute( 'src' );
			$new_src     = 'https://oldsite.sonopath.com' . parse_url( $current_src, PHP_URL_PATH );
			// Download this new src.
			$tmp_file   = download_url( $new_src );

			// Upload it into WordPress server.
			$upload_dir = wp_upload_dir();
			$filename   = basename( $new_src );
			if ( wp_mkdir_p( $upload_dir['path'] ) ) {
				$file = $upload_dir['path'] . '/' . $filename;
			} else {
				$file = $upload_dir['basedir'] . '/' . $filename;
			}

			copy( $tmp_file, $file );
			@unlink( $tmp_file ); // phpcs:ignore
			$wp_filetype        = wp_check_filetype( $filename, null );
			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title'     => sanitize_file_name( $filename ),
				'post_content'   => '',
				'post_status'    => 'inherit',
			);
			$attach_id          = wp_insert_attachment( $attachment, $file );
			$attach_data        = wp_generate_attachment_metadata( $attach_id, $file );
			wp_update_attachment_metadata( $attach_id, $attach_data );
			// Replace the uploaded file URL.
			$new_src    = wp_get_attachment_url( $attach_id );
			$img_tag->setAttribute( 'src', $new_src );
			$img_tag->setAttribute( 'data-oldsrc', $current_src );
		}
		$updated_html = $doc->saveHTML();
		preg_match("/<body[^>]*>(.*?)<\/body>/is", $updated_html, $matches);

		return $matches[1];
	}
}
