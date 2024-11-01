<?php

/**
 * Testimonial custom post type.
 *
 * This class registers the testimonial post type and the taxonomy for testimonial groups.
 *
 * Class name suffix _CPT stands for [C]ustom_[P]ost_[T]ype.
 *
 * @since 0.1.0
 */
class WPGO_Testimonial_CPT {

	protected $module_roots;

	/**
	 * Testimonial class constructor.
	 *
	 * Contains hooks that point to class methods to initialise the custom post type etc.
	 *
	 * @since 0.1.0
	 */
	public function __construct($module_roots) {

		$this->module_roots = $module_roots;

		/* Register CPT and associated taxonomy. */
		add_action( 'init', array( &$this, 'register_post_type' ) );
		add_action( 'init', array( &$this, 'register_taxonomy' ) );

		/* Customize CPT columns on overview page. */
		add_filter( 'manage_wpgo_testimonial_posts_columns', array( &$this, 'change_overview_columns' ) ); /* Which columns are displayed. */
		add_action( 'manage_wpgo_testimonial_posts_custom_column', array( &$this, 'custom_column_content' ), 10, 2 ); /* The html output for each column. */
		add_filter( 'manage_edit-wpgo_testimonial_sortable_columns', array( &$this, 'sort_custom_columns' ) ); /* Specify which columns are sortable. */

		/* Customize the CPT messages. */
		add_filter( 'post_updated_messages', array( &$this, 'update_cpt_messages' ) );
		add_filter( 'enter_title_here', array( &$this, 'update_title_message' ) );

		/* Add meta boxes to testimonial custom post type. */
		add_action( 'admin_init', array( &$this, 'testimonial_cpt_meta_box_init' ) );
		add_action( 'add_meta_boxes', array( &$this, 'move_featured_image_metabox' ) );

		// front end scripts
		add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_testimonial_scripts' ) );

		// Add an ID column to TML group admin page
		add_action( "manage_edit-wpgo_testimonial_group_columns", array( &$this, 'add_id_column' ) );
		add_filter( "manage_edit-wpgo_testimonial_group_sortable_columns", array( &$this, 'add_id_column' ) );
		add_filter( "manage_wpgo_testimonial_group_custom_column", array( &$this, 'show_id_column' ), 10, 3 );
		add_action( 'admin_print_styles-edit-tags.php', array( &$this, 'style_id_column' ) );
		add_action( 'admin_print_scripts-edit.php', array( &$this, 'style_ratings_column' ) );

		/* Add dropdown filter on wpgo_testimonial CPT edit.php to sort by taxonomy. */
		// These work OK but until I can figure out how to get the default taxonomy term to be associated
		// automatically with new CPT items then I will leave this feature out as the show all option doesn't
		// work properly.
		// add_action( 'restrict_manage_posts', array( &$this, 'taxonomy_filter_restrict_manage_posts' ) );
		// add_filter( 'parse_query', array( &$this, 'taxonomy_filter_post_type_request' ) );
	}

	// @todo enqueue this instead to be loaded only on the testimonials admin index page
	public function style_ratings_column() {
		echo "<style>.post-type-wpgo_testimonial .column-rating {width: 5em;}.post-type-wpgo_testimonial .star-rating .star {width: 12px;height: 12px;font-size: 12px;}</style>";
	}

	public function add_id_column( $columns ) {
		return $columns + array ( 'tax_id' => 'ID' );
	}

	public function style_id_column() {
		echo "<style>#tax_id{width:4em}</style>";
	}

	public function show_id_column( $v, $name, $id ) {
		return 'tax_id' === $name ? $id : $v;
	}

	/**
	 * Register Testimonial post type.
	 *
	 * @since 0.1.0
	 */
	public function register_post_type() {

		/* Post type arguments. */
		$args = array(
			'public'              => true,
			'has_archive'         => 'testimonials',
			'exclude_from_search' => false, // @todo Setting this to true will break the pagination for the wpgo_testimonial_group custom taxonomy. See: http://core.trac.wordpress.org/ticket/17592
			'query_var'           => true,
			'rewrite'             => array( 'slug' => 'testimonial' ),
			'capability_type'     => 'page',
			'hierarchical'        => false,
			'menu_icon'           => 'dashicons-format-quote',
			'supports'            => array(
				'editor', 'author', 'thumbnail', 'title', 'revisions', 'comments', 'excerpt'
			),
			'labels'              => array(
				'name'               => __( 'Testimonials', 'wpgo-ultimate-testimonials' ),
				'all_items'          => __( 'All Testimonials', 'wpgo-ultimate-testimonials' ),
				'singular_name'      => __( 'Testimonial', 'wpgo-ultimate-testimonials' ),
				'add_new'            => __( 'Add New', 'wpgo-ultimate-testimonials' ),
				'add_new_item'       => __( 'Add New Testimonial', 'wpgo-ultimate-testimonials' ),
				'edit_item'          => __( 'Edit Testimonial', 'wpgo-ultimate-testimonials' ),
				'new_item'           => __( 'New Testimonial', 'wpgo-ultimate-testimonials' ),
				'view_item'          => __( 'View Testimonial', 'wpgo-ultimate-testimonials' ),
				'search_items'       => __( 'Search Testimonials', 'wpgo-ultimate-testimonials' ),
				'not_found'          => __( 'No Testimonials Found', 'wpgo-ultimate-testimonials' ),
				'not_found_in_trash' => __( 'No Testimonials Found In Trash', 'wpgo-ultimate-testimonials' ),
				'attributes'         => __( 'Testimonial Attributes', 'wpgo-ultimate-testimonials' ),
			)
		);

		/* Register post type. */
		register_post_type( 'wpgo_testimonial', $args );
	}

	/**
	 * Register Testimonial taxonomy.
	 *
	 * @since 0.1.0
	 */
	public function register_taxonomy() {

		/* Testimonial taxonomy arguments. */
		$args = array(
			'hierarchical'  => true,
			'query_var'     => true,
			'show_tagcloud' => false,
			'sort'          => true,
			'rewrite'       => array( 'slug' => 'testimonials' ),
			'labels'        => array(
				'name'              => __( 'Testimonial Groups', 'wpgo-ultimate-testimonials' ),
				'singular_name'     => __( 'Testimonial Group', 'wpgo-ultimate-testimonials' ),
				'edit_item'         => __( 'Edit Testimonial Group', 'wpgo-ultimate-testimonials' ),
				'update_item'       => __( 'Update Testimonial', 'wpgo-ultimate-testimonials' ),
				'add_new_item'      => __( 'Add New Group', 'wpgo-ultimate-testimonials' ),
				'new_item_name'     => __( 'New Testimonial Name', 'wpgo-ultimate-testimonials' ),
				'all_items'         => __( 'All Testimonials', 'wpgo-ultimate-testimonials' ),
				'search_items'      => __( 'Search Testimonials', 'wpgo-ultimate-testimonials' ),
				'parent_item'       => __( 'Parent Genre', 'wpgo-ultimate-testimonials' ),
				'parent_item_colon' => __( 'Parent Genre:', 'wpgo-ultimate-testimonials' )
			)
		);

		/* Register the testimonial taxonomy. */
		register_taxonomy( 'wpgo_testimonial_group', array( 'wpgo_testimonial' ), $args );
	}

	/**
	 * Change the columns on the custom post types overview page.
	 *
	 * @since 0.1.0
	 */
	public function change_overview_columns( $cols ) {

		$cols = array(
			'cb'            => '<input type="checkbox">',
			'title'         => __( 'Name', 'wpgo-ultimate-testimonials' ),
			'rating'        => __( 'Rating', 'wpgo-ultimate-testimonials' ),
			'tml-author'    => __( 'Testimonial Author', 'wpgo-ultimate-testimonials' ),
			'company'       => __( 'Company', 'wpgo-ultimate-testimonials' ),
			'image'         => __( 'Image', 'wpgo-ultimate-testimonials' ),
			'group'         => __( 'Group', 'wpgo-ultimate-testimonials' ),
			'id'            => __( 'ID', 'wpgo-ultimate-testimonials' ),
			'date'          => __( 'Date', 'wpgo-ultimate-testimonials' )
		);

		return $cols;
	}

	/**
	 * Add some content to the custom columns from the custom post type.
	 *
	 * @since 0.1.0
	 */
	public function custom_column_content( $column, $post_id ) {

		switch ( $column ) {
			case "title":
				echo 'title';
				break;
			case "rating":
				$rating = get_post_meta( $post_id, '_wpgo_testimonial_cpt_rating', true );

				if ( empty( $rating ) || $rating == 'a' ) {
					echo 'none';
				} else {
					$rating_arr = array(
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
					$rating_args = array(
						'rating' => $rating_arr[ $rating ],
						'type' => 'rating',
						//'number' => 1234,
					);
					wp_star_rating($rating_args);
				}
				break;
			case "tml-author":
				$testimonial_author = get_post_meta( $post_id, '_wpgo_testimonial_cpt_author', true );
				echo $testimonial_author;
				break;
			case "company":
				$company_url  = trim( get_post_meta( $post_id, '_wpgo_testimonial_cpt_company_url', true ) );
				$company_name = get_post_meta( $post_id, '_wpgo_testimonial_cpt_company', true );
				echo ( $company_url == '' ) ? $company_name : '<a href="' . $company_url . '" target="_blank">' . $company_name . '</a>';
				break;
			case "image":
				/* If no featured image set, use gravatar if specified. */
				if ( ! ( $image = get_the_post_thumbnail( $post_id, array( 32, 32 ) ) ) ) {
					$image = get_post_meta( $post_id, '_wpgo_testimonial_cpt_image', true );
					if ( trim( $image ) == '' ) {
						$image = '<em>' . __( 'No image', 'wpgo-ultimate-testimonials' ) . '</em>';
					} else {
						$image = get_avatar( $image, $size = '32' );
					}
				}
				echo $image;
				break;
			case "group":
				$taxonomy  = 'wpgo_testimonial_group';
				$post_type = get_post_type( $post_id );
				$terms     = get_the_terms( $post_id, $taxonomy );

				/* get_the_terms() only returns an array on success so need check for valid array. */
				if ( is_array( $terms ) ) {
					$str = "";
					foreach ( $terms as $term ) {
						$str .= "<a href='edit.php?post_type={$post_type}&{$taxonomy}={$term->slug}'> " . esc_html( sanitize_term_field( 'name', $term->name, $term->term_id, 'group', 'edit' ) ) . "</a>, ";
					}
					echo rtrim( $str, ", " );
				} else {
					echo '<em>' . __( 'Not in any groups', 'wpgo-ultimate-testimonials' ) . '</em>';
				}
				break;
			case "id":
				echo $post_id;
				break;
		}
	}

	/**
	 * Make custom columns sortable.
	 *
	 * @since 0.1.0
	 */
	function sort_custom_columns() {

		return array(
			'title'   => 'title',
			//'rating' => 'rating',
            'tml-author' => 'tml-author',
			'company' => 'company',
			'date'    => 'date',
			'id'      => 'id'
		);
	}

	/**
	 * Move featured image meta box.
	 *
	 * @since 0.1.0
	 */
	public function move_featured_image_metabox() {

		$post_types = get_post_types( array( '_builtin' => false ) );

		if ( in_array( 'wpgo_testimonial', $post_types ) ) {
			remove_meta_box( 'postimagediv', 'wpgo_testimonial', 'side' );
			add_meta_box( 'postimagediv', __( 'Testimonial Image', 'wpgo-ultimate-testimonials' ), 'post_thumbnail_meta_box', 'wpgo_testimonial', 'side', 'low' );
		}
	}

	/**
	 * Initialise custom post type meta boxes.
	 *
	 * @since 0.1.0
	 */
	public function testimonial_cpt_meta_box_init() {

		/* Add meta boxes to Testimonials CPT editor. */
		add_meta_box( 'wpgo-testimonial-cpt', __( 'Testimonial Details', 'wpgo-ultimate-testimonials' ), array( &$this, 'meta_box_company_info' ), 'wpgo_testimonial', 'normal', 'high' );
		add_meta_box( 'wpgo-testimonial-cpt_sc', __( 'Testimonial Shortcodes', 'wpgo-ultimate-testimonials' ), array( &$this, 'meta_box_shortcode' ), 'wpgo_testimonial', 'normal', 'low' );

		/* Hook to save our meta box data when the post is saved. */
		add_action( 'save_post', array( &$this, 'save_meta_box_company_info' ) );
	}

	/**
	 * Display the meta box for testimonials data fields.
	 *
	 * @since 0.1.0
	 */
	public function meta_box_company_info( $post, $args ) {

		/* Retrieve our custom meta box values */
		$testimonial_cpt_author =       get_post_meta( $post->ID, '_wpgo_testimonial_cpt_author', true );
		$testimonial_cpt_company =      get_post_meta( $post->ID, '_wpgo_testimonial_cpt_company', true );
		$testimonial_cpt_company_url =  get_post_meta( $post->ID, '_wpgo_testimonial_cpt_company_url', true );
		$testimonial_cpt_image =        get_post_meta( $post->ID, '_wpgo_testimonial_cpt_image', true );
		$testimonial_cpt_rating = get_post_meta( $post->ID, '_wpgo_testimonial_cpt_rating', true );

		// Update testimonial width value if a custom width is used for the current theme.
		$w = defined( 'WPGO_TESTIMONIAL_THUMB_WIDTH' ) ? WPGO_TESTIMONIAL_THUMB_WIDTH : 50;
		?>

		<table width="100%">
			<tbody>
            <tr>
                <td><?php _e( 'Testimonial Author', 'wpgo-ultimate-testimonials' ); ?></td>
                <td>
                    <input style="width:100%;" type="text" name="wpgo_testimonial_cpt_author" value="<?php echo esc_attr( $testimonial_cpt_author ); ?>">
                </td>
            </tr>
			<tr>
				<td><?php _e( 'Company', 'wpgo-ultimate-testimonials' ); ?></td>
				<td>
					<input style="width:100%;" type="text" name="wpgo_testimonial_cpt_company" value="<?php echo esc_attr( $testimonial_cpt_company ); ?>">
				</td>
			</tr>
			<tr>
				<td><?php _e( 'Company Link', 'wpgo-ultimate-testimonials' ); ?></td>
				<td>
					<input style="width:100%;" type="text" name="wpgo_testimonial_cpt_company_url" value="<?php echo esc_attr( $testimonial_cpt_company_url ); ?>">
				</td>
			</tr>
			<tr>
				<td width="100"><?php _e( 'Gravatar E-mail', 'wpgo-ultimate-testimonials' ); ?></td>
				<td>
					<input style="width:100%;" type="text" name="wpgo_testimonial_cpt_image" value="<?php echo esc_attr( $testimonial_cpt_image ); ?>">
				</td>
			</tr>
			<tr>
				<td>&nbsp;</td>
				<td>
					<p class="description"><?php printf( __( 'To upload an image, use the Testimonial Image feature to the right (recommended %1$d x %2$d pixels), or enter a Gravatar e-mail above. Leave field blank to NOT show an image.', 'wpgo-ultimate-testimonials' ), $w, $w ); ?></p>
				</td>
			</tr>
			<tr>
				<td>Rating</td>
				<td>
					<select name='wpgo_testimonial_cpt_rating'>
						<option	value='a' <?php selected( 'a', $testimonial_cpt_rating ); ?>>(none)</option>
						<option	value='b' <?php selected( 'b', $testimonial_cpt_rating ); ?>>0</option>
						<option	value='c' <?php selected( 'c', $testimonial_cpt_rating ); ?>>0.5</option>
						<option	value='d' <?php selected( 'd', $testimonial_cpt_rating ); ?>>1</option>
						<option	value='e' <?php selected( 'e', $testimonial_cpt_rating ); ?>>1.5</option>
						<option	value='f' <?php selected( 'f', $testimonial_cpt_rating ); ?>>2</option>
						<option	value='g' <?php selected( 'g', $testimonial_cpt_rating ); ?>>2.5</option>
						<option	value='h' <?php selected( 'h', $testimonial_cpt_rating ); ?>>3</option>
						<option	value='i' <?php selected( 'i', $testimonial_cpt_rating ); ?>>3.5</option>
						<option	value='j' <?php selected( 'j', $testimonial_cpt_rating ); ?>>4</option>
						<option	value='k' <?php selected( 'k', $testimonial_cpt_rating ); ?>>4.5</option>
						<option	value='l' <?php selected( 'l', $testimonial_cpt_rating ); ?>>5</option>
					</select>
					<p class="description"><?php _e( "Select 'none' to display no rating for this testimonial.", "wpgo-ultimate-testimonials" ); ?></p>
				</td>
			</tr>
			</tbody>
		</table>
	<?php
	}

	/**
	 * Save the custom post type meta box input field settings.
	 *
	 * @since 0.1.0
	 */
	public function save_meta_box_company_info( $post_id ) {

		global $typenow;

		/* Only work for specific post type */
		if ( $typenow != 'wpgo_testimonial' ) {
			return;
		}

		/* Save the meta box data as post meta, using the post ID as a unique prefix. */
		if ( isset( $_POST['wpgo_testimonial_cpt_company'] ) ) {
			update_post_meta( $post_id, '_wpgo_testimonial_cpt_company', esc_attr( $_POST['wpgo_testimonial_cpt_company'] ) );
		}

		if ( isset( $_POST['wpgo_testimonial_cpt_author'] ) ) {
			update_post_meta( $post_id, '_wpgo_testimonial_cpt_author', esc_attr( $_POST['wpgo_testimonial_cpt_author'] ) );
		}

		if ( isset( $_POST['wpgo_testimonial_cpt_company_url'] ) ) {
			update_post_meta( $post_id, '_wpgo_testimonial_cpt_company_url', esc_attr( $_POST['wpgo_testimonial_cpt_company_url'] ) );
		}

		if ( isset( $_POST['wpgo_testimonial_cpt_image'] ) ) {
			update_post_meta( $post_id, '_wpgo_testimonial_cpt_image', esc_attr( $_POST['wpgo_testimonial_cpt_image'] ) );
		}

		if ( isset( $_POST['wpgo_testimonial_cpt_rating'] ) ) {
			update_post_meta( $post_id, '_wpgo_testimonial_cpt_rating', esc_attr( $_POST['wpgo_testimonial_cpt_rating'] ) );
		}
	}

	/**
	 * Display meta box to show shortcode for the current testimonial.
	 *
	 * @since 0.1.0
	 */
	public function meta_box_shortcode( $post, $args ) {

		$id = $post->ID;

		$tml_terms = get_the_terms( $id, 'wpgo_testimonial_group' );
		$tml_description = __( 'Copy and paste the shortcode above into any post, or page, to display the single testimonial.', 'wpgo-ultimate-testimonials' );
		$group_tml_html = '';

		if( !empty($tml_terms) ) {
			$group_sc = '';
			$tml_description = __( 'Copy and paste ONE of the shortcodes above into any post, or page, to display the single testimonial or a group of testimonials.', 'wpgo-ultimate-testimonials' );

			foreach($tml_terms as $tml_term) {
				$group_sc .= "[tml group='{$tml_term->term_id}'] ";
			}
			$group_sc = trim($group_sc); // trim trailing space

			if( count($tml_terms) > 1 ) {
				$group_label = "Group testimonial shortcodes";
			} elseif( count($tml_terms) == 1 ) {
				$group_label = "Group testimonial shortcode";
			}

			$group_tml_html = '<tr><td>
				<h4 style="margin: 5px 0;">'.$group_label.'</h4>
				<input style="width:100%;font-family: Courier New;" type="text" readonly name="wpgo_group_testimonial_cpt_sc" value="'.$group_sc.'">
			</td></tr>';
		}

		$single_sc = "[tml id='{$id}']";
		?>

		<table width="100%">
			<tbody>
			<tr>
				<td>
					<h4 style="margin: 5px 0;"><?php _e( 'Single testimonial shortcode', 'wpgo-ultimate-testimonials' ); ?></h4>
					<input style="width:100%;font-family: Courier New;" type="text" readonly name="wpgo_single_testimonial_cpt_sc" value="<?php echo $single_sc; ?>">
				</td>
			</tr>
			<?php echo $group_tml_html; ?>
			<tr>
				<td >
					<p style="margin-top: 7px;" class="description"><?php echo $tml_description; ?></p>
				</td>
			</tr>
			</tbody>
		</table>
	<?php
	}

	/**
	 * Save the custom post type meta box input field settings.
	 *
	 * @since 0.1.0
	 */
	public function update_cpt_messages( $messages ) {
		global $post, $post_ID;

		$messages['wpgo_testimonial'] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( __( 'Testimonial updated.', 'wpgo-ultimate-testimonials' ), esc_url( get_permalink( $post_ID ) ) ),
			2  => __( 'Custom field updated.', 'wpgo-ultimate-testimonials' ),
			3  => __( 'Custom field deleted.', 'wpgo-ultimate-testimonials' ),
			4  => __( 'Testimonial updated.', 'wpgo-ultimate-testimonials' ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Testimonial restored to revision from %s', 'wpgo-ultimate-testimonials' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( __( 'Testimonial published.', 'wpgo-ultimate-testimonials' ), esc_url( get_permalink( $post_ID ) ) ),
			7  => __( 'Testimonial saved.', 'wpgo-ultimate-testimonials' ),
			8  => sprintf( __( 'Testimonial submitted.', 'wpgo-ultimate-testimonials' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
			9  => sprintf( __( 'Testimonial scheduled for: %1$s.', 'wpgo-ultimate-testimonials' ),
				// translators: Publish box date format, see http://php.net/date
				'<strong>' . date_i18n( __( 'M j, Y @ G:i', 'wpgo-ultimate-testimonials' ), strtotime( $post->post_date ) ) . '</strong>', esc_url( get_permalink( $post_ID ) ) ),
			10 => sprintf( __( 'Testimonial draft updated.', 'wpgo-ultimate-testimonials' ), esc_url( add_query_arg( 'preview', 'true', get_permalink( $post_ID ) ) ) ),
		);

		return $messages;
	}

	/**
	 * Update the title edit prompt message shown when editing a new testimonial.
	 *
	 * @since 0.1.0
	 */
	public function update_title_message( $message ) {
		global $post;

		$pt = get_post_type( $post );
		if ( $pt == 'wpgo_testimonial' ) {
			$message = __( 'Enter your name here', 'wpgo-ultimate-testimonials' );
		}

		return $message;
	}

	/**
	 * Filter the request to just give posts for the given taxonomy.
	 *
	 * @since 0.1.0
	 */
	public function taxonomy_filter_restrict_manage_posts() {
		global $typenow;

		/* Only work for specific post type */
		if ( $typenow != 'wpgo_testimonial' ) {
			return;
		}

		$post_types = get_post_types( array( '_builtin' => false ) );

		if ( in_array( $typenow, $post_types ) ) {
			$filters = get_object_taxonomies( $typenow );

			foreach ( $filters as $tax_slug ) {
				if ( ! isset( $_GET[$tax_slug] ) ) {
					$selected = '';
				} else {
					$selected = $_GET[$tax_slug];
				}

				$tax_obj = get_taxonomy( $tax_slug );
				wp_dropdown_categories( array(
					'taxonomy'     => $tax_slug,
					'name'         => $tax_obj->name,
					'orderby'      => 'name',
					'selected'     => $selected,
					'hierarchical' => $tax_obj->hierarchical,
					'show_count'   => true,
					'hide_empty'   => true
				) );
			}
		}
	}

	/**
	 * Add a filter to the query so the dropdown will work.
	 *
	 * @since 0.1.0
	 */
	public function taxonomy_filter_post_type_request( $query ) {
		global $pagenow, $typenow;

		if ( 'edit.php' == $pagenow ) {
			$filters = get_object_taxonomies( $typenow );
			foreach ( $filters as $tax_slug ) {
				$var = & $query->query_vars[$tax_slug];
				if ( isset( $var ) ) {
					$term = get_term_by( 'id', $var, $tax_slug );
					$var  = $term->slug;
				}
			}
		}
	}

	/**
	 * Enqueue front end scripts needed for testimonials.
	 *
	 * @since 0.1.0
	 */
	public function enqueue_testimonial_scripts() {

		wp_register_style( 'wpgo-testimonial', $this->module_roots['uri'] . '/lib/css/wpgo-testimonials.css' );
		wp_enqueue_style( 'wpgo-testimonial' );
	}
}