# Base theme class

This is a neat class based theme template plugin to enable rapid development of wordpress backed blog-less websites.

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

## Support or Contact

Please contact james@curtissmith.me.uk for help.
