# Creating objects (custom post types)

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
