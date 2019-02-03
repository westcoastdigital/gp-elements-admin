<?php
/*
Plugin Name: GP Elements Admin Link
Plugin URI: https://github.com/WestCoastDigital/gp-elements-admin
Description: Adds GeneratePress Elements lnks to admin bar
Version: 1.0.0
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

	if ( floatval( $wp_ver ) >= 3.8 ) {
		$title = '<span class="ab-icon"></span><span class="ab-label">' . __( 'Elements', 'gp_elements' ) . '</span>';
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
						'parent' => 'gp_elements_links' . $img,
					)
				);
			}
		endif;

	}
// }

function gp_elements_sort_hierarchical_posts( $posts ) {
	$final_post_array = array();

	foreach ( $posts as $index => $post ) {
		if ( 0 === $post->post_parent ) {
			$post_id_array[0][ $index ] = $post->ID;
		} else {
			$post_id_array[ $post->post_parent ][ $index ] = $post->ID;
		}
	}

	ksort( $post_id_array );

	foreach ( $post_id_array[0] as $index => $post_id ) {
		$final_post_array[] = $posts[ $index ];
		gp_elements_check_children( $post_id, $post_id_array, $final_post_array, $posts );
	}

	return $final_post_array;
}

function gp_elements_check_children( $post_id, $post_id_array, &$final_post_array, $posts ) {
	if ( isset( $post_id_array[ $post_id ] ) ) {
		foreach ( $post_id_array[ $post_id ] as $index => $child_id ) {
			$final_post_array[] = $posts[ $index ];
			gp_elements_check_children( $child_id, $post_id_array, $final_post_array, $posts );
		}
	}
}

function gp_elements_activation_callback() {
	$options = get_option( 'gp_elements_settings', array() );

	$default = array(
		'types' => array(
			'page' => 'Pages',
		),
	);

	if ( empty( $options ) ) {
		update_option( 'gp_elements_settings', $default );
	}
}
