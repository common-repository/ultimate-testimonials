<?php

/**
 * Framework template parts class.
 *
 * Contains configurable partial template such as post loops.
 *
 * @since 0.1.0
 */
class WPGO_TML_Post_Templates {

	/* Default loop args static class property. */
	private static $default_loop_args;

	/**
	 * WPGO_Post_Templates class initializer.
	 *
	 * Setup the default loop args class property.
	 *
	 * @since 0.2.0
	 */
	public static function init() {

		/* Initialize default loop args class property. */
		self::$default_loop_args = array(	'more'					=> __( 'Read More', 'wpgothemes' ),
											'next'					=> __( 'Next', 'wpgothemes' ),
											'prev'					=> __( 'Previous', 'wpgothemes' ),
											'pre_separator'			=> '',
											'taxonomy_separator'	=> '',
											'date_separator'		=> '&nbsp;&nbsp;&nbsp;',
											'author_separator'		=> '&nbsp;&nbsp;&nbsp;',
											'comment_separator'		=> '',
											'tags_separator'		=> '',
											'meta_term_separator'	=> ',',
											'meta_container_tag'	=> 'ul',
											'meta_item_tag'			=> 'li',
											'date_prefix'			=> '',
											'author_prefix'			=> '',
											'comment_prefix'	    => '',
											'post_tag_prefix'		=> '',
											'category_prefix'		=> '',
											'header_meta_list'		=> array( 'date', 'author', 'comments' ),
											'footer_meta_list'		=> array( 'taxonomy' ),
											'header_taxonomy_list'	=> array( '' ),
											'footer_taxonomy_list'	=> array( 'category', 'post_tag' ),
											'header_meta_class'		=> '',
											'footer_meta_class'		=> '',
											'custom_post_type'      => '',
		);
	}

	// @todo remove the $args parameter?
	// @todo move to the testimonial-templates.php file
	public static function single_testimonial_loop( $args = array() ) {
    ?>

		<?php if ( have_posts() ) : ?>

			<?php
			// 1. Get template from customizer setting
			$customizer_template = WPGO_Customize_Testimonial_Templates::get_option( 'wpgo_drp_default_tml_template' );

			// 2. Override template with post meta setting?
			$tmp = get_post_meta( get_the_ID(), '_wpgo_tml_templates_drp', true );
			if ( ! ( empty( $tmp ) || $tmp == 'none' ) ) {
				$customizer_template = $tmp;
			}

			echo WPGO_Testimonial_Templates::render_tml( get_the_ID(), $customizer_template, 'single' );
			?>

		<?php endif; ?>

	<?php
	}

	public static function testimonial_group_loop( $args = array() ) {

		// add argument defaults specific to this loop
		if ( ! isset( $args['header_taxonomy_list'] ) ) $args['header_taxonomy_list'] = array( 'wpgo_testimonial_group' );
		if ( ! isset( $args['custom_post_type'] ) ) $args['custom_post_type'] = 'wpgo_testimonial';

		$args = self::merge_loop_arguments( $args );
		?>

		<?php WPGO_Hooks::wpgo_before_post_archive_loop($args); ?>

		<?php if ( have_posts() ) : ?>

            <?php
			$global_customizer_tml = WPGO_Customize_Testimonial_Templates::get_option( 'wpgo_drp_default_tml_template' );

			echo WPGO_Testimonial_Templates::render_tml( get_the_ID(), $global_customizer_tml, 'archive', $args );
			?>

		<?php else: ?>

			<?php WPGO_Post_MT::no_posts_found( __( 'No Testimonials Found!', 'wpgothemes' ) ); ?>

		<?php endif; ?>

	<?php
	}

	/**
	 * Merge custom arguments with default arguments.
	 *
	 * If no custom arguments defined then just use default arguments.
	 *
	 * @since 0.2.0
	 */
	public static function merge_loop_arguments( $args = array() ) {

		$args = array_merge( self::$default_loop_args, $args );

		$args['header_meta_class'] = !empty( $args['header_meta_class'] ) ? ' ' . trim($args['header_meta_class']) : '';
		$args['footer_meta_class'] = !empty( $args['footer_meta_class'] ) ? ' ' . trim($args['footer_meta_class']) : '';

		return $args;
	}

} /* End of class definition */

/* Setup static class defaults args property. */
WPGO_TML_Post_Templates::init();
