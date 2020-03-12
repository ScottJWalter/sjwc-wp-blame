<?php
/**
 * Plugin Name: WP Blame
 * Plugin URI: https://wordpress.org/plugins/wp-blame/
 * Description: Keep a record of the activity on your website.
 * Author: James Cooper
 * Author URI: http://www.corematrixgrid.com/
 * Text Domain: wp-blame
 * Version: 2.1.4-sjwc
 */

defined('ABSPATH') || die;


new WP_Blame;

class WP_Blame {
	/**
	 * The plugin version.
	 * 
	 * @since 2.1
	 * 
	 * @var string
	 */
	protected static $version = '2.1.4-sjwc';

	/**
	 * I blame you for getting me hooked.
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public function __construct() {
		register_activation_hook( 	__FILE__, array( __CLASS__, 'plugin_install' 	), 10, 0 );
		register_deactivation_hook( __FILE__, array( __CLASS__, 'plugin_uninstall' 	), 10, 0 );

		add_action( 'plugins_loaded'			, array( __CLASS__, 'load_text_domain' 		), 10, 0 );
		add_action( 'plugins_loaded'			, array( __CLASS__, 'load_log_hooks' 		), 10, 0 );
		add_action( 'admin_menu'				, array( __CLASS__, 'add_admin_pages' 		), 10, 0 );
		add_action( 'load-tools_page_wpb_logs'	, array( __CLASS__, 'add_screen_options'	), 10, 0 );
		add_action( 'admin_init'				, array( __CLASS__, 'register_settings' 	), 10, 0 );

		add_filter( 'plugin_action_links'	, array( __CLASS__, 'add_donate_link' 		), 10, 2 );
		add_filter( 'set-screen-option'		, array( __CLASS__, 'set_screen_options'	), 10, 3 );
	}

	/**
	 * Install the plugin.
	 * 
	 * @since 2.0
	 * @since 2.1 Added per page option.
	 * 
	 * @return boolean
	 */
	public static function plugin_install() {

		global $wpdb;

		// Add the plugin options
		add_option( 'wpb_logs_per_page', '20' );
		add_option( 'wpb_user_whitelist', '' );

		// Create the table name
		$table_name = $wpdb->prefix . 'logs';

		// Get the db charset
		$charset = $wpdb->get_charset_collate();

		// Create the SQL schema for the new table
		$query = <<<EOSQL
CREATE TABLE $table_name (
	log_id 		mediumint(9)									NOT NULL AUTO_INCREMENT,
	site_id 	mediumint(9)									NOT NULL,
	user_id 	mediumint(9)									NOT NULL,
	host_ip 	varchar(50) 	DEFAULT ''						NOT NULL,
	object_id 	mediumint(9) 									NOT NULL,
	slug 		varchar(100) 	DEFAULT '' 						NOT NULL,
	setting 	varchar(25) 	DEFAULT '' 						NOT NULL,
	timestamp 	datetime 		DEFAULT '0000-00-00 00:00:00'	NOT NULL,
	action 		varchar(25) 	DEFAULT '' 						NOT NULL,
	notes 		varchar(250) 	DEFAULT '' 						NOT NULL,
	PRIMARY KEY  (log_id)
) $charset;
EOSQL;	

		// Run the query
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $table_name );
		$wpdb->query( $query );

		return true;
	}

	/**
	 * Uninstall the plugin.
	 * 
	 * @since 2.0
	 * @since 2.1 Added per page option.
	 * 
	 * @return boolean
	 */
	public static function plugin_uninstall() {
		global $wpdb;

		// Delete the options
		delete_option( 'wpb_logs_per_page' );
		delete_option( 'wpb_user_whitelist' );

		// Delete the table
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'logs' );

		return true;
	}

	/**
	 * Load the plugin text domain.
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public static function load_text_domain() {

		load_plugin_textdomain( 'wp-blame', false, untrailingslashit( dirname( __FILE__ ) ) . '/languages' );

	}

	/**
	 * Load the logging functions.
	 * 
	 * @since 2.0
	 * 
	 * @return boolean
	 */
	public static function load_log_hooks() {

		// Include the log hooks
		require_once( 'includes/class-log-hooks.php' );

		// Create new instance
		return new WPB_Log_Hooks();

	}

	/**
	 * Adds a donate link to the plugins table.
	 * 
	 * @since 2.0
	 * 
	 * @param array  $links A list of plugin links
	 * @param string $file  The current plugin file.
	 * 
	 * @return array $links
	 */
	public static function add_donate_link( $links, $file ) {
/*
		// Check if this is the current plugin
		if ( $file == 'wp-blame/wp-blame.php' ) {

			// Create the donate link
			$plugin_link = '<a href="https://www.paypal.me/dtj27" target="_blank" title="' . _x('Donate via PayPal', 'Link title attribute', 'wp-blame') . '">' . __('Donate', 'wp-blame') . '</a>';

			// Add the link to the array
			array_unshift( $links, $plugin_link );

		}
*/
		return $links;

	}

	/**
	 * Add the plugin admin pages.
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public static function add_admin_pages() {

		add_submenu_page(
			'tools.php',
			__('Logs', 'wp-blame'),
			__('Logs', 'wp-blame'),
			'manage_options',
			'wpb_logs',
			array(
				__CLASS__,
				'show_logs_page'
			)
		);

	}

	/**
	 * Adds screen options for the plugin.
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public static function add_screen_options() {

		add_screen_option(
			'per_page',
			array(
				'label' => __('Logs Per Page', 'wp-blame'),
				'default' => 20,
				'option' => 'wpb_logs_per_page'
			)
		);

	}

	/**
	 * Saves the screen options.
	 * 
	 * @since 2.0
	 * 
	 * @param boolean $status Flag to save option.
	 * @param string  $option The option name.
	 * @param mixed   $value  The option value.
	 * 
	 * @return string|boolean
	 */
	public static function set_screen_options( $status, $option, $value ) {

		// Check it's this option
		if ( 'wpb_logs_per_page' == $option ) {

			return (int) $value;

		}

		return $status;

	}

	/**
	 * Show the logs table.
	 * 
	 * @since 2.0
	 * @since 2.0.1 Changed the $selected variable to $tab.
	 * 
	 * @return mixed
	 */
	public static function show_logs_page() {

		// Find out which tab is active
		$tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : false;

		// Include the logs class for the table
		if ( 'settings' != $tab ) {
			require_once( 'includes/class-log-list.php' );
		}
		?>
		<div class="wrap">
			<h1 class="page-title"><?php _e('Logs', 'wp-blame'); ?></h1>
			<h2 class="nav-tab-wrapper wp-clearfix">
				<a href="<?php echo admin_url( 'tools.php?page=wpb_logs&tab=logs' ); ?>" class="nav-tab<?php if ( 'settings' != $tab ) : ?> nav-tab-active<?php endif; ?>"><?php _e('History', 'wp-blame'); ?></a>
				<a href="<?php echo admin_url( 'tools.php?page=wpb_logs&tab=settings' ); ?>" class="nav-tab<?php if ( 'settings' == $tab ) : ?> nav-tab-active<?php endif; ?>"><?php _e('Settings', 'wp-blame'); ?></a>
			</h2>
			<?php if ( 'settings' == $tab ) : ?>
				<div id="settings" class="tab-content">
					<?php settings_errors(); ?>
					<form method="post" action="options.php">
						<table class="dtjwpb-form form-table">
							<tbody>
								<?php settings_fields('wpb_settings_fields'); ?>
								<?php do_settings_sections('wpb_settings_section'); ?>
							</tbody>
						</table>
						<?php submit_button( __('Save Settings', 'wp-blame'), 'primary' ); ?>
					</form>
				</div>
			<?php else : ?>
				<div id="logs" class="tab-content">
					<div class="wrap">
						<form method="post">
							<?php $table = new WPB_Log_List(); ?>
							<?php $table->display(); ?>
						</form>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Register the plugin settings.
	 * 
	 * @since 2.0
	 * 
	 * @return void
	 */
	public static function register_settings() {
		// Create the section
		add_settings_section( 'wpb_settings_group', false, false, 'wpb_settings_section' );

		// Create each option
		add_settings_field( 'wpb_user_whitelist', __('Whitelist These Users', 'wp-blame'), array( __CLASS__, 'wpb_user_whitelist_template' ), 'wpb_settings_section', 'wpb_settings_group', array( 'wpb_user_whitelist' ) );

		// Register the settings
		register_setting( 'wpb_settings_fields', 'wpb_user_whitelist', 'esc_attr');
	}

	/**
	 * Show the setting for user whitelist.
	 * 
	 * @since 2.0
	 * 
	 * @return mixed
	 */
	public static function wpb_user_whitelist_template( $args ) {
		?>
		<p><textarea 
			style="min-width: 320px; min-height: 100px;" 
			class="text-area {$args[0]}" 
			id="{$args[0]}" 
			name="{$args[0]}" 
			aria-describedby="description-{$args[0]}"
			><?php echo get_option( $args[0] ); ?></textarea></p>
		<p class="description" id="description-{$args[0]}"
			><?php echo __( 'The usernames of people who should not have their actions logged.', 'wp-blame' ); ?></p>
		<?php
		// echo '<p><textarea style="min-width: 320px; min-height: 100px;" class="text-area ' . $args[0] . '" id="' . $args[0] . '" name="' . $args[0] . '" aria-describedby="description-' . $args[0] . '">' . get_option( $args[0] ) . '</textarea></p>';
		// echo '<p class="description" id="description-' . $args[0] . '">' . __('The usernames of people who should not have their actions logged.', 'wp-blame') . '</p>';
	}
}
