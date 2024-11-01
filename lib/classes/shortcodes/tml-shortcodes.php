<?php

/**
 * Testimonial shortcodes class.
 *
 * @since 0.1.0
 */
class WPGO_Testimonial_Shortcodes {

	// @todo load the other TML shortcode CSS file if shortcode used on the page. ATM it is always loaded!
	private $_success;
	protected $module_roots;

	/**
	 * Registers the framework shortcodes, and allows them to be used in widgets.
	 *
	 * @since 0.1.0
	 */
	public function __construct($module_roots) {

		$this->module_roots = $module_roots;

	    // Initialize class properties.
		$this->_success = array();

		// @todo Split these shortcodes into separate classes?
		add_shortcode( 'tml', array( &$this, 'tml_shortcode' ) );
		add_shortcode( 'tml-submit', array( &$this, 'tml_submit_shortcode' ) );

		add_action( 'init', array( &$this, 'register_scripts' ) );
		add_action( 'init', array( &$this, 'process_form' ) );
		add_action( 'wp_footer', array( &$this, 'enqueue_tml_form_scripts' ) );
	}

	public function process_form() {

		if ( isset( $_POST['tml-submission-name'] ) && isset( $_POST['tml_submission_register_nonce'] ) && wp_verify_nonce( $_POST['tml_submission_register_nonce'], 'tml_submission_register_nonce' ) ) { // input var okay.

			$name		= $_POST['tml-submission-name'];
			$image		= $_FILES['tml-submission-image'];
			$company 	= $_POST['tml-submission-company'];
			$company_url = $_POST['tml-submission-company-url'];
			$title		= $_POST['tml-submission-title'];
			$content 	= $_POST['tml-submission-content'];
			$rating 	= $_POST['tml-submission-rating'];
			$status 	= $_POST['tml_published_status'];

			if ( '' === $name ) {
				// name is required
				$this->testimonial_form_errors()->add( 'empty_name', __( 'Please enter your name' ) );
			}
			if ( '' === $content ) {
				// content is required
				$this->testimonial_form_errors()->add( 'empty_content', __( 'Please enter a testimonial!' ) );
			}
			if ( '' === $title ) {
				// if no title specified then make the testimonial post title the entered name as we don't want a blank post title
				$title = $name;
			}

			$errors = $this->testimonial_form_errors()->get_error_messages();

			// only create TML if there are no errors
			if ( empty( $errors ) ) {

				// create TML
				$post_data = array(
					'post_title'    => $title,
					'post_content'  => $content,
					'post_status'   => $status,
					'post_type'     => 'wpgo_testimonial',
					'meta_input'    => array(
						'_wpgo_testimonial_cpt_author' => $name,
						'_wpgo_testimonial_cpt_company' => $company,
						'_wpgo_testimonial_cpt_company_url' => $company_url,
						'_wpgo_testimonial_cpt_rating' => $rating,
					),
				);

				// insert new testimonial into the database.
				$post_id = wp_insert_post( $post_data );

				// process TML image if one selected
			    if ( isset( $image['name'] ) && ! empty( $image['name'] ) ) {

				    // These files need to be included as dependencies when on the front end.
				    require_once( ABSPATH . 'wp-admin/includes/image.php' );
				    require_once( ABSPATH . 'wp-admin/includes/file.php' );
				    require_once( ABSPATH . 'wp-admin/includes/media.php' );

				    // let WordPress handle the upload
				    $attachment_id = media_handle_upload( 'tml-submission-image', 0 );

				    if ( is_wp_error( $attachment_id ) ) {
					    // There was an error uploading the image.
					    $this->testimonial_form_errors()->add( 'image_upload_error', __( 'Error uploading image!' ) );
				    } else {
					    // image was uploaded successfully so set TML featured image
					    update_post_meta( $post_id, '_thumbnail_id', $attachment_id );
					    set_post_thumbnail( $post_id, $attachment_id );
				    }
			    } else {
				    // no image to process
				}

				if ( 'publish' === $status ) {
	                $this->_success['new'] = 'Thanks! New testimonial created. :)';
				} else {
	                $this->_success['new'] = 'Thanks! Testimonial created and held in moderation queue. :)';
				}
			} // End if().
		} // End if().
	}

	/**
	 * [tml-submit] Testimonial shortcode function.
	 *
	 * Example usage: [tml-submit]
	 *
	 */
	public function tml_submit_shortcode( $atts ) {

		/* Get testimonial attributes from the shortcode. */
		extract( shortcode_atts( array(
			//'id'    => '',
			//'group' => '',
			//'num'   => '-1',
			//'rnd'   => false,
			//'no_excerpt' => '0',
			//'no_company' => '0',
			//'no_name' => '0',
			//'no_image' => '0',
			//'no_link' => '0',
			//'render' => ''
			'status' => 'pending',
		), $atts ) );

		// Sanitize post status attribute - must be 'pending' or 'publish'.
		if ( 'pending' !== $status && 'publish' !== $status ) {
	        $status = 'pending';
		}

		// set these globals to true to load CSS/JS on the page that this shortcode is used
		global $wpgo_testimonial_form_css;
		global $wpgo_testimonial_form_js;

		$wpgo_testimonial_form_css = true;
		$wpgo_testimonial_form_js = true;

		// TML submission form
		$tml_form = 'TML Form';
		$name		= '';
		$image		= '';
		$company 	= '';
		$company_url = '';
		$title		= '';
		$content 	= '';
		$rating     = '';

		// if post was submitted
		if (isset( $_POST["tml-submission-name"] ) && wp_verify_nonce($_POST['tml_submission_register_nonce'], 'tml_submission_register_nonce')) {

			$errors = $this->testimonial_form_errors()->get_error_messages();

			// refill form fields for convenience if we have errors
			if ( ! empty( $errors ) ) {
				$name		= $_POST["tml-submission-name"];
				//$image		= $_POST["tml-submission-image"];
				$company 	= $_POST["tml-submission-company"];
				$company_url = $_POST["tml-submission-company-url"];
				$title		= $_POST["tml-submission-title"];
				$content 	= $_POST["tml-submission-content"];
				$rating = $_POST["tml-submission-rating"];
			}
		}

		ob_start();
		?>
		<form id="wpgo_tml_submission_form" class="tml-submission-form" method="post" action="" enctype="multipart/form-data">
			<fieldset>
				<?php
				// show any error messages after form submission
				$this->show_error_messages();
				$this->show_success_messages();
				?>

				<div class="tml-submission-wrap" id="tml-submission-title-wrap">
					<label for="tml-submission-title"><?php esc_html_e( 'Testimonial Title', 'wpgo-ultimate-testimonials' ); ?></label>
					<input name="tml-submission-title" id="tml-submission-title" class="required" type="text" value="<?php echo esc_attr( $title ); ?>" />
				</div>
				<div class="tml-submission-wrap" id="tml-submission-name-wrap">
					<label for="tml-submission-name"><?php esc_html_e( 'Your Name', 'wpgo-ultimate-testimonials' ); ?><sup>*</sup></label>
					<input name="tml-submission-name" id="tml-submission-name" class="required" type="text" value="<?php echo esc_attr( $name ); ?>" />
				</div>
				<div class="tml-submission-wrap" id="tml-submission-content-wrap">
					<label for="tml-submission-content"><?php esc_html_e( 'Testimonial Message', 'wpgo-ultimate-testimonials' ); ?><sup>*</sup></label>
					<textarea rows="3" name="tml-submission-content" id="tml-submission-content" form="wpgo_tml_submission_form"><?php echo esc_attr( $content ); ?></textarea>
				</div>
				<div class="tml-submission-wrap" id="tml-submission-company-wrap">
					<label for="tml-submission-company"><?php esc_html_e( 'Company/Site Name', 'wpgo-ultimate-testimonials' ); ?></label>
					<input name="tml-submission-company" id="tml-submission-company" type="text" value="<?php echo esc_attr( $company ); ?>" />
				</div>
				<div class="tml-submission-wrap" id="tml-submission-company-url-wrap">
					<label for="tml-submission-company-url"><?php esc_html_e( 'Company/Site URL', 'wpgo-ultimate-testimonials' ); ?></label>
					<input name="tml-submission-company-url" id="tml-submission-company-url" type="text" value="<?php echo esc_attr( $company_url ); ?>" />
				</div>
				<div class="tml-submission-wrap" id="tml-submission-image-wrap">
					<label for="tml-submission-image"><?php esc_html_e( 'Image/Logo', 'wpgo-ultimate-testimonials' ); ?></label>
					<input name="tml-submission-image" id="tml-submission-image" class="required" type="file" />
				</div>
				<div class="tml-submission-wrap" id="tml-submission-rating-wrap">
					<label for="tml-submission-rating"><?php esc_html_e( 'Rating', 'wpgo-ultimate-testimonials' ); ?></label>
					<select style="padding: 5px 2px;" name="tml-submission-rating" id="tml-submission-rating">
						<option value="a" <?php selected( 'a', $rating ); ?>>(none)</option>
						<option value="b" <?php selected( 'b', $rating ); ?>>0</option>
						<option value="c" <?php selected( 'c', $rating ); ?>>0.5</option>
						<option value="d" <?php selected( 'd', $rating ); ?>>1</option>
						<option value="e" <?php selected( 'e', $rating ); ?>>1.5</option>
						<option value="f" <?php selected( 'f', $rating ); ?>>2</option>
						<option value="g" <?php selected( 'g', $rating ); ?>>2.5</option>
						<option value="h" <?php selected( 'h', $rating ); ?>>3</option>
						<option value="i" <?php selected( 'i', $rating ); ?>>3.5</option>
						<option value="j" <?php selected( 'j', $rating ); ?>>4</option>
						<option value="k" <?php selected( 'k', $rating ); ?>>4.5</option>
						<option value="l" <?php selected( 'l', $rating ); ?>>5</option>
					</select>
				</div>
				<div>
					<input type="hidden" name="tml_published_status" value="<?php echo esc_attr( $status ); ?>"/>
					<input type="hidden" name="tml_submission_register_nonce" value="<?php echo esc_attr( wp_create_nonce( 'tml_submission_register_nonce' ) ); ?>"/>
					<input type="submit" value="<?php esc_html_e( 'SUBMIT','wpgo-ultimate-testimonials' ); ?>"/>
					<span style="margin-left:15px;"><sup>*</sup><small>(required)</small></span>
				</div>
			</fieldset>
		</form>
		<?php

		return ob_get_clean();
	}

	// CSS and JS
	public function register_scripts() {

		wp_register_style( 'wpgo-tml-form-css', $this->module_roots['uri'] . '/lib/css/wpgo-testimonial-form.css' );
	}

	// load our form css
	public function enqueue_tml_form_scripts() {

		global $wpgo_testimonial_form_css;

		// this variable is set to TRUE if the short code is used on a page/post
		if ( ! $wpgo_testimonial_form_css ) {
			return; // this means that neither short code is present, so we get out of here
		}

		wp_enqueue_style( 'wpgo-tml-form-css' );
	}

	public function show_error_messages() {

		if ( $codes = $this->testimonial_form_errors()->get_error_codes() ) {
			echo '<div class="testimonial_form_errors">';
			// Loop error codes and display errors
			foreach ( $codes as $code ) {
				$message = $this->testimonial_form_errors()->get_error_message( $code );
				echo '<span class="error"><strong>' . esc_html__( 'Error' ) . '</strong>: ' . esc_html( $message ) . '</span>';
			}
			echo '</div>';
		}
	}

	// used for tracking error messages
	public function testimonial_form_errors() {
		static $wp_error; // Will hold global variable safely
		return isset( $wp_error ) ? $wp_error : $wp_error = new WP_Error( null, null, null );
	}

	public function show_success_messages() {

		$codes = $this->_success;

		if ( is_array( $codes ) ) {

			echo '<div class="testimonial_success">';
			foreach ( $codes as $code ) {
				echo '<span class="success">' . esc_html( $code ) . '</span>';
			}
			echo '</div>';
		}
	}

	/**
	 * [tml] Testimonial shortcode function.
	 *
	 * Example usage: [tml id="123"] or [tml group="123" rnd="true" num="4"]
	 * Where id is a single testimonial id and group is a testimonial group id.
	 *
	 * Note: the post excerpt only applies when displaying a group of testimonial posts (using the group attribute),
	 * similar to post archives. It won't show for a single testimonial even if an excerpt has been defined on the
	 * testimonial post editor. @todo Add this to user docs.
	 *
	 */
	public function tml_shortcode( $atts ) {

		/* Get testimonial attributes from the shortcode. */
		extract( shortcode_atts( array(
			'id'    => '',
			'group' => '',
			'num'   => '-1',
			'rnd'   => false,
			'no_excerpt' => '0',
			'no_company' => '0',
			'no_name' => '0',
			'no_image' => '0',
			'no_link' => '0',
			'render' => '',
			'template' => '',
		), $atts ) );

		if ( ! empty( $group ) ) {
			return $this->render_testimonial_group( $group, $num, $rnd, $no_excerpt, $no_image, $no_name, $no_company, $no_link, $render, $template );
		}

		/* If no group ID specified then try to render single testimonial, otherwise display error message. */
		return $this->render_single_testimonial( $id, $no_excerpt, $no_image, $no_name, $no_company, $no_link, $template );
	}

	/**
	 * Render [tml] shortcode for a specified testimonial group.
	 *
	 */
	public function render_testimonial_group( $group, $num, $rnd, $no_excerpt, $no_image, $no_name, $no_company, $no_link, $render, $template ) {

		$order = ( $rnd ) ? 'rand' : 'date';
		$np    = ( -1 === (int) $num ) ? true : false;

		$args = array();
		$args['group'] = $group;
		$args['num'] = $num;
		$args['rnd'] = $rnd;
		$args['order'] = $order;
		$args['np'] = $np;
		$args['no_excerpt'] = $no_excerpt;
		$args['no_image'] = $no_image;
		$args['no_name'] = $no_name;
		$args['no_company'] = $no_company;
		$args['no_link'] = $no_link;
		$args['render'] = $render;

		// 1. Get template from customizer setting
		$customizer_template = WPGO_Customize_Testimonial_Templates::get_option( 'wpgo_drp_default_tml_template' );

		// 2. Use shortcode template attribute if set. This has priority over 1.
		if( !empty( $template ) ) {
			// @todo Need to test here whether the entered template is in the specified global TML template array (yet to be created).
			// @todo If not then set $template = '';
			$customizer_template = $template;
		}

		return WPGO_Testimonial_Templates::render_tml( $group, $customizer_template, 'group-shortcode', $args );
	}

	/**
	 * Render [tml] shortcode for single testimonial.
	 */
	public function render_single_testimonial( $id, $no_excerpt, $no_image, $no_name, $no_company, $no_link, $template ) {

		$args = array();
		$args['no_excerpt'] = $no_excerpt;
		$args['no_image'] = $no_image;
		$args['no_name'] = $no_name;
		$args['no_company'] = $no_company;
		$args['no_link'] = $no_link;

		// 1. Get template from customizer setting
		$customizer_template = WPGO_Customize_Testimonial_Templates::get_option( 'wpgo_drp_default_tml_template' );

		// 2. Override template with post meta setting?
		$tmp = get_post_meta( $id, '_wpgo_tml_templates_drp', true );
		if ( ! ( empty( $tmp ) || $tmp == 'none' ) ) {
			$customizer_template = $tmp;
		}

		// 3. Use shortcode template attribute if set. This has priority over 1. and 2.
		if( !empty( $template ) ) {
			// @todo Need to test here whether the entered template is in the specified global TML template array (yet to be created).
			// @todo If not then set $template = '';
			$customizer_template = $template;
		}

		return WPGO_Testimonial_Templates::render_tml( $id, $customizer_template, 'single-shortcode', $args );
	}

	/**
	 * This filter hook allows you filter the HTML output of a single testimonial.
	 *
	 * Testimonial source: single testimonial shortcode, and single testimonial theme template(s).
	 *
	 * All testimonial variables such as $content, $name, $image, $company can be altered. Plus, using the $id variable
	 * specific testimonials can be easily targeted.
	 *
	 * @todo Add this to a hooks class inside the wpgo metro Plugin hooks class. Update all refs to it.
	 *
	 */
	public static function wpgo_render_custom_single_testimonial( $testimonial, $id, $tml ) {
		return apply_filters( 'wpgo_render_custom_single_testimonial', $testimonial, $id, $tml );
	}

	/**
	 * This filter hook allows you filter the HTML output for each testimonial passed to it that is part of a testimonial group.
	 *
	 * Testimonial source: testimonial widget, testimonial group shortcode, and testimonial archive theme template(s).
	 *
	 * All testimonial variables such as $content, $name, $image, $company can be altered. Plus, using the $id variable
	 * specific testimonials can be easily targeted.
	 *
	 * @todo Add this to a hooks class inside the wpgo metro Plugin hooks class. Update all refs to it.
	 *
	 */
	public static function wpgo_render_custom_group_testimonial( $testimonial, $id, $tml ) {
		return apply_filters( 'wpgo_render_custom_group_testimonial', $testimonial, $id, $tml );
	}
}