<?php
/**
 * WPBBP functions and definitions
 *
 * @package WPBBP
 */

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook. The init hook is too late for some features, such
 * as indicating support for post thumbnails.
 */
function setup() {
	add_theme_support( 'post-thumbnails' );
}

add_action( 'after_setup_theme', __NAMESPACE__ . '\setup' );

/**
 * The Header for our theme.
 *
 * @package WPBBP
 */
function wporg_get_global_header() {
	$GLOBALS['pagetitle'] = wp_title( '&#124;', false, 'right' ) . __( 'WordPress.org', 'wporg-learn' );
	require WPORGPATH . 'header.php';
}

/**
 * Get the taxonomies associated to workshop
 *
 * @package WPBBP
 */
function wporg_get_tax_slugs_from_workshop() {
	return wp_get_post_terms( get_the_ID(), 'lesson_group',  array( 'fields' => 'slugs' ) );
}

/**
 * Get the lesson plans associated to a taxonomy
 *
 * @param string $slugs Comma separated list of taxonomy terms
 * @package WPBBP
 */
function wporg_get_lesson_plans_by_tax_slugs_query( $slugs ) {
	$args = array(
		'post_type' => 'lesson-plan',
		'tax_query' => array(
			array(
				'taxonomy' => 'lesson_group',
				'field'    => 'slug',
				'terms'    => $slugs,
			),
		),
	);

	// Get all the lesson plans associated to
	return new WP_Query( $args );
}

/**
 * Get the category from the query vars
 *
 * @package WPBBP
 */
function wporg_get_filter_category() {
	return get_query_var( 'category' );
}

/**
 * Returns a list of filter categories
 *
 * @return array
 */
function wporg_get_filter_categories() {
	return get_categories();
}

/**
 * Returns the default filter category key
 *
 * @return string|null
 */
function wporg_get_default_cat() {
	$cats = wporg_get_filter_categories();

	return reset( $cats );
}

/**
 * Returns the default category if category is not defined
 *
 * @return string
 */
function wporg_get_cat_or_default_slug() {
	$cat = wporg_get_filter_category();

	if( empty( $cat ) ) {
		return wporg_get_default_cat()->slug;
	}

	return $cat;
}


/**
 * Get the values associated to the page/post
 *
 * @param string $id Id of the post
 * @param string $tax_slug The slug for the custom taxonomy
 * @return string
 */
function get_taxonomy_values( $id, $tax_slug ){
	$terms = wp_get_post_terms( $id, $tax_slug, array( 'fields' => 'names' )  );
	return implode( ', ', $terms );
}


/**
 * Returns the taxonomies associated to a lesson or workshop
 *
 * @param string $id Id of the post
 * @return string
 */
function wporg_get_custom_taxonomies( $id ) {
	return [
		[
			'icon' => 'clock',
			'label' => 'Length:',
			'values' => get_taxonomy_values( $id, 'duration' )
		],
		[
			'icon' => 'admin-users',
			'label' => 'Audience:',
			'values' => get_taxonomy_values( $id, 'audience' )
		],
		[
			'icon' => 'dashboard',
			'label' => 'Level:',
			'values' => get_taxonomy_values( $id, 'level' )
		],
		[
			'icon' => 'welcome-learn-more',
			'label' => 'Type of Instruction:',
			'values' => get_taxonomy_values( $id, 'instruction_type' )
		]
	];
}

/**
 * Returns whether the post type is a workshop
 *
 * @return bool
 */
function wporg_post_type_is_workshop() {
	return get_post_type() == 'workshop';
}

/**
 * Returns whether the post type is a lesson-plan
 *
 * @return bool
 */
function wporg_post_type_is_lesson() {
	return get_post_type() == 'lesson-plan';
}

/**
 * Returns the custom field view_lesson_plan_slides_url, if it doesn't exists returns false
 *
 * @return string|bool
 */
function wporg_get_slides_url() {
	return get_post_meta( get_the_ID(), 'view_lesson_plan_slides_url', true );
}

/**
 * Returns the custom field download_lesson_plan_slides_url, if it doesn't exists returns false
 *
 * @return string|bool
 */
function wporg_get_download_slides_url() {
return get_post_meta( get_the_ID(), 'download_lesson_plan_slides_url', true );
}

/**
 * Submit CTA button
 *
 * @package WPBBP
 */
function wporg_submit_idea_cta() { ?>

	<section class="submit-idea-cta">
		<div class="content-icon"><span class="dashicons dashicons-lightbulb"></span></div>
		<h2><?php _e( 'Have an Idea for a Workshop? Let us know!' ); ?></h2>
		<a class="button button-primary button-large" href="https://wordcampcentral.survey.fm/learn-wordpress-workshop-application"><?php _e( 'Submit an Idea' ); ?></a>
	</section>

<?php }

/**
 * Returns whether all post for workshop
 *
 * @return array
 */
function wporg_get_workshops( $options = NULL ) {
	$args = array(
		'post_type' => 'wporg_workshop',
	);

	if( ! is_null( $options ) ) {
		$args = array_merge( $args, $options );

	}

	$query = new \WP_Query( $args );
	return $query;
}

/**
 * Find a wp.tv or YouTube video in a workshop post and output the markup for it.
 *
 * @param WP_Post|null $workshop
 * @param string $fallback
 *
 * @return string
 */
function wporg_get_workshop_video( $workshop = null, $fallback = 'thumbnail' ) {
	// Recursive search function to search blocks and inner blocks for an array of block names.
	$find = function( $block_names, $blocks ) use ( &$find ) {
		$return = false;

		foreach ( $blocks as $block ) {
			if ( in_array( $block['blockName'], $block_names, true ) ) {
				$return = $block;
			} elseif ( isset( $block['innerBlocks'] ) ) {
				$return = $find( $block_names, $block['innerBlocks'] );
			}

			if ( $return ) {
				break;
			}
		}

		return $return;
	};

	$post = get_post( $workshop );
	$blocks = parse_blocks( $post->post_content );
	$found_block = $find( array( 'core-embed/wordpress-tv', 'core-embed/youtube' ), $blocks );

	if ( ! isset( $found_block['attrs']['url'] ) || ! $found_block['attrs']['url'] ) {
		if ( 'thumbnail' === $fallback ) {
			return get_the_post_thumbnail( $post, 'full' );
		} else {
			return '';
		}
	}

	return apply_filters( 'the_content', render_block( $found_block ) );
}

/**
 * Returns the presenters for the workshop.
 *
 * @param WP_Post|int $workshop
 *
 * @return WP_User[]|array
 */
function wporg_get_workshop_presenters( $workshop = null ) {
	$post       = get_post( $workshop );
	$presenters = get_post_meta( $post->ID, 'presenter_wporg_username' );
	$wp_users   = array();

	foreach ( $presenters as $presenter ) {
		$wp_user = get_user_by( 'login', $presenter );

		if ( $wp_user ) {
			array_push( $wp_users, $wp_user );
		}
	}

	return $wp_users;
}
