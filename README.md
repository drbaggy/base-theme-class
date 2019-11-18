# Base theme class

This is a neat class based theme template plugin to enable rapid development of wordpress backed blog-less websites.

## Useful links

* Wordpress tricks
  * [Creating a JSON request](json-request.md)
* Documentation
  * [Notes about methods and data in base class theme](CODING.md)
## Features

### Wordpress management

* Configuration file based addition of CSS/JS to pages and to admin pages

  * Separate config files for public facing site and admin interface;
  * Admin inteface Javascript can be based on roles;

* Tidy up Wordpress - and make it more user friendly

  * Remove additional files and features which a wordpress site (without comments etc doesn't need!)
    * Tidying up meta tags;
    * Removing emoji support;
    * Feed/REST links etc;
    * oembed stuff
  * Remove comments (and optionally posts)
  * Define some default custom parameters [ and put in hook to allow sub-themes to extend ]
  * Define some standard short codes [ and put in hook to allow sub-themes to extend ]
  * Turn on co-authors plus (if enabled) for all post types (pages and custom posts)!
  * Add "credits" field to media files
  * Stop wordpress trying to add image widths back to captioned images
  * Tweak ACF Relationship and Post object fields to include the ID on the image to disambiguate same named posts 
  * Clears many of the wordpress image sizes for "auto-sizing" images
  * Hide ACF admin (optional)
  * Remove "Delete" as an option
  
### Advanced Custom Fields (ACF) wrapper 

* Define custom adminstration blocks for in build objects (page/post);
* Define new objects and create management pages for them;
* Define simple Guttenberg blocks and create management tools for them;

This:

* Define defaults for things like standard input elements, dates, times etc;
* Define admin interface links
* Creates "sensible object/field names" based on the name(s) of fields (over-ridable)

### Simple HTML template support - using tagged HTML rather than PHP

* With (optional) data pre-processor for the resultant ACF fields;
* With (optional) HTML post-processor for the resultant HTML;
* Support for nesting and repeating templates;
* Driven purely by HTML markup - so easy to create from a mocked up page;
* Support for security - by forcing output to run through HTMLentities (unless over-ridden in template)

### Helper functions:

* Human readable variable names; Computer readable words; Simple (English) pluralisation;
* Support to add a taxonomy
* Error log dumping / <pre> structure dumping to page
* Lightweight meta data fetch for large numbers of objects!
* Randomized encoding of URLs, text

## Creating objects (custom post types)

$theme_obj
  ->define_type( "Type name", fields[], options[] );

* "Type name"
    * a human readable type name - the "key" for this type is used by lower-casing this and converting all non-letters to "_"
   
* fields[]
    * an associate array of abstracted "acf" field definitions
    * the "key" is the name of the attribute [human readable] {again made "computer friendly" for keys}
    * the "value" is a an associate array with the following attribues:
        * "type"         =>  string, type of acf field,
        * "label"        =>  string, label to display - defaults to name
        * "required"     =>  1/0 - defaults to 0
        * "layout"       =>  string,
        * "wrapper"      =>  array, usually used in sub_fields of group to define width of column/cell
        * "admin"        =>  1 or "string" add to list of posts in general page
        * "instructions" => "string" markup added to the label to give hints on how to display page
        * for images:
            * "min_width"     => number,
            * "max_width"     => number,
            * "min_height"    => number,
            * "max_height"    => number,
            * "min_size"      => number,
            * "max_size"      => number,
            * "mime_types"    => string, comma separated
        * for wysiwyg:
            * "toolbar"       => array
            * "media_upload"  => 0/1
        * for checkbox/button_group/radio
            * "choices"       => array (hash),
            * "default_value" => string,
        * for repeater, flexible_content
            * "button_label"  => string,
            * "sub_fields"    => array (hash),  field definitions
        * for group
            * "sub_fields"    => array (hash),  field definitions
        * for relationship
            * "post_type"     => [],         of post types
            * "filters"       => [],         usually search and post_types (if relation has more than one post type)
        * for post_object
            * "post_type"     => [],         of post types

* options[]
    * code:   Code to use instead of computer readable form of "Type name"
    * icon:   Name of icon to use from those found on https://developer.wordpress.org/resource/dashicons/
    * prefix: short code used to insert into acf field key to differentiate two attribute defns from different objects
    * plural: Plural if not just adding an "s" to "Type name"
    * add: If set to something this is the restrictions to which users can add this object type ("administrator" for admins only, "do_not_allow" for no-one)
    * title_template: string containing "[[ ]]" tags to insert attributes into the title


## Support or Contact

Please contact james@curtissmith.me.uk for help.
