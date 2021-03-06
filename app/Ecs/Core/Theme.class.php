<?php
namespace Ecs\Core;

/**
 * Theme Base Class
 *
 * @category   ECS_WordPress
 * @package    Core
 * @subpackage Theme
 * @author     Roy Lindauer <hello@roylindauer.com>
 * @license    http://www.apache.org/licenses/LICENSE-2.0.html  Apache License, Version 2.0
 * @link       http://roylindauer.com
 */
class Theme
{
    /**
     * Collection of post type objects.
     *
     * @var array $post_types collection of post type objects
     */
    public $post_types = array();

    /**
     * Setup.
     *
     * @param array $config
     * @todo Develop automatic menu loading and registering of image sizes.
     */
    public function run()
    {
        // Load custom post types, widgets, etc.
        add_action('init', array(&$this, 'loadPostTypes'));
        add_action('init', array(&$this, 'registerTaxonomies'));
        add_action('init', array(&$this, 'loadShortcodes'));

        // Display admin notices
        add_action('admin_notices', array(&$this, 'printThemeErrors'), 9999);

        // Check for PHP library dependencies
        add_action('admin_notices', array(&$this, 'dependencies'));

        // Require plugins
        add_action('tgmpa_register', array(&$this, 'registerRequiredPlugins'));

        // Load assets
        add_action('wp_enqueue_scripts', array(&$this, 'registerStylesheets'));
        add_action('wp_enqueue_scripts', array(&$this, 'registerScripts'));

        add_action('wp_enqueue_scripts', array(&$this, 'enqueueStylesheets'));
        add_action('wp_enqueue_scripts', array(&$this, 'enqueueScripts'));

        // Setup wp theme features
        add_action('after_setup_theme', array(&$this, 'registerThemeFeatures'));
        add_action('after_setup_theme', array(&$this, 'registerImageSizes'));
        add_action('after_setup_theme', array(&$this, 'registerNavMenus'));
        add_action('after_setup_theme', array(&$this, 'registerSidebars'));

        // Setup ajax endpoint
        add_action('wp_ajax_ecs_ajax', array(&$this, 'executeAjax'));
        add_action('wp_ajax_nopriv_ecs_ajax', array(&$this, 'executeAjax'));

    }

    ////////////////////////////////////////////////////////////////////////////////
    //
    // Enqueue and Load CSS/JS
    //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * Does the actual work of registered stylesheets. Must be called before enqueue.
     */
    public function registerStylesheets()
    {
        $stylesheets = \Ecs\Core\Configure::read('assets.stylesheets');

        if (empty($stylesheets)) {
            return;
        }

        foreach ($stylesheets as $handle => $data) {
            $data = $data;
            wp_register_style($handle, $data['source'], $data['dependencies'], $data['version']);
        }
    }

    /**
     * Does the actual work of enqueing stylesheets
     */
    public function enqueueStylesheets()
    {
        $stylesheets = \Ecs\Core\Configure::read('assets.stylesheets');

        if (empty($stylesheets)) {
            return;
        }

        foreach ($stylesheets as $handle => $data) {
            wp_enqueue_style($handle, $data['source'], $data['dependencies'], $data['version']);
        }
    }

    /**
     * Does the actual work of registered scripts. Must be called before enqueue.
     */
    public function registerScripts()
    {
        $scripts = \Ecs\Core\Configure::read('assets.scripts');

        if (empty($scripts)) {
            return;
        }

        foreach ($scripts as $handle => $data) {
            wp_register_script($handle, $data['source'], $data['dependencies'], $data['version'], $data['in_footer']);
        }
    }

    /**
     * Does the actual work of enqueing scripts
     */
    public function enqueueScripts()
    {
        $scripts = \Ecs\Core\Configure::read('assets.scripts');

        if (empty($scripts)) {
            return;
        }

        foreach ($scripts as $handle => $data) {
            wp_enqueue_script($handle, $data['source'], $data['dependencies'], $data['version'], $data['in_footer']);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////
    //
    // Dependency Checks
    //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * Register Required Plugins
     */
    public function registerRequiredPlugins()
    {
        if (($plugins = \Ecs\Core\Configure::read('dependencies.plugins')) == false) {
            return;
        }

        /**
         * Array of configuration settings. Amend each line as needed.
         * If you want the default strings to be available under your own theme domain,
         * leave the strings uncommented.
         * Some of the strings are added into a sprintf, so see the comments at the
         * end of each line for what each argument will be.
         */
        $config = array(
            'default_path' => '',                      // Default absolute path to pre-packaged plugins.
            'menu'         => 'tgmpa-install-plugins', // Menu slug.
            'has_notices'  => true,                    // Show admin notices or not.
            'dismissable'  => true,                    // If false, a user cannot dismiss the nag message.
            'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
            'is_automatic' => false,                   // Automatically activate plugins after installation or not.
            'message'      => '',                      // Message to output right before the plugins table.
            'strings'      => array(
                'page_title'                      => __('Install Required Plugins', 'tgmpa'),
                'menu_title'                      => __('Install Plugins', 'tgmpa'),
                'installing'                      => __('Installing Plugin: %s', 'tgmpa'), // %s = plugin name.
                'oops'                            => __('Something went wrong with the plugin API.', 'tgmpa'),
                'notice_can_install_required'     => _n_noop('This theme requires the following plugin: %1$s.', 'This theme requires the following plugins: %1$s.'), // %1$s = plugin name(s).
                'notice_can_install_recommended'  => _n_noop('This theme recommends the following plugin: %1$s.', 'This theme recommends the following plugins: %1$s.'), // %1$s = plugin name(s).
                'notice_cannot_install'           => _n_noop('Sorry, but you do not have the correct permissions to install the %s plugin. Contact the administrator of this site for help on getting the plugin installed.', 'Sorry, but you do not have the correct permissions to install the %s plugins. Contact the administrator of this site for help on getting the plugins installed.'), // %1$s = plugin name(s).
                'notice_can_activate_required'    => _n_noop('The following required plugin is currently inactive: %1$s.', 'The following required plugins are currently inactive: %1$s.'), // %1$s = plugin name(s).
                'notice_can_activate_recommended' => _n_noop('The following recommended plugin is currently inactive: %1$s.', 'The following recommended plugins are currently inactive: %1$s.'), // %1$s = plugin name(s).
                'notice_cannot_activate'          => _n_noop('Sorry, but you do not have the correct permissions to activate the %s plugin. Contact the administrator of this site for help on getting the plugin activated.', 'Sorry, but you do not have the correct permissions to activate the %s plugins. Contact the administrator of this site for help on getting the plugins activated.'), // %1$s = plugin name(s).
                'notice_ask_to_update'            => _n_noop('The following plugin needs to be updated to its latest version to ensure maximum compatibility with this theme: %1$s.', 'The following plugins need to be updated to their latest version to ensure maximum compatibility with this theme: %1$s.'), // %1$s = plugin name(s).
                'notice_cannot_update'            => _n_noop('Sorry, but you do not have the correct permissions to update the %s plugin. Contact the administrator of this site for help on getting the plugin updated.', 'Sorry, but you do not have the correct permissions to update the %s plugins. Contact the administrator of this site for help on getting the plugins updated.'), // %1$s = plugin name(s).
                'install_link'                    => _n_noop('Begin installing plugin', 'Begin installing plugins'),
                'activate_link'                   => _n_noop('Begin activating plugin', 'Begin activating plugins'),
                'return'                          => __('Return to Required Plugins Installer', 'tgmpa'),
                'plugin_activated'                => __('Plugin activated successfully.', 'tgmpa'),
                'complete'                        => __('All plugins installed and activated successfully. %s', 'tgmpa'), // %s = dashboard link.
                'nag_type'                        => 'updated' // Determines admin notice type - can only be 'updated', 'update-nag' or 'error'.
            )
        );

        tgmpa($plugins, $config);
    }

    /**
     *  Check for dependencies
     */
    public function dependencies()
    {
        // check php
        if (\Ecs\Core\Configure::read('dependencies.classes') !== false) {
            foreach (\Ecs\Core\Configure::read('dependencies.classes') as $name => $class) {
                if (!class_exists($class)) {
                    echo '<div class="error"><p>'
                    . sprintf(\Ecs\Helpers\__('Please make sure that %s is installed'), $class)
                    . '</p></div>';
                }
            }
        }
    }

    ////////////////////////////////////////////////////////////////////////////////
    //
    // Custom Post Types & Taxonomies
    //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * Load post type classes
     */
    public function loadPostTypes()
    {
        $post_types = \Ecs\Core\Configure::read('post_types');

        if ($post_types === false) {
            return;
        }

        foreach ($post_types as $post_type => $params) {
            new \Ecs\Core\PostType($post_type, $params);
        }
    }

    /**
     * Register Taxonomies
     */
    public function registerTaxonomies()
    {
        $taxonomies = \Ecs\Core\Configure::read('taxonomies');

        if (empty($taxonomies)) {
            return;
        }

        foreach ($taxonomies as $name => $opts) {
            new \Ecs\Core\Taxonomy($name, $opts['params'], $opts['args']);
        }
    }

    ////////////////////////////////////////////////////////////////////////////////
    //
    // Error Handling
    //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * Adds theme specific messages to the global theme WP_Error object.
     *
     * Takes the theme name as $code for the WP_Error object.
     * Merges old $data and new $data arrays @uses wp_parse_args().
     *
     * @param (string) $message
     * @param (mixed) $data_key
     * @param (mixed) $data_value
     * @return void
     */
    public function addThemeError($message, $data_key = '', $data_value = '')
    {
        global $wp_theme_error, $wp_theme_error_code;
     
        if (!isset( $wp_theme_error_code)) {
            $theme_data = wp_get_theme();
            $name = str_replace(' ', '', strtolower($theme_data['Name']));
            $wp_theme_error_code = preg_replace("/[^a-zA-Z0-9\s]/", '', $name);
        }
     
        if (!is_wp_error($wp_theme_error) || !$wp_theme_error) {
            $data[$data_key] = $data_value;
            $wp_theme_error = new \WP_Error($wp_theme_error_code, $message, $data);
            return $wp_theme_error;
        }
     
        // merge old and new data
        $old_data = $wp_theme_error->get_error_data($wp_theme_error_code);
        $new_data[$data_key] = $data_value;
        $data = wp_parse_args($new_data, $old_data);
     
        return $wp_theme_error->add($wp_theme_error_code, $message, $data);
    }
     
     
    /**
     * Prints the error messages added to the global theme specific WP_Error object
     *
     * Only displays for users that have 'manage_options' capability,
     * needs WP_DEBUG & WP_DEBUG_DISPLAY constants set to true.
     * Doesn't output anything if there's no error object present.
     *
     * Adds the output to the 'shutdown' hook to render after the theme viewport is output.
     *
     * @return
     */
    public function printThemeErrors()
    {
        global $wp_theme_error, $wp_theme_error_code;
     
        if (!current_user_can('manage_options') || !is_wp_error($wp_theme_error)) {
            return;
        }

        $output = '';
        foreach ($wp_theme_error->errors[$wp_theme_error_code] as $error) {
            $output .= '<li>' . $error . '</li>';
        }

        echo '<div class="error"><h4>' . \Ecs\Helpers\__('Theme Errors & Warnings').'</h4><ul>';
        echo $output;
        echo '</ul></div>';
    }

    ////////////////////////////////////////////////////////////////////////////////
    //
    // Theme Support, Theme Features, Theme...
    //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * Add theme features
     */
    public function registerThemeFeatures()
    {
        $features = \Ecs\Core\Configure::read('theme_features');

        if (empty($features)) {
            return;
        }

        foreach ($features as $k => $v) {
            if (is_array($v)) {
                add_theme_support($k, $v);
            } else {
                add_theme_support($v);
            }
        }
        
    }

    /**
     * Register Image Sizes
     */
    public function registerImageSizes()
    {
        $image_sizes = \Ecs\Core\Configure::read('image_sizes');

        if (empty($image_sizes)) {
            return;
        }

        foreach ($image_sizes as $name => $opts) {
            // Check wp reserved names for image sizes
            if (in_array($name, array('thumb', 'thumbnail'))) {
                $this->addThemeError(sprintf('Image size identifier "%s" is reserved', $name));
            } else {
                add_image_size($name, @$opts['width'], @$opts['height'], @$opts['crop']);
            }
        }
    }

    /**
     * Register Nav Menus
     */
    public function registerNavMenus()
    {
        $menus = \Ecs\Core\Configure::read('menus');

        if (empty($menus)) {
            return;
        }

        register_nav_menus($menus);
    }

    /**
     * Register Sidebars
     */
    public function registerSidebars()
    {
        $menus = \Ecs\Core\Configure::read('menus');

        if (empty($menus)) {
            return;
        }

        register_nav_menus($menus);
    }

    /**
     * Load Shortcodes
     */
    public function loadShortcodes()
    {
        $shortcodes = \Ecs\Core\Configure::read('shortcodes');

        if (empty($shortcodes)) {
            return;
        }

        foreach ($shortcodes as $shortcode) {
            $shortcode = "\Ecs\Shortcodes\\$shortcode\\$shortcode";
            new $shortcode();
        }
    }

    /**
     * Simple interface for executing ajax requests
     *
     * Usage: /wp-admin/admin-ajax.php?action=ecs_ajax&c=CLASS&m=METHOD&_wpnonce=NONCE
     *
     * Params for ajax request:
     * c         = class to instantiate
     * m         = method to run
     * _wpnonce  = WordPress Nonce
     * display   = json,html
     *
     * Naming Conventions
     * Method names will be prefixed with "ajax_" and then run through the Inflector to camelize it
     *     - eg: "doThing" would become "ajaxDoThing", so you need a method in your class called "ajaxDoThing"
     *
     * Classes can be whatever you want. They are expected to be namespaces to \Ecs\Modules
     *
     * Output can be rendered as JSON, or HTML
     *
     * Generate a nonce: wp_create_nonce('execute_ajax_nonce');
     */
    public function executeAjax()
    {
        try {
            // We expect a valid wp nonce
            if (!isset($_REQUEST['_wpnonce']) || !wp_verify_nonce($_REQUEST['_wpnonce'], 'execute_ajax_nonce')) {
                throw new \Exception('Invalid ajax request');
            }

            // Make sure we have a class and a method to execute
            if (!isset($_REQUEST['c']) && !isset($_REQUEST['m'])) {
                throw new \Exception('Invalid params in ajax request');
            }

            // Make sure that the requested class exists and instantiate it
            $c = filter_var($_REQUEST['c'], FILTER_SANITIZE_STRING);
            $class = "\Ecs\\Modules\\$c";

            if (!class_exists($class)) {
                throw new \Exception('Class does not exist');
            }

            $Obj = new $class();

            // Add our prefix and camelize the requested method
            // eg: "method" becomes "ajaxMethod"
            // eg: "do_thing" becomes "ajaxDoThing", or "doThing" becomes "ajaxDoThing"
            $Inflector = new \Ecs\Core\Inflector();
            $m = $Inflector->camelize('ajax_' . filter_var($_REQUEST['m'], FILTER_SANITIZE_STRING));

            // Make sure that the requested method exists in our object
            if (!method_exists($Obj, $m)) {
                throw new \Exception('Ajax method does not exist');
            }

            // Execute
            $result = $Obj->$m();

            // Render the response
            $display = 'json';

            if (isset($_REQUEST['display'])) {
                $display = filter_var($_REQUEST['display'], FILTER_SANITIZE_STRING);
            }

            // Render results
            $this->ajaxDisplay($result, $display);

        } catch (\Exception $e) {
            $this->ajaxDisplay(array('error' => $e->getMessage()));
        }

        // Make sure this thing dies so it never echoes back that damn zero.
        die();
    }

    /**
     * Render ajax response
     */
    private function ajaxDisplay($result = '', $display = 'json')
    {
        switch ($display)
        {
            case 'html':
                echo $result;
                break;

            case 'json':
            default:
                \Ecs\Helpers\json_response($result);
                break;
        }
    }
}

/* End of file Theme.class.php */
/* Location: ./app/Ecs/Core/Theme.class.php */
