<?php

namespace DevHub;

/**
 * Registrations (post type, taxonomies, etc).
 */
require __DIR__ . '/inc/registrations.php';

/**
 * HTML head tags and customizations.
 */
require __DIR__ . '/inc/head.php';

/**
 * Custom template tags for this theme.
 */
require __DIR__ . '/inc/template-tags.php';

/**
 * Custom functions that act independently of the theme templates.
 */
require __DIR__ . '/inc/extras.php';

/**
 * Customizer additions.
 */
require __DIR__ . '/inc/customizer.php';

/**
 * Load Jetpack compatibility file.
 */
require __DIR__ . '/inc/jetpack.php';

/**
 * Class for editing parsed content on the Function, Class, Hook, and Method screens.
 */
require_once( __DIR__ . '/inc/parsed-content.php' );

if ( ! function_exists( 'loop_pagination' ) ) {
	require __DIR__ . '/inc/loop-pagination.php';
}

if ( ! function_exists( 'breadcrumb_trail' ) ) {
	require __DIR__ . '/inc/breadcrumb-trail.php';
}

/**
 * User-submitted content (comments, notes, etc).
 */
require __DIR__ . '/inc/user-content.php';

/**
 * User-submitted content preview.
 */
require __DIR__ . '/inc/user-content-preview.php';

/**
 * Voting for user-submitted content.
 */
require __DIR__ . '/inc/user-content-voting.php';

/**
 * Editing for user-submitted content.
 */
require __DIR__ . '/inc/user-content-edit.php';

/**
 * CLI commands custom post type and importer.
 */
require __DIR__ . '/inc/cli.php';

/**
 * Docs importer.
 */
if ( class_exists( '\\WordPressdotorg\\Markdown\\Importer' ) ) {
	// Docs Importer base class.
	require __DIR__ . '/inc/import-docs.php';

	// Block Editor handbook.
	require __DIR__ . '/inc/import-block-editor.php';

	// Coding Standards handbook.
	require __DIR__ . '/inc/import-coding-standards.php';

	// REST API handbook.
	require __DIR__ . '/inc/rest-api.php';
}

/**
 * Explanations for functions. hooks, classes, and methods.
 */
require( __DIR__ . '/inc/explanations.php' );

/**
 * Handbooks.
 */
require __DIR__ . '/inc/handbooks.php';

/**
 * Redirects.
 */
require __DIR__ . '/inc/redirects.php';

/**
 * Content formatting.
 */
require __DIR__ . '/inc/formatting.php';

/**
 * Autocomplete.
 */
require __DIR__ . '/inc/autocomplete.php';

/**
 * Search query.
 */
require __DIR__ . '/inc/search.php';

/**
 * Parser customizations.
 */
require __DIR__ . '/inc/parser.php';

/**
 * CLI commands.
 */
if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require __DIR__ . '/inc/cli-commands.php';
}

/**
 * Admin area customizations.
 */
if ( is_admin() ) {
	require __DIR__ . '/inc/admin.php';
}

/**
 * Function reference sidebar menu.
 */
require __DIR__ . '/inc/sidebar-menu.php';

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 640; /* pixels */
}


add_action( 'init', __NAMESPACE__ . '\\init' );
add_action( 'widgets_init', __NAMESPACE__ . '\\widgets_init' );

/**
 * Set up the theme.
 */
function init() {

	register_nav_menus();

	add_action( 'pre_get_posts', __NAMESPACE__ . '\\pre_get_posts' );
	add_action( 'wp_enqueue_scripts', __NAMESPACE__ . '\\theme_scripts_styles' );
	add_action( 'wp_head', __NAMESPACE__ . '\\header_js' );
	add_action( 'add_meta_boxes', __NAMESPACE__ . '\\rename_comments_meta_box', 10, 2 );

	add_filter( 'post_type_link', __NAMESPACE__ . '\\method_permalink', 11, 2 );
	add_filter( 'term_link', __NAMESPACE__ . '\\taxonomy_permalink', 10, 3 );

	add_theme_support( 'automatic-feed-links' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'title-tag' );

	// Modify default breadcrumbs.
	add_filter( 'breadcrumb_trail_items', __NAMESPACE__ . '\\breadcrumb_trail_items_for_hooks', 10, 2 );
	add_filter( 'breadcrumb_trail_items', __NAMESPACE__ . '\\breadcrumb_trail_items_for_handbook_root', 10, 2 );

	add_filter( 'mkaz_code_syntax_force_loading', '__return_true' );
	add_filter( 'mkaz_prism_css_path', __NAMESPACE__ . '\\update_prism_css_path' );

	add_filter( 'body_class', __NAMESPACE__ . '\\body_class' );
}

/**
 * Fix breadcrumb for hooks.
 *
 * A hook has a parent (the function containing it), which causes the Breadcrumb
 * Trail plugin to introduce trail items related to the parent that shouldn't
 * be shown.
 *
 * @param  array $items The breadcrumb trail items.
 * @param  array $args  Original args.
 * @return array
 */
function breadcrumb_trail_items_for_hooks( $items, $args ) {
	$post_type = 'wp-parser-hook';

	// Bail early when not the single archive for hook
	if ( ! is_singular() || get_post_type() !== $post_type || ! isset( $items[4] ) ) {
		return $items;
	}

	$post_type_object = get_post_type_object( $post_type );

	// Replaces 'Functions' archive link with 'Hooks' archive link
	$items[2] = '<a href="' . get_post_type_archive_link( $post_type ) . '">' . $post_type_object->labels->name . '</a>';
	// Replace what the plugin thinks is the parent with the hook name
	$items[3] = $items[4];
	// Unset the last element since it shifted up in trail hierarchy
	unset( $items[4] );

	return $items;
}

/**
 * Fix breadcrumb for handbook root pages.
 *
 * The handbook root/landing pages do not need a duplicated breadcrumb trail
 * item that simply links to the currently loaded page. The trailing breadcrumb
 * item is already the unlinked handbook name, which is sufficient.
 *
 * @param  array $items The breadcrumb trail items.
 * @param  array $args  Original args.
 * @return array
 */
function breadcrumb_trail_items_for_handbook_root( $items, $args ) {
	// Bail early if not a handbook landing page.
	if ( ! function_exists( 'wporg_is_handbook_landing_page' ) || ! wporg_is_handbook_landing_page() ) {
		return $items;
	}

	// Unset link to current handbook.
	unset( $items[1] );

	return $items;
}

/**
 * Register widget areas.
 *
 * @access public
 * @return void
 */
function widgets_init() {
	register_sidebar(
		array(
			'name'          => __( 'Sidebar', 'wporg' ),
			'id'            => 'sidebar-1',
			'before_widget' => '<aside id="%1$s" class="box gray widget %2$s">',
			'after_widget'  => '</div></aside>',
			'before_title'  => '<h1 class="widget-title">',
			'after_title'   => '</h1><div class="widget-content">',
		)
	);

	register_sidebar(
		array(
			'name'          => __( 'Landing Page Footer - Left', 'wporg' ),
			'id'            => 'landing-footer-1',
			'description'   => __( 'Appears in footer of the primary landing page', 'wporg' ),
			'before_widget' => '<div id="%1$s" class="widget box %2$s">',
			'after_widget'  => '</div>',
			'before_title'  => '<h4 class="widget-title">',
			'after_title'   => '</h4>',
		)
	);

	register_sidebar( array(
		'name'          => __( 'Landing Page Footer - Center', 'wporg' ),
		'id'            => 'landing-footer-2',
		'description'   => __( 'Appears in footer of the primary landing page', 'wporg' ),
		'before_widget' => '<div id="%1$s" class="widget box %2$s">',
		'after_widget'  => '</div>',
		'before_title'  => '<h4 class="widget-title">',
		'after_title'   => '</h4>',
	) );

	register_sidebar( array(
		'id'            => 'wp-parser-function',
		'name'          => __( 'Function Reference Sidebar', 'wporg' ),
		'description'   => __( 'Sidebar for function reference pages', 'wporg' ),
		'before_widget' => '<aside id="%1$s" class="box gray widget %2$s">',
		'after_widget'  => '</div></aside>',
		'before_title'  => '<h1 class="widget-title">',
		'after_title'   => '</h1><div class="widget-content">',
	) );

}

/**
 * Modify the default query.
 *
 * @param \WP_Query $query
 */
function pre_get_posts( $query ) {

	if ( $query->is_main_query() && $query->is_post_type_archive() ) {
		$query->set( 'orderby', 'title' );
		$query->set( 'order', 'ASC' );
	}

	if ( $query->is_main_query() && $query->is_tax() && $query->get( 'wp-parser-source-file' ) ) {
		$query->set( 'wp-parser-source-file', str_replace( array( '.php', '/' ), array( '-php', '_' ), $query->query['wp-parser-source-file'] ) );
	}

	// For search query modifications see DevHub_Search.
}

/**
 * Regiser navigation menu area.
 */
function register_nav_menus() {
	\register_nav_menus(
		array(
			'devhub-menu' => __( 'Developer Resources Menu', 'wporg' ),
			'devhub-cli-menu' => __( 'WP-CLI Commands Menu', 'wporg' ),
			'reference-home-api' => __( 'Reference API Menu', 'wporg' ),
		)
	);
}

/**
 * Filters the permalink for a wp-parser-method post.
 *
 * @param string   $link The post's permalink.
 * @param \WP_Post $post The post in question.
 * @return string
 */
function method_permalink( $link, $post ) {
	global $wp_rewrite;

	if ( ! $wp_rewrite->using_permalinks() || ( 'wp-parser-method' !== $post->post_type ) ) {
		return $link;
	}

	$parts  = explode( '-', $post->post_name );
	$method = array_pop( $parts );
	$class  = implode( '-', $parts );

	return home_url( user_trailingslashit( "reference/classes/$class/$method" ) );
}

/**
 * Filer the permalink for a taxonomy.
 */
function taxonomy_permalink( $link, $term, $taxonomy ) {
	global $wp_rewrite;

	if ( ! $wp_rewrite->using_permalinks() ) {
		return $link;
	}

	if ( 'wp-parser-source-file' === $taxonomy ) {
		$slug = $term->slug;
		if ( substr( $slug, -4 ) === '-php' ) {
			$slug = substr( $slug, 0, -4 ) . '.php';
			$slug = str_replace( '_', '/', $slug );
		}
		$link = home_url( user_trailingslashit( "reference/files/$slug" ) );
	} elseif ( 'wp-parser-since' === $taxonomy ) {
		$link = str_replace( $term->slug, str_replace( '-', '.', $term->slug ), $link );
	}

	return $link;
}

/**
 * Outputs JavaScript intended to appear in the head of the page.
 */
function header_js() {
	// Output CSS to hide markup with the class 'hide-if-js'. Ensures the markup is visible if JS is not present.
	// Add class 'js' to the body element if JavaScript is enabled
	echo "
	<script type=\"text/javascript\">
		jQuery( '<style>.hide-if-js { display: none; }</style>' ).appendTo( 'head' );
		jQuery( function($) {
			$( 'body' ).addClass('js');
		} );
	</script>\n";
}

/**
 * Register and enqueue the theme assets.
 */
function theme_scripts_styles() {
	wp_enqueue_style( 'dashicons' );
	// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
	wp_enqueue_style( 'open-sans', '//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,400,300,600' );
	wp_enqueue_style( 'wporg-developer-style', get_stylesheet_uri(), array(), filemtime( __DIR__ . '/style.css' ) );
	wp_enqueue_style(
		'wp-dev-sass-compiled',
		get_template_directory_uri() . '/stylesheets/main.css',
		array( 'wporg-developer-style' ),
		filemtime( __DIR__ . '/stylesheets/main.css' )
	);
	wp_enqueue_script( 'wporg-developer-navigation', get_stylesheet_directory_uri() . '/js/navigation.js', array(), filemtime( __DIR__ . '/js/navigation.js' ), true );
	wp_enqueue_script( 'wporg-developer-search', get_stylesheet_directory_uri() . '/js/search.js', array(), filemtime( __DIR__ . '/js/search.js' ), true );
	wp_enqueue_script( 'wporg-developer-chapters', get_stylesheet_directory_uri() . '/js/chapters.js', array( 'jquery' ), filemtime( __DIR__ . '/js/chapters.js' ) );
	wp_enqueue_script( 'wporg-developer-menu', get_stylesheet_directory_uri() . '/js/menu.js', array( 'jquery' ), filemtime( __DIR__ . '/js/menu.js' ), true );
}

/**
 * Rename the 'Comments' meta box to 'User Contributed Notes' for reference-editing screens.
 *
 * @param string  $post_type Post type.
 * @param WP_Post $post      WP_Post object for the current post.
 */
function rename_comments_meta_box( $post_type, $post ) {
	if ( is_parsed_post_type( $post_type ) ) {
		remove_meta_box( 'commentsdiv', $post_type, 'normal' );
		add_meta_box( 'commentsdiv', __( 'User Contributed Notes', 'wporg' ), 'post_comment_meta_box', $post_type, 'normal', 'high' );
	}
}

/**
 * Customize the syntax highlighter style.
 * See https://github.com/PrismJS/prism-themes.
 *
 * @param string $path Path to the file to override, relative to the theme.
 * @return string
 */
function update_prism_css_path( $path ) {
	return '/stylesheets/prism.css';
}

function body_class( $classes ) {
	// Trick the CSS into displaying a handbook menu on the function reference pages
	if ( is_singular() && 'wp-parser-function' === get_post_type() ) {
		$classes[] = 'single-handbook';
	}

	return $classes;
}
