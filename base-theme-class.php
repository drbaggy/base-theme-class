<?php
/*
+----------------------------------------------------------------------
| Copyright (c) 2018,2019,2020 Genome Research Ltd.
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
 * Description: Support functions to: apply simple templates to acf pro data structures!; to fix annoying defaults in wordpress; to handle sanger publications [Sanger plugin]
 * Version:     0.7.0
 * Author:      James Smith
 * Author URI:  https://jamessmith.me.uk
 * Text Domain: base-theme-class-locale
 * License:     GNU Lesser General Public v3
 * License URI: https://www.gnu.org/licenses/lgpl.txt
 * Domain Path: /lang

# Change log
# ==========

 * Version 0.2.3 - code to hide dump if requested by simply static
 * Version 0.2.2 - add user's role to profile when they are logged in.
 * Version 0.2.1 - added post status to object - so can preview pages which
                   you would otherwise not be able to
                 - move no_of_words() configuration reader to base theme class from theme
                 - fudge to scroll top to fix margin bug
 * Version 0.1.5 - add created time stamp to objects
                 - fixed remove draft call
                 - jquery configuration so can use uncompressed jquery for debug purposes
                 - moved jquery log in to base theme class rather than theme
 * Version 0.1.2 - Code to check for empty strings/HTML
                 - Code to allow "separator to be added to templates so can simplify list display from templates without writing a post handler
                 - Added better "null" checks in templates
 * Version 0.1.1 - Fixed code which rendered the wrong publications
                 - Country list added to base theme class - so could be used in multiple places
                 - list-pager js tweaks to add counts
                 - admin filter fixes
 * Version 0.0.2 - Initial import

 */

define( 'BOILERPLATE_FIELDS', [
  'Name'      => [ 'type' => 'text' ],
  'Content'   => [ 'type' => 'wysiwyg' ],
]);

const WP_COLUMNS = [
  'ID', 'post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', 'post_excerpt',
  'post_status', 'comment_status', 'ping_status', 'post_password', 'post_name', 'to_ping', 'pinged',
  'post_modified', 'post_modified_gmt', 'post_content_filtered', 'post_pa    rent', 'guid', 'menu_order',
  'post_type', 'post_mime_type', 'comment_count', 'filter' ];

const FILTER_LIST = [
  'png' => [ 'png' => 'image/png' ],
  'svg' => [ 'svg' => 'image/svg+xml' ],
  'gif' => [ 'gif' => 'image/gif' ],
  'jpg' => [ 'jpg|jpeg|jpe' => 'image/jpg' ],
  'images' => [ 'png' => 'image/png', 'jpg|jpeg|jpe' => 'image/jpg' ],
  'documents' => [ 
             'pdf' => 'application/pdf',
             'ppt' => 'application/vnd.ms-powerpoint',
             'odp' => 'application/vnd.oasis.opendocument.presentation',
             'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
             'doc' => 'application/msword',
             'odt' => 'application/vnd.oasis.opendocument.text',
             'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
             'xls' => 'application/vnd.ms-excel',
             'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
             'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
             'txt' => 'text/plain',
             'tab|tsv' => 'text/tab-separated-values','csv' => 'text/csv' ],
  'pdf' => [ 'pdf' => 'application/pdf' ],
  'ppt' => [ 'ppt' => 'application/vnd.ms-powerpoint',
             'odp' => 'application/vnd.oasis.opendocument.presentation',
             'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation' ],
  'doc' => [ 'doc' => 'application/msword',
             'odt' => 'application/vnd.oasis.opendocument.text',
             'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' ],
  'xls' => [ 'xls' => 'application/vnd.ms-excel',
             'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
             'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' ],
  'txt' => [ 'txt' => 'text/plain' ],
  'csv' => [ 'tab|tsv' => 'text/tab-separated-values','csv' => 'text/csv' ],
  'zip' => [ 'zip' => 'application/zip' ],
  'json' => [ 'json' => 'application/json' ],
];

define( 'QR_FIELDS', [
  'Slug'    => [ 'type' => 'text' ],
  'URL'     => [ 'type' => 'link' ],
]);

require_once( 'lib/class-basethemeclass-filterer.php' );

function no_of_words() {
  $w = get_theme_mod( 'card_words');
  if( $w <= 0 ) {
    $w = 20;
  }
  return $w;
}

function is_non_empty_array( $data, $key='' ) {
  if( $key != '' ) {
    if( preg_match( '/(.*?)[.](.*)/', $key, $matches ) ) {
      $key = $matches[1];
      $part = $matches[2];
    } else {
      $part = '';
    }
    if( is_array( $data ) && array_key_exists( $key, $data ) && isset( $data[$key] ) ) {
      $data = $data[$key];
    } elseif( is_object( $data ) && property_exists( $data, $key ) && isset( $data->$key ) ) {
      $data = $data->$key;
    } else {
      return false;
    }
    if( $part != '' ){
      return is_non_empty_array( $data, $part );
    }
  }
  return is_array($data) && 0 < count($data);
}

function switch_non_empty_array( $data, $key = '') {
  if( is_non_empty_array( $data, $key ) ) {
    return;
  }
  return false;
}

function is_non_empty_string( $data, $key='' ) {
  if( $key != '' ) {
    if( preg_match( '/(.*?)[.](.*)/', $key, $matches ) ) {
      $key = $matches[1];
      $part = $matches[2];
    } else {
      $part = '';
    }
    if( is_array( $data ) && array_key_exists( $key, $data ) && isset( $data[$key] ) ) {
      $data = $data[$key];
    } elseif( is_object( $data ) && property_exists( $data, $key ) && isset( $data->$key ) ) {
      $data = $data->$key;
    } else {
      return false;
    }
    if( $part != '' ){
      return is_non_empty_string( $data, $part );
    }
  }
  return isset( $data )              // Exists
      && is_scalar( $data )          // Is a string
      && 0 < strlen( $data )         // Is empty string
      && $data != 'undefined'
      && preg_replace( [ '/<[^>]+>/', '/\s+/' ], ['',''], $data ) != '' // No non-tag / non-white space characters
      ;
}

function switch_non_empty_string( $data, $key = '') {
  if( is_non_empty_string( $data, $key ) ) {
    return;
  }
  return false;
}

function switch_non_empty( $data, $key = '' ) {
  $k=$key;
  while( $key != '' ) {
    if( preg_match( '/(.*?)[.](.*)/', $key, $matches ) ) {
      $key = $matches[1];
      $part = $matches[2];
    } else {
      $part = '';
    }
    if( is_array( $data ) && array_key_exists( $key, $data ) && isset( $data[$key] ) ) {
      $data = $data[$key];
    } elseif( is_object( $data ) && property_exists( $data, $key ) && isset( $data->$key ) ) {
      $data = $data->$key;
    } else {
      return false;
    }
    $key = $part;
  }
  if( isset( $data ) && is_array( $data ) ) {
    if( is_non_empty_array( $data ) ) {
      return;
    }
    return false;
  }
  if( is_non_empty_string( $data ) ) {
    return;
  }
  return false;
}

function f( $a ) {
  $b = array_map( function($x) { return $x == 'None' || $x == 'Other' ? lcfirst($x) : $x; }, $a );
  return array_combine( $b, $a );
}

define( 'COUNTRY_LIST', f(array_map( 'html_entity_decode', [
  'Afghanistan',                                 '&Aring;land Islands',
  'Albania',                                     'Algeria',
  'American Samoa',                              'Andorra',
  'Angola',                                      'Anguilla',
  'Antarctica',                                  'Antigua and Barbuda',
  'Argentina',                                   'Armenia',
  'Aruba',                                       'Australia',
  'Austria',                                     'Azerbaijan',
  'Bahamas',                                     'Bahrain',
  'Bangladesh',                                  'Barbados',
  'Belarus',                                     'Belgium',
  'Belize',                                      'Benin',
  'Bermuda',                                     'Bhutan',
  'Bolivia, Plurinational State of',             'Bosnia and Herzegovina',
  'Botswana',                                    'Bouvet Island',
  'Brazil',                                      'British Indian Ocean Territory',
  'Brunei Darussalam',                           'Bulgaria',
  'Burkina Faso',                                'Burundi',
  'Cambodia',                                    'Cameroon',
  'Canada',                                      'Cape Verde',
  'Cayman Islands',                              'Central African Republic',
  'Chad',                                        'Chile',
  'China',                                       'Christmas Island',
  'Cocos (Keeling) Islands',                     'Colombia',
  'Comoros',                                     'Congo',
  'Congo, the Democratic Republic of the',       'Cook Islands',
  'Costa Rica',                                  'C&ocirc;te d&#39;Ivoire',
  'Croatia',                                     'Cuba',
  'Cyprus',                                      'Czech Republic',
  'Denmark',                                     'Djibouti',
  'Dominica',                                    'Dominican Republic',
  'Ecuador',                                     'Egypt',
  'El Salvador',                                 'Equatorial Guinea',
  'Eritrea',                                     'Estonia',
  'Ethiopia',                                    'Falkland Islands (Malvinas)',
  'Faroe Islands',                               'Fiji',
  'Finland',                                     'France',
  'French Guiana',                               'French Polynesia',
  'French Southern Territories',                 'Gabon',
  'Gambia',                                      'Georgia',
  'Germany',                                     'Ghana',
  'Gibraltar',                                   'Greece',
  'Greenland',                                   'Grenada',
  'Guadeloupe',                                  'Guam',
  'Guatemala',                                   'Guernsey',
  'Guinea',                                      'Guinea-Bissau',
  'Guyana',                                      'Haiti',
  'Heard Island and McDonald Islands',           'Holy See (Vatican City State)',
  'Honduras',                                    'Hong Kong',
  'Hungary',                                     'Iceland',
  'India',                                       'Indonesia',
  'Iran, Islamic Republic of',                   'Iraq',
  'Ireland',                                     'Isle of Man',
  'Israel',                                      'Italy',
  'Jamaica',                                     'Japan',
  'Jersey',                                      'Jordan',
  'Kazakhstan',                                  'Kenya',
  'Kiribati',                                    'Korea, Democratic People&#39;s Republic of',
  'Korea, Republic of',                          'Kuwait',
  'Kyrgyzstan',                                  'Lao People&#39;s Democratic Republic',
  'Latvia',                                      'Lebanon',
  'Lesotho',                                     'Liberia',
  'Libyan Arab Jamahiriya',                      'Liechtenstein',
  'Lithuania',                                   'Luxembourg',
  'Macao',                                       'Macedonia, the former Yugoslav Republic of',
  'Madagascar',                                  'Malawi',
  'Malaysia',                                    'Maldives',
  'Mali',                                        'Malta',
  'Marshall Islands',                            'Martinique',
  'Mauritania',                                  'Mauritius',
  'Mayotte',                                     'Mexico',
  'Micronesia, Federated States of',             'Moldova, Republic of',
  'Monaco',                                      'Mongolia',
  'Montenegro',                                  'Montserrat',
  'Morocco',                                     'Mozambique',
  'Myanmar',                                     'Namibia',
  'Nauru',                                       'Nepal',
  'Netherlands',                                 'Netherlands Antilles',
  'New Caledonia',                               'New Zealand',
  'Nicaragua',                                   'Niger',
  'Nigeria',                                     'Niue',
  'Norfolk Island',                              'Northern Mariana Islands',
  'Norway',                                      'Oman',
  'Pakistan',                                    'Palau',
  'Palestinian Territory, Occupied',             'Panama',
  'Papua New Guinea',                            'Paraguay',
  'Peru',                                        'Philippines',
  'Pitcairn',                                    'Poland',
  'Portugal',                                    'Puerto Rico',
  'Qatar',                                       'R&eacute;union',
  'Romania',                                     'Russian Federation',
  'Rwanda',                                      'Saint Barth&eacute;lemy',
  'Saint Helena',                                'Saint Kitts and Nevis',
  'Saint Lucia',                                 'Saint Martin (French part)',
  'Saint Pierre and Miquelon',                   'Saint Vincent and the Grenadines',
  'Samoa',                                       'San Marino',
  'Sao Tome and Principe',                       'Saudi Arabia',
  'Senegal',                                     'Serbia',
  'Seychelles',                                  'Sierra Leone',
  'Singapore',                                   'Slovakia',
  'Slovenia',                                    'Solomon Islands',
  'Somalia',                                     'South Africa',
  'South Georgia and the South Sandwich Islands','Spain',
  'Sri Lanka',                                   'Sudan',
  'Suriname',                                    'Svalbard and Jan Mayen',
  'Swaziland',                                   'Sweden',
  'Switzerland',                                 'Syrian Arab Republic',
  'Taiwan, Province of China',                   'Tajikistan',
  'Tanzania, United Republic of',                'Thailand',
  'Timor-Leste',                                 'Togo',
  'Tokelau',                                     'Tonga',
  'Trinidad and Tobago',                         'Tunisia',
  'Turkey',                                      'Turkmenistan',
  'Turks and Caicos Islands',                    'Tuvalu',
  'Uganda',                                      'Ukraine',
  'United Arab Emirates',                        'United Kingdom',
  'United States of America',                    'United States Minor Outlying Islands',
  'Uruguay',                                     'Uzbekistan',
  'Vanuatu',                                     'Venezuela, Bolivarian Republic of',
  'Viet Nam',                                    'Virgin Islands, British',
  'Virgin Islands, U.S.',                        'Wallis and Futuna',
  'Western Sahara',                              'Yemen',
  'Zambia',                                      'Zimbabwe',
])));

const EXTRA_SETUP = [
  'date_picker'      => [ 'return_format' => 'Y-m-d'       ], // Return values in "mysql datetime" format
  'date_time_picker' => [ 'return_format' => 'Y-m-d H:i:s' ], //  - or relevant part of....
  'time_picker'      => [ 'return_format' => 'H:i:s'       ], //
  'image'            => [ 'save_format' => 'object', 'library' => 'all', 'preview_size' => 'large' ],
  'medium_editor'    => [
    'standard_buttons' => [ 'bold', 'italic', 'subscript', 'superscript', 'removeFormat' ],
    'other_options'    => [ 'disableReturn', 'disableDoubleReturn', 'disableExtraSpaces' ],
    'custom_buttons'   => [],
  ],
  'medium_editor_link'    => [
    'standard_buttons' => [ 'bold', 'italic', 'subscript', 'superscript', 'removeFormat', 'anchor', ],
    'other_options'    => [ 'disableReturn', 'disableDoubleReturn', 'disableExtraSpaces' ],
    'custom_buttons'   => [],
  ],
  'medium_editor_paragraphs'    => [
    'standard_buttons' => [ 'bold', 'italic', 'subscript', 'superscript', 'removeFormat', 'unorderedlist', 'anchor', ],
    'other_options'    => [ 'disableExtraSpaces' ],
    'custom_buttons'   => [],
  ],
  'medium_editor_title'    => [
    'standard_buttons' => [ 'italic', 'subscript', 'superscript', 'removeFormat' ],
    'other_options'    => [ 'disableReturn', 'disableDoubleReturn', 'disableExtraSpaces' ],
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
    'card_words' => [
      'type'        => 'text',
      'section'     => 'base-theme-class',
      'default'     => 0,
      'description' => 'Number of words to appear in cards before being replaced with ellipsis',
    ],
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
  protected $index = 0;
  protected $scripts;
  protected $sequence;
  protected $is_simply_static;

  private $tmp_data;

  function  menu_fix( $html ) {
    $html = str_replace( ['__OB__','__QUOT__','__CB__'],['{','&quot;','}'], $html );
    error_log($html);
    return $html;
  }


  public function type_name( $code ) {
    return $this->custom_types[$code]['name'];
  }

  public function simple_feed( $object_type, $mapper, $prefix='', $image_size = '' ) {
    $base_url = wp_upload_dir()['baseurl'];
    global $wpdb;
    // Get array of post IDs of given type....
    $posts = [];
    $clause = is_array( $object_type )
            ? ' in ("'.implode('","',$object_type).'")'
            : ' = "'.$object_type.'"'
            ;
    $mapper_copy = [];
    $mapper_map  = [];
    foreach( $mapper as $k => $v ) {
      if( substr($k,0,1)=='_' ) {
        $mapper_copy[ substr( $k, 1 ) ] = $v;
      } else {
        $mapper_map[ $k ] = $v;
      }     
    }
    foreach( $wpdb->dbh->query(
      'select ID, post_type, post_modified, post_date, post_title, post_excerpt
         from wp_posts
        where post_status = "publish" and post_type'.$clause.'
        order by post_modified'
    )->fetch_all() as $p ) {
      // Get permalink for each post and to each post object...
      $posts[ $p[0] ] = array_merge(
         [ 'uid'         => $prefix==''?$p[0]:"$prefix-$p[0]",
           'url'         => get_permalink($p[0]),
           'post_type'   => $p[1],
           'update'      => $p[2],
           'create'      => $p[3],
<<<<<<< HEAD
=======
           'post_title'   => $p[4],
           'post_excerpt' => $p[5],
>>>>>>> 70eac63a325515f7932676f36262364810f35afc
         ], $mapper_copy );
    }
    // Get selected meta data for each post..... and add it to post hash [ note we map to a consistent space ]
    foreach( $wpdb->dbh->query('
        select post_id,meta_key,meta_value
          from wp_postmeta where post_id in ('.implode(',',array_keys($posts)).') and
               meta_key in ("'.implode('","',array_keys($mapper_map)).'")'
      )->fetch_all() as $r ) {
        $posts[$r[0]][$mapper_map[$r[1]]] = $r[2];
    }
    // Get image data for each of these posts (specifically URL of each image - possibly one of the "resized" versions)
    $image_hash = [];
    $image_ids = array_map(function($r) { return $r['image_id']; }, array_filter( $posts, function($r) { return isset( $r['image_id'] ) && $r['image_id'] !=''; } ) );
    if( sizeof( $image_ids ) ) {
      foreach(  $wpdb->dbh->query( '
        select post_id,
               group_concat(if(meta_key="_wp_attached_file",meta_value,NULL) separator "")       as file,
               group_concat(if(meta_key="_wp_attachment_metadata",meta_value,NULL) separator "") as meta
          from wp_postmeta where post_id in ( '.implode(',', $image_ids ).'  )  group by post_id'
      ) as $r ) {
        $image_hash[$r['post_id']]=$r;
      }
      // Attach data to posts..
      foreach( $posts as &$r ) {
        if(isset($r['image_id']) && isset( $image_hash[$r['image_id']] ) ) {
          $t     = $image_hash[$r['image_id']];
          $url   = $base_url.'/'.$t['file'];
          if( $image_size != '' && isset( $t['meta'] ) ) {
            $tmeta = unserialize( $t['meta'] );
            if( isset( $tmeta['sizes'] ) && isset( $tmeta['sizes'][$image_size] ) ) {
              $url = preg_replace('/[^\/]+$/','',$url).$tmeta['sizes'][$image_size]['file'];
            }
          }
          $r['image_url'] = $url;
        }
      }
    }
    return $posts;
  }

  public function __construct( $defn ) {
    $this->is_simply_static = preg_match( '/WordPress\/\d+\.\d+\.\d+/', $_SERVER['HTTP_USER_AGENT'] ) ||
                              preg_match( '/FETCHER/',                  $_SERVER['HTTP_USER_AGENT'] );
    $this->sequence = 0;
    $this->custom_types = [];
    $this->defn = $defn;
    $this->scripts = [];
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
         //**** ->stop_wordpress_screwing_up_image_widths_with_captions()
         //->tidy_up_image_sizes()
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
         ->add_template_column()
         ->fix_acf_fields()
         ->fix_reset_email()
         ->add_roles_to_profile()
         ->nav_menu_fixup()
         ;
  }
  function nav_menu_fixup() {
    add_filter('wp_nav_menu_items', [ $this, 'menu_fix' ] , 10, 2);
    return $this;
  }

  function restrict_uploads( $arr ) {
    add_filter('upload_mimes', function( $mimes ) use( $arr ) {
      $mt = [];
      foreach( $arr as $type ) {
        if( isset( FILTER_LIST[ $type ] ) ) {
          foreach( FILTER_LIST[ $type ] as $k => $v ) {
            $mt[$k] = $v;
          }
        } elseif( isset( $mimes[ $type ] ) ) {
          $mt[$type] = $mimes[ $type ];
        }
      }
      return $mt;
    });
    return $this;
  }
  function initialise_proofpoint_protection_protection() {
    add_action('acf/save_post', [ $this, 'proofpoint_protection_fixer' ], 5);
    return $this;
  }

  function qr_code_base_url() {
    $base_url = get_option(    'qr_code_base_url' );
    if( ! $base_url ) {
      $base_url = 'https:'.$_SERVER['HTTP_HOST'].'/q/';
    }
    return $base_url;
  }

  function initialise_boilerplate_codes() {
    $this->define_type( 'Boilerplate text', BOILERPLATE_FIELDS,
      [ 'title_template' => '[[name]]', 'icon' => 'clipboard',
        'prefix' => 'bp', 'add' => 'administrator',
        'position' => 'settings' ] );

    // a shortcode so you can embed the boilerplate wherever
    // you need to in any WYSIWYG fields.
    //
    // Short code is [ boilerplate {boilerplate-name} ]

    add_shortcode( 'boilerplate', function( $atts ) {
      return $this->get_text( implode( ' ', $atts ));
    } );

    // Define a template scalar method so that you can use
    //
    //  [[boilerplate:-:{boilerplate-name}]]
    //
    // in templates
    $this->add_scalar_method( 'boilerplate', function( $s, $e ) {
      return $this->get_text( $s );
    });
    return $this;
  }
  function get_text( $code ) {
    $t = $this->get_entries( 'boilerplate_text',
      [ 'meta_key' => 'name', 'meta_value' => $code ]
    );
    return sizeof($t)
         ? $t[0]['content']
         : "*** Undefined boilerplate '$code' ***";
  }
  function initialise_qr_codes() {
    register_setting( 'qr_code', 'qr_code_base_url',     [ 'default' => '' ] );
    if( is_admin() ) {
      add_action( 'admin_menu', [ $this, 'qr_code_admin_menu' ], PHP_INT_MAX );
    }
    add_action( 'update_option_qr_code_base_url', [ $this, 'qr_code_update_base_url' ], PHP_INT_MAX, 2 );
    add_filter( 'wp_insert_post_data', [ $this, 'qr_code_update_post_data' ] );
    add_action( 'parse_request', [ $this, 'qr_code_parse_request' ] );
    add_action( 'rest_api_init', function () { // Nasty SQL query used by static publish to create the rewrite-map-files.txt
      register_rest_route( 'base', 'qr_redirects', array(
        'methods' => 'GET',
        'callback' => [ $this, 'qr_code_results' ]
      ) );
    } );

    $this->define_type( 'QR code', QR_FIELDS, [ 'title_template' => '[[slug]] - [[url.url]]', 'icon' => 'warning', 'prefix' => 'q', 'add' => 'edit_private_pages', 'menu_order' => 49 ] );
    return $this;
  }
  function qr_code_admin_menu() {
    add_options_page( 'QR code', 'QR code', 'administrator', 'QR code', 'qr_code_options_form' );
  }
  function qr_code_options_form() {
    $base_url = get_option(    'qr_code_base_url' );
    echo '
  <div>
    <h2>QR code generation options</h2>
    <p>
      <strong>QR code/short urls</strong> allow easier to publish URLs for pages (on this site and on others) to be
      referenced by a shorter "typeable" URL...
    </p>
    <form method="post" action="options.php">';
    settings_fields(      'qr_code'        );
    do_settings_sections( 'qr_code'        );
    echo '
      <table class="form-table">
        <tbody>
          <tr>
            <th>Base URL:</th>
            <td>
              <input type="text" name="qr_code_base_url" id="qr_code_base_url" value=', $base_url, '
              Base URL to use if not using default: ', $_SERVER['HTTP_HOST'],'/q/ , e.g. if you are
              using an alternative subdomain
            </td>
          </tr>
        </tbody>
      </table>';
    submit_button();
    echo '
    </form>
  </div>';
  }
  function qr_code_update_base_url( $old, $new ) {
    if( $old == $new ) {
      return;
    }
    $posts = get_posts([
      'post_type'   => 'qr_code',
      'numberposts' => 1e6
    ]);
    $base_url = $new == '' ? 'https:'.$_SERVER['HTTP_HOST'].'/q/' : $new;
    foreach( $posts as $p ) {
      $p->post_title = preg_replace( '/^.*?->/',$base_url.substr($p->post_name,3).' ->', $p->post_title );
      wp_update_post( $p );
    }
  }
  function qr_code_update_post_data( $post_data ) {
    if( $post_data[ 'post_type' ] === 'qr_code' && array_key_exists( 'acf', $_POST ) ) {
      $slug = preg_replace('/\W+/', '', $_POST['acf']['field_q_slug'] );
      do {
        if( $slug === '' ) {
          $slug = implode( '', array_map( function($i) { $p = '0123456789abcdefghijklmnopqrstuvwxyz'; return $p[mt_rand(0,35)]; }, range(1,8) ) );
          $_POST['acf']['field_q_slug'] = $slug;
        }
        // Could add test here to see if generated slug already exists!
      } while( $slug === '' );
      $post_data[ 'post_name' ]  = 'qr-'.$slug;
      $post_data[ 'post_title' ] = 'https://'.$_SERVER['HTTP_HOST'].'/q/'.$slug.'  ->  '.$_POST['acf']['field_q_url']['url'] ;
    }
    return $post_data;
  }

  function qr_code_parse_request() {
    global $wp;
    if( preg_match( '/^q\/(\w+)([.]png)?$/', $wp->request, $matches ) ) {
      // Find post $matches[1];
      header( 'Content-type: text/plain' );
      $render_image = sizeof( $matches ) > 2;
      $my_post = get_page_by_path( 'qr-'.$matches[1], OBJECT, 'qr_code' );
      if( !$my_post || $my_post->post_status !== 'publish' ) {
        return;
      }
      if( $render_image ) {
        $URL = escapeshellcmd( 'https://'.$_SERVER['HTTP_HOST'].'/q/'.$matches[1] );
        $cmd = implode( ' ',[ '/usr/bin/qrencode', '-m', '1', '-s', '4', '-l', 'Q', '-8', '-v', '3', '-o', '-', $URL ] );
        header( 'Content-type: image/png' );
        passthru( $cmd );
      } else {
        header( 'Location: '. get_field( 'url', $my_post->ID ) );
      }
      exit;
    }
    return;
  }

  function add_roles_to_profile() {
    add_action( 'show_user_profile', [ $this, 'show_user_roles' ], 10, 1);
    return $this;
  }
  function show_user_roles() {
    $user  = wp_get_current_user();
    $roles = implode( ', ', array_values(  $user->roles ) );
    printf( '<h3>Your Role</h3>
<table class="form-table" role="presentation">
<tbody>
  <tr>
    <th>Current user role</th>
    <td>
      <p class="description">%s</p>
      </p>
    </td>
  </tr>
<tbody>
</table>', ucfirst( $roles ) );
  }

  function fix_reset_email() {
    // Wordpress is stupid! The password reset URL is included between "<>"s most
    // email clients hide this in the output as they treat it as an HTML tag..
    add_filter( 'retrieve_password_message', [ $this, 'fix_password_message'], PHP_INT_MAX, 1 );
    return $this;
  }
  function fix_password_message( $mess ) {
    return preg_replace( '/(following address:\s+)<(.*?)>/','$1[$2]', $mess );
  }
  function fix_acf_fields() {
    // Remove disabled tags from markup...
    add_filter( 'acf/update_value/type=medium_editor', [$this,'fix_medium_editor_update_value'], PHP_INT_MAX, 3 );
    // Add <p> tags if none are included to stop screwed up output!
    add_filter( 'acf/format_value/type=wysiwyg',       [$this,'fix_wysiwyg_format_value'],       PHP_INT_MAX, 3 );
    return $this;
  }

  function fix_wysiwyg_format_value( $value, $post_id, $field ) {
    return preg_match('/^\s*</',$value) ? $value : '<p>'.$value.'</p>';
  }

  function fix_medium_editor_update_value( $value, $post_id, $field ) {
    if( is_string($value) ) {
      if( ! in_array('bold', $field['standard_buttons'] ) ) {
        $value = preg_replace( '/<\/?b>/', '', $value );
      }
      if( ! in_array('italic', $field['standard_buttons'] ) ) {
        $value = preg_replace( '/<\/?i>/', '', $value );
      }
      if( ! in_array('underline', $field['standard_buttons'] ) ) {
        $value = preg_replace( '/<\/?u>/', '', $value );
      }
      $value = trim(preg_replace('/\s+/s', ' ', $value));
    }
    return $value;
  }

  function add_template_column() {
    add_action( 'manage_page_posts_custom_column', [ $this, 'template_column' ], 10, 2 );
    add_filter( 'manage_page_posts_columns',       function( $columns ) { return array_merge( $columns, [ '_wp_page_template' => 'Template'] ); } );
    return $this;
  }
  function template_column( $column, $post_id ) {
    if( $column == '_wp_page_template' ) {
      $q = get_post_meta( $post_id, '_wp_page_template' );
      if( !empty($q) and isset($q) and is_array($q) ) {
        print preg_replace('/-/',' ', $this->hr( preg_replace( '/\.php$/', '', $q[0] ) ) );
      }
    }
    return;
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
    remove_action( 'wp_head',                'adjacent_posts_rel_link_wp_head');
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
    return strtolower( preg_replace( '/\W+/', '_', $string ) );
  }
  function lodash( $string ) {
  // Convert a human readable name into a valid variable name...
    return strtolower( preg_replace( '/\W+/', '-', $string ) );
  }
  function pl( $string ) {
  // Pluralize and english string...
  // ends in "y" replace with "ies" ; o/w add "s"
    if( preg_match( '/[aeiou]y$/', $string ) ) {
      return preg_replace( '/y$/', 'ies', $string );
    }
    if( preg_match( '/is$/', $string ) ) {
      return preg_replace( '/is$/', 'es', $string );
    }
    return $string.'s';
  }

  function sequence_id() {
    return sprintf( '%04d', $this->sequence++ );
  }
  function random_id() {
    return sprintf( '%s-%d-%04d', str_replace('.','-',microtime(true)), mt_rand(1e6,1e7), $this->sequence++ );
  }

  function block_render( $block, $content = '', $is_preview = false, $post_id = 0 ) {
    $template_code = 'block-'.$this->cr( $block['title'] );
    print $this->render( $template_code, array_merge( [ 'random_id' => $this->sequence_id() ], get_fields() ) );
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

  // Function to add clone link to post category page
  function add_clone_link( $type, $actions, $post, $user_type = 'edit_posts' ) {
    // Only add link if use can edit posts && has clone set {may 
    if( get_current_screen()->post_type === $type && current_user_can($user_type) ) {
      $actions['duplicate'] = sprintf( '<a href="%s" title="Clone this item" rel="permalink">Clone</a>',
        wp_nonce_url('admin.php?action=clone_post&post='. $post->ID, basename(__FILE__), 'duplicate_nonce' )
      );
    }
    return $actions;
  }  

  // Handles cloning of post...
  function clone_post( $type, $user_type = 'edit_posts' ) {
    global $wpdb;

    // First we check that the attributes in the URL are valid...
    //
    if( ! isset( $_GET['post']  )
     && ! isset( $_POST['post'] )
    ) {
      return; // post id not passed in.
    }
    if( ! isset( $_REQUEST['action'] )
     || 'clone_post' != $_REQUEST['action']
    ) {
      return; // action isn't clone post
    }
    if( ! isset( $_GET['duplicate_nonce'] )
     || ! wp_verify_nonce( $_GET['duplicate_nonce'], basename( __FILE__ ) )
    ) {
      return; // Nonce is missing
    }
    $post = get_post( $post_id = absint( isset($_GET['post']) ? $_GET['post'] : $_POST['post'] ) );
    if( $post->post_type != $type ) {
      return; // Post is not of correct type...
    }

    // Now create the base post.. with the same attributes EXCEPT status,
    // author and title (prefix clone)
    $new_post_id = wp_insert_post([
      'post_status'    => 'draft',
      'post_author'    => wp_get_current_user()->ID,
      'post_title'     => '[CLONE] '.$post->post_title,
      'comment_status' => $post->comment_status,
      'ping_status'    => $post->ping_status,
      'post_content'   => $post->post_content,
      'post_excerpt'   => $post->post_excerpt,
      'post_name'      => $post->post_name,
      'post_parent'    => $post->post_parent,
      'post_password'  => $post->post_password,
      'post_type'      => $post->post_type,
      'to_ping'        => $post->to_ping,
      'menu_order'     => $post->menu_order,
    ]);

    // Get all taxonomy terms associated with old post and duplicate them..
    //
    foreach ( get_object_taxonomies($post->post_type) as $taxonomy) {
      wp_set_object_terms($new_post_id,
        wp_get_object_terms($post_id, $taxonomy, array('fields' => 'slugs')),
        $taxonomy, false
      );
    }

    // Duplicate all the post meta attributes
    //
    $wpdb->query( sprintf('
      insert into %s (post_id, meta_key, meta_value)
      select %d, meta_key, meta_value
        from %s
       where post_id = %d and meta_key not in ("_wp_old_slug","_edit_lock")',
      $wpdb->postmeta, $new_post_id, $wpdb->postmeta, $post_id
    ));

    // Finally redirect to the new edit page
    //
    wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
    return;
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
      'show_ui'         => true,
      'show_in_menu'    => true,
      'options'         => [ 'position' => 'normal', 'layout' => 'no_box', 'hide_on_screen' => [] ],
      'menu_position'   => array_key_exists( 'menu_order', $extra ) ? $extra['menu_order'] : 26,
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
        $defn[ 'menu_position'       ]++;
        $defn[ 'label_placement'  ] = isset( $fg['labels'] ) ? $fg['labels'] : 'left';
        $defn[ 'fields'           ] = $this->munge_fields( $prefix.$fg['type'].'_', $fg['fields'], $type, $fg['type'].'_' );
        $defn[ 'options'          ] = [ 'position' => 'normal' ];
        register_field_group( $defn );
      }
    }

    if( array_key_exists( 'clone', $extra ) ) {
      $user_type = $extra['clone'] == 1 ? 'edit_posts' : $extra['clone'];
      add_filter('post_row_actions', function($actions,$post ) use ($type,$user_type) {
        $sc = get_current_screen();
        if( $sc->post_type == $type ) {
          return $this->add_clone_link($type,$actions,$post, $user_type);
        }
        return $actions;
      }, 10, 2);
      add_action( 'admin_action_clone_post', function() use ($type,$user_type) {
        return $this->clone_post( $type, $user_type );
      });
    }

    if( array_key_exists( 'title_template', $extra ) ) {
      add_action( 'admin_head', function( ) use ($type) {
        $sc = get_current_screen();
        if( $sc->post_type == $type ) {
          echo '<script>window.hide_title = true;</script>';
        }
      } );
      add_filter( 'wp_insert_post_data', function( $post_data, $post_arr ) use ( $type, $prefix, $extra ) {
        if( $post_data[ 'post_type' ] === $type && array_key_exists( 'acf', $_POST ) ) {
          $post_data[ 'post_title' ] = $this->__title_template( $extra['title_template'], $prefix );
          $t_name = array_key_exists( 'slug_template', $extra ) ? $this->__title_template( $extra['slug_template'], $prefix ) : '';
          $t_name = sanitize_title( $t_name == '' ? $post_data['post_title'] : $t_name );
          if( ! preg_match( '/^'.$t_name.'-\d+$/', $post_data['post_title'] ) ) {
            $posts = array_filter(
              get_posts( [ 'name' => $t_name, 'post_type' => $type, 'posts_per_page' => -1, 'post_status' => 'publish' ] ),
              function( $_ ) use ( $post_arr ) { return $_->ID != $post_arr['ID'];}
            );
            if(sizeof($posts) ) {
              $t_name .= '-'.$post_arr['ID'];
            }
          }
          $post_data[ 'post_name' ] = $t_name;
        }
        return $post_data;
      }, 10, 2 );
    }
    return $this;
  }

  function __title_template( $template, $prefix ) {
    if( is_callable( $template ) ) {
      return $template( $_POST['acf'] );
    }
    return trim(preg_replace( '/\s+/', ' ',
      preg_replace_callback( '/\[\[([.\w]+)\]\]/',
        function( $m ) use ( $prefix ) {
          $t = $_POST['acf'];
          foreach( explode('.',$m[1]) as $k ) {
            if( is_object( $t ) ) {
              $t = $t->$k;
            } else {
              $p = "field_${prefix}$k";
              if( array_key_exists( $p, $t ) ) {
                $t = $t[$p];
              } else {
                $t = $t[$k];
              }
            }
          }
          return $t;
        },
        $template
      )
    ));
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
      'rewrite'           => [ 'slug' => $code ],
      'heirarchical'      => isset( $extra['hierarchical'] ) ? $extra['hierarchical'] : false,
      'supports'          => [ 'title', 'revisions' ],
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
        $me = array_merge( $me, EXTRA_SETUP[ $def[
          ( array_key_exists( 'alt', $def ) && array_key_exists( $def['alt'], EXTRA_SETUP ) ) ? 'alt' : 'type'
        ] ] );
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
        $field = $field_prefix.$code;
        $cn = $def['admin'] == 1 ? $me['label'] : $def['admin'];
        add_action( 'manage_'.$type.'_posts_custom_column',   [ $this, 'acf_custom_column'        ], 10, 2  );
        add_filter( 'manage_'.$type.'_posts_columns',         function( $columns ) use ($fn, $cn ) {
          return array_merge( $columns, [ $fn => $cn ] );
        });
        add_filter( 'manage_edit-'.$type.'_sortable_columns', function( $columns ) use ($fn, $cn ) {
          return array_merge( $columns, [ $fn => $cn ] );
        });
        if( array_key_exists( 'admin_filter', $def ) ) {
          $flag = $def['type'] == 'checkbox' ? 1 : (  (isset($def['multiple'] ) && $def['multiple']>0) ? 2 : 0 );
          add_action( 'restrict_manage_posts', function() use($type,$cn,$field,$flag) {
            global $wpdb, $table_prefix;
            $post_type = preg_replace( '/[^-\w]/', '', (isset($_GET['post_type'])) ? $_GET['post_type'] : 'post' );

            if( $post_type != $type ) {
              return;
            }
            $var_k = "admin_filter_$field";
            $values = $wpdb->get_results(
               'select meta_value as K,count(*) N
                  from '.$table_prefix.'postmeta m, '.$table_prefix.'posts p
                 where m.meta_key = "'.$field.'" and m.post_id = p.ID and p.post_type = "'.$post_type.'"
                 group by K
                 order by K' );
            $t = [];
            if( $flag > 0 ) {
              foreach( $values as $r ) {
                if( preg_match('/^a:/', $r->K ) ) {
                  foreach( unserialize( $r->K ) as $_ ) {
                    $name = $_;
                    if( $flag == 2 ) {
                      $name = get_the_title( $_ );
                    }
                    if(isset( $t[$_] ) ) {
                      $t[$_][1] += $r->N;
                    } else {
                      $t[$_] = [ $name, $r->N ];
                    }
                  }
                }
              }
            } else {
              foreach( $values as $r ) {
                $t[$r->K] = [ $r->K, $r->N ];
              }
            }
            print '<select name="'.$var_k.'"><option value="">All '.$cn.'</option>';
            $curr = isset($_GET[$var_k])?$_GET[$var_k]:'';
            foreach( $t as $k => $v ){
              printf( '<option value="%s"%s>%s (%d)</option>', $k, $k==$curr?' selected="selected"':'', $v[0], $v[1] );
            }
            print '</select>';
          });
          add_action( 'pre_get_posts', function( $query ) use ($type,$field,$flag) {
            global $post_type, $pagenow, $wpdb;
            if( $post_type == $type && $pagenow == 'edit.php' ) {
              $var_k = 'admin_filter_'.$field;
              if( isset( $_GET[ $var_k ] ) && $_GET[ $var_k ] ) {
                if($flag) {
                  $query->query_vars['meta_key']     = $field;
                  $query->query_vars['meta_value']   = '"'.$_GET[$var_k].'"';
                  $query->query_vars['meta_compare'] = 'LIKE';
                } else {
                  $query->query_vars['meta_key']     = $field;
                  $query->query_vars['meta_value']   = $_GET[$var_k];
                  $query->query_vars['meta_compare'] = '=';
                }
              }
            }
          }, 1 );
        }
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
    $slug       = isset( $def['slug_plural'] ) && $def['slug_plural'] ? $this->cr($plural) : $code;

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
      'rewrite'      => [ 'slug' => $slug, 'with_front' => false ],
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

  function qr_code_results( $data ) {
    global $wpdb;
    $res = $wpdb->dbh->query( '
select group_concat(if(m.meta_key="slug",m.meta_value,"") separator "") code,
       group_concat(if(m.meta_key="url",
        SUBSTRING_INDEX(SUBSTRING_INDEX(SUBSTRING_INDEX(m.meta_value,"\"url\";s:",-1),
          "\"",2),"\"",-1),"") separator "") url
  from wp_posts p, wp_postmeta m where p.ID = m.post_id and
       p.post_type = "qr_code" and p.post_status = "publish"
 group by p.ID' );
    return $res->fetch_all();
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
           get_permalink( $r->ID ),
           $r->post_title,
           $labels[$r->post_type],
           $r->ID,
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
    ], 'jquery_version' => [
      'type'        => 'text',
      'section'     => 'base-theme-class',
      'default'     => '',
      'description' => 'Version of jQuery to use [ currently either jquery-3.5.1.min.js or jquery-3.5.1.js (for debug)',
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
    return $this;
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
    $wp_admin_bar->remove_node( 'new-post' );
    $wp_admin_bar->remove_node('new-content');
    $wp_admin_bar->add_node( $new_content_node);
    $wp_admin_bar->remove_menu('comments');
 //   $wp_admin_bar->remove_node('new-post');
    $wp_admin_bar->remove_menu('wp-logo');   // Not to do with posts - but good to get rid of in admin interface!
  }


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
    if( ! ( isset( $atts ) && is_array( $atts ) && sizeof( $atts ) ) ) {
      return '';
    }
    $attr_string = implode( ' ', $atts );
    if( $atts == 'undefined' ) {
      return '';
    }
    $atts_components = [];
    foreach( $atts as $k => $v ) {
      $atts_components[] = substr($k,0,1) == '-' ? "$k=$v" : $v;
    }
    $attr_string = implode( ' ', $atts_components );
    $class='pub-simple';
    if( isset( $atts['class'] ) ) {
      $class=$atts['class'];
      unset($atts['class']);
    } else {
      $class = 'publications_list';
    }
    $random_id = $this->sequence_id();
    return sprintf(
'
<div id="pub-%s" class="%s" data-ids="%s %s"><span class="loading_publications">Loading publications...</span></div>
',
      $random_id,
      $class,
      HTMLentities( get_theme_mod( 'publication_options' ) ),
      $attr_string
    ).
    $this->add_script( '', 'show_pubs("#pub-'.$random_id.'")' );
  }

  // Short code: [email_link {email} {link text}?]
  //
  // Render an (source code) obfuscated email (mailto:) link
  //
  //  * If email does not contain "@" then we add email_domain from customizer...
  //  * If link text isn't defined it defaults to email address
  //

  function email_link( $atts, $content = null ) {
    if( ! is_array( $atts) ) {
      $atts = [$atts];
    }
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
  public function pretty_dump( $d ) {
    return '<pre style="height:400px;width:100%;border:1px solid red; background-color: #fee; color: #000; font-weight: bold;font-size: 10px; overflow: auto">'.HTMLentities(print_r($d,1)).'</pre>';
  }
  function templates_join( $t_data, $extra, $sep, $sep_last = '' ) {
    if( !is_array($t_data) ) {
      return '';
    }
    $res = array_map(function($row) use ($extra) {
      $tn =  $this->template_name( $extra, $row );
      return $this->expand_template( $tn, $row );
    }, $t_data );
    if( $sep_last != '' && sizeof($res) > 1 ) {
      $last = array_pop( $res );
      return implode( $sep, $res ).$sep_last.$last;
    }
    return implode( $sep, $res );
  }

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
        if( $this->is_simply_static ) {
          return '';
        }
        return $this->pretty_dump( $t_data );
      },
      'templates'           => function( $t_data, $extra ) { return $this->templates_join( $t_data, $extra, '' ); },
      'templates_and'       => function( $t_data, $extra ) { return $this->templates_join( $t_data, $extra, ', ', ' and ' ); },
      'templates_comma'     => function( $t_data, $extra ) { return $this->templates_join( $t_data, $extra, ', ' ); },
      'templates_semicolon' => function( $t_data, $extra ) { return $this->templates_join( $t_data, $extra, '; ' ); },
      'templates_space'     => function( $t_data, $extra ) { return $this->templates_join( $t_data, $extra, ' ' ); },
      'image_url' => function( $t_data, $extra='' ) {
        if( $t_data && is_array($t_data) && isset( $t_data['url'] ) && $t_data['url'] != '' ) {
          return isset( $t_data['sizes'][$extra] ) ? $t_data['sizes'][$extra] : $t_data['url'];
        }
        return '';
      },
      'template'  => function( $t_data, $extra ) {
        $tn =  $this->template_name( $extra, $t_data );
        return $this->expand_template( $tn, $t_data );
      }
    ];
    $this->scalar_methods = [
      'raw'           => function( $s, $e=''  ) { return $s; },
      'html_with_br'  => function( $s, $e=''  ) { return implode( "<br />\n", array_map( 'HTMLentities', preg_split( '/\r?\n/', $s ) ) ); },
      'post_url_link' => function( $s, $e=''  ) { return HTMLentities(get_permalink( $s )); },
      'post_url_raw'  => function( $s, $e=''  ) { return get_permalink( $s ); },
      'ucfirst'       => function( $s, $e=''  ) { return ucfirst($s); },
      'hr'            => function( $s, $e=''  ) { return $this->hr($s); },
      'cr'            => function( $s, $e=''  ) { return $this->cr($s); },
      'uc'            => function( $s, $e=''  ) { return strtoupper($s); },
      'lc'            => function( $s, $e=''  ) { return strtolower($s); },
      'lodash'        => function( $s, $e=''  ) { return $this->lodash($s); },
      'striptags'     => function( $s, $e=''  ) { return strip_tags($s); },
      'date'          => function( $s, $e=''  ) { return $s ? date_format( date_create( $s ), $this->date_format ) : ''; },
      'enc'           => function( $s, $e=''  ) { return rawurlencode( $s ); },
      'rand_enc'      => function( $s, $e=''  ) { return $this->random_url_encode( $s ); },
      'integer'       => function( $s, $e=''  ) { return intval($s); },
      'boolean'       => function( $s, $e=''  ) { return $s ? 'true' : 'false'; },
      'shortcode'     => function( $s, $e=''  ) { return do_shortcode($s); },
      'strip'         => function( $s, $e=''  ) { return preg_replace( '/\s*\b(height|width)=["\']\d+["\']/', '', do_shortcode( $s ) ); },
      'spliturl'      => function( $s, $e=''  ) { return preg_replace( '/([.\/])(?![.\/])/','\1<wbr/>', HTMLentities($s) ); },
      'rand_html'     => function( $s, $e=''  ) { return $this->random_html_entities( $s ); },
      'html'          => function( $s, $e=''  ) { return HTMLentities($s); },
      'bytes'         => function( $s, $e='-' ) {
         if( $e=='' ) {
           $e = $s > 8e8 ? 'G' : ($s > 8e5 ? 'M' : ( $s > 10000 ? 'K' : '' ));
         }
         return $e == 'G' ? sprintf( '0.1f GB', $s/1024/1024/1024 ) :
              ( $e == 'M' ? sprintf( '0.1f MB', $s/1024/1024 ) : 
              ( $e == 'K' ? sprintf( '%d KB', $s/1024 ) : $s.' bytes' )); },
      'para'      => function( $s, $e='' ) { return preg_match( '/\s+<p>/', $s ) ? $s : ( preg_match( '/(.*?)<p>/', $s ) ?
                                                    preg_replace( '/(.*?)<p>/', '<p>\1</p><p>', $s ) : "<p>$s</p>" ); },
      'email'     => function( $s, $e='' ) { // embeds an email link into the page!
        if($s=='') {
          return '';
        }
        $s = strpos( $s, '@' ) !== false ? $s : $s.'@'.get_theme_mod('email_domain');
        return sprintf( '<a href="mailto:%s">%s</a>', $this->random_url_encode( $s ),
          $this->random_html_entities( $s ) );
      },
      'wp'        => function( $s, $e='' ) { // Used to call one of the standard wordpress template blocks
         switch( $s ) {
           case 'part-' === substr( $s, 0, 5) :
             ob_start();
             get_template_part( substr( $s, 5 ) );
             return ob_get_clean();
           case 'prev_index' :
             return $this->index;
           case 'index' :
             return ++$this->index;
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
        if( array_key_exists( 'post', $template ) ) {
          $this->add_postprocessor( $key, $template['post'] );
        }
        return $this;
      } else { // Switch needs to be outwith template logic as well - as we may not have a real template
        if( array_key_exists( 'switch', $template ) ) {      // Just some logic as to which template to use
          $this->add_switcher( $key, $template['switch'] );  // or just to chose another template
          $this->add_template( $key, "{{{{STUB TEMPLATE FOR EMPTY SWITCH}}}}" );
        }
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
            if( '.' !== substr($file,0,1) && '~' !== substr($file,-1) ) {
              $this->load_from_directory( $dirname.'/'.$file );
            }
          }
          closedir($dh);
        }
      } elseif( '.php' == substr($full_path,-4) ) {
        $templates = include $full_path;
        if( isset( $templates ) && is_array( $templates ) ) {
          foreach( $templates as $key => $template ) {
            $this->add_template( $key, $template );
          }
        } else {
          error_log( " " );
          error_log( "BASE THEME CLASS: File $dirname does not return an array of templates" );
          error_log( " " );
        }
      } else {
        error_log( " " );
        error_log( "BASE THEME CLASS: Skipped processing of template file: $dirname" );
        error_log( " " );
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

  protected function is_empty_string( $string ) {
    if( !isset($string) ) {
      return true;
    }
    if( $this->collapse_empty( $string ) == '' ) {
      return true;
    }
    return false;
  }

  protected function expand_string( $str, $data, $template_code ) {
    $regexp = sprintf( '/\[\[(?:(%s|%s):)?([-#=@~.!\w+]+)(?::([^\]]+))?\]\]/',
       implode('|',array_keys( $this->array_methods )),
       implode('|',array_keys( $this->scalar_methods )) );

    return implode( '', array_map(
      function( $t ) use ( $data, $template_code, $regexp ) {
        return is_object($t) && ($t instanceof Closure)
      ? $t( $data, $template_code ) // If the template being parsed is a closure then we call the function
      : preg_replace_callback(      // It's a string so parse it - regexps are wonderful things!!!
          $regexp,
          function($match) use ($data, $template_code ) {
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
              return $this->scalar_methods[ $render_type ]( $t_data, $extra );
            }
            if( !is_scalar( $t_data ) ) { error_log( $template_code.' '.gettype( $t_data ) ); error_log( print_r( $t_data, 1 ) ); }
            return HTMLentities( $t_data );
          },
          $t
        );
      },
      $str
    ));
  }
  protected function expand_template( $template_code, $data ) {
    if( substr($template_code,0,2)=='__' ) {
      if( array_key_exists(substr($template_code,2), $this->scalar_methods ) ) {
        return $this->scalar_methods[ substr($template_code,2) ]( $data );
      }
      return $this->show_error( "Scalar method as template does not exist '$template_code'" );
    }
    if( ! array_key_exists( $template_code, $this->templates ) ) {
      return $this->show_error( "Template '$template_code' is missing" );
    }
    // Apply any pre-processors to data - thie munges/amends the data-structure
    // being passed...
    if( array_key_exists( $template_code, $this->switchers ) ) {
      $function = $this->switchers[$template_code];
      if( is_array( $function ) ) {
        $t = switch_non_empty( $data, $function[0] );
        if( $t === false ) {
          $t=$function[1];
        }
      } elseif( is_string( $function ) ) {
        $t = switch_non_empty( $data, $function );
      } else {
        $t = $function( $data, $this );
      }
      if( is_array( $t ) ) {
        return $this->expand_string( $t, $data, 'switch-'.$template_code );
      }
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
    $out = $this->expand_string( $this->templates[$template_code], $data, $template_code );
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
      case '=': // wp option....
        return get_option( $extra );
      case '.'; // just pass data through!
        return $data;
      default:  // navigate down data tree...
        $vars = explode('/',$variable);
        $out = [];
        foreach( $vars as $v ) {
          $t_data = $data;
          foreach( explode( '.', $v ) as $key ) {
            if( is_object( $t_data) ) {
              // If object - 1 see if we need to grab extra data from object...
              if( substr( $key, 0, 1 ) === '!' ) { // Need to look this up in the database as not in the
                                                   // object hash at the moment.
                $t_data = get_field( substr($key,1), $t_data->ID );
                continue;
              }
              if( substr( $key, 0, 1 ) === '#' ) { // Need to look this up in the database as not in the
                                                   // object hash at the moment.
                $t_data = get_post_meta( $t_data->ID, substr($key,1), true );
                continue;
              }
              if( $key == '@' ) {
                $key = 'comment_count';
              }
              if( property_exists( $t_data, $key ) ) { // Check we can access object value...
                $t_data = $t_data->$key;
                continue;
              }
              if( method_exists( $t_data, $key ) ) {
                $t_data = $t_data->$key();
                continue;
              }
            }
            if( !is_array( $t_data ) ) {
              return ''; // No value in tree with that key!
            }
            if( isset($t_data['ID']) ) {
              if( substr( $key, 0, 1 ) === '!' ) { // Need to look this up in the database as not in the
                                                   // object hash at the moment.
                 $t_data = get_field( substr($key,1), $t_data['ID'] );
                continue;
              }
              if( substr( $key, 0, 1 ) === '#' ) { // Need to look this up in the database as not in the
                                                   // object hash at the moment.
                $t_data = get_post_meta( $t_data['ID'], substr($key,1), true );
                continue;
              }
            }
            // key doesn't exist in data structure or has null value...
            if( !array_key_exists( $key, $t_data ) ||
              !isset(            $t_data[$key] ) ||
              is_null(           $t_data[$key] ) ) {
              return '';
            }
            $t_data = $t_data[$key];
          }
          $out[] = $t_data;
        }
        if( sizeof($out)==1 ) {
          return $out[0];
        }
        return $out;
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
      preg_replace('/~EMPTY~/',                                '', // Hold empty attribute open!!
      preg_replace('/<a\s[^>]*?href=""[^>]*>.*?<\/a>/s',       '', // Empty links
      preg_replace('/<iframe\s[^>]*?src=""[^>]*><\/iframe>/',  '', // Empty iframes
      preg_replace('/<img\s[^>]*?src=""[^>]*>/',               '', // Empty images
        $this->expand_template( $template_code, $data, "RENDER" ) ) ) ) ) );
  }

  function output( $template_code, $data = [] ) {
    print $this->render( $template_code, $data );
    return $this;
  }

  function output_page( $page_type ) {
    global $post;
    $extra = [
      'ID'           => get_the_ID(),
      'page_url'     => get_permalink(),
      'page_title'   => the_title('','',false),
      'page_content' => $post->post_content,
      'post_url'     => get_permalink(),
      'post_title'   => the_title('','',false),
      'post_content' => $post->post_content,
      'post_status'  => $post->post_status,
      'created_at'   => $post->post_date_gmt,
      'updated_at'   => $post->post_modified_gmt,
    ];
    $fields = $this->remove_draft( get_fields() );
    $out = $this->render( $page_type, is_array($fields) ? array_merge($fields,$extra) : $extra );
    if( ! $out ) {
      header( 'HTTP/1.0 404 Not found' );
      $this->set_post( 'not-found' ); // should have a not found post set up!
      include_once( $this->template_directory.'/'.get_page_template_slug( $GLOBALS['post']->ID ) );
    }
    get_header();
    print $out;
    get_footer();
  }

  function remove_draft($a) {
    if( ! is_array( $a ) ) {
      return $a;
    }
    $new_a = [];
    foreach( $a as $k => $b ) {
      if( is_array($b) && array_key_exists( 'object', $b ) ) {
        if( isset( $b['object'] ) && is_a( $b['object'], 'WP_Post' ) ) {
          if( $b['object']->post_status != 'publish' ) {
            continue;
          }
        } else {
          continue;
        }
      }
      if( is_object( $b ) && is_a( $b, 'WP_Post' ) ) {
        if( $b->post_status != 'publish' ) {
          continue;
        }
      }
      $new_a[$k] = $this->remove_draft( $b );
    }
    return $new_a;
  }
//----------------------------------------------------------------------
// Support functions used by other methods!
//----------------------------------------------------------------------

  function hide_acf_admin() {
    define( 'ACF_LITE', true );
    return $this;
  }

  function get_entry( $id ) {
    $get_posts = new WP_Query;
    $post = get_post( $id );

    if( !$post ) {
      return;
    }
    return $this->process_entry( $post );
  }
  function process_entry( $post ) {
    $meta = get_fields( $post->ID );
    if( !is_array( $meta ) ) {
      $meta = [];
    }
    foreach( (array) $post as $k => $v ) {
      if( !in_array($k,WP_COLUMNS) ) {
        $meta[$k] = $v;
      }
    }
    $return = array_merge( $meta, [
      'ID'           => $post->ID,
      'post_title'   => $post->post_title,
      'post_type'    => $post->post_type,
      'post_excerpt' => $post->post_excerpt,
      'post_content' => $post->post_content,
      'post_url'     => get_permalink( $post ),
      'post_name'    => $post->post_name,
      'created_at'   => $post->post_date_gmt,
      'updated_at'   => $post->post_modified_gmt,
    ] );
    return $return;
  }

  function get_entries( $type, $extra = array(), $clauses = [] ) {
    $pars = [ 'posts_per_page'=>-1 ];
    if( $type != '' ) {
      $pars[ 'post_type' ] = $type;
    }
    if( sizeof($clauses) ) {
      $this->tmp_data = $clauses;
      add_filter( 'posts_clauses', [ $this, 'extra_clauses' ], 10, 2 );
    }
    $get_posts = new WP_Query;
    $entries = $get_posts->query( array_merge( $pars, $extra ) );
    if( sizeof($clauses) ) {
      $this->tmp_data = '';
      remove_filter( 'posts_clauses', [ $this, 'extra_clauses'] );
    }
    return array_map( function( $_ ) { return $this->process_entry($_); }, $entries );
  }

  function extra_clauses( $clauses, $q ) {
    foreach( $this->tmp_data as $k => $v ) {
      $clauses[$k] .= $v;
    }
    return $clauses;
  }

  function get_entries_light( $type, $extra = array(), $keys = array() ) {
    $get_posts = new WP_Query;
    $entries = $get_posts->query( array_merge( ['cache_results'=>false,'update_post_term_cache'=>false,'update_post_meta_cache'=>false,'posts_per_page'=>-1,'post_type'=>$type], $extra ) );
    if( is_array($entries) && sizeof($entries) > 0 ) {
      $munged = $this->fetch_meta( $entries, $keys );
      $t = array_map( function( $x ) use ($munged) {
        if(!isset( $munged[$x->ID] ) ) {
          $munged[$x->ID] = [];
          error_log("NO META FOR POST ".$x->ID." (".$x->post_type.")");
        }
        return array_merge( $munged[$x->ID], [
          'ID'           => $x->ID,
          'post_title'   => $x->post_title,
          'post_type'    => $x->post_type,
          'post_excerpt' => $x->post_excerpt,
          'post_content' => $x->post_content,
          'post_url'     => get_permalink( $x ),
          'post_name'    => $x->post_name,
          'created_at'   => $x->post_date_gmt,
          'updated_at'   => $x->post_modified_gmt,
        ] );
      }, $entries );
      return $t;
    }
    return [];
  }

//----------------------------------------------------------------------
// Replace characters in string with encoded version of character -
// either replace with HTML entity code (hex or dec) or URL encoding...
//----------------------------------------------------------------------

  function random_html_entities( $string ) { // Use md5 of string to define a "random" sequence - this will then be deterministic.. meaning reloads won't throw diffs
    $alwaysEncode = array('.', ':', '@');
    $res='';
    $random_array = str_split( md5($string) );
    for($i=0;$i<strlen($string);$i++) {
      $x = htmlentities( $string[$i] );
      $r = hexdec( array_shift( $random_array ) );
      $random_array[] = $r;
      $r1 = $r & 3;
      $r2 = $r >> 2;
      $random_array[] = $r2;
      if( $x === $string[$i] && ( in_array( $x, $alwaysEncode ) || $r1<2 ) ) {
        $x = '&#'.sprintf( ['%d','x%x','x%X','%d'][$r2], ord($x) ).';';
      }
      $res.=$x;
    }
    return $res;
  }

  function random_url_encode( $string ) { // Use md5 of string to define a "random" sequence - this will then be deterministic.. meaning reloads won't throw diffs
    $alwaysEncode = array('.', ':', '@');
    $res='';
    $random_array = str_split( md5($string) );
    for($i=0;$i<strlen($string);$i++){
      $x = urlencode( $string[$i] );
      $r = hexdec( array_shift( $random_array ) );
      $random_array[] = $r;
      $r2 = $r & 3;
      $r1 = $r >> 2;
      if( $x === $string[$i] && ( in_array( $x, $alwaysEncode ) || $r1<2 ) ) {
        $x = '%'.sprintf( ['%02X','%02x','%02X','%02x'][$r2], ord($x) );
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
        preg_replace_callback( '/<(li|ol|ul|a|span|p|div|h\d)[^>]*>\s*<\/\1>/', function( $matches ) {
          return strpos($matches[0],'keep') === false ? '' : $matches[0];
        }, $html_str )
      );
    }
    return preg_replace( '/\s*[\r\n]+\s*[\r\n]/', "\n", $html_str ); // Remove blank lines
  }

// The following functions are looking at defining a new role which would
// allow assigning editors to individual pages
  function add_roles_on_plugin_activation() {
    add_role( 'content_editor', 'Content editor', [ 'read' => true, 'edit_posts' => true, 'edit_owned_posts' => true ] );
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
/*
    $field_value = get_post_meta( $post->ID, 'image_size', true );
    $value = $field_value ? $field_value : 'full';
    $html = '';
    foreach( ['thumbnail','medium','large','full'] as $size ) {
      $html .= '<option value="'.$size.'"'.($size == $value ?' selected="selected"' : '').'>'.$size.'</option>';
    }
    $form_fields['image_size'] = array(
      'label' => 'Image size',
      'helps' => __( 'Select image size to use' ),
      'input' => 'html',
      'html'  => '<select id="attachments-'.$post->ID.'-image_size">'.$html.'</select>',
      'value' => $value
    );
*/
    return $form_fields;
  }

  function include_credit_as_data_attribute( $html, $id, $alt, $title ) {
    $t = get_post_meta( $id );
    $credit = $t['custom_credit'];
    if( is_array( $credit ) ) {
      $credit = $credit[0];
    }
//  $size = $t['image_size'];  wp_get_attachment_image_src
    return $credit ? preg_replace( '/<img /','<img data-credit="'.HTMLentities($credit).'" ', $html ) : $html;
  }

  function custom_media_save_attachment( $attachment_id ) {
    if ( isset( $_REQUEST['attachments'][ $attachment_id ]['custom_credit'] ) ) {
      $custom_credit = $_REQUEST['attachments'][ $attachment_id ]['custom_credit'];
      update_post_meta( $attachment_id, 'custom_credit', $custom_credit );
//      $image_size    = $_REQUEST['attachments'][ $attachment_id ]['image_size'];
//      update_post_meta( $attachment_id, 'image_size', $image_size );
    }
  }
  function add_credit_code() {
    add_filter( 'attachment_fields_to_edit',           [ $this, 'custom_media_add_credit'      ], null, 2 );
    add_action( 'edit_attachment',                     [ $this, 'custom_media_save_attachment' ] );
    add_filter( 'get_image_tag',                       [ $this, 'include_credit_as_data_attribute' ], 0, 4);
//   add_filter( 'wp_get_attachment_image_attributes',  [ $this, 'include_credit_as_data_attribute' ], 0, 4);
    add_action( 'wp_ajax_save-attachment-compat',  [ $this, 'custom_media_save_attachment' ], PHP_INT_MAX );
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
    $tmp = get_page_by_path( $key, OBJECT );
    if( $tmp ) {
      $GLOBALS['post']                          = $tmp;      // Get the post.. and store it post object
                                                             // This fixes the post object - but that isn't enough -
                                                             // there are other bits which are got from the wp_query
                                                             // which we need to set!
      $GLOBALS['wp_query']->queried_object      = $tmp;      // Replace queried_object with post
      $GLOBALS['wp_query']->queried_object_id   = $tmp->ID;  // and it's ID
      $GLOBALS['wp_query']->is_singular         = 1;         // and finally make it a singular object...
      return true;
    }
    return false; // We can chain this now with $theme_obj->set_post( {key} )->output_page( {template_name} );
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
    #$v = get_post_meta( $post_id, substr($column,4), true );
    $v = get_field( substr($column,4), $post_id, true );

    if( is_array($v) && empty( $v ) ) {
      $v= get_post_meta($post_id,substr($column,4));
      if( is_array($v) && sizeof($v)>0) {
        $v=$v[0];
      }
    }
    if( !is_array($v) ) {
      $v = [$v];
    }

    $s = implode( '; ', array_map( function($s) {
      if( is_object($s) ) {
        return $s->post_title;
      }
      return preg_replace( '/^(\d{4})[-\/]?(\d\d)[-\/]?(\d\d).*$/', '$1-$2-$3', $s );
    }, $v ));
    if( $s == '' ) { $s = '-'; };
    print $s;
  }

  function add_style( $style_src, $css = '') {
    $md5 = md5( 'css:#:#:'.$style_src.':#:#:'.$css );
    if( isset( $this->scripts[$md5] ) ) {
      return '';
    }
    $out = '';
    if( $style_src != '' ) {
      $out = sprintf( '<link rel="stylesheet" href="%s" />', HTMLentities( $style_src ) );
    }
    if( $css != '' ) {
      $out .= sprintf( '<style>
/*<![CDATA[*/
%s
/*]]>*/
</style>', $css );
    }
    $this->scripts[$md5] = true;
    return $out;
  }

  function wp_jquery_manager_plugin_front_end_scripts() {
    $wp_admin = is_admin();
    $wp_customizer = is_customize_preview();

    // jQuery
    if ( $wp_admin || $wp_customizer ) {
      // echo 'We are in the WP Admin or in the WP Customizer';
      return $this;
    } else {
      // Deregister WP core jQuery, see https://github.com/Remzi1993/wp-jquery-manager/issues/2 and https://github.com/WordPress/WordPress/blob/91da29d9afaa664eb84e1261ebb916b18a362aa9/wp-includes/script-loader.php#L226
      wp_deregister_script( 'jquery' ); // the jquery handle is just an alias to load jquery-core with jquery-migrate
      // Deregister WP jQuery
      wp_deregister_script( 'jquery-core' );
      // Deregister WP jQuery Migrate
      wp_deregister_script( 'jquery-migrate' );
      // Register jQuery in the head
      $jquery_version = $flag = get_theme_mod('jquery_version');
      if( !isset($jquery_version) || empty( $jquery_version ) ) {
        $jquery_version = 'jquery-3.5.1.min.js';
      }
      wp_register_script( 'jquery-core', plugin_dir_url(__FILE__).$jquery_version, array(), null, false );
      /**
       * Register jquery using jquery-core as a dependency, so other scripts could use the jquery handle
       * see https://wordpress.stackexchange.com/questions/283828/wp-register-script-multiple-identifiers
       * We first register the script and afther that we enqueue it, see why:
       * https://wordpress.stackexchange.com/questions/82490/when-should-i-use-wp-register-script-with-wp-enqueue-script-vs-just-wp-enque
       * https://stackoverflow.com/questions/39653993/what-is-diffrence-between-wp-enqueue-script-and-wp-register-script
       */
      wp_register_script( 'jquery', false, array( 'jquery-core' ), null, false );
      wp_enqueue_script( 'jquery' );
    }
    return $this;
  }
  function register_jquery_latest() {
    add_action( 'wp_enqueue_scripts', [ $this, 'wp_jquery_manager_plugin_front_end_scripts'] );
    return $this;
  }
  function _fix_proofpoint($o) {
    if( is_scalar($o) ) {
      return preg_replace_callback(
        '/https:\/\/urldefense\.proofpoint\.com\/v2\/url\?u=([-.\w]*)(\&[-=;&\w]+|)/',
        function($m){
          return preg_replace_callback(
            ['/-25(60|5[CE]|7[BCD])/','/-(3[ABDF]|2[13456A89DB]|4[0]|5[BDF]|7E)/'],
            function( $matches ) {
              return chr(hexdec($matches[1]));
            },
            preg_replace(
              ['/_/','/-26quot-3B/','/-26lt-3B/','/-262339-3B/'],
              ['/','"','<',"'"],
              $m[1]
            )
          );
        },
        $o
      );
    }
    if( is_array($o) ) {
      foreach($o as $k => $v ) {
        $o[$k] = $this->_fix_proofpoint($v);
      }
    }
    return $o;
  }

  function proofpoint_protection_fixer( $post_id ) { 
    $_POST['acf'] = $this->_fix_proofpoint($_POST['acf']);
  }

  function add_script( $script, $js = '' ) {
    $md5 = md5( 'js:#:#:'.$script.':#:#:'.$js );
    if( isset( $this->scripts[$md5] ) ) {
      return '';
    }
    if( $script != '' ) {
      $out = sprintf( '<script src="%s">', HTMLentities( $script ) );
    } else {
      $out = '<script>';
    }
    if( $js != '' ) {
      $out .= sprintf( '
//<![CDATA[
%s
//]]>
', $js );
    }
    $out .= '</script>';
    $this->scripts[$md5] = true;
    return $out;
  }
  function clean_and_shorten( $str, $max  = 15, $decode = 1, $allowed_tags   = [ 'b', 'i', 'strong', 'em', 'sup', 'sub' ] ) {
  // parameters:
  //   * $str    - string to "shorten" and "remove tags"...
  //   * $max    - (default 15)   - maximum number of words to include before adding an ellipsis
  //   * $decode - (default true) - whether to decode/reencode entities
  //                                [ set to false if the text does not contain entities ]
  //   * $allowed_tags            - See above for defaults, list of tags which are preserved...
  //                                [ other tags are dropped ]
    if( !$max || $max < 0 ) {      // Set to unlimited characters {just a clean up!}
      $max = PHP_INT_MAX;
    }
    $count  = 0;
    $tags   = [];
    $parts  = preg_split( '/(\s*<.*?>\s*)/', $str, 0, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
    $output = []; // Contents of HTML to be rendered...
    $title  = ''; // Value of title tag if there is an ellipsis
    $tag_n  = 1;
    //error_log( print_r([$parts, $count, $max],1) );
    while( $part = array_shift( $parts ) ) {
      /* Check to see if starts with a "less than" followed by optional "/" and
       a sequence of alpha characters - if it does it is a tag! */
      if( preg_match( '/(\s*)<(\/?)(\w+).*?>(\s*)/', $part, $matches ) ) {
        list( , $pre_space, $close, $tagname, $post_space ) = $matches; // $close "" or "/"
        $tagname = strtolower( $tagname );     // $tagname - name of tag...
        if( $count > $max ) {                  // This is in the ... text so skip...
          $title .= ' ';                       // We add a space incase the tag would
          continue;                            // force white space...
        }
        if( ! in_array( $tagname, $allowed_tags ) ) { // Is this one we allow?
          $output[] = "$pre_space$post_space";
          continue;                                   // No - we skip this tag!
        }
        if( $close === '/' ) {                 // Is it a close tag
          if( sizeof($tags) === 0 ) {
            continue;                          // No tags - must be trying to close something wrong!
          }
          $output[] = $pre_space;
          while( $open_tag = array_pop( $tags ) ) {
            $output[] = "</$open_tag>";
            if( $open_tag === $tagname ) {
              $output[] = $post_space;
              break;
            }
          }
          $output[] = $post_space;
        } else {                                 // It's an open tag
          $tags[]   = $tagname;
          $output[] = "$pre_space<$tagname>$post_space";
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
    $new = implode( '', $output );
    $new = trim(preg_replace( [ '/(<\w+>)\s+/', '/\s+(<\/\w+>)/', '/\s+/' ], [ '$1', '$1 ', ' ' ], $new ));
    if( $title ) {
      $new .= sprintf( '&nbsp;<span title="... %s">...</span>', $title );
    }
    return $new;
  }

// Filterer function to add filters...

  function get_filterer( $entries ) {
    return new BaseThemeClass\Filterer( $this, $entries );
  }

  function get_title_map( $post_type ) {
    $q = new WP_Query;
    $posts = $q->query( [ 'posts_per_page' => -1, 'post_type' => $post_type ] );
    return array_combine(
      array_map( function($_){ return $_->ID;         }, $posts ),
      array_map( function($_){ return $_->post_title; }, $posts )
    );
  }
}
