<?php

/**
 * Customize Global Testimonial Templates Class
 *
 * Manages global testimonial layout via the theme customizer (which can be overridden on a per testimonial basis).
 *
 * @todo Separate out into classes for customizer and post editor controls.
 *
 * @since 0.2.0
 *
 */

class WPGO_Customize_Testimonial_Templates {

	private $template_array;
	protected $module_roots;
	protected static $customizer_defaults = array(
		'wpgo_drp_default_tml_template' => 'default',
	);

	/**
	 * Class constructor.
	 *
	 * @since 0.2.0
	 */
	public function __construct($module_roots) {

		$this->module_roots = $module_roots;

		$this->template_array = array(
			'default'   => __( 'Default', 'wpgothemes' ),
			'blog-style' => __( 'Blog Style', 'wpgothemes' ),
			'legacy' => __( 'Legacy', 'wpgothemes' ),
		);

		/* Priority set to 12 so the callback fires AFTER the supported features have been specified in the extended WPGO_Theme_Framework class.
		 * This allows a call in a child theme such as add_action( 'after_setup_theme', 'child_framework_features', 11 ) to easily remove/redefine added features.
		 */
		add_action( 'after_setup_theme', array( &$this, 'customizer_supported_features' ), 12 );
		add_action( 'admin_init', array( &$this, 'tml_meta_box_init' ) );
		add_action( 'admin_enqueue_scripts', array( &$this, 'enqueue_post_editor_scripts' ) );
	}

	// scripts to be added to the testimonial post editor
	public function enqueue_post_editor_scripts($hook) {

		// only load these scripts on the post editor
		// @todo these should also only be loaded on allowed post types as specified in WPGO_Theme_Framework::spt()
		if ( 'post-new.php' != $hook && 'post.php' != $hook )
			return;

		global $post;

		$wpgo_override_glb_tml_template = get_post_meta( $post->ID, '_wpgo_override_glb_tml_template', true );

		if ( 'wpgo_testimonial' === $post->post_type ) {

			// Enqueue admin post editor scripts/styles
			wp_register_script( 'wpgo-tml-editor-cs-js', $this->module_roots['uri'] . '/lib/js/wpgo-tml-editor-metabox.js', array( 'jquery' ), false, true );
			wp_enqueue_script( 'wpgo-tml-editor-cs-js' );

			wp_register_style( 'wpgo-tml-editor-css', $this->module_roots['uri'] . '/lib/css/wpgo-tml-editor.css' );
			wp_enqueue_style( 'wpgo-tml-editor-css' );

			// pass PHP variables to JS
			$data = array(
				//'post_type_label' => $post_type_label,
				'wpgo_override_glb_tml_tmp' => $wpgo_override_glb_tml_template,
				//'wpgo_override_glb_ls_tmp' => $wpgo_override_glb_ls_tmp,
				//'wpgo_override_glb_col_tmp' => $wpgo_override_glb_col_tmp
			);
			wp_localize_script( 'wpgo-tml-editor-cs-js', 'customizer_preview_tml_js', $data );
		}
	}

	/**
	 * Register column layouts and setup defaults.
	 *
	 * @since 0.2.0
	 */
	public function customizer_supported_features() {

		/* Add global testimonial template drop down to the theme customizer. */
		add_filter( 'wpgo_default_customizer_theme_options', array( &$this, 'customizer_testimonial_template_defaults' ) );
		add_action( 'customize_register', array( &$this, 'customizer_testimonial_templates' ) );
	}

	/**
	 * Customizer testimonial template defaults.
	 *
	 * @since 0.1.0
	 */
	public function customizer_testimonial_template_defaults( $defaults ) {

		$defaults['wpgo_drp_default_tml_template'] = 'default';

		return $defaults;
	}

	/**
	 * Add a select box drop down to theme customizer to control global testimonial templates.
	 *
	 * @since 0.1.0
	 *
	 * @param WP_Customize_Manager $wp_customize Customizer object.
	 *
	 * @return void
	 */
	public function customizer_testimonial_templates( $wp_customize ) {

		$wp_customize->add_section( 'wpgo_tml_templates', array(
			'title'    => __( 'Testimonial Templates', 'wpgothemes' ),
			'description' => 'Global template used for all testimonials',
			//'priority' => 10,
			'capability' => 'edit_theme_options',
		) );

		$wp_customize->add_setting(
			'ultimate_tml_customize_options[wpgo_drp_default_tml_template]',
			array(
				'default' => self::$customizer_defaults['wpgo_drp_default_tml_template'],
				'type'    => 'option',
				'capability' => 'edit_theme_options',
				'sanitize_callback' => array( &$this, 'sanitize_drp_default_layout' ),
		) );
		$wp_customize->add_control(
			'ultimate_tml_customize_options[wpgo_drp_default_tml_template]',
			array(
				'label'   => __( 'Select Testimonial Template', 'wpgothemes' ),
				'priority' => 1,
				'section' => 'wpgo_tml_templates',
				'type'    => 'select',
				'description' => 'Override on individual testimonials',
				'choices' => $this->template_array,
			)
		);
	}

	/**
	 * Meta box functionality for adding the meta boxes and saving the data.
	 *
	 * @since 0.1.0
	 */
	public function tml_meta_box_init() {

		add_meta_box( 'wpgo-tml-display-options-meta', __( 'Testimonial Templates', 'wpgothemes' ), array( &$this, 'tml_meta_box_display_options' ), 'wpgo_testimonial', 'side', 'default', array( 'type' => 'wpgo_testimonial' ) );

		/* Save meta box data. */
		add_action( 'save_post', array( &$this, 'tml_display_options_save_meta_box' ) );
	}

	public function sanitize_checkbox( $input ) {
		return $input == 1 ? 1 : '';
	}

	public function sanitize_radio_choices( $input, $setting ) {
		global $wp_customize;

		$control = $wp_customize->get_control( $setting->id );

		if ( array_key_exists( $input, $control->choices ) ) {
			return $input;
		} else {
			return $setting->default;
		}
	}

	/**
	 * Display the testimonial meta box on editor screen.
	 *
	 * @since 0.1.0
	 */
	public function tml_meta_box_display_options( $post, $args ) {

		$wpgo_tml_templates_drp = $this->get_value( 'wpgo_tml_templates_drp', $post, $args );

		/* Only show the hide title checkbox, and display featured image controls on supported post types. */
		//$page_type = $args['args']['type'];

		// get post type singular name as some post type names are not suitable for direct output. e.g. 'wpgo_testimonial'
		//$obj = get_post_type_object( $page_type );
		//$page_type_label = strtolower($obj->labels->singular_name);

		//if ( in_array( $page_type, array_keys( WPGO_Theme_Framework::spt() ) ) ) {
		?>

		<p style="margin:7px 0 3px 0;"><?php _e( 'Select template for this testimonial only', 'wpgothemes' ); ?></p>
		<label class="screen-reader-text" for="wpgo_tml_templates_drp"><?php _e( 'Select Template (this testimonial only)', 'wpgothemes' ); ?></label>
		<select name='wpgo_tml_templates_drp' class='widefat'>
			<option	value='none' <?php selected( 'none', $wpgo_tml_templates_drp ); ?>><?php _e( 'Use Global Template (default)', 'wpgothemes' ); ?></option>
			<option	value='default' <?php selected( 'default', $wpgo_tml_templates_drp ); ?>><?php _e( 'Default', 'wpgothemes' ); ?></option>
			<option	value='blog-style' <?php selected( 'blog-style', $wpgo_tml_templates_drp ); ?>><?php _e( 'Blog Style', 'wpgothemes' ); ?></option>
			<option	value='legacy' <?php selected( 'legacy', $wpgo_tml_templates_drp ); ?>><?php _e( 'Legacy', 'wpgothemes' ); ?></option>
		</select>
		<?php
	}

	/**
	 * Saves meta box settings.
	 *
	 * @since 0.1.0
	 */
	public function tml_display_options_save_meta_box( $post_id ) {

		/* The 'save_post' action hook seems to be triggered when adding new posts/pages so check for an empty $_POST array. */
		if ( empty( $_POST ) ) {
			return;
		}

		//global $typenow;

		// return if we're not on an allowed post type
		//if ( !in_array($typenow, array_keys( WPGO_Theme_Framework::spt() ) ) )
		//	return;

		/* Process form data if $_POST is set */
		/* Save the meta box data as post meta, using the post ID as a unique prefix */

		if ( isset( $_POST['wpgo_tml_templates_drp'] ) ) {
			update_post_meta( $post_id, '_wpgo_tml_templates_drp', esc_attr( $_POST['wpgo_tml_templates_drp'] ) );
		}
	}

	public function sanitize_drp_default_layout( $input ) {
		return array_key_exists( $input, $this->template_array ) ? $input : '';
	}

	public function sanitize_num_txt( $input ) {
		$input = sanitize_text_field( $input ); // strip tags etc.
		return (int) $input; // cast to integer and return
	}

	private function get_value( $meta_key, $post, $args ) {

		// Get post meta value.
		$value = get_post_meta( $post->ID, '_' . $meta_key, true );

		// If meta empty get value via filter (if filter not defined return the empty value). otherwise, return found meta value.
		return empty( $value ) ? apply_filters( $meta_key . '_' . $post->post_type . '_default', $value, $post, $args ) : $value;
	}

	/**
	 * Get a specific customizer theme option.
	 *
	 * If no customizer theme option exists then use default.
	 *
	 * @since 0.1.0
	 */
	public static function get_option( $opt ) {

		$options = self::get_customizer_options();
		return isset($options[$opt]) ? $options[$opt] : false;
	}

	/**
	 * Get current customizer theme options.
	 *
	 * Merges customizer theme options with the defaults to ensure any gaps are filled.
	 * i.e. when adding new options.
	 *
	 * @since 0.1.0
	 */
	public static function get_customizer_options() {

		return wp_parse_args(
			get_option( 'ultimate_tml_customize_options' ),
			self::$customizer_defaults
		);
	}
}