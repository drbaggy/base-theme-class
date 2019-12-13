<?php
/*
+----------------------------------------------------------------------
| Copyright (c) 2018 Genome Research Ltd.
| This is part of the Wellcome Sanger Institute extensions to
| wordpress.
+----------------------------------------------------------------------
| This extension to Worpdress is free software: you can redistribute
| it and/or modify it under the terms of the GNU Lesser General Public
| License as published by the Free Software Foundation; either version
| 3 of the License, or (at your option) any later version.
|
| This program is distributed in the hope that it will be useful, but
| WITHOUT ANY WARRANTY; without even the implied warranty of
| MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
| Lesser General Public License for more details.
|
| You should have received a copy of the GNU Lesser General Public
| License along with this program. If not, see:
|     <http://www.gnu.org/licenses/>.
+----------------------------------------------------------------------

# Support functions to make ACF managed pages easier to render..
# This is a very simple class which defines templates {and an
# associated template language which can then be used to render
# page content... more easily...}
#
# See foot of file for documentation on use...
#
# Author         : js5
# Maintainer     : js5
# Created        : 2018-02-09
# Last modified  : 2018-02-12

 * @package   BaseThemeClass
 * @author    JamesSmith james@jamessmith.me.uk
 * @license   GLPL-3.0+
 * @link      https://jamessmith.me.uk/base-theme-class/
 * @copyright 2018 James Smith
 *
 * @wordpress-plugin
 * Plugin Name: Website Base Theme Class
 * Plugin URI:  https://jamessmith.me.uk/base-theme-class/
 * Description: Support functions to apply simple templates to acf pro data structures!
 * Version:     0.0.1
 * Author:      James Smith
 * Author URI:  https://jamessmith.me.uk
 * Text Domain: base-theme-class-locale
 * License:     GNU Lesser General Public v3
 * License URI: https://www.gnu.org/licenses/lgpl.txt
 * Domain Path: /lang
 */

const EXTRA_SETUP = [
  'date_picker'      => [ 'return_format' => 'Y-m-d'       ], // Return values in "mysql datetime" format
  'date_time_picker' => [ 'return_format' => 'Y-m-d H:i:s' ], //  - or relevant part of....
  'time_picker'      => [ 'return_format' => 'H:i:s'       ], //
  'image'            => [ 'save_format' => 'object', 'library' => 'all', 'preview_size' => 'large' ],
  'medium_editor'    => [
    'standard_buttons' => [ 'bold', 'italic', 'subscript', 'superscript', 'removeFormat' ],
    'other_options'    => [ 'disableReturn', 'disableDoubleReturn' ],
    'custom_buttons'   => [],
  ],
];

const DEFAULT_DEFN = [
  'PARAMETERS'   => [   // Associate array of definitions...
    // Key is variable "name"
    // type    - is type of input
    // section - is which part of menu to add this to [may need to add "add-section" code
    // default - default value
    // description - help text appears under
  ],
  'DEFAULT_TYPE'  => 'page',  // We need to know what type to default to as removing posts!
  'STYLES'        => [],      // Associate array of CSS files (key/filename)
  'SCRIPTS'       => [ 'pubs'  => [ '/wp-content/plugins/base-theme-class/pubs.js', false ]  ],      // Associate array of JS files  (key/filename)
  'ADMIN_SCRIPTS' => [ '/wp-content/plugins/base-theme-class/admin.js'                       ],      // Associate array of JS files  (key/filename)
  'ADMIN_STYLES'  => [],      // Associate array of JS files  (key/filename)
  'FEATURES'      => [ 'captioned_widths' => true, ],
];

class BaseThemeClass {
  protected $template_directory;
  protected $template_directory_uri;

  protected $defn;
  protected $templates;
  protected $preprocessors;
  protected $postprocessors;
  protected $switchers;
  protected $debug;
  protected $array_methods;
  protected $scalar_methods;
  protected $date_format;
  protected $range_format;
  protected $custom_types;

  public function __construct( $defn ) {
    $this->custom_types = [];
    $this->defn = $defn;
    $this->date_format = 'F jS Y';
//                          //year diff               // month diff            // day diff            // same day!
    $this->range_format = [ [ 'F jS Y',' - F jS Y' ], [ 'F jS', ' - F jS Y' ], [ 'F jS', ' - jS Y' ], [ 'F jS Y', '' ] ];
//  $this->range_format = [ [ 'j F Y', ' - j F Y'  ], [ 'j F',  ' - j F Y'  ], [ 'j',   ' - j F Y' ], [ 'j F Y',  '' ] ];
    $this->initialize_templates()
         ->initialize_templates_directory()
         ->initialize_theme()
         // The following four lines are just to tidy up some of the
         // quirks of wordpress when using it to make a website
         // rather than a blog!
         ->clean_up_the_rubbish_wordpress_adds()
         ->stop_wordpress_screwing_up_image_widths_with_captions()
         ->tidy_up_image_sizes()
         ->remove_comments_admin()
         // Now we just set up stuff that we need to have set up for
         // this site - some of these are part of the base theme -
         // others are added by the theme
         ->add_my_scripts_and_stylesheets()
         ->register_custom_parameters()
         ->register_short_codes()
         // The following is experimental - creating a new sub-editor role [[ please ignore at the moment ]]
         //->register_new_role()
         ->enable_co_authors_plus_on_all_post_types()  // Enable co-authors plus on all post types
         ->restrict_who_can_manage_authors()           // Switch to allow admins OR owners OR authors to manage authors to post
         ->add_credit_code()
         ->add_augmented_relationship_labels()         // By default include post ID in relationship labels (extendable)!
         ->extend_at_a_glance()                        // Add custom post types (and total) to at a glance panel on dashboard
         ->reconfigure_dashboard_and_show_my_posts()   // Re-arrange dashboard layout and a "my posts" panel
         ;
  }

  function set_date_format( $s ) {
    $this->date_format = $s;
    return $this;
  }

  function set_range_format( $s ) {
    $this->range_format = $s;
    return $this;
  }

  function format_date_range( $start, $end ) {
    $s = date_create($start);
    $e = date_create($end);
    $index = date_format($s,'Y') !== date_format($e,'Y') ? 0
           : ( date_format($s,'m') !== date_format($e,'m') ? 1
           : ( date_format($s,'d') !== date_format($e,'d') ? 2
           : 3 ) );
    return date_format($s,$this->range_format[$index][0]).
           date_format($e,$this->range_format[$index][1]);
  }

  function initialize_templates_directory() {
    $this->template_directory     = get_template_directory();
    $this->template_directory_uri = get_template_directory_uri();
    return $this;
  }

//======================================================================
//
// Add CSS/javascript files condigured in the class "DEFN" constant
//
// $this->add_my_scripts_and_stylesheets() in class initialization
// function does this
//
// [enqueue_scripts and enqueue_admin_scripts are the functions which
// do the work]
//
// If they start with http or / then they are treated as absolute
// o/w they are treated relative to the theme's template directory...
//
// For the javascript if the "filename" is an array the
//  * The first element is the name of the file
//  * The second element is the location of the JS head/foot
//    (defaults head)
//  * If admin script the third element is the role's for which the
//    javascript is included.
//
//======================================================================

  function add_my_scripts_and_stylesheets() {
    add_action( 'wp_enqueue_scripts',     array( $this, 'enqueue_scripts'        ), PHP_INT_MAX );
    add_action( 'admin_enqueue_scripts',  array( $this, 'enqueue_admin_scripts'  ), PHP_INT_MAX );
    return $this;
  }

  public function disabled( $feature_name ) {
    if( ! array_key_exists( $feature_name, $this->defn['FEATURES'] ) ) {
      return false;
    }
    $flag = $this->defn['FEATURES'][$feature_name];
    if( is_array( $flag ) ) {
      $flag = array_pop( $flag );
    }
    return !$flag;
  }

  public function enqueue_scripts() {
    // Push styles into header...
    if( isset( $this->defn[ 'STYLES' ] ) ) {
      foreach( $this->defn[ 'STYLES' ] as $key => $name ) {
        if( preg_match( '/^(https?:\/)?\//', $name ) ){
          wp_enqueue_style( $key, $name,array(),null,false);
        } else {
          wp_enqueue_style( $key, $this->template_directory_uri.'/'.$name,array(),null,false);
        }
      }
    }
    // Push scripts into footer...
    if( isset( $this->defn[ 'SCRIPTS' ] ) ) {
      foreach( $this->defn[ 'SCRIPTS' ] as $key => $conf ) {
        if( is_array($conf) ) {
          $name = $conf[0];
          $flag = $conf[1];
        } else {
          $name = $conf;
          $flag = true;
        }
        if( preg_match( '/^(https?:\/)?\//', $name ) ){
          wp_enqueue_script( $key, $name,                                  array(),null,$flag);
        } else {
          wp_enqueue_script( $key, $this->template_directory_uri.'/'.$name,array(),null,$flag);
        }
      }
    }
  }
  public function enqueue_admin_scripts( $hook = '' ) {
    global $post_type;
    if( isset( $this->defn[ 'ADMIN_STYLES' ] ) ) {
      foreach( $this->defn[ 'ADMIN_STYLES' ] as $key => $name ) {
        if( preg_match( '/^(https?:\/)?\//', $name ) ){
          wp_enqueue_style( $key, $name,array(),null,false);
        } else {
          wp_enqueue_style( $key, $this->template_directory_uri.'/'.$name,array(),null,false);
        }
      }
    }
    // Push scripts into footer...
    if( isset( $this->defn[ 'ADMIN_SCRIPTS' ] ) ) {
      foreach( $this->defn[ 'ADMIN_SCRIPTS' ] as $key => $conf ) {
        if( is_array($conf) ) {
          $name   = $conf[0];
          $flag   = $conf[1];
          $filter = sizeof($conf)>2 ? $conf[2] : [];
        } else {
          $name = $conf;
          $flag = true;
          $filter = [];
        }
        if( sizeof($filter) ) {
          $skip = true;
          foreach( $filter as $role ) {
            if( current_user_can( $role ) ) {
              $skip = false;
              break;
            }
          }
          if( $skip ) {
            continue;
          }
        }
        if( preg_match( '/^(https?:\/)?\//', $name ) ){
          wp_enqueue_script( $key, $name,array(),null,$flag);
        } else {
          wp_enqueue_script( $key, $this->template_directory_uri.'/'.$name,array(),null,$flag);
        }
      }
    }
  }

//----------------------------------------------------------------------
// Stop wordpress generating multiple image copies...
// We just leave the thumbnails needed for the media manager!
//----------------------------------------------------------------------

  public function tidy_up_image_sizes() {
    add_filter( 'intermediate_image_sizes_advanced', array( $this, 'remove_default_images' ), PHP_INT_MAX  );
    return $this;
  }

  function remove_default_images( $sizes ) {
    unset( $sizes['small']);        // 150px
    unset( $sizes['medium']);       // 300px
    unset( $sizes['large']);        // 1024px
    unset( $sizes['medium_large']); // 768px
    return $sizes;
  }

//----------------------------------------------------------------------
// Just minor theme support functionality
//----------------------------------------------------------------------

  function initialize_theme() {
    add_action( 'after_setup_theme',          array( $this, 'theme_setup'      ) );
    return $this;
  }

  public function theme_setup() {
    add_theme_support( 'html5' );        // Make it HTML 5 compliant
    add_theme_support( 'title-tag' );
  }

//----------------------------------------------------------------------
// Functions to clean up stuff which wordpress adds that we don't want
// or was just a really bad decisions...!
//----------------------------------------------------------------------

  public function clean_up_the_rubbish_wordpress_adds() {
    remove_filter( 'oembed_dataparse',       'wp_filter_oembed_result', 10 );
    remove_action( 'wp_head',                'wlwmanifest_link');
    remove_action( 'wp_head',                'rsd_link');
    remove_action( 'wp_head',                'rest_output_link_wp_head' );
    remove_action( 'wp_head',                'wp_oembed_add_discovery_links' );
    remove_action( 'wp_head',                'wp_oembed_add_host_js' );
    remove_action( 'wp_head',                'print_emoji_detection_script', 7);
    remove_action( 'wp_print_styles',        'print_emoji_styles');
    remove_action( 'admin_print_scripts',    'print_emoji_detection_script' );
    remove_action( 'admin_print_styles',     'print_emoji_styles' );
    remove_action( 'rest_api_init',          'wp_oembed_register_route' );
    add_filter(    'emoji_svg_url',          '__return_false' );
    add_filter(    'embed_oembed_discover',  '__return_false' );
    remove_action( 'wp_head',                'wp_shortlink_wp_head', 10);
    remove_action( 'template_redirect',      'wp_shortlink_header', 11);
    remove_action( 'wp_head',                'feed_links', 2 );
    remove_action( 'wp_head',                'feed_links_extra', 3 );
    remove_action( 'wp_head',                'wp_generator' );
    return $this;
  }

  public function stop_wordpress_screwing_up_image_widths_with_captions() {
    if( $this->disabled( 'captioned_widths' ) ) {
      return $this;
    }
    add_filter(    'post_thumbnail_html',            array( $this, 'remove_width_attribute'  ), PHP_INT_MAX );
    add_filter(    'image_send_to_editor',           array( $this, 'remove_width_attribute'  ), PHP_INT_MAX );
    add_filter(    'get_image_tag',                  array( $this, 'remove_width_attribute'  ), PHP_INT_MAX );
    add_filter(    'image_widget_image_attributes',  array( $this, 'responsive_image_widget' ), PHP_INT_MAX );
    add_filter(    'wp_calculate_image_sizes',       '__return_empty_array',                    PHP_INT_MAX );
    add_filter(    'wp_calculate_image_srcset',      '__return_empty_array',                    PHP_INT_MAX );
    add_filter(    'img_caption_shortcode_width',    '__return_false',                          PHP_INT_MAX );
    add_filter(    'wp_get_attachment_image_attributes',
                        array( $this,  'remove_image_attributes_related_to_size' ),             PHP_INT_MAX );
    remove_filter( 'the_content',                    'wp_make_content_images_responsive' );
    return $this;
  }

  function remove_image_attributes_related_to_size( $attr )  {
    foreach( array('sizes','srcset','width','height') as $key) {
      if( isset( $attr[$key] ) ) {
        unset( $attr[$key] );
      }
    }
    return $attr;
  }
  function remove_width_attribute( $html ) {
    return preg_replace( '/(width|height)="\d*"\s/',          "", $html );
  }
  function responsive_image_widget($html) {
    return preg_replace( '/(width|height)=["\']\d*["\']\s?/', "", $html );
  }

// Support functions - to convert between human readable and
// computer readable "variable" names - and to pluralize names

  function hr( $string ) {
  // Make human readable version of variable name
    return ucfirst( preg_replace( '/_/', ' ', $string ) );
  }
  function cr( $string ) {
  // Convert a human readable name into a valid variable name...
    return strtolower( preg_replace( '/\s+/', '_', $string ) );
  }
  function pl( $string ) {
  // Pluralize and english string...
  // ends in "y" replace with "ies" ; o/w add "s"
    if( preg_match( '/y$/', $string ) ) {
      return preg_replace( '/y$/', 'ies', $string );
    }
    return $string.'s';
  }

  function block_render( $block, $content = '', $is_preview = false, $post_id = 0 ) {
    $template_code = 'block-'.$this->cr( $block['title'] );

    print $this->render(
      $template_code,
      array_merge(
        [ 'random_id' => str_replace('.','-',microtime(true)).'-'.mt_rand(1e6,1e7) ],
        get_fields()
      )
    );
  }

  function define_block( $name, $fields, $extra ) {
    if( ! function_exists('acf_register_block_type') ) {
      return $this->show_error( 'ACF plugin not installed or does not support blocks', true );
    }
    $type = array_key_exists( 'code', $extra ) ? $extra['code'] : $this->cr( $name );
    $desc = array_key_exists( 'desc', $extra ) ? $extra['desc'] : 'A custom '.$name.' block';
    $temp = array_key_exists( 'temp', $extra ) ? $extra['temp'] : 'block-'.$type;
    $icon = array_key_exists( 'icon', $extra ) ? 'admin-'.$extra['icon'] : 'admin-comments';
    $cat  = array_key_exists( 'cat', $extra )  ? $extra['cat']  : 'embed';
    $defn = [
      'name'            => $type,
      'title'           => $name,
      'description'     => $desc,
      'render_callback' => array( $this, 'block_render' ),// function( $block ) use ( $temp ) { echo $this->render( $temp, $block ); },
      'category'        => $cat,
      'icon'            => $icon,
      'keywords'        => array( $name, $type, 'custom' ),
    ];
    acf_register_block_type( $defn );
    $fg_defn = [
      'id'              => 'acf_block_'.$type,
      'title'           => $name,
      'fields'          => [],
      'options'         => [],
      'location'        => [[[ 'param'=>'block', 'operator' => '==', 'value'=>'acf/'.$type ]]],
      'label_placement' => isset( $extra['labels'] ) ? $extra['labels'] : 'left',
    ];
    $prefix            = isset( $extra['prefix'] ) ? $extra['prefix'].'_' : '';
    $fg_defn['fields'] = $this->munge_fields( $prefix, $fields, $type, '' );
    register_field_group( $fg_defn );
    return $this;
  }

  function define_type( $name, $fields, $extra=[] ) {
    if(! function_exists("register_field_group") ) {
      return  $this->show_error( 'ACF plugin not installed!', true );
    }
    // type is page or post or "not_custom" isn't set in extra
    // we will generate a custome type...
    $type = array_key_exists( 'code', $extra ) ? $extra['code'] : $this->cr( $name );

    if( $type !== 'page' & $type !== 'post' && !isset( $extra['not_custom'] ) ) {
      $this->create_custom_type( array_merge( [ 'name' => $name ] , $extra ) );
    }
    // We do some magic now to the name to get the type...
    // Set the location - unless over-ridden in extra...
    $location = [[[ 'param' => 'post_type', 'operator' => '==', 'value' => $type ]]];

    if( isset( $extra['location'] ) ) {
      $t        = $extra['location'];
      if( is_array( $t[0] ) ) {
        $location = [ array_map( function( $r ) { return [[ 'param' => $r[0], 'operator' => $r[1], 'value' => $r[2] ]]; }, $t ) ];
      } else {
        $location = [[[ 'param' => $t[0], 'operator' => $t[1], 'value' => $t[2] ]]];
      }
    }
    // Create the basic definition
    $defn = [
      'id'              => 'acf_'.$type,
      'title'           => $name,
      'fields'          => [],
      'location'        => $location,
      'options'         => [ 'position' => 'normal', 'layout' => 'no_box', 'hide_on_screen' => [] ],
      'menu_order'      => array_key_exists( 'menu_order', $extra ) ? $extra['menu_order'] : 50,
      'label_placement' => isset( $extra['labels'] ) ? $extra['labels'] : 'left'
    ];
    if( !( array_key_exists( 'show_contents', $extra ) && $extra[ 'show_contents' ]) ) {
      $defn['options']['hide_on_screen'][] = 'the_content';
    }
    if( array_key_exists( 'title_template' , $extra ) ) {
      $defn['options']['hide_on_screen'][] = 'permalink';
      $defn['options']['hide_on_screen'][] = 'slug';
    }
    // Allow a prefix for type so we don't have issues of field name clash
    // across multiple types....
    $prefix         = isset( $extra['prefix'] ) ? $extra['prefix'].'_' : '';
    $defn['fields'] = $this->munge_fields( $prefix, $fields, $type, '' );
    // Finally register the acf group to generate the admin interface!
    register_field_group( $defn );
    //$t = fopen( '/tmp/variables.txt', 'a' ); ob_start(); var_export( $defn ); fwrite( $t, '$def["'.$type.'"] = '.ob_get_clean().";\n\n" ); fclose( $t );
    if( isset( $extra['fields'] ) ) {
      foreach( $extra['fields'] as $fg ) {
        $pos++;
        $defn[ 'id'               ] = 'acf_'.$type.'_'.$fg['type' ];
        $defn[ 'title'            ] = $fg['title'];
        $defn[ 'menu_order'       ]++;
        $defn[ 'label_placement'  ] = isset( $fg['labels'] ) ? $fg['labels'] : 'left';
        $defn[ 'fields'           ] = $this->munge_fields( $prefix.$fg['type'].'_', $fg['fields'], $type, $fg['type'].'_' );
        $defn[ 'options'          ] = [ 'position' => 'normal' ];
        register_field_group( $defn );
      }
    }

    if( array_key_exists( 'title_template', $extra ) ) {
      add_filter( 'wp_insert_post_data', function( $post_data ) use ($type,$prefix,$extra) {
        if( $post_data[ 'post_type' ] === $type && array_key_exists( 'acf', $_POST ) ) {
          $post_data[ 'post_title' ] = trim(preg_replace( '/\s+/', ' ',
            preg_replace_callback( '/\[\[([.\w]+)\]\]/',
              function( $m ) use ( $prefix ) {
                $t = $_POST['acf'];
                foreach( explode('.',$m[1]) as $k ) {
                  $t = $t[ "field_${prefix}$k" ];
                }
                return $t;
              },
              $extra['title_template']
            )
          ));
        }
        return $post_data;
      } );
    }
    return $this;
  }

  function add_taxonomy( $name, $object_types, $extra = [] ) {
  // Add a taxonomy to the give classes.. similar to add type - works
  // out plurals, codes, labels etc from given name
  // then attaches to the appropriate object types....
    $plural     = isset( $extra['plural'] ) ? $extra['plural'] : $this->pl( $name );
    $code       = isset( $extra['code']   ) ? $extra['code']   : $this->cr( $name );
    $lc         = strtolower($name);
    $new_item   = __("New $lc");
    $edit_item  = __("Edit $lc");
    register_taxonomy( $code, $object_types, [
      'query_var'         => true,
      'show_ui'           => true,
      'show_admin_column' => true,
      'show_in_menu'      => true,
      'rewrite'           => array( 'slug' => $code ),
      'heirarchical'      => isset( $extra['hierarchical'] ) ? $extra['hierarchical'] : false,
      'labels'            => [
        'name'              => __($plural),
        'singular_name'     => __($name),
        'edit_item'         => $edit_item,
        'update_item'       => $edit_item,
        'add_new_item'      => $new_item,
        'menu_name'         => __($plural),
      ]
    ] );
    return $this;
  }

// Nasty re-cursive code - munges fields + add sub_fields/layouts....
  function munge_fields( $prefix, $fields, $type, $field_prefix ) {
    // and add fields to it... note we don't have complex fields here!!!
    $munged = [];
    foreach( $fields as $field => $def ) {
      $code = isset( $def['code'] ) ? $def['code'] : $this->cr( $field ); // Auto generate code for field, along with name etc..
      $me = ['key'=>'field_'.$prefix.$code, 'label' => $field, 'name' => $code, 'layout' => 'row' ];
      if( ! array_key_exists( 'type', $def ) ) {
        error_log( "                                                                                       " );
        error_log( "BASE THEME CLASS: Definition of '$code' for '$type' object - has no type defined       " );
        error_log( "                                                                                       " );
      }
      if( array_key_exists( 'type', $def ) && array_key_exists( $def['type'], EXTRA_SETUP ) ) {
        $me = array_merge( $me, EXTRA_SETUP[ $def['type'] ] );
      }
      if( is_array( $def ) ) {
        $me = array_merge( $me, $def );
      }
      if( isset( $def['sub_fields'] ) ){
        $me['sub_fields'] = $this->munge_fields( $prefix.$code.'_', $def['sub_fields'], $type, $field_prefix.$code.'_' );
      }
      if( isset( $def['layouts'] ) ){
        $me[ 'layouts' ] = $this->munge_fields(  $prefix.$code.'_', $def['layouts'], $type, $field_prefix.$code.'_' );
      }
      if( array_key_exists( 'admin', $def ) ) {
        // Now we need to add the columns to the interface
        $fn = "acf-$field_prefix$code";
        $cn = $def['admin'] == 1 ? $me['label'] : $def['admin'];
        add_action( 'manage_'.$type.'_posts_custom_column',   [ $this, 'acf_custom_column'        ], 10, 2  );
        add_filter( 'manage_'.$type.'_posts_columns',         function( $columns ) use ($fn, $cn ) {
          return array_merge( $columns, [ $fn => $cn ] );
        });
        add_filter( 'manage_edit-'.$type.'_sortable_columns', function( $columns ) use ($fn, $cn ) {
          return array_merge( $columns, [ $fn => $cn ] );
        });
      }
      $munged[]=$me;
    }
    return $munged;
  }

  function create_custom_type( $def ) {
    // Take name and generate plural, computer readable versions etc...
    $name       = $def['name'];
    $plural     = isset( $def['plural'] ) ? $def['plural'] : $this->pl( $name );
    $code       = isset( $def['code']   ) ? $def['code']   : $this->cr( $name );
    $lc         = strtolower($name);

    $new_item   = __("New $lc");
    $edit_item  = __("Edit $lc");
    $view_item  = __("View $lc");
    $view_items = __('View '.strtolower($plural) );
    $all_items  = __('All '.strtolower($plural) );

    // Define icon this is a dashicon icon....
    $icon       = isset( $def['icon']   ) ? $def['icon']   : 'admin-page';

    $this->custom_types[ $code ] = [ 'icon' => 'dashicons-'.$icon, 'name' => $name, 'names' => $plural ];

    $add = array_key_exists('add',$def)
         ? [ 'map_meta_cap' => true,'capability_type' => 'post', 'capabilities' => [ 'create_posts' => $def['add'] ], ]
         : [];
    register_post_type( $code, array_merge($add,[
      'public'       => true,
      'has_archive'  => true,
      'menu_icon'    => 'dashicons-'.$icon,
      'heirarchical' => isset( $def['hierarchical'] ) ? $def['hierarchical'] : false,
      'labels'       => [
        'add_new'          => $new_item,
        'add_new_singular' => $new_item,
        'new_item'         => $new_item,
        'add_new_item'     => "Add new $lc",
        'edit_item'        => $edit_item,
        'view_item'        => $view_item,
        'view_items'       => $view_items,
        'all_items'        => $all_items,
        'singular_name'    => __($name),
        'name'             => __($plural)
      ]
    ]) );
    return $this;
  }

// The main dashboard page of the wordpress admin has an "At a glance section" which includes
// counts of published pages and posts... This doesn't include custom post types - so we have
// to add these - note we keep a list of custom post types in the custom_types hash (the type
// is the key - and the values are the icon and name (singular/plural) we use this to generate
// the markup... Which is an array of values to go in "ul > li" list elements..
// (Elements is passed in for this script to add to!)

  function extend_at_a_glance() {
    add_filter( 'dashboard_glance_items', [ $this, 'add_custom_post_types_to_at_a_glance' ] );
    add_filter( 'dashboard_recent_posts_query_args', [ $this, 'add_custom_post_types_to_activity' ] );
    return $this;
  }

  function add_custom_post_types_to_activity( $query_args ) {
    $query_args['post_type'] = is_array( $query_args['post_type'] )
                             ? array_merge( $query_args['post_type'], ['page'], array_keys($this->custom_types) )
                             : array_merge( ['post','page'], array_keys($this->custom_types) )
                             ;
    $query_args['posts_per_page'] = 10;
    return $query_args;
  }

  function add_custom_post_types_to_at_a_glance( $elements ) {
    $t = wp_count_posts( 'post' )->publish + wp_count_posts( 'page' )->publish;
    foreach( $this->custom_types as $type => $def ) {
      $num_posts = wp_count_posts( $type )->publish;
      $t += $num_posts;
      $elements[] = sprintf( '<a class="%s" href="/wp-admin/edit.php?post_type=%s">%d %s</a>',
        $def['icon'], $type, $num_posts, _n( $def['name'], $def['names'], $num_posts ) );
    }
    $elements[] = "<strong>TOTAL: $t POSTS</strong>";
    return $elements;
  }

// Reconfigure the wordpress dashboard - the standard dashboard is OK for a single editor - but
// we have approximately a thousand editors so we really need to re-arrange this a bit - get
// rid of the adminy bits - and add in our special "all my stuff panel"....
// $this->reconfigure_dashboard_and_show_my_posts()
//     links in code to add a custom widget panel on the main dashboard
//     page to show all content that the user has. In turn, the
//     following method is called:
// $this->reconfigure_dashboard()
//     this moves the current 2nd column to the fourth column
//     and moves the first column into the second column, and
//     finally replaces the first column with out "my posts" panel...
// $this->dashboard_my_pages_and_objects()
//     Looks for entries for which I'm a co-author of (or owner of if coauthors not activated)
//     - any type: posts, pages, custom post types - using a taxonomy query (this
//     is how co-authors stores the "ownership" of files {or direct author query if no co-authors}
//     This then lists the entries in reverse time order of modification


  function reconfigure_dashboard_and_show_my_posts() {
    add_action( 'wp_dashboard_setup', [ $this, 'reconfigure_dashboard' ] );
    add_action( 'rest_api_init', function () {
       register_rest_route( 'base', 'search/(?P<s>.+)', array(
         'methods' => 'GET',
         'callback' => [ $this, 'my_admin_search' ]
       ) );
    } );
    return $this;
  }

  function reconfigure_dashboard() {
    global $wp_meta_boxes;
    // Move the "side" 2nd column to the fourth column
    $wp_meta_boxies['dashboard']['column4'] = $wp_meta_boxes['dashboard']['side'];                                      // Push 2nd column into 4th column
    // Clear the 2nd column... and move the new widget from the bottom of the left hand column to the 2nd column!
    $wp_meta_boxes['dashboard']['side']     = $wp_meta_boxes['dashboard']['normal'];                                    // Move first column into second column
    //$wp_meta_boxes['dashboard']['normal']   = ['core'=>[ array_pop( $wp_meta_boxes['dashboard']['side']['core'] ) ]];   // Create new column (with last element of 3rd col)
    $wp_meta_boxes['dashboard']['normal']   = [];
    wp_add_dashboard_widget('custom_help_widget', 'My pages and objects', [$this, 'dashboard_my_pages_and_objects' ]);  // Add custom widget
    wp_add_dashboard_widget('custom_search_widget', 'Quick Search',             [$this, 'custom_search_box' ]);  // Add custom widget
  }

  function my_admin_search( $data ) {
    $q = new WP_Query;
    $labels = [];
    return array_map(
       function($r) use ($labels) {
         if( !array_key_exists($r->post_type, $labels ) ) {
           $labels[$r->post_type] = get_post_type_labels(get_post_type_object($r->post_type))->singular_name;
         }
         return [
           current_user_can( 'edit_post', $r->ID) ? get_edit_post_link( $r->ID ) : get_permalink( $r->ID ),
           $r->post_title,
           $labels[$r->post_type],
         ];
       },
       $q->query( [
         'cache_results'          => false,
         'update_post_term_cache' => false,
         'update_post_meta_cache' => false,
         'posts_per_page'         => 10,
         'post_type'              => 'any',
         'post_status'            => [ 'draft', 'publish' ],
         's'                      => urldecode($data['s']),
       ] )
    );
  }

  function custom_search_box() {
    echo '<input id="searchbox" type="text" name="in" style="width:100%" />';
    echo '<ul id="search-results"></ul>';
    echo '<p><em>Enter at least 3 characters to search through all posts</em></p>';
  }
  function dashboard_my_pages_and_objects() {
    $query        = new WP_Query;
    $u   = wp_get_current_user();
    if( is_plugin_active( 'co-authors-plus/co-authors-plus.php' ) ) { // Coauthors+ is enabled so use taxonomy information
      $un  = $u->user_nicename; // $un = 'oauth2-mt9-sanger-ac-uk'; // TEST TO SEE OTHERS LIST!
      $entries = $query->query( [
        'cache_results'=>false,'update_post_term_cache'=>false,'update_post_meta_cache'=>false,'posts_per_page'=>-1,
        'tax_query' => [[ 'taxonomy' => 'author', 'field' => 'slug', 'terms' => "cap-$un" ]],
        'order'   => 'DESC',
        'orderby' => 'modified',
      ] );
    } else { // Just look for "owned" by the current author!
      $entries = $query->query( [
        'cache_results' => false,'update_post_term_cache'=>false,'update_post_meta_cache'=>false,'posts_per_page'=>-1,
        'author'        => $u->ID,
        'order'         => 'DESC',
        'orderby'       => 'modified',
      ] );
    }
    if( sizeof( $entries ) ) { // If we have entries display them (may need to limit if more than say 40?)
      echo '<p>You are an author of the following pages:</p><ol>';
      $labels = [];
      foreach ( $entries as $x ) {
        if( !array_key_exists($x->post_type, $labels ) ) {
          $labels[$x->post_type] = get_post_type_labels(get_post_type_object($x->post_type))->singular_name;
        }
        printf( '<li>%s%s: <a href="%s">%s (%s)</a>%s</li>',
          $x->post_status === 'publish' ? '<strong>' : '<em>',
          $labels[$x->post_type],
          current_user_can( 'edit_post', $x->ID ) ? get_edit_post_link( $x->ID ) : get_permalink( $x->ID ),
          HTMLentities($x->post_title),
          substr($x->post_modified,0,10),
          $x->post_status === 'publish' ? '</strong>' : '</em>'
        );
      }
      echo '</ol>';
    } else { // Otherwise show we have not pages/posts...
      echo '<p>You do not currently have any pages/posts on the Sanger website</p>';
    }
  }

//----------------------------------------------------------------------
// Set up custom paraemters (in customizer) from config hash (PARAMETERS)
//----------------------------------------------------------------------

//
// This functionality allows us to add site wide "variables"
//
//  e.g contact email, default email domain, facebook group etc....
//
// Retrieved with:
//   * get_theme_mod('variable_name')
// or in templates
//   * [[raw:~:variable_name]]
//

  function register_custom_parameters() {
    add_action( 'customize_register',         array( $this, 'create_custom_theme_params' ) );
    return $this;
  }

// Configuration is an associate array of associate arrays...
//
// [ 'key_name' => [
//   'type'        => '', ## text|checkbox|radio|select|textarea|dropdown-pages|email|url|number|hidden|date.
//   'section'     => '', ## themes|title_tagline|colors|header_image?|background_image?|static_front_page|...
//   'default'     => '', ## default value!
//   'description' => '', ## "Help text"...
// ] ]
// These mainly define the control so see:
//   https://codex.wordpress.org/Class_Reference/WP_Customize_Manager/add_control
// for documentation...

// <<TO DO>> OTHER OPTIONS - array_merge "extra"

// If you want to do other more complex mods can always "extend in theme class"
//
// function create_custom_theme_paras( $wp_customize );
//   parent::create_custom_theme_params( $wp_customize );
//   // Add my custom code here....
// }

  function create_custom_theme_params( $wp_customize ) {
    $params = [ 'email_domain' => [
      'type'        => 'text',
      'section'     => 'base-theme-class',
      'default'     => 'mydomainname.org.uk',
      'description' => 'Specify the domain for email addresses.'
    ], 'publication_options' => [
      'type'        => 'text',
      'section'     => 'base-theme-class',
      'default'     => '',
      'description' => 'Options for publications listings',
    ], 'coauthor_options' => [
      'type'        => 'radio',
      'choices'     => [ 'admin' => 'Administrator', 'owner' => 'Owner', 'author' => 'Author' ],
      'section'     => 'base-theme-class',
      'default'     => 'admin',
      'description' => 'Adding authors is restricted to',
    ] ];
    $wp_customize->add_section( 'base-theme-class', [ 'title' => __( 'Base theme class settings'), 'priority' => 30 ] );
    if( isset( $this->defn[ 'PARAMETERS' ] ) ) {
      $params = array_merge( $params, $this->defn[ 'PARAMETERS' ] );
    }
    foreach( $params as $par => $def ) {
      $name = isset( $def['name'] ) ? $def['name'] : $this->hr( $par );
      $type = isset( $def['type'] ) ? $def['type'] : 'text';
      $sanitize = 'sanitize_text_field';
      $options =  [ 'default'           => isset( $def['default'] ) ? $def['default'] : '' ];
      if( $type === 'text' ) $options['sanitize_callback'] = 'sanitize_text_field';
      $wp_customize->add_setting( $par, $options );
      $options = [
        'type'        => $type,
        'label'       => __( $name ),
        'description' => __( isset( $def['description'] ) ? $def['description'] : '' )
      ];
      foreach( [ 'section', 'choices' ] as $k ) {
        if(array_key_exists($k,$def) ) {  $options[$k] = $def[$k]; }
      }
      $wp_customize->add_control( $par, $options );
    }
  }


//----------------------------------------------------------------------
// As we are removing "blog" functionality we don't need posts and
// comments fields... this requires remove a number of different bits
// of code hooked in a number of different places...
// 1) We need to modify the "new" link in the admin bar so it defaults
//    to a type other than post (default this to page - but possibly
//    could recall if you want it to default to something else!!)
// 2) Remove the new post and comments link from this menu bar!
//----------------------------------------------------------------------

  function remove_posts_admin() {
    add_action( 'admin_bar_menu',             array( $this, 'change_default_new_link' ), PHP_INT_MAX-1 );
    add_action( 'admin_menu',                 array( $this, 'remove_posts_sidebar' ) );
  }

  function remove_comments_admin() {
    add_action( 'admin_menu',                 array( $this, 'remove_comments_sidebar') );
    add_filter( 'manage_edit-post_columns',   array( $this, 'remove_comments_column') ,10,1);
    add_filter( 'manage_edit-page_columns',   array( $this, 'remove_comments_column') ,10,1);
    return $this;
  }

  // Remove the comments and new post menu entries
  //   and change the default "New" link to "page" of if type is passed type..
  function change_default_new_link( $wp_admin_bar, $type = '', $title = '' ) {
    if( $type === '' ) {
      $type = array_key_exists( 'DEFAULT_TYPE', $this->defn )
            ? $this->defn[  'DEFAULT_TYPE' ]
            : DEFAULT_DEFN[ 'DEFAULT_TYPE' ]
            ;
    }
    if( $title === '' ) {
      $title = ucfirst( $type );
    }
    // We can't have the node directly (shame) so we have to copy the node...
    $new_content_node = $wp_admin_bar->get_node('new-content');
    // Change the link... and set the
    $new_content_node->href .= '?post_type='.$type;
    // Change the title (to include default add action!)
    $new_content_node->title = preg_replace(
       '/(label">).*?</', '$1'.__('New').' ('.__($title).')<', $new_content_node->title );
    $wp_admin_bar->remove_node('new-content');
    $wp_admin_bar->add_node( $new_content_node);
    $wp_admin_bar->remove_menu('comments');
 //   $wp_admin_bar->remove_node('new-post');
    $wp_admin_bar->remove_menu('wp-logo');   // Not to do with posts - but good to get rid of in admin interface!
  }


  // Remove posts sidebar entries...
  function remove_posts_sidebar() {
    $this->remove_sidebar_entry('edit.php');
  }
  // Remove comments from post/page listings...
  function remove_comments_sidebar() {
    $this->remove_sidebar_entry('edit-comments.php');
  }
  function remove_sidebar_entry( $name ) {
    global $menu;
    end($menu);
    while( prev($menu) ) {
      if( $menu[key($menu)][2] == $name ) {
        unset( $menu[key($menu)] );
        return;
      }
    }
  }

  // Remove comments from post/page listings...
  function remove_comments_column($columns) {
    unset($columns['comments']);
    return $columns;
  }

//----------------------------------------------------------------------
// Add email link short code functionality to obfuscate emails...
//----------------------------------------------------------------------

// We can add additional short codes in theme by extending this method
//
// public function register_short_codes() {
//   add_shortcode( 'my_short_code', array( $this, 'show_my_short_code' ) );
//   return parent::register_short_codes();
// }

  public function register_short_codes() {
    add_shortcode( 'email_link', array( $this, 'email_link' ) );
    add_shortcode( 'publications', array( $this, 'publications_shortcode' ) );
    return $this;
  }

  function publications_shortcode( $atts, $content = null ) {
    return sprintf(
'
<div class="ajax_publications" data-ids="%s %s">Loading publications...</div>
',
      HTMLentities( get_theme_mod( 'publication_options' ) ),
      HTMLentities( implode( ' ', $atts ) )
    );
  }

  // Short code: [email_link {email} {link text}?]
  //
  // Render an (source code) obfuscated email (mailto:) link
  //
  //  * If email does not contain "@" then we add email_domain from customizer...
  //  * If link text isn't defined it defaults to email address
  //

  function email_link( $atts, $content = null ) {
    $email = array_shift( $atts );
    if( !$email ) { // If no email provided die!!
      return '';
    }

    $email = strpos( $email, '@' ) !== false
           ? $email
           : $email.'@'.get_theme_mod('email_domain')
           ;

    $name  = implode( $atts, ' ' );
    if( $name === '' ) {
      $name = $email;
    }
    return sprintf( '<a href="mailto:%s">%s</a>',
      $this->random_url_encode( $email ),
      $this->random_html_entities( $name )
    );
  }

//----------------------------------------------------------------------
// Some additional functions!
//----------------------------------------------------------------------

  public function theme_version() {
    return wp_get_theme()->get( 'Version' );
  }

//----------------------------------------------------------------------
// Template funcations....
//----------------------------------------------------------------------

  function initialize_templates() {
    $this->templates      = [];
    $this->switchers      = [];
    $this->preprocessors  = [];
    $this->postprocessors = [];
    $this->debug          = false;
    $this->array_methods = [
      'size'      => function( $t_data ) { return sizeof( $t_data ); },
      'json'      => function( $t_data, $extra ) {
        return HTMLentities( json_encode( $t_data ) );
      },
      'dump'      => function( $t_data, $extra ) {
        return '<pre style="height:400px;width:100%;border:1px solid red; background-color: #fee; color: #000; font-weight: bold;font-size: 10px; overflow: auto">'.HTMLentities(print_r($t_data,1)).'</pre>';
      },
      'templates' => function( $t_data, $extra ) {
        if( is_array( $t_data ) ) {
          return implode( '', array_map(function($row) use ($extra) {
            return $this->expand_template( $this->template_name( $extra, $row ), $row );
          }, $t_data ));
        }
        return '';
      },
      'template'  => function( $t_data, $extra ) {
        return $this->expand_template( $this->template_name( $extra, $t_data ), $t_data );
      }
    ];
    $this->scalar_methods = [
      'ucfirst'   => function( $s ) { return ucfirst($s); },
      'hr'        => function( $s ) { return $this->hr($s); },
      'cr'        => function( $s ) { return $this->cr($s); },
      'uc'        => function( $s ) { return strtoupper($s); },
      'lc'        => function( $s ) { return strtolower($s); },
      'raw'       => function( $s ) { return $s; },
      'date'      => function( $s ) { return $s ? date_format( date_create( $s ), $this->date_format ) : '-'; },
      'enc'       => 'rawurlencode',
      'rand_enc'  => function( $s ) { return $this->random_url_encode( $s ); },
      'integer'   => 'intval',
      'boolean'   => function( $s ) { return $s ? 'true' : 'false'; },
      'shortcode' => 'do_shortcode',
      'strip'     => function( $s ) { return preg_replace( '/\s*\b(height|width)=["\']\d+["\']/', '', do_shortcode( $s ) ); },
      'rand_html' => function( $s ) { return $this->random_html_entities( $s ); },
      'html'      => 'HTMLentities',
      'email'     => function( $s ) { // embeds an email link into the page!
        $s = strpos( $s, '@' ) !== false ? $s : $s.'@'.get_theme_mod('email_domain');
        return sprintf( '<a href="mailto:%s">%s</a>', $this->random_url_encode( $s ),
          $this->random_html_entities( $s ) );
      },
      'wp'        => function( $s ) { // Used to call one of the standard wordpress template blocks
         switch( $s ) {
           case 'part-' === substr( $s, 0, 5) :
             ob_start();
             get_template_part( substr( $s, 5 ) );
             return ob_get_clean();
           case 'charset' :
             return get_bloginfo( 'charset' );
           case 'lang':
             return get_language_attributes();
           case 'path' :
             return $this->template_directory_uri;
           case 'body_class' :
             return join( ' ', get_body_class() );
           case 'menu-' === substr( $s, 0, 5) :
             return preg_replace( '/\n/', "\n    ",
                wp_nav_menu( ['menu' => substr( $s, 5 ), 'container' => 'nav', 'fallback_cb' => false, 'echo' => false ] ));
           case 'head' :
             ob_start();
             wp_head();
             return preg_replace( '/\n/', "\n    ", trim(ob_get_clean()));
           case 'foot' :
             ob_start();
             wp_footer();
             return preg_replace( '/\n/', "\n    ", trim(ob_get_clean()));
           default:
             return sprintf('<p>unknown part %s</p>', HTMLentities($s));
        }
      }
    ];

    return $this;
  }

  function add_array_method( $key, $fn ) {
    $this->array_methods[  $key ] = $fn;
    return $this;
  }
  function add_scalar_method( $key, $fn ) {
    $this->scalar_methods[ $key ] = $fn;
    return $this;
  }
  function add_template($key, $template) {
    if( ! array_key_exists( $key, $this->templates ) ) {
      $this->templates[$key] = array();
    }
    if( is_array( $template ) ) {
      if( array_key_exists( 'template', $template ) ){
        if( array_key_exists( 'switch', $template ) ) {
          $this->add_switcher( $key, $template['switch'] );
        }
        if( array_key_exists( 'pre', $template ) ) {
          $this->add_preprocessor( $key, $template['pre'] );
        }
        $this->add_template( $key, $template['template'] );
      #  $this->templates[$key][] = $template['template'];
        if( array_key_exists( 'post', $template ) ) {
          $this->add_postprocessor( $key, $template['post'] );
        }
        return $this;
      }
      foreach( $template as $t ) {
        $this->templates[$key][] = $t;
      }
      return $this;
    }
    $this->templates[$key][] = $template;
    return $this;
  }

  public function load_from_file( $filename ) {
    $full_path = $this->template_directory.'/'.$filename;
    if( file_exists( $full_path ) && substr($full_path,-4,4) == '.php' ) {
      $templates = include $full_path;
      foreach( $templates as $key => $template ) {
        $this->add_template( $key, $template );
      }
    }
    return $this;
  }

  public function load_from_directory( $dirname = '__templates' ) {
    $full_path = $this->template_directory.'/'.$dirname;
    if( file_exists( $full_path ) ) {
      if( is_dir( $full_path ) ) {
        if( $dh = opendir($full_path) ) {
          while( ($file = readdir($dh)) !== false ) {
            if( '.' !== substr($file,0,1) ) {
              $this->load_from_directory( $dirname.'/'.$file );
            }
          }
          closedir($dh);
        }
      } else {
        $templates = include $full_path;
        foreach( $templates as $key => $template ) {
          $this->add_template( $key, $template );
        }
      }
    }
    return $this;
  }

  public function dump_templates( ) {
    print '<pre style="height:800px;overflow:scrollbar">';
    print '<h4>Switchers</h4>';
    print_r( $this->switchers );
    print '<h4>Pre-processors</h4>';
    print_r( $this->preprocessors );
    print '<h4>Templates</h4>';
    print_r( $this->templates );
    print '<h4>Post-processors</h4>';
    print_r( $this->postprocessors );
    print '</pre>';
    return $this;
  }

// Pre-processor code...

  public function add_switcher( $key, $function ) {
    $this->switchers[ $key ] = $function;
    return $this;
  }

  public function add_preprocessor( $key, $function ) {
    $this->preprocessors[ $key ] = $function;
    return $this;
  }

  public function add_postprocessor( $key, $function ) {
    $this->postprocessors[ $key ] = $function;
    return $this;
  }

// Debug and error code

  public function debug_on() {
    $this->debug = true;
    return $this;
  }

  public function debug_off() {
    $this->debug = false;
    return $this;
  }

  public function pre_dump( $obj ) {
    printf( '<pre>%s</pre>', HTMLentities( print_r( $obj, 1 ) ) );
    return '';
  }

  public function error_dump( $obj ) {
    foreach( preg_split( '/[\r\n]+/', print_r( $obj, 1 ) ) as $_ ) {
      error_log( $_ );
    }
    return '';
  }

  protected function show_error( $message, $flag = false ) {
    if( $this->debug ) {
      return '<div class="error">'.HTMLentities( $message ).'</div>';
    }
    error_log( $message );
    return $flag ? $this : '';
  }

  protected function expand_template( $template_code, $data) {
    if( ! array_key_exists( $template_code, $this->templates ) ) {
      return $this->show_error( "Template '$template_code' is missing" );
    }
    // Apply any pre-processors to data - thie munges/amends the data-structure
    // being passed...
    if( array_key_exists( $template_code, $this->switchers ) ) {
      $function = $this->switchers[$template_code];
      $t = $function( $data, $this );
      if( $t === false ) {
        return '';
      }
      if( $t ) {
        return $this->expand_template( $t, $data );
      }
    }
    if( array_key_exists( $template_code, $this->preprocessors ) ) {
      $function = $this->preprocessors[$template_code];
      $data = $function( $data, $this );
    }
    $regexp = sprintf( '/\[\[(?:(%s|%s):)?([-@~.!\w+]+)(?::([^\]]+))?\]\]/',
       implode('|',array_keys( $this->array_methods )),
       implode('|',array_keys( $this->scalar_methods )) );

    $out = implode( '', array_map(
      function( $t ) use ( $data, $template_code, $regexp ) {
        return is_object($t) && ($t instanceof Closure)
      ? $t( $data, $template_code ) // If the template being parsed is a closure then we call the function
      : preg_replace_callback(      // It's a string so parse it - regexps are wonderful things!!!
          $regexp,
          function($match) use ($data, $template_code) {
            // For each substitute - get the parsed values....

            list( $render_type, $variable, $extra ) = [ $match[1], $match[2], array_key_exists( 3, $match ) ? $match[3] : '' ];

            $t_data = $this->parse_variable( $variable, $extra, $data );
            if( array_key_exists( $render_type, $this->array_methods ) ) {
              return $this->array_methods[ $render_type ]( $t_data, $extra );
            }
            if( is_array( $t_data ) ) {
              $this->show_error(
                "Rendering array as '$variable' in '$template_code' ($render_type)<pre>".print_r($t_data,1).'</pre>'
              );
              return '';
            }
            if( array_key_exists( $render_type, $this->scalar_methods ) ) {
              return $this->scalar_methods[ $render_type ]( $t_data );
            }
            return HTMLentities( $t_data );
          },
          $t
        );
      },
      $this->templates[$template_code]
    ));
    // Apply any post processors to the markup - this can clean up the HTML afterwards...
    if( array_key_exists( $template_code, $this->postprocessors ) ) {
      $function = $this->postprocessors[$template_code];
      $out = $function( $out, $data, $this );
    }
    return $out;
  }

  function parse_variable( $variable, $extra, $data ) {
    //
    // First switch - parse the variable name and get the data from the object
    //
    // special variable names:
    //     "-" - data is just the raw value of extra
    //     "~" - data is the theme parameter {from customizer} give by extra
    //     "." - data is just the data for the current template
    // otherwise
    //   split the variable on "." and use these as keys for the elements of data to
    //   sub-value or sub-tree of data...
    //
    switch( $variable ) {
      case '-':  // raw string
        return $extra;
      case '~': // customizer parameter
        return get_theme_mod( $extra );
      case '.'; // just pass data through!
        return $data;
      default:  // navigate down data tree...
        $t_data = $data;
        foreach( explode( '.', $variable ) as $key ) {
          // Missing data
          if( is_object( $t_data) ) {
            if( substr( $key, 0, 1 ) === '!' ) {
              $t_data = get_field( substr($key,1), $t_data->ID );
              continue;
            }
            if( $key == '@' ) {
              $key = 'comment_count';
            }
            if( property_exists( $t_data, $key ) ) {
              $t_data = $t_data->$key;
              continue;
            }
          }
          if( !is_array( $t_data ) ) {
            return ''; // No value in tree with that key!
          }
          // key doesn't exist in data structure or has null value...
          if( !array_key_exists( $key, $t_data ) ||
            !isset(            $t_data[$key] ) ||
            is_null(           $t_data[$key] ) ) {
            return '';
          }
          $t_data = $t_data[$key];
        }
        return $t_data;
    }
  }

  function render_scalar( $scalar, $style = 'default' ) {
  }

  function template_name( $str, $data ) {
    return preg_replace_callback( '/[*](\w+)/', function ($match) use ($data) {
      return array_key_exists( $match[1], $data ) ? $data[$match[1]] : '';
    },$str);
  }

  function render( $template_code, $data = [] ) {
    return $this->collapse_empty(
      preg_replace('/<a\s[^>]*?href=""[^>]*>.*?<\/a>/s',       '', // Empty links
      preg_replace('/<iframe\s[^>]*?src=""[^>]*><\/iframe>/',  '', // Empty iframes
      preg_replace('/<img\s[^>]*?src=""[^>]*>/',               '', // Empty images
        $this->expand_template( $template_code, $data ) ) ) ) );
  }

  function output( $template_code, $data = [] ) {
    print $this->render( $template_code, $data );
    return $this;
  }

  function output_page( $page_type ) {
    get_header();
    global $post;
    $extra = [
      'ID'=>get_the_ID(),
      'page_url'=>get_permalink(),
      'page_title'=>the_title('','',false),
      'page_content' => $post->post_content
    ];
    if( is_array( get_fields() ) ) {
      $this->output( $page_type, array_merge(get_fields(),$extra) );
    } else {
      $this->output( $page_type, $extra );
    }
    get_footer();
  }

//----------------------------------------------------------------------
// Support functions used by other methods!
//----------------------------------------------------------------------

  function hide_acf_admin() {
    define( 'ACF_LITE', true );
    return $this;
  }

  function get_entries( $type, $extra = array() ) {
    $get_posts = new WP_Query;
    $entries = $get_posts->query( array_merge( ['posts_per_page'=>-1,'post_type'=>$type], $extra ) );

    $return = [];
    foreach( $entries as $post ) {
      $meta = get_fields( $post->ID );
      if( !is_array( $meta ) ) {
        $meta = [];
      }
      $return[] = array_merge( $meta, [
        'ID'           => $post->ID,
        'post_title'   => $post->post_title,
        'post_excerpt' => $post->post_excerpt,
        'post_content' => $post->post_content,
        'post_url'     => get_permalink( $post ),
        'post_name'    => $post->post_name
      ] );
    }
    return $return;
  }

  function get_entries_light( $type, $extra = array(), $keys = array() ) {
    $get_posts = new WP_Query;
    $entries = $get_posts->query( array_merge( ['cache_results'=>false,'update_post_term_cache'=>false,'update_post_meta_cache'=>false,'posts_per_page'=>-1,'post_type'=>$type], $extra ) );
    $munged = $this->fetch_meta( $entries, $keys );
    $t = array_map( function( $x ) use ($munged) {
      return array_merge( $munged[$x->ID], [
        'ID'           => $x->ID,
        'post_title'   => $x->post_title,
        'post_excerpt' => $x->post_excerpt,
        'post_content' => $x->post_content,
        'post_url'     => get_permalink( $x ),
        'post_name'    => $x->post_name ]
      );
    }, $entries );
    return $t;
  }

//----------------------------------------------------------------------
// Replace characters in string with encoded version of character -
// either replace with HTML entity code (hex or dec) or URL encoding...
//----------------------------------------------------------------------

  function random_html_entities( $string ) {
    $alwaysEncode = array('.', ':', '@');
    $res='';
    for($i=0;$i<strlen($string);$i++) {
      $x = htmlentities( $string[$i] );
      if( $x === $string[$i] && ( in_array( $x, $alwaysEncode ) || !mt_rand(0,3) ) ) {
        $x = '&#'.sprintf( ['%d','x%x','x%X'][mt_rand(0,2)], ord($x) ).';';
      }
      $res.=$x;
    }
    return $res;
  }

  function random_url_encode( $string ) {
    $alwaysEncode = array('.', ':', '@');
    $res='';
    for($i=0;$i<strlen($string);$i++){
      $x = urlencode( $string[$i] );
      if( $x === $string[$i] && ( in_array( $x, $alwaysEncode ) || !mt_rand(0,3) ) ) {
        $x = '%'.sprintf( ['%02X','%02x'][mt_rand(0,1)], ord($x) );
      }
      $res.=$x;
    }
    return $res;
  }

  function collapse_empty( $html_str ) {
    $munged = '';
    while( $munged !== $html_str ) {
      // Trim empty tags -- a, span, p, div, h[1-6], ...
      list($munged,$html_str) = array(
        $html_str,
        preg_replace( '/<(li|ol|ul|a|span|p|div|h\d)[^>]*>\s*<\/\1>/', '', $html_str )
      );
    }
    return preg_replace( '/\s*[\r\n]+\s*[\r\n]/', "\n", $html_str ); // Remove blank lines
  }

// The following functions are looking at defining a new role which would
// allow assigning editors to individual pages
  function add_roles_on_plugin_activation() {
    add_role( 'content_dditor', 'Content editor', [ 'read' => true, 'edit_posts' => true, 'edit_owned_posts' => true ] );
  }

  function content_editor_filter( ) {
    global $wp_query;
    if( ! is_admin() ) {
      return;
    }
    $user = wp_get_current_user();
    if( ! in_array( 'content_editor', (array) $user->roles ) ) {
    //The user has the "author" role
      return;
    }
    $wp_query->set( 'meta_key',   'country' );
    $wp_query->set( 'meta_value', 'GB' );
  }

  function get_atts( ) {
    $defaults = func_get_args();
    $atts = array_shift( $defaults );
    if( ! is_array( $atts ) ) {
      $atts = [];
    }
    $ret = [];
    foreach( $defaults as $d ) {
      $ret[] = sizeof( $atts ) > 0 ? array_shift( $atts ) : $d;
    }
    $ret = array_merge( $ret, $atts );
    return $ret;
  }

  // Code to allow editors to edit theme options - mainly the menus...
  function give_editors_menu_permissions() {
    $role_object = get_role( 'editor' );
    $role_object->add_cap( 'edit_theme_options' );
    return $this;
  }


  function register_new_role() {
    register_activation_hook( __FILE__, [ $this, 'add_roles_on_plugin_activation' ] );
    add_action( 'pre_get_posts', [ $this, 'content_editor_filter' ] );
    return $his;
  }
  function custom_media_add_credit( $form_fields, $post ) {
    $field_value = get_post_meta( $post->ID, 'custom_credit', true );
    $form_fields['custom_credit'] = array(
        'value' => $field_value ? $field_value : '',
        'label' => __( 'Credit' ),
        'helps' => __( 'Enter credit details for image' ),
        'input'  => 'text'
    );
    return $form_fields;
  }
  function include_credit_as_data_attribute( $html, $id, $alt, $title ) {
    $t = get_post_meta( $id );
    $credit = $t['custom_credit'];
    if( is_array( $credit ) ) {
      $credit = $credit[0];
    }
    return $credit ? preg_replace( '/<img /','<img data-credit="'.HTMLentities($credit).'" ', $html ) : $html;
  }
  function custom_media_save_attachment( $attachment_id ) {
    if ( isset( $_REQUEST['attachments'][ $attachment_id ]['custom_credit'] ) ) {
      $custom_credit = $_REQUEST['attachments'][ $attachment_id ]['custom_credit'];
      update_post_meta( $attachment_id, 'custom_credit', $custom_credit );
    }
  }
  function add_credit_code() {
    add_filter( 'attachment_fields_to_edit', [ $this, 'custom_media_add_credit'      ], null, 2 );
    add_action( 'edit_attachment',           [ $this, 'custom_media_save_attachment' ] );
    add_filter( 'get_image_tag',             [ $this, 'include_credit_as_data_attribute' ], 0, 4);
    return $this;
  }

  function fetch_meta( $objects, $field_names ) {
    $ids = array_map( function($o) { return $o->ID; }, $objects );
    global $wpdb;
    $res = $wpdb->dbh->query( 'select post_id,meta_key,meta_value from ' . $wpdb->prefix . 'postmeta
      where post_id in ('.implode(',',$ids).')
        and meta_key in ("'.implode('","',$field_names).'")' );
    $mapped = [];
    foreach( $res->fetch_all( MYSQLI_ASSOC ) as $row ) {
      $mapped[$row['post_id']][$row['meta_key']] = $row['meta_value'];
    }
    return $mapped;
  }

  function augment_objects( $objects, $field_names ) {
    $return_scalar = 0;
    if( ! is_array($objects) ) {
      $return_scalar = 1;
      $objects = [$objects];
    }
    $mapped = $this->fetch_meta( $objects, $field_names );
    foreach( $objects as $o ) {
      $o->comment_count = $mapped[$o->ID];
    }
    return $return_scalar ? $objects[0] : $objects;
  }

//======================================================================
//
// Co-author plus configuration
//
//----------------------------------------------------------------------
//
// You will need to install the Co-author plus plugin to make this
// work...
//
//----------------------------------------------------------------------
//
// Along with the configuration for the theme this does three things:
//
// * Enables co-authors plus on all post types (including custom types)
// * Moves the co-authors plus configuration to the bottom of the right
//   hand side navigation panel
//
//   [ These two are added by $this->enable_co_authors_plus_on_all_post_types() ]
//
// * Tweak co-authors plus configuration to allow one of:
//   * Admins can add/remove authors
//   * Owners can add/remove authors
//   * Authors can add/remove authors [ Can steal posts! ]
//
//   [ This functionality is added by calling $this->allow_multiple_authors(),
//     and configured in the web interface with co-authors
//     theme customisation ]
//
//======================================================================

  function enable_co_authors_plus_on_all_post_types() {
    // Now get the custom_post types we generated and attach co-authors to them!
    add_filter( 'coauthors_supported_post_types', function( $post_types ) { return array_merge( $post_types, array_keys($this->custom_types) ); } );
    // The following two lines place the co-author box on the right hand side
    // After the main page "meta-data" publish box...
    add_filter( 'coauthors_meta_box_context',     function() { return 'side'; } ); // Move to right hand side
    add_filter( 'coauthors_meta_box_priority',    function() { return 'low';  } ); // Place under other boxes
    return $this;
  }

  // Wrapper around co-authors to allow authors to add other authors...
  function restrict_who_can_manage_authors() {  // This is the default one - let the owner (first author change authors)
    $flag = get_theme_mod('coauthor_options');
    switch( $flag ) {
      case 'owner':
        add_filter( 'coauthors_plus_edit_authors', [ $this, 'let_owner_add_other_authors' ] );
        break;
      case 'author':
        add_filter( 'coauthors_plus_edit_authors', [ $this, 'let_author_add_other_authors' ] );
        break;
    }
    return $this;
  }

  function let_owner_add_other_authors( $can_set_authors ) {
    $f = $can_set_authors || ( get_post() && wp_get_current_user()->ID == get_post()->post_author );
    return $f;
  }
  function let_author_add_other_authors( $can_set_authors ) {
    if( $can_set_authors ) return true; // We know that the person can edit so return true;
    if( ! get_post() ) {
      return false;
    }
    $user_id   = wp_get_current_user()->ID;
    foreach( get_coauthors( get_post()->ID ) as $auth )  {
      if( $auth->ID == $user_id ) return true;
    }
    return false;
  }

//======================================================================
//
// Remove ability to delete
//
//----------------------------------------------------------------------
//
// Add $this->remove_ability_to_delete() in sub-class initialization
// to make sure ALL users cannot delete
//
// [disable_delete is the function which does the work and is called at
//  the init phase]
//
//======================================================================

  function remove_ability_to_delete() {
    add_action( 'init', [ $this, 'disable_delete' ] );
    return $this;
  }

  function disable_delete( ) {
    $x = new WP_Roles();
    $T = $x->roles;
    foreach( $T as $role_name => $role_info ) {
      $r = get_role( $role_name );
      foreach( array_filter( $role_info['capabilities'], function( $k ) { return substr($k,0,7) == 'delete_' && $k != 'delete_themes'; }, ARRAY_FILTER_USE_KEY  ) as $cap => $_) {
        $r->remove_cap( $cap );
      }
      ### REMOVE HACK ###
      if( $role_name == 'administrator' ) {
        $r->add_cap( 'delete_themes', true );
      }
    }
  }

//======================================================================
//
// Add IDs to titles in ACF relationship and post_object field types
//
//----------------------------------------------------------------------
//
// $this->add_id_to_relationship_fields() in class initialization
// function does this
//
// [add_id_to_title is the function which does the work and is called at
//  the rending phase of both relationship and post_object fields]
//
//======================================================================

  function set_post( $key ) {
    $GLOBALS['post']                          = get_page_by_path( $key, OBJECT ); // Get the post.. and store it post object
                                                                                  // This fixes the post object - but that
                                                                                  // isn't enough - there are other bits
                                                                                  // which are got from the wp_query object
                                                                                  // which we need to set!
    $GLOBALS['wp_query']->queried_object      = $GLOBALS['post'];                 // Replace queried_object with post
    $GLOBALS['wp_query']->queried_object_id   = $GLOBALS['post']->ID;             // and it's ID
    $GLOBALS['wp_query']->is_singular         = 1;                                // and finally make it a singular object...
    return $this; // We can chain this now with $theme_obj->set_post( {key} )->output_page( {template_name} );
  }

  function augment_relationship_labels( $title, $post ) {
    return $title.' ('.$post->ID.')';
  }

  function add_augmented_relationship_labels() {
    add_filter('acf/fields/relationship/result', [$this, 'augment_relationship_labels'], 10, 2);
    add_filter('acf/fields/post_object/result',  [$this, 'augment_relationship_labels'], 10, 2);
    return $this;
  }

  function acf_custom_column( $column, $post_id ) {
    $v = get_post_meta( $post_id, substr($column,4), true );
    $v = get_field( substr($column,4), $post_id, true );
    if( !is_array($v) ) {
      $v = [$v];
    }
    echo implode( '; ', array_map( function($s) {
      if( is_object($s) ) {
        return $s->post_title;
      }
      return preg_replace( '/^(\d{4})[-\/]?(\d\d)[-\/]?(\d\d).*$/', '$1-$2-$3', $s );
    }, $v ));
  }

  function clean_and_shorten(
    $str,
    $max            = 15,
    $decode         = 1,
    $allowed_tags   = [ 'b', 'i', 'strong', 'em', 'sup', 'sub' ]
  ) {
  // parameters:
  //   * $str    - string to "shorten" and "remove tags"...
  //   * $max    - (default 15)   - maximum number of words to include before adding an ellipsis
  //   * $decode - (default true) - whether to decode/reencode entities
  //                                [ set to false if the text does not contain entities ]
  //   * $allowed_tags            - See above for defaults, list of tags which are preserved...
  //                                [ other tags are dropped ]
    if( $max === 0 ) {      // Set to unlimited characters {just a clean up!}
      $max = PHP_INT_MAX;
    }
    $count  = 0;
    $tags   = [];
    $parts  = preg_split( '/(<.*?>)/', $str, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
    $output = []; // Contents of HTML to be rendered...
    $title  = ''; // Value of title tag if there is an ellipsis
    $tag_n  = 1;
    while( $part = array_shift( $parts ) ) {
      // Check to see if starts with a "<" followed by optional "/" and
      // a sequence of alpha characters - if it does it is a tag!

      if( preg_match( '/<(\/?)(\w+).*?>/', $part, $matches ) ) {
        list( , $close, $tagname ) = $matches; // $close "" or "/"
        $tagname = strtolower( $tagname );     // $tagname - name of tag...
        if( $count > $max ) {                  // This is in the ... text so skip...
          $title .= ' ';                       // We add a space incase the tag would
          continue;                            // force white space...
        }
        if( ! in_array( $tagname, $allowed_tags ) ) { // Is this one we allow?
          continue;                                   // No - we skip this tag!
        }
        if( $close === '/' ) {                 // Is it a close tag
          if( sizeof($tags) === 0 ) {
            continue;                          // No tags - must be trying to close something wrong!
          }
          while( $open_tag = array_pop( $tags ) ) {
            $output[] = "</$open_tag>";
            if( $open_tag === $tagname ) {
              break;
            }
          }
        } else {                                 // It's an open tag
          $tags[]   = $tagname;
          $output[] = "<$tagname>";
        }
        continue;
      }

      // Now we fall through to the else part - we have text.. we need
      // to chunk this into words (and gaps) and push them either to the
      // visible text array OR the ellipsis text.
      if( $decode ) { // We make entities characters so they don't get split up and counted as words...
        $part = html_entity_decode( $part );
      }
      $words = preg_split( '/((?:[\p{L}_\d]+-+)*[\p{L}_\d]+)/u', $part, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
      $txt   = '';
      foreach( $words as $word ) {
        if( preg_match( '/[\p{L}_\d]/u', $word ) ) {     // We have a new word!
          $count++;
        }
        if( $count <= $max ) {                   // Do we have space left!
          $txt   .= $word;                         // add it..
        } else {
          $title .= preg_replace( '/&nbsp;/',' ', $word );
        }
      }
      if( $decode ) {
        $txt      = htmlentities( $txt );
      }
      if( $txt ) {
        $output[] = preg_replace( '/&nbsp;/', ' ', $txt );
      }
      $txt   = preg_replace( '/&nbsp;/',' ', $txt );
    }
    while( $open_tag = array_pop( $tags ) ) {
      $output[] = "</$open_tag>";
    }
    if( $decode ) {
      $title = htmlentities( $title );
      $title = preg_replace( '/&nbsp;/',' ', $title );
    }
    $title = trim(preg_replace( '/\s+/', ' ', $title ));
    $new = implode( ' ', $output );
    $new = trim(preg_replace( [ '/(<\w+>)\s+/', '/\s+(<\/\w+>)/', '/\s+/' ], [ '$1', '$1', ' ' ], $new ));
    if( $title ) {
      $new .= sprintf( ' <span title="... %s">...</span>', $title );
    }
    return $new;
  }

}
