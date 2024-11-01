<?php

// -------------------------
// Testimonials Widget Class
// -------------------------

class wpgo_tml_widget extends WP_Widget {

	function __construct() {
		$widget_ops = array( 'classname' => 'wpgo_tml_widget', 'description' => __( 'Display a list of testimonials by group.', 'wpgo-ultimate-testimonials' ) );
		parent::__construct( 'wpgo_tml_widget', __( 'Testimonials', 'wpgo-ultimate-testimonials' ), $widget_ops );
	}

	function form( $instance ) {

		$defaults = array(
			'title' => '',
			'tml_groups' => '1',
			'number_tml' => 4,
			'description' => '',
			'randomize' => false
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		$title       = strip_tags( $instance['title'] );
		$description = strip_tags( $instance['description'] );
		$randomize   = strip_tags( $instance['randomize'] );

		if ( ! isset( $instance['number_tml'] ) || ! $number_tml = (int) $instance['number_tml'] ) {
			$number_tml = 4;
		}

		/* Check the taxonmy contains any terms. If none found then exit the function. */
		$taxonomy_args = array( 'taxonomy' => 'wpgo_testimonial_group', 'title_li' => '', 'show_option_none' => 'zero', 'style' => 'none', 'echo' => 0 );
		if ( wp_list_categories( $taxonomy_args ) == 'zero' ) {
			echo '<p>' . __( 'No testimonial groups found. Add a new one via Testimonials -> Testimonial Groups.', 'wpgo-ultimate-testimonials' ) . '</p>';

			return;
		}
		?>

		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'wpgo-ultimate-testimonials' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>">
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'description' ); ?>"><?php _e( 'Description', 'wpgo-ultimate-testimonials' ) ?></label>
			<textarea class="widefat" id="<?php echo $this->get_field_id( 'description' ); ?>" name="<?php echo $this->get_field_name( 'description' ); ?>" rows="2"><?php echo esc_attr( $description ); ?></textarea>
		</p>
		<p>
		<div style="margin-bottom:1px;">
			<label for="<?php echo $this->get_field_id( 'tml_groups' ); ?>"><?php _e( 'Display testimonial group', 'wpgo-ultimate-testimonials' ); ?></label>
		</div>
		<?php
		$category_args = array(
			'id'           => $this->get_field_id( 'tml_groups' ),
			'hide_empty'   => 0,
			'hierarchical' => 1,
			'show_count'   => 1,
			'name'         => $this->get_field_name( 'tml_groups' ),
			'taxonomy'     => 'wpgo_testimonial_group',
			'class'        => 'widefat',
			'selected'     => $instance['tml_groups']
		);
		wp_dropdown_categories( $category_args );
		?>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'number_tml' ); ?>"><?php _e( 'Max number of testimonials', 'wpgo-ultimate-testimonials' ); ?></label>
			<input id="<?php echo $this->get_field_id( 'number_tml' ); ?>" name="<?php echo $this->get_field_name( 'number_tml' ); ?>" type="text" value="<?php echo $number_tml; ?>" size="3">
		</p>
		<p>
			<label><?php _e( 'Show random testimonials?', 'wpgo-ultimate-testimonials' ) ?>&nbsp;<input type="checkbox" value="1" <?php checked( $randomize, '1' ); ?> name="<?php echo $this->get_field_name( 'randomize' ); ?>"></label>
		</p>

	<?php
	}

	function update( $new_instance, $old_instance ) {
		$instance                = $old_instance;
		$instance['title']       = strip_tags( $new_instance['title'] );
		$instance['tml_groups']  = $new_instance['tml_groups'];
		$instance['number_tml']  = (int) $new_instance['number_tml'];
		$instance['description'] = $new_instance['description'];
		$instance['randomize']   = strip_tags( $new_instance['randomize'] );

		return $instance;
	}

	function widget( $args, $instance ) {

		/* Check the taxonomy contains any terms. If none found then exit the function. */
		$taxonomy_args = array( 'taxonomy' => 'wpgo_testimonial_group', 'title_li' => '', 'show_option_none' => 'zero', 'style' => 'none', 'echo' => 0 );
		if ( wp_list_categories( $taxonomy_args ) == 'zero' ) {
			return;
		}

		extract( $args );

		$title       = $instance['title'];
		$number_tml  = $instance['number_tml'];
		$tml_groups  = $instance['tml_groups'];
		$description = $instance['description'];
		$randomize   = $instance['randomize'];

		$order = ( $randomize ) ? 'rand' : 'date';

		$tml_widget_r = new WP_Query(
			array(
				'post_type'           => 'wpgo_testimonial',
				'orderby'             => $order,
				'posts_per_page'      => $number_tml,
				'nopaging'            => false,
				'post_status'         => 'publish',
				'ignore_sticky_posts' => 1,
				'tax_query'           => array( array(
					'taxonomy' => 'wpgo_testimonial_group',
					'field'    => 'id',
					'terms'    => $tml_groups
				) )
			)
		);

		if ( $tml_widget_r->have_posts() ) :
			?>

			<?php echo $before_widget; ?>
			<?php if ( ! empty( $title ) ) echo $before_title . $title . $after_title; ?>

			<?php
			if ( ! empty( $description ) )
				echo "<p>" . $description . "</p>";
			?>

			<?php
			$global_customizer_tml = WPGO_Customize_Testimonial_Templates::get_option( 'wpgo_drp_default_tml_template' );

			// If we are coming from a tml template
			if ( strpos( $global_customizer_tml, 'blog' ) !== false ) {
				$global_customizer_tml = 'blog';
			} elseif ( strpos( $global_customizer_tml, 'legacy' ) !== false ) {
				$global_customizer_tml = 'legacy';
			}
			?>

			<div class="wpgo-testimonial-container <?php echo $global_customizer_tml; ?>">

				<?php while ( $tml_widget_r->have_posts() ) : $tml_widget_r->the_post(); ?>

					<?php
					$method = $global_customizer_tml . '_template';
					WPGO_Testimonial_Templates::$method();
					?>

				<?php endwhile; ?>

			</div><!-- .wpgo-testimonial-container -->

			<?php echo $after_widget; ?>

			<?php

			/* Reset the global $the_post as this query will have stomped on it. */
			wp_reset_postdata();

		endif;
	}
}
?>