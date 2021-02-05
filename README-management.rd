# Wordpress management

* Configuration file based addition of CSS/JS to pages and to admin pages

  * Separate config files for public facing site and admin interface;
    * 
  * Admin inteface Javascript can be based on roles;
    * add_my_scripts_and_stylesheets() - called in base-theme-class

* Tidy up Wordpress - and make it more user friendly

  * Remove additional files and features which a wordpress site (without comments etc doesn't need!)
    * Tidying up meta tags;
    * Removing emoji support;
    * Feed/REST links etc;
    * oembed stuff
    * `clean_rubbish_directory()` called in base-theme-class
  * Remove comments (and optionally posts)
    * `remove_comments_admin()` called in base-theme-class
  * Define some default custom parameters [ and put in hook to allow sub-themes to extend ]
    *  
  * Define some standard short codes [ and put in hook to allow sub-themes to extend ]
    * `register_short_code` called in base-theme-class, by default adds:
      * `publications` defined in base-theme-class
        * parameters are passed straight through to Pagesmith Component plugin - probably needs re-writing in PHP..
      * `email_link` defined in base-theme-class
        * two parameters - first is the email address, optional - any other words are used as the text for the email link - otherwise uses the email address
        * configuration value to define the email domain (so can just use "username" without the bit after the @ )
    * can override in class to add additional short codes - remember to call `parent::register_short_code()!
  * Turn on co-authors plus (if enabled) for all post types (pages and custom posts)!
    * `enable_co_authors_plus_on_all_post_types()` called in base-theme-class
  * Add "credits" field to media files
  * Stop wordpress trying to add image widths back to captioned images
  * Tweak ACF Relationship and Post object fields to include the ID on the image to disambiguate same named posts 
  * Clears many of the wordpress image sizes for "auto-sizing" images
  * Hide ACF admin (optional)
  * Remove "Delete" as an option
