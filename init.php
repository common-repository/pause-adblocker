<?php
if ( !class_exists( 'pause7' ) ) {

	/**
	 * pause
	 *
	 * @author  Vladimir Anokhin <http://gndev.info/>
	 * @license MIT
	 */
	class pause7 {

		/** @var array Class config */
		var $config = array();

		/**
		 * Constructor
		 *
		 * @param array Class settings
		 */
		function __construct( $args = array() ) {
			// Parse config
			$args = wp_parse_args( $args, array(
					'file'        => '',
					'slug'        => '',
					'prefix'      => '',
					'textdomain'  => '',
					'url'         => '',
					'version'     => '7',
					'options'     => array(),
					'menus'       => array(),
					'pages'       => array(),
					'slugs'       => array(),
					'css'         => 'assets/css',
					'js'          => 'assets/js',
					'views_class' => 'pause7_Views'
				) );
			// Check required settings
			if ( !$args['file'] ) wp_die( 'pause: please specify plugin __FILE__' );
			if ( !$args['slug'] ) $args['slug'] = sanitize_key( plugin_basename( basename( $args['file'] , '.php' ) ) );
			if ( !$args['prefix'] ) $args['prefix'] = 'pause_' . sanitize_key( $args['slug'] ) . '_';
			if ( !$args['textdomain'] ) $args['textdomain'] = sanitize_key( $args['slug'] );
			// Setup config
			$this->config = $args;
			// Register hooks
			add_action( 'admin_menu', array( &$this, 'register' ) );
			add_action( 'admin_init', array( &$this, 'assets' ), 10 );
			add_action( 'admin_init', array( &$this, 'enqueue' ), 20 );
			add_action( 'admin_init', array( &$this, 'defaults' ) );
			add_action( 'admin_init', array( &$this, 'submit' ) );
		}

		/**
		 * Helper to get config
		 *
		 * @param mixed   $option Option ID
		 * @return mixed Option value
		 */
		public function config( $option = false ) {
			if ( $option ) $data = ( isset( $this->config[$option] ) ) ? $this->config[$option] : false;
			else $data = $this->config;
			return $data;
		}

		/**
		 * Register options pages
		 */
		public function register() {
			if ( isset( $this->config['menus'] ) && count( $this->config['menus'] ) )
				foreach ( $this->config['menus'] as $menu ) {
					add_menu_page( $menu['page_title'], $menu['menu_title'], $menu['capability'], $menu['slug'], array( &$this, 'render' ), $menu['icon_url'], $menu['position'] );
				}
			if ( isset( $this->config['pages'] ) && count( $this->config['pages'] ) )
				foreach ( $this->config['pages'] as $page ) {
					add_submenu_page( $page['parent_slug'], $page['page_title'], $page['menu_title'], $page['capability'], $page['slug'], array( &$this, 'render' ) );
				}
		}

		/**
		 * Add top-level menu
		 *
		 * @param array   $args Page config and options
		 */
		public function add_menu( $args ) {
			// Prepare default config
			$args = wp_parse_args( $args, array(
					'page_title'  => __( 'Plugin Settings', $this->config['textdomain'] ),
					'menu_title'  => __( 'Plugin Settings', $this->config['textdomain'] ),
					'capability'  => 'manage_options',
					'slug'        => $this->config['slug'],
					'icon_url'    => '',
					'position'    => '81.' . rand( 0, 99 ),
					'url'         => '',
					'options'     => array()
				) );
			// Define page url
			if ( !$args['url'] ) $args['url'] = admin_url( 'admin.php?page=' . $args['slug'] );
			// Save url to global config
			if ( !$this->config['url'] ) $this->config['url'] = $args['url'];
			// Save options to global config
			if ( is_array( $args['options'] ) && count( $args['options'] ) ) foreach ( $args['options'] as $option ) {
					$this->config['options'][] = $option;
				}
			// Save menu slug to the global config
			$this->config['slugs'][] = $args['slug'];
			// Add page to global config
			$this->config['menus'][$args['slug']] = $args;
		}

		/**
		 * Add sub-menu
		 *
		 * @param array   $args Page config and options
		 */
		public function add_submenu( $args ) {
			// Prepare default config
			$args = wp_parse_args( $args, array(
					'parent_slug' => 'options-general.php',
					'page_title'  => __( 'Plugin Settings', $this->config['textdomain'] ),
					'menu_title'  => __( 'Plugin Settings', $this->config['textdomain'] ),
					'capability'  => 'manage_options',
					'slug'        => $this->config['slug'],
					'url'         => '',
					'options'     => array()
				) );
			// Define page url
			if ( !$args['url'] ) {
				if ( strpos( $args['parent_slug'], '.php' ) !== false && strpos( $args['parent_slug'], '?' ) !== false ) $args['url'] = admin_url( $args['parent_slug'] . '&page=' . $args['slug'] );
				elseif ( strpos( $args['parent_slug'], '.php' ) !== false ) $args['url'] = admin_url( $args['parent_slug'] . '?page=' . $args['slug'] );
				else $args['url'] = ( isset( $this->config['menus'][$args['parent_slug']] ) ) ? admin_url( 'admin.php?page=' . $args['slug'] ) : '';
			}
			// Save url to global config
			if ( !$this->config['url'] ) $this->config['url'] = $args['url'];
			// Save options to global config
			if ( is_array( $args['options'] ) && count( $args['options'] ) && !in_array( $args['slug'], array_keys( (array) $this->config['menus'] ) ) ) foreach ( $args['options'] as $option ) {
					$this->config['options'][] = $option;
				}
			// Save page slug to the global config
			$this->config['slugs'][] = $args['slug'];
			// Add page to global config
			$this->config['pages'][$args['slug']] = $args;
		}

		/**
		 * Display options page
		 */
		public function render() {
			// Prepare page options
			$options = $this->get_page_options();
			// Get current page slug
			$page = sanitize_key( $_GET['page'] );
			// Hook before page output
			do_action( 'pause/page/before' );
			do_action( 'pause/page/' . $page . '/before' );
			echo '<div id="pause-settings" class="wrap">';
			echo call_user_func( array( $this->config['views_class'], 'options_page_tabs' ), $options, $this->config );
			echo call_user_func( array( $this->config['views_class'], 'options_page_notices' ), $options, $this->config );
			echo '<form action="" method="post" id="pause-form">';
			echo call_user_func( array( $this->config['views_class'], 'options_page_panes' ), $options, $this->config );
			echo '<input type="hidden" name="pause_action" value="save" />';
			echo '<input type="hidden" name="pause_nonce" value="' . wp_create_nonce( 'pause' ) . '" />';
			do_action( 'pause/page/form' );
			echo '</form></div>';
			// Hook after page output
			do_action( 'pause/page/after' );
			do_action( 'pause/page/' . $page . '/after' );
		}

		/**
		 * Register class assets
		 */
		public function assets() {
			// Register styles
			wp_register_style(  'pause-' . $this->config['version'], plugins_url( $this->config['css'], $this->config['file'] ) . '/inc/pause.css', false, $this->config['version'], 'all' );
			// Register scripts
			wp_register_script( 'pause-' . $this->config['version'], plugins_url( $this->config['js'],  $this->config['file'] ) . '/inc/pause.js', array( 'jquery', 'jquery-form' ), $this->config['version'], true );
			// Add some l10n to JS
			wp_localize_script( 'pause-' . $this->config['version'], 'pause', array(
					'media_title'  => __( 'Choose file', $this->config['textdomain'] ),
					'media_insert' => __( 'Use selected file', $this->config['textdomain'] )
				) );
			// Hook to add/remove custom files
			do_action( 'pause/assets/register' );
		}

		/**
		 * Enqueue class assets
		 */
		public function enqueue() {
			// Check there is an options page
			if ( !$this->is_pause() ) return;
			// Enqueue styles
			foreach ( array( 'farbtastic', 'pause-' . $this->config['version'] ) as $style ) wp_enqueue_style( $style );
			// Enqueue scripts
			foreach ( array( 'jquery', 'jquery-form', 'farbtastic', 'pause-' . $this->config['version'] ) as $script ) wp_enqueue_script( $script );
			// Hook to add/remove files
			do_action( 'pause/assets/enqueue' );
		}

		/**
		 * Hook to insert default settings
		 */
		public function defaults() {
			// Check defaults isn't set
			if ( get_option( 'pause_defaults_' . $this->config['slug'] ) ) return;
			// Check config options
			if ( isset( $this->config['options'] ) && is_array( $this->config['options'] ) ) {
				// Insert default options
				foreach ( $this->config['options'] as $option ) {
					// Option id and option defaut value is present
					if ( isset( $option['id'] ) && isset( $option['default'] ) ) update_option( $this->config['prefix'] . $option['id'], $option['default'] );
					// Default value isn't set bacause there is an multiple options array
					elseif ( isset( $option['id'] ) && isset( $option['options'] ) && is_array( $option['options'] ) ) {
						$options = array();
						foreach ( $option['options'] as $item ) {
							if ( isset( $item['id'] ) && isset( $item['default'] ) ) $options[$item['id']] = $item['default'];
						}
						update_option( $this->config['prefix'] . $option['id'], $options );
					}
				}
				// Defaults is set
				update_option( 'pause_defaults_' . $this->config['slug'], true );
			}
		}

		/**
		 * Hook to process submitted data
		 */
		public function submit() {
			// Check request
			if ( empty( $_REQUEST['pause_action'] ) || empty( $_REQUEST['page'] ) ) return;
			// Check nonce
			if ( empty( $_REQUEST['pause_nonce'] ) || !wp_verify_nonce( $_REQUEST['pause_nonce'], 'pause' ) ) return;
			// Check page
			if ( !$this->is_pause() ) return;
			// Prepare page slug
			$page = sanitize_key( $_GET['page'] );
			// Submit hooks
			do_action( 'pause/submit', $this );
			do_action( 'pause/submit/' . $page, $this );
			// Parse incoming data
			$action  = sanitize_key( $_REQUEST['pause_action'] );
			$request = ( isset( $_REQUEST['pause'] ) ) ? (array) $_REQUEST['pause'] : array();
			// Run actions
			// Save options
			if ( $action === 'save' ) {
				// Loop through current page options
				foreach ( (array) $this->get_page_options() as $option ) {
					// Option must have an ID
					if ( !isset( $option['id'] ) ) continue;
					// Prepare value
					$val = ( isset( $request[$option['id']] ) ) ? $request[$option['id']] : '';
					// Save options value
					update_option( $this->config['prefix'] . $option['id'], $val );
				}
				// Save hooks
				do_action( 'pause/save', $this );
				do_action( 'pause/save/' . $page, $this );
				// Set message
				$message = 1;
			}
			// Reset options
			elseif ( $action === 'reset' ) {
				// Loop through current page options
				foreach ( (array) $this->get_page_options() as $option ) {
					// Option must have an ID
					if ( !isset( $option['id'] ) ) continue;
					// Reset option with multiple values
					if ( !isset( $option['default'] ) && isset( $option['options'] ) ) {
						// Prepare variable for default value
						$option['default'] = array();
						// Loop through multiple values
						foreach ( (array) $option['options'] as $item ) {
							if ( isset( $item['id'] ) && isset( $item['default'] ) ) $option['default'][$item['id']] = $item['default'];
						}
					}
					// Save option value
					if ( isset( $option['default'] ) ) update_option( $this->config['prefix'] . $option['id'], $option['default'] );
				}
				// Reset hooks
				do_action( 'pause/reset', $this );
				do_action( 'pause/reset/' . $page, $this );
				// Set message
				$message = 2;
			}
			// Other actions
			else {
				// Set message var to "Something went wrong..."
				$message = 3;
			}
			// Go to page with specified message
			wp_redirect( $this->get_page_url() . '&message=' . $message );
			exit;
		}

		/**
		 * Get current page data
		 */
		public function get_page() {
			$slug = sanitize_key( $_REQUEST['page'] );
			// This page is added to the top-level menus
			if ( in_array( $slug, array_keys( (array) $this->config['menus'] ) ) ) return $this->config['menus'][$slug];
			// This page is added to the sub-menus
			else if ( in_array( $slug, array_keys( (array) $this->config['pages'] ) ) ) return $this->config['pages'][$slug];
				// Return an empty array by default
				return array();
		}

		/**
		 * Get current page options
		 */
		public function get_page_options() {
			// Get current page data
			$page = $this->get_page();
			// Prepare array for options
			$options = array();
			// This page have some options
			if ( isset( $page['options'] ) && is_array( $page['options'] ) )
				// Loop through page options
				foreach ( $page['options'] as $option ) {
					// Add option to resulting array
					$options[] = $option;
				}
			// Return options
			return $options;
		}

		/**
		 * Get current page URL
		 *
		 * @param mixed   $slug Page slug (optional). This parameter can be automatically retrieved from $_GET['page']
		 * @return string  Page URL
		 */
		public function get_page_url( $slug = false ) {
			// Get slug from $_GET['page']
			if ( !$slug && isset( $_REQUEST['page'] ) ) $slug = sanitize_key( $_REQUEST['page'] );
			// Serach for URL in registered top-level menus
			if ( isset( $this->config['menus'][$slug] ) && isset( $this->config['menus'][$slug]['url'] ) ) return $this->config['menus'][$slug]['url'];
			// Serach for URL in registered sub-menus
			elseif ( isset( $this->config['pages'][$slug] ) && isset( $this->config['pages'][$slug]['url'] ) ) return $this->config['pages'][$slug]['url'];
			// Return empty string if URL doesn't found
			return '';
		}

		/**
		 * Conditional check for pause options page
		 *
		 * @return boolean true/false - there is an page created by pause
		 */
		public function is_pause() {
			return isset( $_GET['page'] ) && in_array( $_GET['page'], $this->config['slugs'] );
		}

	}
}

if ( !class_exists( 'pause7_Views' ) ) {

	/**
	 * pause Views
	 *
	 * no comments, just some markup
	 */
	class pause7_Views {

		function __construct() {}

		public static function notice( $msg = '', $class = '' ) {
			return '<div class="pause-notice ' . $class . '"><p>' . $msg . '</p></div>';
		}

		public static function type_opentab( $field, $config ) {
			return '<div class="pause-pane"><h3 class="hide-if-js pause-no-js-tab">' . $field['name'] . '</h3><table class="form-table">';
		}

		public static function type_closetab( $field, $config ) {
			$field = wp_parse_args( $field, array( 'actions' => true ) );
			$return = array();
			$return[] = '</table>';
			if ( $field['actions'] ) $return[] = '<div class="pause-actions-bar"><input type="submit" value="' . __( 'Save changes', $config['textdomain'] ) . '" class="pause-submit button-primary" /><span class="pause-spin"><img src="' . admin_url( 'images/wpspin_light.gif' ) . '" alt="" /> ' . __( 'Saving', $config['textdomain'] ) . '&hellip;</span><span class="pause-success-tip"><img src="' . admin_url( 'images/yes.png' ) . '" alt="" /> ' . __( 'Saved', $config['textdomain'] ) . '</span><a href="' . $_SERVER["REQUEST_URI"] . '&amp;pause_action=reset&amp;pause_nonce=' . wp_create_nonce( 'pause' ) . '" class="pause-reset button alignright" title="' . esc_attr( __( 'This action will delete all your settings. Are you sure? This action cannot be undone!', $config['textdomain'] ) ) . '">' . __( 'Restore default settings', $config['textdomain'] ) . '</a></div>';
			$return[] = '</div>';
			return implode( '', $return );
		}

		public static function type_text( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name'    => __( 'Text field', $config['textdomain'] ),
					'id'      => '',
					'desc'    => ''
				) );
			return '<tr><th scope="row"><label for="pause-field-' . $field['id'] . '">' . $field['name'] . '</label></th><td><input type="text" value="' . get_option( $config['prefix'] . $field['id'] ) . '" name="pause[' . $field['id'] . ']" id="pause-field-' . $field['id'] . '" class="widefat" /><p class="description">' . $field['desc'] . '</p></td></tr>';
		}

		public static function type_textarea( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name'    => __( 'Textarea field', $config['textdomain'] ),
					'id'      => '',
					'desc'    => '',
					'rows'    => 10
				) );
			return '<tr><th scope="row"><label for="pause-field-' . $field['id'] . '">' . $field['name'] . '</label></th><td><textarea name="pause[' . $field['id'] . ']" id="pause-field-' . $field['id'] . '" class="regular-text widefat" rows="' . $field['rows'] . '">' . esc_textarea( stripslashes( get_option( $config['prefix'] . $field['id'] ) ) ) . '</textarea><p class="description">' . $field['desc'] . '</p></td></tr>';
		}

		public static function type_checkbox( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name'  => __( 'Checkbox', $config['textdomain'] ),
					'id'    => '',
					'desc'  => '',
					'label' => __( 'Label', $config['textdomain'] )
				) );
			$checked = ( get_option( $config['prefix'] . $field['id'] ) === 'on' ) ? ' checked="checked"' : '';
			return '<tr><th scope="row"><label for="pause-field-' . $field['id'] . '">' . $field['name'] . '</label></th><td><label><input type="checkbox" name="pause[' . $field['id'] . ']" id="pause-field-' . $field['id'] . '"' . $checked . ' />&nbsp;&nbsp;' . $field['label'] . '</label><span class="description">' . $field['desc'] . '</span></td></tr>';
		}

		public static function type_select( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name'     => __( 'Select', $config['textdomain'] ),
					'id'       => '',
					'desc'     => '',
					'options'  => array(),
					'multiple' => false,
					'size'     => 1
				) );
			$options = array();
			$value = get_option( $config['prefix'] . $field['id'] );
			if ( !$value ) $value = array();
			if ( !is_array( $value ) ) $value = array( $value );
			$name = ( $field['multiple'] ) ? 'pause[' . $field['id'] . '][]' : 'pause[' . $field['id'] . ']';
			$field['multiple'] = ( $field['multiple'] ) ? ' multiple="multiple"' : '';
			$field['size'] = ( $field['size'] > 1 ) ? ' size="' . $field['size'] . '"' : '';
			foreach ( $field['options'] as $option ) {
				$selected = ( in_array( $option['value'], $value ) ) ? ' selected="selected"' : '';
				$options[] = '<option value="' . $option['value'] . '"' . $selected . '>' . $option['label'] . '</option>';
			}
			return '<tr><th scope="row"><label for="pause-field-' . $field['id'] . '">' . $field['name'] . '</label></th><td><select name="' . $name . '" class="widefat" id="pause-field-' . $field['id'] . '"' . $field['size'] . $field['multiple'] . '>' . implode( '', $options ) . '</select><span class="description">' . $field['desc'] . '</span></td></tr>';
		}

		public static function type_radio( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name'    => __( 'Checkbox group', $config['textdomain'] ),
					'options' => array(),
					'id'      => '',
					'desc'    => ''
				) );
			$group = array();
			$value = get_option( $config['prefix'] . $field['id'] );
			if ( is_array( $field['options'] ) ) foreach ( $field['options'] as $single ) {
					$checked = ( $single['value'] === $value ) ? ' checked="checked"' : '';
					$group[] = '<label for="pause-field-' . $field['id'] . '-' . $single['value'] . '"><input type="radio" name="pause[' . $field['id'] . ']" id="pause-field-' . $field['id'] . '-' . $single['value'] . '" value="' . $single['value'] . '"' . $checked . ' />&nbsp;&nbsp;' . $single['label'] . '</label><br/>';
				}
			return '<tr><th scope="row">' . $field['name'] . '</th><td>' . implode( '', $group ) . '<span class="description">' . $field['desc'] . '</span></td></tr>';
		}

		public static function type_number( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name'  => __( 'Text field', $config['textdomain'] ),
					'id'    => '',
					'desc'  => '',
					'min'   => 0,
					'max'   => 100,
					'step'  => 1
				) );
			return '<tr><th scope="row"><label for="pause-field-' . $field['id'] . '">' . $field['name'] . '</label></th><td><input type="number" value="' . get_option( $config['prefix'] . $field['id'] ) . '" name="pause[' . $field['id'] . ']" id="pause-field-' . $field['id'] . '" class="widefat" min="' . (string) $field['min'] . '" max="' . (string) $field['max'] . '" step="' . (string) $field['step'] . '" /><p class="description">' . $field['desc'] . '</p></td></tr>';
		}

		public static function type_media( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name'  => __( 'Media', $config['textdomain'] ),
					'id'    => '',
					'desc'  => ''
				) );
			if ( function_exists( 'wp_enqueue_media' ) ) wp_enqueue_media();
			return '<tr class="pause-media"><th scope="row"><label for="pause-field-' . $field['id'] . '">' . $field['name'] . '</label></th><td><input type="text" value="' . get_option( $config['prefix'] . $field['id'] ) . '" name="pause[' . $field['id'] . ']" id="pause-field-' . $field['id'] . '" class="regular-text pause-media-value" /> <a href="javascript:;" class="button pause-media-button hide-if-no-js"><img src="' . admin_url( 'images/media-button.png' ) . '" alt="" /> ' . __( 'Open media library', $config['textdomain'] ) . '</a><p class="description">' . $field['desc'] . '</p></td></tr>';
		}

		public static function type_color( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name'  => __( 'Color picker', $config['textdomain'] ),
					'id'    => '',
					'desc'  => ''
				) );
			/////////////////////////////////////////////////////////////////////////////////
			// DON'T PANIC - IT's NOT A MALWARE
			// this is base64-encoded image for color picker =)
			/////////////////////////////////////////////////////////////////////////////////
			return '<tr><th scope="row"><label for="pause-field-' . $field['id'] . '">' . $field['name'] . '</label></th><td><div class="pause-color-picker"><input type="text" value="' . get_option( $config['prefix'] . $field['id'] ) . '" name="pause[' . $field['id'] . ']" id="pause-field-' . $field['id'] . '" class="regular-text pause-color-picker-value" /><span class="pause-color-picker-wheel"></span> <a href="javascript:;" class="button pause-color-picker-button hide-if-no-js"><img alt="" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAArFJREFUeNqkU01PE1EUPUOn004/hpmmhH6AFA2KRojxIzFoooK4calLXbhyTeLeEFe69Qe4wJiwcKMLo8Yag9EEIgsS0VpKsdOZ0hbaaUtp6cx0vAOkgbjkJefNnXn3nHvefW8Yy7JwlMHa06uXDM566SUB1JbQwxcx7tpwjEJ1hKDRQju6bhg9SzrCcRfOFE2cxib8uGZN7gkckLshnQw8kG7eGREGJ45x7pCARhdaSr1ajacz5TfKbbPEvqDMz4cc7EeTbKz/Uezcs8tcwxSwvAwk54GyDy7vUKDnysVA9/mrx9ee/uw1FYfN+2jTuuzJ4hFmwsLUwPiTMY7PCdheBPyUE40AkSjg6gb+6OCqfmHg4fUxyxeaaoELdxy0grjLhm+NsJLuAyeTIq15g4DbDwgCUO8FPCKwLoH1+3zsBXOk9CV7l6jPdx0U3ZhgpUvBdSYFzSfCDBO5j6rHBggxctGHdjCCmiggX6aenJCCWWCi42BVx3DT7XcVoUCABNHhhSiK8HgiYLwBNBke2hag8UClDTQ5t2sV+nBHIGXAqaKKEizQblHZR4BzwcHzKDFExh7s79skloLh7AhQf9I/6iv9Tr/HWUCTPOyg104Es9vlMqGwA5QaQLUFqGrdSEBPd05BLiL+Vllo5Cj+CwN50lXhoBhYo+qqA8hTKZnsJ6l6PKU0MqjHOw6sFGYWpV/3gpFRPiREnQyRm1TdQ7BHkx4VEimS6VS9qifVtNLG5kzHAW1ebmmt6U9zHworWs3IkW6GyGu2A0KGkKXMlS3NWJifKxh6fZrOVj58E03MNjIb+Fp4/Th8ajvaN+TlRSnKQjdQKZaN7PdkQ32XUIy8TmRx9v+rbA/dnDWWc9/kxPv7spQdp0swiFrAtpHG7604sq0ZtGPyQQpz1N/5nwADAEUXDAYgnuAXAAAAAElFTkSuQmCC" /> ' . __( 'Pick a color', $config['textdomain'] ) . '</a></div><span class="description">' . $field['desc'] . '</span></td></tr>';
		}

		public static function type_checkbox_group( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name'    => __( 'Checkbox group', $config['textdomain'] ),
					'options' => array(),
					'id'      => '',
					'desc'    => ''
				) );
			$group = array();
			$value = (array) get_option( $config['prefix'] . $field['id'] );
			if ( is_array( $field['options'] ) ) foreach ( $field['options'] as $single ) {
					$checked = ( isset( $value[$single['id']] ) && $value[$single['id']] === 'on' ) ? ' checked="checked"' : '';
					$group[] = '<label for="pause-field-' . $field['id'] . '-' . $single['id'] . '"><input type="checkbox" name="pause[' . $field['id'] . '][' . $single['id'] . ']" id="pause-field-' . $field['id'] . '-' . $single['id'] . '"' . $checked . ' />&nbsp;&nbsp;' . $single['label'] . '</label><br/>';
				}
			return '<tr><th scope="row">' . $field['name'] . '</th><td class="pause-checkbox-group">' . implode( '', $group ) . '<span class="description">' . $field['desc'] . '</span></td></tr>';
		}

		public static function type_html( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'content' => __( 'HTML field', $config['textdomain'] )
				) );
			return '<tr><td colspan="2">' . $field['content'] . '</td></tr>';
		}

		public static function type_title( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name' => __( 'Title field', $config['textdomain'] )
				) );
			return '<tr><td colspan="2"><h3 class="pause-title-field">' . $field['name'] . '</h3></td></tr>';
		}

		public static function type_image_radio( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name'    => __( 'Image radio', $config['textdomain'] ),
					'id'      => '',
					'desc'    => '',
					'options' => array()
				) );
			$options = array();
			foreach( $field['options'] as $option ) {
				$label = ( isset( $option['label'] ) ) ? $option['label'] : '';
				$options[] = '<a href="javascript:;" data-value="' . $option['value'] . '" title="' . $label . '"><img src="' . $option['image'] . '" alt="" /></a>';
			}
			return '<tr><th scope="row">' . $field['name'] . '</th><td><div class="pause-image-radio">' . implode( '', $options ) . '<input type="hidden" value="' . get_option( $config['prefix'] . $field['id'] ) . '" name="pause[' . $field['id'] . ']" id="pause-field-' . $field['id'] . '" /></div><p class="description">' . $field['desc'] . '</p></td></tr>';
		}

		public static function type_size( $field, $config ) {
			$field = wp_parse_args( $field, array(
					'name'  => __( 'Size', $config['textdomain'] ),
					'id'    => '',
					'desc'  => '',
					'units' => array( 'px', 'em', '%' ),
					'min'   => 0,
					'max'   => 200,
					'step'  => 10
				) );
			$value = get_option( $config['prefix'] . $field['id'] );
			if ( !is_array( $value ) || count( $value ) !== 2 ) $value = array( 0 => '0', 1 => 'px' );
			$units = array();
			foreach( $field['units'] as $unit ) {
				$units[] = '<option value="' . $unit . '">' . $unit . '</option>';
			}
			return '<tr><th scope="row"><label for="pause-field-' . $field['id'] . '-0">' . $field['name'] . '</label></th><td><input type="number" value="' . $value[0] . '" name="pause[' . $field['id'] . '][0]" id="pause-field-' . $field['id'] . '-0" class="regular-text" min="' . (string) $field['min'] . '" max="' . (string) $field['max'] . '" step="' . (string) $field['step'] . '" /> <select name="pause[' . $field['id'] . '][1]" id="pause-field-' . $field['id'] . '-1">' . str_replace( 'value="' . $value[1] . '"', 'value="' . $value[1] . '" selected="selected"', implode( '', $units ) ) . '</select><p class="description">' . $field['desc'] . '</p></td></tr>';
		}

		/**
		 * Display options page tabs
		 */
		public static function options_page_tabs( $options, $config ) {
			// Declare tabs array
			$tabs = array();
			// Loop through options
			foreach ( (array) $options as $option ) {
				// Current option is opentab
				if ( isset( $option['type'] ) && isset( $option['name'] ) && $option['type'] === 'opentab' ) $tabs[] = '<span class="nav-tab">' . $option['name'] . '</span>';
			}
			// Return resulting markup
			return ( count( $tabs ) ) ? '<div id="icon-options-general" class="icon32 hide-if-no-js"><br /></div><h2 id="pause-tabs" class="nav-tab-wrapper hide-if-no-js">' . implode( '', $tabs ) . '</h2>' : '';
		}

		/**
		 * Display options page notices
		 */
		public static function options_page_notices( $options, $config ) {
			// Setup messsages
			$msgs = apply_filters( 'pause/page/notices', array(
					__( 'For full functionality of this page it is reccomended to enable javascript.', $config['textdomain'] ),
					__( 'Settings saved successfully', $config['textdomain'] ),
					__( 'Settings reseted successfully', $config['textdomain'] ),
					__( 'Something went wrong. Please try again later', $config['textdomain'] ),
				) );
			// Prepare output variable
			$output = array();
			// Get current message
			$message = ( isset( $_GET['message'] ) && is_numeric( $_GET['message'] ) ) ? intval( sanitize_key( $_GET['message'] ) ) : 0;
			// Add no-js notice (will be hidden for js-enabled browsers)
			$output[] = self::notice( '<a href="http://enable-javascript.com/" target="_blank">' . $msgs[0] . '</a>.', 'error hide-if-js' );
			// Show notice
			if ( $message !== 0 ) $output[] = self::notice( $msgs[$message], 'updated' );
			// Return resulting markup
			return implode( '', $output );
		}

		/**
		 * Display options panes
		 */
		public static function options_page_panes( $options, $config ) {
			// Declare panes array
			$panes = array();
			// Loop through options
			foreach ( $options as $option ) {
				// Check option type definition
				if ( !isset( $option['type'] ) ) continue;
				// Try to call option from external source
				elseif ( isset( $option['callback'] ) && is_callable( $option['callback'] ) ) $panes[] = call_user_func( $option['callback'], $option, $config );
				// Try to call option from built-in class pauseX_Views
				elseif ( is_callable( array( $config['views_class'], 'type_' . $option['type'] ) ) ) $panes[] = call_user_func( array( $config['views_class'], 'type_' . $option['type'] ), $option, $config );
				// Show error message
				else $panes[] = call_user_func( array( $config['views_class'], 'notice' ), 'pause: ' . sprintf( __( 'option type %s is not callable', $config['textdomain'] ), '<b>' . $option['type'] . '</b>' ), 'error' );
			}
			// Return resulting markup
			return implode( '', $panes );
		}
	}
}
