# Templating

One of the main parts of Base Theme Class is the templating language.
This is designed around a basic premise that the majority of the templates
should be basic HTML, and not need to write much PHP.

## Syntax.

A template file contains a single return statement which returns an array of
templates in the form:

``` php
return [
  'my_first_template' => '<h1>Markup</h1>',
  'my_second_template' => [
    'switch'   => function( $data[, $self] ) { return $template_name || false || undef },
    'pre'      => function( ) { },
    'template' => '<h1>Markup</h1>',
    'post'     => function( ) { },
  
  ],
];
```

## Switcher

This function allows you to use an alternate template OR to just skip the content in the markup

The function takes one/two parameters - the first is a data structure, the second (optional) is the Base Theme Class object.

Return values:
 * `false` - Skip the template
 * `{template_name}` - Use template {template_name}
 * `undef` - Use the template
  
## Pre

This function allows you to modify the data structure which gets past to the template

The function takes one/two parameters - the first is a data structure, the second (optional) is the Base Theme Class object.

Return value:
 * modified data structure

## Template

This is an HTML string with template placeholders - See notes below

## Post

This function allows you to modify the HTML after the template has been processed

It takes one/two/three parameters - the first is the processed HTML, the second (optional) is the Base Theme Class object, the third (optional) is the data structure.

Return value:
 * modified HTML.
 
## Template strings
 
Template inclusion is of the form:
 
 * [variable_name]
 * [template_type:variable_name]
 * [template_type:variable_name:extra]
 * [variable_name:extra]
 
### Scalar template options

 * `html_with_br`  -> HTML entities of content (but convert "\r?\n" with <br />)
 * `post_url_link` -> HTML entities version of permalink
 * `post_url_raw`  -> raw version of permalink
 * `ucfirst` -> First character upper cased
 * `hr` -> Human readable version of variable name (_ separated)
 * `cr` -> Computer readable version of string (' ' separated)
 * `uc` -> All upper case
 * `lc` -> All lower case
 * `raw` -> Raw string
 * `date` -> Date format - using standard date format for site
 * `enc` -> URL encoded version of string
 * `rand_enc` -> String with some values URL encoded
 * `integer` -> Integer
 * `boolean` -> displays true / false based on value
 * `shortcode` -> Expands out short code in convert
 * `strip` -> Remove height/width etc from markup
 * `spliturl` -> returns URL but with the option of it wrapping on "/"s
 * `html` -> HTML entities of content {default action}
 * `email` -> encodes some characters in email as urlencoded (in link) and html entities in body; uses 'email_domain' property for domain if none specified
 * `wp` -> see below


### wp template options

These are parts of wordpress {passed in "extra"}

 * `part-{part}` - returns template_part given by {part}
 * `index` - incrementing numeric value
 * `prev_index` - previous value of increment above
 * `charset` - the charset for the website
 * `lang` - language attributes
 * `path` - path to theme folder
 * `body_class` - class of body
 * `menu-{menu}` - markup from menu give by {menu}
 * `head` - the output of wp_head
 * `foot` - the output of wp_footer

### Array template options

 * `size` - size of array
 * `json` - json structure of array
 * `dump` - styled dump of contents of array
 * `templates` - expand the template name provided with content
 * `templates_and` - expand the template name provided with content, join with ", " or "and" resepectively
 * `templates_comma` - expand the template name provided with content, join with ", "
 * `templates_semicolon` - expand the template name provided with content, join with "; "
 * `templates_space` - expand the template name provided with content, join with " "
 * `template` - expand another template passing array in
 
### Expanding variable names

 * `.` - current object
 * `-` - raw string
 * `~` - theme mod parameter
 * `=` - get option
 * `string1.string2.string3`
    * Expands the element [string1][string2][string3], where the indexing may be object accessor or array lookup
    * If first character of string is "!" then it uses get_field to get other (ACF) properties of object
    * If the key is "@" it returns the comment count
