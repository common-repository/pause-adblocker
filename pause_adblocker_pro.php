<?php
/*
  Plugin Name: Pause Adblocker
  Plugin URI: http://blocker.platin.site/
  Version: 1.0.0
  Description: Pause Adblocker to alert visitors to pause their adblockers and then visit website.
  Author: Sign in World
  Author URI: http://signin.world/
  Text Domain: pause-adblocker
  Domain Path: /languages
  License: GPLv3
 */

require_once 'init.php';
require_once 'front.php';

/**
 * Initialize Pause Adblocker
 */
function pause_adblocker_init() {

	// Make plugin available for translation, you can change /languages/ to your .mo-files folder name
	load_plugin_textdomain( 'pause-adblocker', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );

	// Initialize pause
	$admin = new pause7( array(
			// pause file path
			'file' => __FILE__,
			// Plugin slug (should be equal to plugin directory name)
			'slug' => 'pause-adblocker',
			// Plugin prefix
			'prefix' => 'pause_',
			// Plugin textdomain
			'textdomain' => 'pause-adblocker',
			// Custom CSS assets folder
			'css' => '',
			// Custom JS assets folder
			'js' => '',
		) );

	// Prepare array with options
	$options = array(

		// Open tab: Title of the page
		array(
			'type' => 'opentab',
			'name' => __( 'Pause AdBlocker Settings', 'pause-adblocker' ),
		),
		
		// Settings page description
		array(
			'type'    => 'html',
			'content' => '<h3>Options page for Pause Adblocker</p>',
		),

		// Enable / Disable Pause Adblocker
		array(
			'id'      => 'enabled',
			'type'    => 'checkbox',
			'default' => 'off',
			'name'    => __( 'Status', 'pause-adblocker' ),
			'desc'    => __( 'Check if you want to enable plugin', 'pause-adblocker' ),
			'label'   => __( 'Enabled/Disable', 'pause-adblocker' ),
		),
		
		// Heading
		array(
			'id'      => 'heading',
			'type'    => 'text',
			'default' => 'Think!',
			'name'    => __( 'Heading', 'pause-adblocker' ),
			'desc'    => __( 'What you want to display in heading?', 'pause-adblocker' ),
		),

		// Refresh button text
		array(
			'id'      => 'refresh',
			'type'    => 'text',
			'default' => 'Refresh Now',
			'name'    => __( 'Refresh Button', 'pause-adblocker' ),
			'desc'    => __( 'What you want to display in refresh button?', 'pause-adblocker' ),
		),		
		
		// Description
		array(
			'id'      => 'description',
			'type'    => 'textarea',
			'default' => 'Please Pause your Adblocker extension and refresh page. Its important for us to earn something to keep our services live.',
			'rows'    => 10,
			'name'    => __( 'Description', 'pause-adblocker' ),
			'desc'    => __( 'Tell users that they are caught!', 'pause-adblocker' ),
		),

		// Logo for popup
		array(
			'id'      => 'logoimage',
			'type'    => 'media',
			'default' => '' . plugins_url( 'img/logo.png', __FILE__ ) . '',
			'name'    => __( 'Logo', 'pause-adblocker' ),
			'desc'    => __( 'Choose logo image', 'pause-adblocker' ),
		),

		// Heading Color
		array(
			'id'      => 'bghead',
			'type'    => 'color',
			'default' => '#ffffff',
			'name'    => __( 'Heading background', 'pause-adblocker' ),
			'desc'    => __( 'Top heading area Backgruond color', 'pause-adblocker' ),
		),
		
		// Description Color
		array(
			'id'      => 'bgdesc',
			'type'    => 'color',
			'default' => '#ab0205',
			'name'    => __( 'Description background', 'pause-adblocker' ),
			'desc'    => __( 'Bottom Description area Backgruond color', 'pause-adblocker' ),
		),

		// Button Text Color
		array(
			'id'      => 'bgbtntxt',
			'type'    => 'color',
			'default' => '#666666',
			'name'    => __( 'Heading background', 'pause-adblocker' ),
			'desc'    => __( 'Top heading area Backgruond color', 'pause-adblocker' ),
		),
		
		// Button Background Color
		array(
			'id'      => 'bgbtn',
			'type'    => 'color',
			'default' => '#ffffff',
			'name'    => __( 'Description background', 'pause-adblocker' ),
			'desc'    => __( 'Bottom Description area Backgruond color', 'pause-adblocker' ),
		),

		// Custom Styles
		array(
			'id'      => 'customstyles',
			'type'    => 'textarea',
			'default' => '',
			'rows'    => 10,
			'name'    => __( 'Custom Styles', 'pause-adblocker' ),
			'desc'    => __( 'Add Custom CSS Styles', 'pause-adblocker' ),
		),
		
		// Popup Style
		array(
			'id'      => 'popup_style',
			'type'    => 'image_radio',
			'default' => 'popup-1',
			'name'    => __( 'Popup Style', 'pause-adblocker' ),
			'desc'    => __( 'Select Popup Style | More styles and features are coming soon.', 'pause-adblocker' ),
			'options' => array(
				array(
					'value' => 'popup-1',
					'label' => __( 'popup 1', 'pause-adblocker' ),
					'image' => '  ' . plugins_url( 'img/popup-1.png', __FILE__ ) . '  ',
				),
			),
		),

		// Remove Plugin Settings
		array(
			'id'      => 'remove_unistall',
			'type'    => 'checkbox',
			'default' => 'on',
			'name'    => __( 'Remove Settings', 'pause-adblocker' ),
			'desc'    => __( 'Do you want to remove Settings on unistall?', 'pause-adblocker' ),
			'label'   => __( 'yes/no', 'pause-adblocker' ),
		),
		
		// Close tab: Extra fields
		array(
			'type' => 'closetab',
		)
	);

	// Add top-level menu (like Dashboard -> Comments)
	$admin->add_menu( array(
			// Settings page <title>
			'page_title' => __( 'Pause Adblocker Settings', 'pause-adblocker' ),
			// Menu title, will be shown in left dashboard menu
			'menu_title' => __( 'Pause Adblocker', 'pause-adblocker' ),
			// Minimal user capability to access this page
			'capability' => 'manage_options',
			// Unique page slug
			'slug' => 'pause-adblocker-settings',
			// Add here your custom icon url, or use [dashicons](https://developer.wordpress.org/resource/dashicons/)
			// 'icon_url' => admin_url( 'images/wp-logo.png' ),
			'icon_url' => 'dashicons-wordpress',
			// Menu position from 80 to <infinity>, you can use decimals
			'position' => '91.1',
			// Array with options available on this page
			'options' => $options,
		) );
}

// Hook to plugins_loaded
add_action( 'plugins_loaded', 'pause_adblocker_init' );