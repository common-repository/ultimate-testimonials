<?php

/**
 * Testimonial options class.
 *
 * Handles all the functionality for testimonial options.
 *
 * @since 0.1.0
 */
class WPGO_Testimonial_Options {

	protected $_plugin_options_page;
	protected $_args; // handle to the plugin options page

	/**
	 * Plugin options class constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {

		add_action( 'admin_init', array( &$this, 'register_settings' ) );
		add_action( 'admin_menu', array( &$this, 'add_options_page' ) );
	}

	/**
	 * Register testimonial options with Settings API.
	 *
	 * @since 0.1.0
	 */
	public function register_settings() {

		/* Register plugin options settings for all tabs. */
		register_setting(
			'wpgo_testimonial_options_group',
			'wpgo_testimonial_options',
			array( $this, 'sanitize_options' )
		);
	}

	/**
	 * Sanitize plugin options.
	 *
	 * Get rid of the local license key status option when adding a new one
	 *
	 * @since 0.1.0
	 */
	public function sanitize_options( $input ) {

		//$input['txtar_keywords'] = wp_filter_nohtml_kses( $input['txtar_keywords'] );
		//$input['txt_exclude']    = wp_filter_nohtml_kses( $input['txt_exclude'] );

		/* Sanitize testimonial options via filter hook. */
		// @todo maybe do this in a future version - if so need to setup a new hook
		// this allows you to sanitize options via another class
		//return WPGO_Content_Censor_Hooks::wpgo_sanitize_plugin_options( $input );
		return $input;
	}

	/**
	 * Get plugin option default settings.
	 *
	 * @since 0.1.0
	 */
	public static function get_default_plugin_options() {

		$defaults = array();

		// setup an array to store list of checkboxes that have a checkbox default set to 1
		$defaults["default_on_checkboxes"] = array();

		$defaults["chk_post_content"] = "0";

		return $defaults;
	}

	/**
	 * Get current plugin options.
	 *
	 * Merges plugin options with the defaults to ensure any gaps are filled.
	 * i.e. when adding new options.
	 *
	 * @since 0.1.0
	 */
	public static function get_options() {

		$options = get_option( 'wpgo_testimonial_options' );
		$defaults = self::get_default_plugin_options();

		// store the OFF checkboxes array
		$default_on_checkboxes_arr = $defaults["default_on_checkboxes"];

		// remove the OFF checkboxes array from the main defaults array
		unset($defaults["default_on_checkboxes"]);

		if( is_array($options) ) {
			// merge OFF checkboxes into main options array to add entries for empty checkboxes
			$options = array_merge( $default_on_checkboxes_arr, $options );
		}

		return wp_parse_args(
			$options,
			$defaults
		);

		//return wp_parse_args(
		//	get_option( WPGO_CONTENT_CENSOR_OPTIONS_DB_NAME ),
		//	self::get_default_plugin_options()
		//);
	}

	/**
	 * Display plugin options page.
	 *
	 * @since 0.1.0
	 */
	public function render_plugin_form() {
		?>
		<div class="wrap">
			<h2 class="plugin-title">Testimonial Settings</h2>

			<!-- Start Main Form -->
			<form id="plugin-options-form" method="post" action="options.php">
				<?php settings_fields( 'wpgo_testimonial_options_group' ); ?>
				<?php $options = self::get_options(); ?>

				<table class="form-table">

					<tr valign="top">
						<th scope="row">Testimonial Archives</th>
						<td>
							<label><input name="wpgo_testimonial_options[chk_post_content]" type="checkbox" value="1" <?php if ( isset( $options['chk_post_content'] ) ) {
									checked( '1', $options['chk_post_content'] );
								} ?>> Remove Archive Title Links</label>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>

			</form>
			<!-- main form closing tag -->
		</div><!-- .wrap -->
		<?php
	}

	/**
	 * Register plugin options page, and enqueue scripts/styles.
	 *
	 * @since 0.1.0
	 */
	public function add_options_page() {

		add_submenu_page(
			'edit.php?post_type=wpgo_testimonial',
			__( 'Testimonial Settings', 'wpgo-ultimate-testimonials' ),
			__( 'Settings', 'wpgo-ultimate-testimonials' ),
			'manage_options',
			'wpgo-testimonial-settings-page',
			array( &$this, 'render_plugin_form' )
		);
	}
}