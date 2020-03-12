<?php

// No thank you
if ( ! defined('ABSPATH') ) die;

class WPB_Log_Hooks {

	/**
	 * Listen for various actions.
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public function __construct() {

		add_action('transition_post_status', array( __CLASS__, 'new_post_status' ), 10, 3);
		add_action('publish_post', array( __CLASS__, 'post_data_updated' ), 10, 1);
		add_action('publish_page', array( __CLASS__, 'post_data_updated' ), 10, 1);
		add_action('trash_post', array( __CLASS__, 'post_data_updated' ), 10, 1);
		add_action('untrash_post', array( __CLASS__, 'post_data_updated' ), 10, 1);
		add_action('before_delete_post', array( __CLASS__, 'post_data_updated' ), 10, 1);
		add_action('created_term', array( __CLASS__, 'term_data_altered' ), 10, 3);
		add_action('edit_terms', array( __CLASS__, 'term_data_edited' ), 10, 2);
		add_action('delete_term', array( __CLASS__, 'term_data_altered' ), 10, 3);
		add_action('add_attachment', array( __CLASS__, 'media_altered' ), 10, 1);
		add_action('edit_attachment', array( __CLASS__, 'media_altered' ), 10, 1);
		add_action('delete_attachment', array( __CLASS__, 'media_altered' ), 10, 1);
		add_action('comment_post', array( __CLASS__, 'comment_altered' ), 10, 1);
		add_action('edit_comment', array( __CLASS__, 'comment_altered' ), 10, 1);
		add_action('deleted_comment', array( __CLASS__, 'comment_altered' ), 10, 1);
		add_action('trashed_comment', array( __CLASS__, 'comment_altered' ), 10, 1);
		add_action('untrashed_comment', array( __CLASS__, 'comment_altered' ), 10, 1);
		add_action('spammed_comment', array( __CLASS__, 'comment_altered' ), 10, 1);
		add_action('unspammed_comment', array( __CLASS__, 'comment_altered' ), 10, 1);
		add_action('transition_comment_status', array( __CLASS__, 'comment_updated' ), 10, 3);
		add_action('user_register', array( __CLASS__, 'user_account_altered' ), 10, 1);
		add_action('profile_update', array( __CLASS__, 'user_account_altered' ), 10, 2);
		add_action('delete_user', array( __CLASS__, 'user_account_altered' ), 10, 1);
		add_action('wp_login', array( __CLASS__, 'user_authentication' ), 10, 2);
		add_action('wp_login_failed', array( __CLASS__, 'user_authentication' ), 10, 1);
		add_action('wp_logout', array( __CLASS__, 'user_authentication' ), 10, 0);
		add_action('switch_theme', array( __CLASS__, 'theme_switched' ), 10, 0);
		add_action('activated_plugin', array( __CLASS__, 'plugin_altered' ), 10, 2);
		add_action('deactivated_plugin', array( __CLASS__, 'plugin_altered' ), 10, 2);
		add_action('updated_option', array( __CLASS__, 'option_updated' ), 10, 3);

	}

	/**
	 * Creates a new log entry.
	 * 
	 * @since 2.0
	 * 
	 * @param array $log_data The data to save.
	 * 
	 * @return boolean
	 */
	public static function save_new_log( $log_data = array() ) {

		global $wpdb;

		// Setup the whitelist trigger
		$is_whitelisted = false;

		// Get the username of the current user
		$get_user = get_userdata( get_current_user_id() );

		// Did we get a valid user
		if ( false !== $get_user ) {
		
			// Get the username
			$username = $get_user->user_login;

		} else {

			return false;

		}

		// Get the user whitelist
		$whitelisted_users = get_option('dtjwpb_user_filter_list');

		// Explode the list into an array we can loop through
		$whitelist = preg_split( '/\r\n|[\r\n]/', $whitelisted_users );

		// Loop through the white list
		foreach ( $whitelist as $a_user ) {

			// Check if the current user is whitelisted or not
			if ( $a_user === $username ) {

				$is_whitelisted = true;

			}

		}

		// Stop if we're whitelisted
		if ( true === $is_whitelisted ) {

			return false;

		}

		// Save the log data
		$wpdb->insert( 
			$wpdb->prefix . 'logs', 
			array(
				'site_id'    => intval(get_current_blog_id()),
				'user_id'    => intval(get_current_user_id()),
				'host_ip' 	 => esc_html($_SERVER['REMOTE_ADDR']),
				'object_id'  => intval($log_data['object_id']),
				'slug'       => $log_data['slug'],
				'setting'    => $log_data['setting'],
				'timestamp'  => date('Y-m-d H:i:s'),
				'action'     => $log_data['action'],
				'notes'      => $log_data['notes']
			)
		);

		return true;

	}

	/**
	 * Log a post status change.
	 * 
	 * @since 2.0
	 * 
	 * @param string $new_status The new post status.
	 * @param string $old_status The old post status.
	 * @param object $post       The current post object.
	 * 
	 * @return boolean
	 */
	public static function new_post_status( $new_status = false, $old_status = false, $post ) {

		// Get the post type
		$post_type = get_post_type( $post->ID );

		// Setup log data array
		$log_data = array(
			'object_id' => $post->ID,
			'slug' => 'post',
			'setting' => $post_type,
			'action' => __('Update', 'wp-blame'),
			'notes' => ''
		);

		// Get the new status
		if ( 'auto-draft' == $old_status && 'draft' == $new_status ) {

			$log_data['action'] = __('Create', 'wp-blame');
			$log_data['notes'] = __('New post created', 'wp-blame');

		} elseif ( ( 'draft' == $old_status && 'draft' == $new_status ) || ( 'publish' == $old_status && 'publish' == $new_status ) ) {

			$log_data['notes'] = __('Post was updated', 'wp-blame');

		} elseif ( 'pending' == $new_status ) {

			$log_data['notes'] = __('Post is pending', 'wp-blame');

		} elseif ( 'private' == $new_status ) {

			$log_data['notes'] = __('Post is private', 'wp-blame');

		} elseif ( 'future' == $new_status ) {

			$log_data['notes'] = __('Post is scheduled', 'wp-blame');

		} elseif ( 'trash' == $new_status ) {

			$log_data['notes'] = __('Post is binned', 'wp-blame');

		} else {

			return false;

		}

		// Record the log 
		return self::save_new_log( $log_data );

	}

	/**
	 * Called when a post is updated.
	 * 
	 * @since 2.0
	 * 
	 * @param string $post_id The post id.
	 * 
	 * @return boolean
	 */
	public static function post_data_updated( $post_id = false ) {

		// Get the current filter
		$filter = current_filter();

		// Get the post type
		$post_type = get_post_type( $post_id );

		// Make sure this isn't a revision
		if ( 'Revision' == $post_type || 'revision' == $post_type ) {

			return false;

		}

		// Setup log data array
		$log_data = array(
			'object_id' => $post_id,
			'slug' => 'post',
			'setting' => $post_type
		);

		// Which hook is active
		if ( 'publish_post' == $filter ) {

			$log_data['action'] = __('Published', 'wp-blame');
			$log_data['notes'] = __('Post was published', 'wp-blame');
		
		} elseif ( 'publish_page' == $filter ) {

			$log_data['action'] = __('Published', 'wp-blame');
			$log_data['notes'] = __('Page was published', 'wp-blame');
		
		} elseif ( 'trash_post' == $filter ) {

			$log_data['action'] = __('Binned', 'wp-blame');
			$log_data['notes'] = __('Post was binned', 'wp-blame');
		
		} elseif ( 'untrash_post' == $filter ) {

			$log_data['action'] = __('Restored', 'wp-blame');
			$log_data['notes'] = __('Post was restored', 'wp-blame');
		
		} elseif ( 'before_delete_post' == $filter ) {

			$log_data['action'] = __('Deleted', 'wp-blame');
			$log_data['notes'] = __('Post was deleted', 'wp-blame');
		
		}

		// Record the log 
		return self::save_new_log( $log_data );

	}

	/**
	 * Called when a term is added or deleted.
	 * 
	 * @since 2.0
	 * 
	 * @param string $term_id  The term id.
	 * @param string $tt_id    The taxonomy term id.
	 * @param string $taxonomy The taxonomy name.
	 * 
	 * @return boolean
	 */
	public static function term_data_altered( $term_id = false, $tt_id = false, $taxonomy = false ) {

		// Get the current filter
		$filter = current_filter();

		// Setup log data array
		$log_data = array(
			'object_id' => $term_id,
			'slug' => 'term',
			'setting' => $taxonomy
		);

		// Check which filter is used
		if ( 'created_term' == $filter ) {

			$log_data['action'] = __('Created', 'wp-blame');
			$log_data['notes'] = __('Term was created', 'wp-blame');

		} elseif ( 'delete_term' == $filter ) {

			$log_data['action'] = __('Deleted', 'wp-blame');
			$log_data['notes'] = __('Term was deleted', 'wp-blame');

		}

		// Record the log 
		return self::save_new_log( $log_data );

	}

	/**
	 * Called when a term is edited.
	 * 
	 * @since 2.0
	 * 
	 * @param string $term_id  The term id.
	 * @param string $taxonomy The taxonomy name.
	 * 
	 * @return boolean
	 */
	public static function term_data_edited( $term_id = false, $taxonomy = false ) {

		// Get the current filter
		$filter = current_filter();

		// Setup log data array
		$log_data = array(
			'object_id' => $term_id,
			'slug' => 'term',
			'setting' => $taxonomy,
			'action' => __('Updated', 'wp-blame'),
			'notes' => __('Term was edited', 'wp-blame')
		);

		// Record the log 
		return self::save_new_log( $log_data );

	}

	/**
	 * Called when an attachment is added, edited or deleted.
	 * 
	 * @since 2.0
	 * 
	 * @param string $attachmentid The attachment id.
	 * 
	 * @return boolean
	 */
	public static function media_altered( $attachmentid = false ) {

		// Get the current filter
		$filter = current_filter();

		// Setup log data array
		$log_data = array(
			'object_id' => $attachmentid,
			'slug' => 'media',
			'setting' => __('Media', 'wp-blame')
		);

		// Which action was performed
		if ( 'add_attachment' == $filter ) {

			$log_data['action'] = __('Created', 'wp-blame');
			$log_data['notes'] = __('Attachment uploaded', 'wp-blame');

		} elseif ( 'edit_attachment' == $filter ) {

			$log_data['action'] = __('Updated', 'wp-blame');
			$log_data['notes'] = __('Attachment updated', 'wp-blame');

		} elseif ( 'delete_attachment' == $filter ) {

			$log_data['action'] = __('Deleted', 'wp-blame');
			$log_data['notes'] = __('Attachment deleted', 'wp-blame');

		}

		// Record the log 
		return self::save_new_log( $log_data );

	}

	/**
	 * Called when a comment is altered in some way.
	 * 
	 * @since 2.0
	 * 
	 * @param string $comment_id The comment id.
	 * 
	 * @return boolean
	 */
	public static function comment_altered( $comment_id = false ) {

		// Get the current filter
		$filter = current_filter();

		// Setup log data array
		$log_data = array(
			'object_id' => $comment_id,
			'slug' => 'comment',
			'setting' => __('Comment', 'wp-blame')
		);

		// Which action was performed
		if ( 'comment_post' == $filter ) {

			$log_data['action'] = __('Created', 'wp-blame');
			$log_data['notes'] = __('Comment posted', 'wp-blame');

		} elseif ( 'edit_comment' == $filter ) {

			$log_data['action'] = __('Updated', 'wp-blame');
			$log_data['notes'] = __('Comment updated', 'wp-blame');

		} elseif ( 'deleted_comment' == $filter ) {

			$log_data['action'] = __('Deleted', 'wp-blame');
			$log_data['notes'] = __('Comment deleted', 'wp-blame');

		} elseif ( 'trashed_comment' == $filter ) {

			$log_data['action'] = __('Binned', 'wp-blame');
			$log_data['notes'] = __('Comment binned', 'wp-blame');

		} elseif ( 'untrashed_comment' == $filter ) {

			$log_data['action'] = __('Restored', 'wp-blame');
			$log_data['notes'] = __('Comment restored', 'wp-blame');

		} elseif ( 'spammed_comment' == $filter ) {

			$log_data['action'] = __('Spammed', 'wp-blame');
			$log_data['notes'] = __('Comment marked as spam', 'wp-blame');

		} elseif ( 'unspammed_comment' == $filter ) {

			$log_data['action'] = __('Not Spam', 'wp-blame');
			$log_data['notes'] = __('Comment unmarked as spam', 'wp-blame');

		}

		// Record the log 
		return self::save_new_log( $log_data );

	}

	/**
	 * Called when a comment status is edited.
	 * 
	 * @since 2.0
	 * 
	 * @param string $new_status The new comment status.
	 * @param string $old_status The old comment status.
	 * @param string $comment    The comment id.
	 * 
	 * @return boolean
	 */
	public static function comment_updated( $new_status = false, $old_status = false, $comment = false ) {

		// Check a status was changed
		if ( $new_status == $old_statusd ) {

			return false;

		}

		// Setup log data array
		$log_data = array(
			'object_id' => $comment,
			'slug' => 'comment',
			'setting' => __('Comment', 'wp-blame'),
			'action' => __('Updated', 'wp-blame'),
			'notes' => sprintf( _x('Comment status changed to %s', 'Comment status', 'wp-blame'), $new_status )
		);

		// Record the log 
		return self::save_new_log( $log_data );

	}

	/**
	 * Called when a user account is altered.
	 * 
	 * @since 2.0
	 * 
	 * @param string $user_id The user id.
	 * 
	 * @return boolean
	 */
	public static function user_account_altered( $user_id = false ) {

		// Get the current filter
		$filter = current_filter();

		// Setup log data array
		$log_data = array(
			'object_id' => $user_id,
			'slug' => 'user',
			'setting' => __('User', 'wp-blame')
		);

		// What happened to the user
		if ( 'user_register' == $filter ) {

			$log_data['action'] = __('Registered', 'wp-blame');
			$log_data['notes'] = __('New user registered', 'wp-blame');

		} elseif ( 'profile_update' == $filter ) {

			$log_data['action'] = __('Updated', 'wp-blame');
			$log_data['notes'] = __('User profile updated', 'wp-blame');

		} elseif ( 'delete_user' == $filter ) {

			$log_data['action'] = __('Deleted', 'wp-blame');
			$log_data['notes'] = __('User account deleted', 'wp-blame');

		}

		// Record the log 
		return self::save_new_log( $log_data );

	}

	/**
	 * Called when a user tries to authenticate.
	 * 
	 * @since 2.0
	 * 
	 * @param string $username The username of the user.
	 * 
	 * @return boolean
	 */
	public static function user_authentication( $username = false ) {

		// Get the current filter
		$filter = current_filter();

		// Get the users id by login name.
		$user = get_user_by( 'login', $username );

		// Did we find the user
		if ( false !== $user ) {

			$user_id = $user->ID;

		} else {

			$user_id = 0;

		}

		// Setup log data array
		$log_data = array(
			'object_id' => $user_id,
			'slug' => 'user',
			'setting' => __('User', 'wp-blame')
		);

		// Check which filter we have
		if ( 'wp_login' == $filter ) {

			$log_data['action'] = __('Logged in', 'wp-blame');
			$log_data['notes'] = __('User logged into account', 'wp-blame');

		} elseif ( 'wp_login_failed' == $filter ) {

			$log_data['action'] = __('Login failed', 'wp-blame');
			$log_data['notes'] = __('User could not login', 'wp-blame');

		} elseif ( 'wp_logout' == $filter ) {

			$log_data['action'] = __('Logged out', 'wp-blame');
			$log_data['notes'] = __('User logged out of account', 'wp-blame');

		}

		// Record the log 
		return self::save_new_log( $log_data );

	}

	/**
	 * Called when a new theme is activated.
	 * 
	 * @since 2.0
	 * 
	 * @return boolean
	 */
	public static function theme_switched() {

		// Get the current theme
		$theme = get_option('stylesheet');

		// Setup log data array
		$log_data = array(
			'object_id' => 0,
			'slug' => 'theme',
			'setting' => __('Theme', 'wp-blame'),
			'action' => __('Activated', 'wp-blame'),
			'notes' => sprintf( __('%s theme activated', 'wp-blame'), $theme )
		);

		// Record the log 
		return self::save_new_log( $log_data );

	}

	/**
	 * Called when a plugin is activated or deactivated.
	 * 
	 * @since 2.0
	 * 
	 * @param string $plugin The plugin slug.
	 * 
	 * @return boolean
	 */
	public static function plugin_altered( $plugin = false ) {

		// Get the current filter
		$filter = current_filter();

		// Setup log data array
		$log_data = array(
			'object_id' => 0,
			'slug' => 'plugin',
			'setting' => __('Plugin', 'wp-blame')
		);

		// Which filter is being run
		if ( 'activated_plugin' == $filter ) {

			$log_data['action'] = __('Plugin activated', 'wp-blame');
			$log_data['notes'] = sprintf( __('%s was activated', 'wp-blame'), $plugin );

		} elseif ( 'deactivated_plugin' == $filter ) {

			$log_data['action'] = __('Plugin deactivated', 'wp-blame');
			$log_data['notes'] = sprintf( __('%s was deactivated', 'wp-blame'), $plugin );

		}

		// Record the log 
		return self::save_new_log( $log_data );

	}

	/**
	 * Called when an option is updated.
	 * 
	 * @since 2.0
	 * 
	 * @param string $option    The option slug.
	 * @param string $new_value The new option value.
	 * @param string $old_value The old option value.
	 * 
	 * @return boolean
	 */
	public static function option_updated( $option = false, $new_value = false, $old_value = false ) {

		// Don't always log the update
		if ( $new_value == $old_value || 'cron' == $option || false !== strpos( $option, '_transient' ) ) {

			return false;

		}

		// Setup log data array
		$log_data = array(
			'object_id' => 0,
			'slug' => 'option',
			'setting' => __('Option', 'wp-blame'),
			'action' => __('Option updated', 'wp-blame'),
			'notes' => sprintf( _x('%s was updated', 'The updated option', 'wp-blame'), $option )
		);

		// Record the log 
		return self::save_new_log( $log_data );

	}

}

