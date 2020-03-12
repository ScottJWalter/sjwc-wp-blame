<?php

// No thank you
if ( ! defined('ABSPATH') ) die;

class WPB_Log_List extends WP_List_Table {

	/**
	 * Initialise the logs table.
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public function __construct() {

		parent::__construct(
			array(
				'singular' => __('Log', 'wp-blame'),
				'plural' => __('Logs', 'wp-blame'),
				'ajax' => false
			)
		);

		$this->prepare_items();

	}

	/**
	 * Define the table columns.
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public function get_columns() {

		$columns = [
			'cb'        => '<input type="checkbox" />',
			'log_id'    => __('Log', 'wp-blame'),
			'item'    	=> __('Item', 'wp-blame'),
			'action'    => __('Action', 'wp-blame'),
			'user_id'   => __('User', 'wp-blame'),
			'notes'     => __('Notes', 'wp-blame'),
			'timestamp' => __('Date', 'wp-blame')
		];

		return $columns;

	}

	/**
	 * Set which columns are sortable.
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public function get_sortable_columns() {

		$sortable_columns = array(
			'timestamp' => array('timestamp', false)
		);

		return $sortable_columns;
	
	}

	/**
	 * Return the checkbox column.
	 * 
	 * @since 2.0
	 * 
	 * @param object $item Current row item.
	 * 
	 * @return string
	 */
	public function column_cb( $item ) {
		
		return sprintf('<input type="checkbox" name="%1$s[]" value="%2$s" />', $this->_args['singular'], $item->log_id );
	
	}

	/**
	 * Return the log id column.
	 * 
	 * @since 2.0
	 * 
	 * @param object $item Current row item.
	 * 
	 * @return string
	 */
	public function column_log_id( $item ) {

		return $item->log_id;

	}

	/**
	 * Return the log type column.
	 * 
	 * @since 2.0
	 * 
	 * @param object $item Current row item.
	 * 
	 * @return string
	 */
	public function column_item( $item ) {

		if ( 'post' == $item->slug ) {

			$name = ! empty( get_the_title( $item->object_id ) ) ? get_the_title( $item->object_id ) : __('Post', 'wp-blame');
			$link = esc_url( admin_url('post.php?post=' . $item->object_id . '&action=edit') );

		} elseif ( 'term' == $item->slug ) {

			$term = get_term( $item->object_id );
			$name = ! empty( $term->name ) ? $term->name : __('Term', 'wp-blame');
			$link = esc_url( admin_url('term.php?tag_ID=' . $item->object_id) );

		} elseif ( 'media' == $item->slug ) {

			$name = ! empty( get_the_title( $item->object_id ) ) ? get_the_title( $item->object_id ) : __('Media', 'wp-blame');
			$link = esc_url( admin_url('upload.php?item=' . $item->object_id) );

		} elseif ( 'comment' == $item->slug ) {

			$name = sprintf( _x('Comment %s', 'Comment id', 'wp-blame'), $item->object_id );
			$link = esc_url( admin_url('comment.php?action=editcomment&c=' . $item->object_id) );

		} elseif ( 'user' == $item->slug ) {

			$name = sprintf( _x('User %s', 'User id', 'wp-blame'), $item->object_id );
			$link = esc_url( admin_url('user-edit.php?user_id=' . $item->object_id) );

		} elseif ( 'theme' == $item->slug ) {

			$name = __('Theme', 'wp-blame');
			$link = esc_url( admin_url('themes.php') );

		} elseif ( 'plugin' == $item->slug ) {

			$name = __('Plugin', 'wp-blame');
			$link = esc_url( admin_url('plugins.php') );

		} elseif ( 'option' == $item->slug ) {

			$name = __('Option', 'wp-blame');
			$link = esc_url( admin_url('options.php') );

		} else {

			return '<abbr title="' . __('Item could not be found.', 'wp-blame') . '">' . __('Not Found', 'wp-blame') . '</abbr>';

		}

		return '<a href="' . $link . '">' . $name . '</a>';

	}

	/**
	 * Return the log action column.
	 * 
	 * @since 2.0
	 * 
	 * @param object $item Current row item.
	 * 
	 * @return string
	 */
	public function column_action( $item ) {

		return $item->action;
	
	}

	/**
	 * Return the user column.
	 * 
	 * @since 2.0
	 * 
	 * @param object $item Current row item.
	 * 
	 * @return string
	 */
	public function column_user_id( $item ) {
		
		$get_user_data = get_userdata( $item->user_id );

		if ( ! empty( $get_user_data ) ) {

			$get_display_name = $get_user_data->display_name;

			if ( ! empty( $item->host_ip ) ) {

				return '<a href="' . admin_url('user-edit.php?user_id=') . $item->user_id . '" class="profile" title="' . __('IP: ', 'wp-blame') . $item->host_ip . '">' . $get_display_name . '</a>';

			} else {

				return '<a href="' . admin_url('user-edit.php?user_id=') . $item->user_id . '" class="profile">' . $get_display_name . '</a>';

			}

		} else {

			return '<abbr title="' . __('IP: ', 'wp-blame') . $item->host_ip . '">' . __('Unknown', 'wp-blame') . '</abbr>';

		}
	
	}

	/**
	 * Return the notes column.
	 * 
	 * @since 2.0
	 * 
	 * @param object $item Current row item.
	 * 
	 * @return string
	 */
	public function column_notes( $item ) {
	
		return $item->notes;
	
	}

	/**
	 * Return the time stamp column.
	 * 
	 * @since 2.0
	 * 
	 * @param object $item Current row item.
	 * 
	 * @return string
	 */
	public function column_timestamp( $item ) {
	
		return '<abbr title="' . date( _x('F jS Y H:i:s a', 'PHP date format', 'wp-blame'), strtotime( $item->timestamp ) ) . '">' . date( 'Y/m/d', strtotime( $item->timestamp ) ) . '</abbr>';
	
	}

	/**
	 * Return the default (unknown) column.
	 * 
	 * @since 2.0
	 * 
	 * @param object $item Current row item.
	 * 
	 * @return string
	 */
	public function column_default( $item, $column_name ) {

		return $item->$column_name;

	}

	/**
	 * Return the no logs found notice.
	 * 
	 * @since 2.0
	 * 
	 * @return string
	 */
	public function no_items() {
	
		_e('There are no logs to show right now.', 'wp-blame');
	
	}

	/**
	 * Define the bulk option items.
	 * 
	 * @since 2.0
	 * 
	 * @return array
	 */
	public function get_bulk_actions() {

		return array(
			'delete' => __('Delete', 'wp-blame')
		);

	}

	/**
	 * Get the total number of logs.
	 * 
	 * @since 2.0
	 * 
	 * @return string
	 */
	public function get_total_logs() {

		global $wpdb;

		// Query to count the log rows
		$total_logs = $wpdb->get_var('SELECT COUNT(*) FROM ' . $wpdb->prefix . 'logs');

		return $total_logs;

	}

	/**
	 * Process the bulk actions.
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public function process_bulk_action() {

		global $wpdb;

		if ( isset( $_POST['_wpnonce'] ) ) {

			$nonce  = sanitize_text_field( $_POST['_wpnonce'] );
			$action = $this->current_action();

			if ( wp_verify_nonce( $nonce, 'bulk-' . $this->_args['plural'] ) && isset( $_POST['log'] ) && 'delete' == $action ) {

				foreach ( $_POST['log'] as $log ) {

					$log = sanitize_text_field( $log );

					$wpdb->query(
						$wpdb->prepare(
							'DELETE FROM ' . $wpdb->prefix . 'logs WHERE log_id = %s LIMIT 1',
							$log
						)
					);

				}

			}

		}

	}

	/**
	 * Prepare the items for output.
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public function prepare_items() {

		global $wpdb;

		$screen = get_current_screen();
		$per_page = get_user_meta( get_current_user_id(), 'wpb_logs_per_page', true );

		if ( false === $per_page || $per_page < 1 ) {
			$per_page = $screen->get_option( 'wpb_logs_per_page', 20 );
		}

		// SJWC:  force per_page one lase time to avoid div by zero
		$per_page = $per_page ?? 20;
		
		$columns = $this->get_columns();
		$hidden = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->process_bulk_action();

		$current_page = $this->get_pagenum();
		$total_items = $this->get_total_logs();

		$this->set_pagination_args([
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page )
		]);

		if ( ( ! empty( $_REQUEST['orderby'] ) ) && ( ! empty( $_REQUEST['order'] ) ) ) {

			$query = 'SELECT * FROM ' . $wpdb->prefix . 'logs';

			$order_by = sanitize_text_field( strtoupper( $_REQUEST['orderby'] ) );
			$order_col = sanitize_text_field( strtoupper( $_REQUEST['order'] ) );

			$query .= ' ORDER BY ' . $order_by;
			$query .= $order_col == 'ASC' ? ' ' . $order_col : ' ' . $order_col;

		} else {

			$query = 'SELECT * FROM ' . $wpdb->prefix . 'logs ORDER BY log_id DESC';

		}

		if ( $current_page >= 1 ) {

			$query .= ' LIMIT ' . $per_page;
			$query .= ' OFFSET ' . ($current_page - 1) * $per_page;

		}

		$this->items = $wpdb->get_results($query);

	}

}

