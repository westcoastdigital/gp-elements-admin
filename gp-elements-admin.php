<?php
/*
Plugin Name: GP Elements Admin Link
Plugin URI: https://github.com/WestCoastDigital/gp-elements-admin
Description: Adds GeneratePress Elements lnks to admin bar
Version: 1.2
Author: Jon Mather
Author URI: https://westcoastdigital.com.au/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GP_ELEMENTS_URL', plugin_dir_url( __FILE__ ) );
define( 'GP_ELEMENTS_PATH', plugin_dir_path( __FILE__ ) );

/**
 * Checks if GP Premium is active
 */
function gp_premium_active() {
	if (defined('GP_PREMIUM_VERSION')) {
		return true;
	}
}

/**
 * Checks if we should add links to the bar.
 */
function gp_elements_admin_bar_init() {
	// Is the user sufficiently leveled, or has the bar been disabled?
	if ( ! is_super_admin() || ! is_admin_bar_showing() || ! gp_premium_active() ) {
		return;
	}

	// Good to go, lets do this!
	add_action( 'admin_bar_menu', 'gp_elements_admin_bar_links', 500 );

}

// Get things running!
add_action( 'admin_bar_init', 'gp_elements_admin_bar_init' );

/**
 * Adds links to the bar.
 */
function gp_elements_admin_bar_links() {
	global $wp_admin_bar;
	$options = get_option( 'gp_elements_settings' );
	$options = $options['types'];

	$wp_ver = get_bloginfo( 'version' );

	$iconurl = GP_ELEMENTS_URL . '/images/edit-page-icon.png';

	$iconspan = '<span class="ab-icon" style="
    float:left; width:22px !important; height:22px !important;
    margin-left: 5px !important; margin-top: 5px !important;
    background-image:url(\''.$iconurl.'\')!important;background-size: contain;background-repeat: no-repeat;"></span>';

	if ( floatval( $wp_ver ) >= 3.8 ) {
		$title = $iconspan.'<span class="ab-label">' . __( 'Elements', 'gp_elements' ) . '</span>';
		$img   = '';
	} else {
		$title = '<span class="ab-icon"><img src="' . GP_ELEMENTS_URL . '/images/edit-page-icon.png" /></span><span class="ab-label">' . __( 'Edit Content', 'gp_elements' ) . '</span>';
		$img   = '_no_dashicon';
	}

	$admin_url = admin_url();

	// Add the Parent link.
	$wp_admin_bar->add_menu(
		array(
			'title' => $title,
			'href'  => false,
			'id'    => 'gp_elements_links' . $img,
		)
	);


	$wp_admin_bar->add_menu(
		array(
			'title'  => __('Add New', 'generatepress'),
			'href'   => admin_url('post-new.php?post_type=gp_elements'),
			'id'     => 'new_gp_element',
			'parent' => 'gp_elements_links' . $img,
		)
	);

	$wp_admin_bar->add_menu(
		array(
			'title'  => __('Headers', 'generatepress'),
			// 'href'   => admin_url('post-new.php?post_type=gp_elements'),
			'id'     => 'header',
			'parent' => 'gp_elements_links' . $img,
		)
	);

	$wp_admin_bar->add_menu(
		array(
			'title'  => __('Layouts', 'generatepress'),
			// 'href'   => admin_url('post-new.php?post_type=gp_elements'),
			'id'     => 'layout',
			'parent' => 'gp_elements_links' . $img,
		)
	);

	$wp_admin_bar->add_menu(
		array(
			'title'  => __('Hooks', 'generatepress'),
			// 'href'   => admin_url('post-new.php?post_type=gp_elements'),
			'id'     => 'hook',
			'parent' => 'gp_elements_links' . $img,
		)
	);

	$post_type = 'gp_elements';

		$args = array(
			'order'          => 'ASC',
			'orderby'        => 'menu_order',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'post_type'      => 'gp_elements',
		);

		// Filter the args now
		if ( has_filter( 'gp_elements_query_args' ) ) {
			$args = apply_filters( 'gp_elements_query_args', $args );

			// Let's reset the post type in case the user inadvertently changed it - there's really no reaon to change it.
			$args['post_type'] = $post_type;
		}

		$gp_elements_query = new WP_Query( $args );

		if ( $gp_elements_query->have_posts() ) :

			if ( is_post_type_hierarchical( $post_type ) && 'menu_order' === $args['orderby'] ) {
				// Sort them into a hierarchical list here
				$gp_elements_query->posts = gp_elements_sort_hierarchical_posts( $gp_elements_query->posts );
			}

			foreach ( $gp_elements_query->posts as $post ) {
				$type = get_post_meta( $post->ID, '_generate_element_type', true );

				if ( 0 !== $post->post_parent && 'menu_order' === $args['orderby'] ) {
					$label     = '&nbsp;&nbsp;&ndash; ' . ucwords( $post->post_title );
					$parent_id = $post->post_parent;

					// Loop through to indent the post type the appropriate number of times
					$post_ancestors_count = count( get_post_ancestors( $parent_id ) );
					for ( $i = 0; $i < $post_ancestors_count; $i++ ) {
						$label = '&nbsp;&nbsp;&nbsp;' . $label;
					}
				} else {
					$label = ucwords( $post->post_title );
				}

				$url = get_edit_post_link( $post->ID );

				$wp_admin_bar->add_menu(
					array(
						'title'  => $label,
						'href'   => $url,
						'id'     => $post->ID,
						'parent' => $type,
					)
				);
			}
		endif;

	}