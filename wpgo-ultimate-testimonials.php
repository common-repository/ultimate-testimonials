<?php
/*
Plugin Name: Ultimate Testimonials
Plugin URI: http://wordpress.org/plugins/ultimate-testimonials/
Description: The only testimonials plugin you'll ever need! Fully functional and includes plenty of great features.
Version: 0.2
Author: David Gwyer
Author URI: http://www.wpgoplugins.com
*/

/*  Copyright 2017 David Gwyer (email : david@wpgoplugins.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Bootstrap class for WPGO Ultimate Testimonials. */
class WPGO_Ultimate_Testimonials {

	protected $module_roots;

	/* Main class constructor. */
	public function __construct($module_roots) {

		// If the Ultimate Testimonials plugin is running then don't load the same classes
		if ( class_exists( 'WPGO_Testimonial_CPT' ) ) {
			return;
		}

		$this->module_roots = $module_roots;

		// add support by default
		add_theme_support( 'wpgo-ultimate-testimonials' );

		/* Setup class default features. */
		add_action( 'after_setup_theme', array( &$this, 'setup_default_features' ), 9 ); // higher priority to allow feature removal via parent theme functions.php

		/* Priority set to 12 so the callback fires AFTER the supported features have been specified in the extended WPGO_Theme_Framework class.
		 * This allows a call in a child theme such as: add_action( 'after_setup_theme', 'child_framework_features', 11 ) to easily remove/redefine added features.
		 */
		add_action( 'after_setup_theme', array( &$this, 'load_supported_features' ), 12 );

		// Customizer Control
		// @todo this has to be here rather than in load_features()
		// @todo find a way to disable the customizer control(s) if tmls not supported
		require_once( $this->module_roots['dir'] . 'lib/classes/customizer/customize-global-tml-templates.php' );
		new WPGO_Customize_Testimonial_Templates($this->module_roots);

		/* Load widgets. */
		add_action( 'widgets_init', array( &$this, 'register_widgets' ) );
		add_action( 'customize_controls_enqueue_scripts', array( &$this, 'enqueue_customizer_scripts' ) );
		add_action( 'plugins_loaded', array( &$this, 'localize_plugin' ) );
	}

	/**
	 * Setup class default features.
	 *
	 * @since 0.2.0
	 */
	public function setup_default_features() {

	}

	/* Check for specific CPT used in the current WPGO theme. */
	public function load_supported_features() {

		if ( current_theme_supports( 'wpgo-ultimate-testimonials' ) ) {
			$this->load_features();
		}
	}

	/**
	 * Load testimonial features.
	 *
	 * @since 0.2.0
	 */
	public function load_features() {

		$root = $this->module_roots['dir'];

		// Testimonial CPT
		require_once( $root . 'lib/classes/cpt/testimonial.php' );
		new WPGO_Testimonial_CPT($this->module_roots);

		// Testimonial Shortcodes
		require_once( $root . 'lib/classes/shortcodes/tml-shortcodes.php' );
		new WPGO_Testimonial_Shortcodes($this->module_roots);

		// Testimonial Data
		require_once( $root . 'lib/classes/templates/testimonial-data.php' );

		// Testimonial Templates
		require_once( $root . 'lib/classes/templates/testimonial-templates.php' );

		// Testimonial Post Templates
		require_once( $root . 'lib/classes/templates/testimonial-post-templates.php' );

		// Testimonial Settings
		require_once( $root . 'lib/classes/templates/testimonial-settings.php' );
		new WPGO_Testimonial_Options();

		// Allow shortcodes to be used in widgets. These callbacks are WordPress functions.
		add_filter( 'widget_text', 'shortcode_unautop' );
		add_filter( 'widget_text', 'do_shortcode' );
	}

	/**
	 * Register widgets.
	 *
	 * @since 0.2.0
	 */
	function register_widgets() {

		// Testimonial Widgets
		require_once( $this->module_roots['dir'] . 'lib/classes/widgets/widget-tml.php' );
		register_widget( 'wpgo_tml_widget' );
	}

	/**
	 * Enqueue scripts and styles for the customizer panel (NOT preview window).
	 *
	 * @since 0.2.0
	 */
	public function enqueue_customizer_scripts() {

		$root = $this->module_roots['uri'];

		wp_enqueue_style( 'wpgo-ultimate-tml-customizer-styles', $root . '/lib/css/wpgo-tml-customizer.css' );
	}

	/**
	 * Add Plugin localization support.
	 */
	function localize_plugin() {
		load_plugin_textdomain( 'ultimate-testimonials', false, plugin_basename( $this->module_roots['dir'] ) . '/languages' );
	}

} /* End class definition */

$module_roots = array(
	'dir' => plugin_dir_path( __FILE__ ),
	'uri' => plugins_url( '', __FILE__ ),
);
new WPGO_Ultimate_Testimonials( $module_roots );
