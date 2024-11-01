<?php

/**
 * Testimonial data.
 *
 * @since 0.1.0
 */
class WPGO_Testimonial_Data {

	public $data;
	private $title_link;

	public function __construct( $tml_id, $title_link = false ) {

		// Initialize testimonial class properties.
		$this->data = array(); // Store all testimonial data in array.
		$this->title_link = $title_link; // Make the testimonial a link?

		// Get testimonial title.
		$this->data['title'] = $this->title( $tml_id );

		// Get testimonial title text only.
		$this->data['title_text'] = $this->title_text( $tml_id );

		// Get testimonial image.
		$this->data['image'] = $this->image( $tml_id );

		// Get testimonial author.
		$this->data['author'] = $this->author( $tml_id );

		// Get testimonial company.
		$this->data['company'] = $this->company( $tml_id );

		// Get testimonial excerpt.
		$this->data['excerpt'] = $this->excerpt( $tml_id );

		// Get testimonial content.
		$this->data['content'] = $this->content( $tml_id );

		// Get testimonial rating.
		$this->data['rating'] = $this->rating( $tml_id );

		// Store individual testimonial id too.
		$this->data['id'] = $tml_id;
	}

	/**
	 * Get testimonial title.
	 *
	 * Depending on status of $this->title_link the returned title will be a link
	 * to the single testimonial.
	 *
	 */
	private function title( $id ) {

		// If the testimonial hide title checkbox selected then don't return title
		if( get_post_meta( $id, '_wpgo_hide_title_header_tag', true ) ) {
			return '';
		}

		$title_text = get_the_title( $id );
		if ( $this->title_link ) {
			$title = '<a href="' . get_permalink( $id ) . '" class="tml-title-link">' . $title_text . '</a>';
		} else {
			$title = $title_text;
		}

		return '<h2 class="testimonial-title">' . $title . '</h2>';
	}

	/**
	 * Get testimonial title text only with no formatting or markup.
	 *
	 */
	private function title_text( $id ) {

		// If the testimonial hide title checkbox selected then don't return title_text
		if( get_post_meta( $id, '_wpgo_hide_title_header_tag', true ) ) {
			return '';
		}

		return get_the_title( $id );
	}

	/** Get testimonial image. */
	private function image( $id ) {

		$w  = defined( 'WPGO_TESTIMONIAL_THUMB_WIDTH' ) ? WPGO_TESTIMONIAL_THUMB_WIDTH : 50;
		$h  = $w;

		/* If no featured image set, use gravatar if specified. */
		if ( ! ( $image = get_the_post_thumbnail( $id, array( $w, $h ), array( 'class' => 'avatar', 'title' => '' ) ) ) ) {
			$image = get_post_meta( $id, '_wpgo_testimonial_cpt_image', true );
			if ( ! trim( $image ) == '' ) {
				$image = get_avatar( $image, $w );
			}
			// try and remove testimonial image title attribute here if shortcode attribute set?
		}

		return empty( $image ) ? '' : '<div class="tml-image-container">' . $image . '</div>';
	}

	/** Get testimonial author. */
	private function author( $id ) {

		return get_post_meta( $id, '_wpgo_testimonial_cpt_author', true );
	}

	/** Get testimonial company. */
	private function company( $id ) {

		$company = get_post_meta( $id, '_wpgo_testimonial_cpt_company', true );
		$company_url = trim( get_post_meta( $id, '_wpgo_testimonial_cpt_company_url', true ) );

		if ( ! empty( $company ) ) {
			if ( ! empty( $company_url ) ) {
				$company = '<a href="' . $company_url . '" target="_blank">' . $company . '</a>';
			}
			return '<p class="testimonial-company">' . $company . '</p>';
		} else {
			return '';
		}
	}

	/** Get testimonial excerpt. */
	private function excerpt( $id ) {

		$excerpt = get_post( $id )->post_excerpt; // Get post excerpt if defined.
		if ( ! empty( $excerpt ) ) {
			$excerpt = '<p>' . $excerpt . '<span class="read-more-wrapper"><a href="' . get_permalink( $id ) . '" class="more-link">(more&#8230;)</a></span></p>';
			$excerpt = 	'<div class="quote">' . $excerpt . '</div>'; // Get output contents.
		} else {
			$excerpt = '';
		}

		return $excerpt;
	}

	/** Get testimonial content. */
	private function content( $id ) {

		ob_start(); // Start recording output.
		the_content( '(more&#8230;)' ); // Get TML contents WITH formatting.
		$content = '<div class="quote">' . ob_get_contents() . '</div>'; // Get output contents.
		ob_end_clean(); // End recording output and flush buffer.

		return $content;
	}

	/**
	 * This function is based on the core wp_star_rating() version
	 *
	 * Output a HTML element with a star rating for a given rating.
	 *
	 * Outputs a HTML element with the star rating exposed on a 0..5 scale in
	 * half star increments (ie. 1, 1.5, 2 stars). Optionally, if specified, the
	 * number of ratings may also be displayed by passing the $number parameter.
	 *
	 * @since 3.8.0
	 * @since 4.4.0 Introduced the `echo` parameter.
	 *
	 * @param array $args {
	 *     Optional. Array of star ratings arguments.
	 *
	 *     @type int    $rating The rating to display, expressed in either a 0.5 rating increment,
	 *                          or percentage. Default 0.
	 *     @type string $type   Format that the $rating is in. Valid values are 'rating' (default),
	 *                          or, 'percent'. Default 'rating'.
	 *     @type int    $number The number of ratings that makes up this rating. Default 0.
	 *     @type bool   $echo   Whether to echo the generated markup. False to return the markup instead
	 *                          of echoing it. Default true.
	 * }
	 *
	 * @return string
	 */
	function rating( $id, $args = array() ) {

		$rating = get_post_meta( $id, '_wpgo_testimonial_cpt_rating', true );
		if ( empty($rating) || $rating === 'a' ) { return ''; }

		// don't need to include 'a' as that triggers a return
		// letter a-l used so we can sort the ratings column on admin TML index.
		// @todo if the sorting doesn't work then we should revert to using numbers for simplicity
		$rating_arr = array (
			'b' => '0.0',
			'c' => '0.5',
			'd' => '1.0',
			'e' => '1.5',
			'f' => '2.0',
			'g' => '2.5',
			'h' => '3.0',
			'i' => '3.5',
			'j' => '4.0',
			'k' => '4.5',
			'l' => '5.0',
		);

		$defaults = array(
			'rating' => $rating_arr[$rating],
			'type'   => 'rating',
			'number' => 0,
		);
		$r = wp_parse_args( $args, $defaults );

		// Non-english decimal places when the $rating is coming from a string
		$rating = str_replace( ',', '.', $r['rating'] );

		// Convert Percentage to star rating, 0..5 in .5 increments
		if ( 'percent' == $r['type'] ) {
			$rating = round( $rating / 10, 0 ) / 2;
		}

		// Calculate the number of each type of star needed
		$full_stars = floor( $rating );
		$half_stars = ceil( $rating - $full_stars );
		$empty_stars = 5 - $full_stars - $half_stars;

		if ( $r['number'] ) {
			/* translators: 1: The rating, 2: The number of ratings */
			$format = _n( '%1$s rating based on %2$s rating', '%1$s rating based on %2$s ratings', $r['number'] );
			$title = sprintf( $format, number_format_i18n( $rating, 1 ), number_format_i18n( $r['number'] ) );
		} else {
			/* translators: 1: The rating */
			$title = sprintf( __( '%s rating' ), number_format_i18n( $rating, 1 ) );
		}

		$output = '<div class="star-rating">';
		$output .= str_repeat( '<div class="star star-full" aria-hidden="true"></div>', $full_stars );
		$output .= str_repeat( '<div class="star star-half" aria-hidden="true"></div>', $half_stars );
		$output .= str_repeat( '<div class="star star-empty" aria-hidden="true"></div>', $empty_stars );
		$output .= '</div>';

		return $output;
	}

} /* End of class definition */
