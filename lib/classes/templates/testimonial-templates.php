<?php

/**
 * Testimonial templates.
 *
 * @since 0.1.0
 */
class WPGO_Testimonial_Templates {

	public static function render_tml( $id, $tml_template = 'default', $type = 'single', $args = array() ) {

		// @todo can we move these to a better location?
		$args['header_taxonomy_list'] = array( 'wpgo_testimonial_group' );
		$args['custom_post_type'] = 'wpgo_testimonial';

		// If we are coming from a tml template
		if ( strpos( $tml_template, 'blog' ) !== false ) {
			$tml_template = 'blog';
		} elseif ( strpos( $tml_template, 'legacy' ) !== false ) {
			$tml_template = 'legacy';
		}

		ob_start(); // Start recording output.

		if ( 'single' === $type ) {
			self::render_single( $args, $tml_template );
		} elseif ( 'archive' === $type ) {
			self::render_archive( $args, $tml_template );
		} elseif ( 'single-shortcode' === $type ) {
			self::render_single_shortcode( $id, $args, $tml_template );
		} elseif ( 'group-shortcode' === $type ) {
			self::render_group_shortcode( $id, $args, $tml_template );
		}

		$tml_content = ob_get_contents(); // Get output contents.
		ob_end_clean(); // End recording output and flush buffer.

		return $tml_content;
	}

	// --------------------------
	// CORE TESTIMONIAL TEMPLATES
	// --------------------------

	public static function default_template(
		$title_link = false,
		$excerpt = false,
		$hook = 'single',
		$author_link = false,
		$args = array()
	) {

		$tmlObj = new WPGO_Testimonial_Data( get_the_ID(), $title_link );
		$tml = $tmlObj->data;

		if ( $excerpt && ! empty( $tml['excerpt'] ) ) {
			$tml['content'] = $tml['excerpt'];
		}

		$author = empty( $tml['author'] ) ? $tml['title_text'] :  $tml['author'];

		$testimonial_html = '<div class="wpgo-testimonial">'
							. '<span class="dashicons dashicons-format-quote"></span>'
							. $tml['title']
							. $tml['content']
							. '<div class="testimonial-meta">'
		                    . $tml['rating']
		                    . $tml['image']
							. '<div class="tml-meta-text">' . $author . $tml['company'] . '</div>'
							. '</div>'
							. '</div>'
							. "\n";

		$method = 'wpgo_render_custom_' . $hook . '_testimonial';
		echo WPGO_Testimonial_Shortcodes::$method(
			$testimonial_html,
			get_the_ID(),
			$tml
		); // Filter hook.
	}

	public static function blog_template(
		$title_link = false,
		$excerpt = false,
		$hook = 'single',
		$author_link = false,
		$args = array()
	) {

		$tmlObj = new WPGO_Testimonial_Data( get_the_ID(), $title_link );
		$tml = $tmlObj->data;

		if ( $excerpt && ! empty( $tml['excerpt'] ) ) {
			$tml['content'] = $tml['excerpt'];
		}

		$author = empty($tml['author']) ? '' : '<p class="testimonial-name">' . $tml['author'] . '</p>';
		$separator = empty($author) || empty($tml['company']) ? '' : ' - ';

		$testimonial_html = '<div class="wpgo-testimonial">'
							. $tml['title']
							. $tml['content']
							. '<div class="testimonial-meta">'
							. $tml['rating']
							. $tml['image']
							. '<div class="tml-meta-text">' . $author . $separator . $tml['company'] . '</div>'
							. '</div>'
							. '</div>'
							. "\n";

		$method = 'wpgo_render_custom_' . $hook . '_testimonial';
		echo WPGO_Testimonial_Shortcodes::$method(
			$testimonial_html,
			get_the_ID(),
			$tml
		); // Filter hook.
	}

	public static function legacy_template(
		$title_link = false,
		$excerpt = false,
		$hook = 'single',
		$author_link = false,
		$args = array()
	) {

		$tmlObj = new WPGO_Testimonial_Data( get_the_ID(), $title_link );
		$tml = $tmlObj->data;

		if ( $excerpt && ! empty( $tml['excerpt'] ) ) {
			$tml['content'] = $tml['excerpt'];
		}

		$author = empty($tml['author']) ? $tml['title_text'] :  $tml['author'];
		if ( $author_link ) {
			$author = '<a href="' . $tml['title_link'] . '" class="tml-title-link">' . $author . '</a>';
		}

		$testimonial_html = '<div class="wpgo-testimonial">'
		                    . '<span class="dashicons dashicons-format-quote"></span>'
							. $tml['content']
							. '<div class="testimonial-meta">'
		                    . $tml['rating']
		                    . $tml['image']
							. '<div class="tml-meta-text">' . $author . $tml['company'] . '</div>'
							. '</div>'
							. '</div>'
							. "\n";

		$method = 'wpgo_render_custom_' . $hook . '_testimonial';
		echo WPGO_Testimonial_Shortcodes::$method(
			$testimonial_html,
			get_the_ID(),
			$tml
		); // Filter hook.
	}

	// --------------------------
	// TESTIMONIAL TEMPLATE TYPES
	// --------------------------

	/**
	 * Single testimonial template.
	 *
	 */
	public static function render_single( $args, $tml_template ) {
	?>
		<div class="wpgo-testimonial-container <?php echo $tml_template; ?>">

			<?php while ( have_posts() ) : the_post(); ?>

				<article id="post-<?php the_ID(); ?>" <?php post_class( 'post singular-page' ); ?>>

					<?php WPGO_Hooks::wpgo_before_post_content( $args ); ?>

					<?php
					$method = $tml_template . '_template';
					self::$method();
					?>

					<?php WPGO_Hooks::wpgo_after_post_content($args); ?>

                </article><!-- .post -->

				<?php WPGO_Hooks::wpgo_after_post($args); ?>

				<?php comments_template( '', true ); ?>

			<?php endwhile; // end of the loop. ?>

        </div><!-- .wpgo-testimonial-container -->
	<?php
	}

	public static function render_single_shortcode( $id, $args, $tml_template ) {

		$tml_query = new WP_Query( array(
			'post_type'           => 'wpgo_testimonial',
			'p'					  => $id,
		) );
		?>

		<div class="wpgo-testimonial-container shortcode <?php echo $tml_template; ?>">

			<?php if ( $tml_query->have_posts() ) : $tml_query->the_post();

				$method = $tml_template . '_template';
				self::$method();

				wp_reset_postdata();

			else :

				echo '<p>' . __( 'Error! No testimonial found with ID: ' . $id . '. Please enter a valid ID.', 'wpgo-ultimate-testimonials' ) . '</p>';

			endif; ?>

		</div><!-- .wpgo-testimonial-container -->
	<?php
	}

	public static function render_group_shortcode( $id, $args, $tml_template ) {

		$tml_group_query = new WP_Query(
			array(
				'post_type'           => 'wpgo_testimonial',
				'orderby'             => $args['order'],
				'posts_per_page'      => $args['num'],
				'nopaging'            => $args['np'],
				'post_status'         => 'publish',
				'ignore_sticky_posts' => 1,
				'tax_query'           => array(
					array(
						'taxonomy' => 'wpgo_testimonial_group',
						'field'    => 'id',
						'terms'    => $args['group'],
					),
				),
			)
		);

		$tml_class = ( 'grid' === $args['render'] ) ? ' grid ' . $tml_template : ' ' . $tml_template ;
		?>

		<div class="wpgo-testimonial-container shortcode<?php echo $tml_class; ?>">

			<?php
			if ( $tml_group_query->have_posts() ) :

				while ( $tml_group_query->have_posts() ) : $tml_group_query->the_post();
				?>

					<article id="post-<?php the_ID(); ?>" <?php post_class( array('archive-post', 'post') ); ?>>

						<div class="article-wrap">

							<?php
							// If the shortcode attribute 'no_link' is set to true then don't show title link
							$tl =  $args['no_link'] == 1 ? false: true;
							$method = $tml_template . '_template';
							self::$method( $tl, true, 'group' );
							?>

						</div>

					</article><!-- .post -->

				<?php

				endwhile;

				/* Reset the global $the_post as this query will have stomped on it. */
				wp_reset_postdata();

			else :
				echo '<p>' . __( 'Error! No testimonials found with the group ID: ' . $args['group'] . '. Please enter a valid ID.', 'wpgo-ultimate-testimonials' ) . '</p>';
			endif;
		?>

		</div><!-- .wpgo-testimonial-container -->
	<?php
	}

	/**
	 * Archive testimonial template.
	 *
	 */
	public static function render_archive( $args, $tml_template ) {

		$tml_options = WPGO_Testimonial_Options::get_options();
		?>

		<div class="wpgo-testimonial-container <?php echo $tml_template; ?>">

			<?php while ( have_posts() ) : the_post(); ?>

                <article id="post-<?php the_ID(); ?>" <?php post_class( array('archive-post', 'post') ); ?>>

                    <div class="article-wrap">

						<?php WPGO_Hooks::wpgo_before_post_archive_content($args); ?>

						<?php
						// If the tml archive link setting is set to true then don't show title link
						$tl =  $tml_options['chk_post_content'] == 1 ? false: true;
						$method = $tml_template . '_template';
						self::$method( $tl, true, 'group' );
						?>

                    </div>

                </article><!-- .post -->

			<?php endwhile; // end of the loop. ?>

        </div><!-- .wpgo-testimonial-container -->

		<?php WPGO_Utility::paginate_links( $args['next'], $args['prev'] ); ?>
	<?php
	}

} /* End of class definition */
