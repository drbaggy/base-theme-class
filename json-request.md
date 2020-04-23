Creating a JSON request in Wordpress
====================================

A simple how to on adding a "JSON" endpoint to WordPress - hooking into the
wordpress REST API - under "/wp-json/" - we need to define the route and the
associated handler.... This is registered with "register_rest_route" - and is
initialized by the "rest_api_init" action....

```php

/* Add a JSON end point to the Wordpress "Web API"...
   This means /wp-json/base/v1/search/****** will call
   the function my_json_search with an array containing a
   parameter "s" from the regular expression.... */

  function create_my_json_request() {
    add_action( 'rest_api_init', function () {
       register_rest_route( 'base/v1', 'search/(?P<s>.+)', array(
         'methods' => 'GET',
         'callback' => [ $this, 'my_json_search' ]
       ) );
    } );
    return $this; // This is a base class thing that "setup functions" are chained so have to return "self"
  }

/* This is the search function.... which is called - uses WP_Query with a
   parameter of "s" to search posts...
*/

  function my_json_search( $data ) {
    $q      = new WP_Query;
    $labels = [];     // Create a cache for labels...
    return array_map( // Use array_map to mogrify the posts into a simpler JSON strucure,
                      // and return the resultant array - WordPress does the rest!
       function($r) use ($labels) {
         if( !array_key_exists($r->post_type, $labels ) ) {
           $labels[$r->post_type] = get_post_type_labels(get_post_type_object($r->post_type))->singular_name;
         }
         return [ '/wp-admin/post.php?post='.$r->ID.'&action=edit', $r->post_title, $labels[$r->post_type]];
       },
       $q->query( [
         'cache_results'          => false,
         'update_post_term_cache' => false,
         'update_post_meta_cache' => false,
         'posts_per_page'         => 10,
         'post_type'              => 'any',
         'post_status'            => [ 'draft', 'publish' ],
         's'                      => urldecode($data['s']),  // Parameter "s" passed through in data array
       ] )
    );
  }
```