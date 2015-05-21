<?php
/**
 * Wordpess Theme init
 */

require 'app/Ecs/common.php';

$registry = Ecs\Core\Registry::getInstance();

// Init a theme object by passing in a unique name for the theme. This name will be used as the langkey
$theme = new Ecs\Modules\Theme('my-theme-name');

// Pass a config array to Theme::run() to setup the theme.
$theme->run(array(

	// Define Custom Post Types
	'post_types' => array(
		'Cover' => array(
			'supports' => array(
		        'title', 
		        'thumbnail',
		        'custom-fields',  
		    ),
		)
	),

	// Define theme features
	// http://codex.wordpress.org/Function_Reference/add_theme_support
	'theme_features' => array(
		'post-thumbnails',
		'post-formats' => array(
			'aside', 
			'gallery'
		) 
	),

	// Custom Image Sizes
	'image_sizes' => array(
		'cover_large' => array(
			'width' => 1920,
			'height' => 1080,
			'crop' => false
		),
	),

	// Define custom nav menus
	// https://codex.wordpress.org/Function_Reference/register_nav_menu
	'menus' => array(
		'main-menu'   => $theme->__('Main Menu'),
		'sub-menu'    => $theme->__('Sub Menu'),
		'footer-menu' => $theme->__('Footer Menu')
	),

	// Define custom sidebars
	// https://codex.wordpress.org/Function_Reference/register_sidebar
	'sidebars' => array(
		array(
			'id'            => 'my-custom-sidebar',
			'name'          => $theme->__('My Custom Sidebar'),
			'description'   => '',
			'class'         => '',
			'before_widget' => '<li id="%1$s" class="widget %2$s">',
			'after_widget'  => '</li>',
			'before_title'  => '<h2 class="widgettitle">',
			'after_title'   => '</h2>',
		),
	),
	
	// Define theme dependencies
	// Require WP Plugins - http://tgmpluginactivation.com/
	// Require Core PHP Classes / Libraries
	'dependencies' => array(
		'plugins' => array(
			// MetaBox is amazing, and we use it in the PostType model
			array(
				'name'      => 'Meta Box',
				'slug'      => 'meta-box',
				'required'  => true,
			),
			// Options Framework is also amazing
			array(
				'name'      => 'Options Framework',
				'slug'      => 'options-framework',
				'required'  => false,
			),
			array(
				'name'      => 'Wordpress SEO',
				'slug'      => 'wordpress-seo',
				'required'  => true,
			),
		),
		'classes' => array(
			'Imagick',
		),
	),
	
	// Define stylesheets and scripts
	'assets' => array(
		'stylesheets' => array(
			'style' => array(
				'source' => get_stylesheet_directory_uri() . '/assets/css/app.css',
				'dependencies' => FALSE,
				'version' => 'v1'
			),
		),
		'scripts' => array(
			'main' => array(
				'source' => get_stylesheet_directory_uri() . '/assets/js/main.js',
				'dependencies' => FALSE,
				'version' => 'v1',
				'in_footer' => TRUE
			),
		)
	)
));

$registry->set('Theme', $theme);

/* End of file functions.php */
/* Location: ./functions.php */