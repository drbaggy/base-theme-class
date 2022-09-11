# BaseThemeClass\Filterer

## Methods:

  * `__construct( BaseThemeClass object, $entries[] )`

    * Note usually use "helper" on `BaseThemeClass`
     
      `$this->get_filterer( $entries );

  * `add_filter( $name, $pars[] )`

    * adds a filter called `$name`
    * If $pars contains an entry "key" then this is used to compute the value for "$name" in the entry
    * If $pars contains an entry "mapper" then this is used as a map function to map "key" to value
    * If $pars contains an entry "value" then this is used to compute the value based on the entry
    * If it contains neither "mapper" or "value" then the key value is used as the value

  * `add_letter_filter( $name )`

    adds a letter index using the first letter of the `$name` fields

  * `add_text_filter( $keys[] )`

    adds a text fied which is the union of all the text in fields with given keys

  * `finish_up( $flag )`

    * tidies up the filters - converts the structure into the key and value pairs required for rendering
    * if `$flag` is true then we include counts in the select boxes
    * adds "filter markup" to each element of entries which contains the values for each of the index fields.
    * adds a "unique" ID for use in elements

  * `out()`

    returns the filters and entries in a standard way `[ 'filter' => $filters, 'entries' => $entries ]`

## Example 1 - Sanger news

```php
'pre' => function($entries,$self) {
  return $this->get_filterer( $entries )
    ->add_text_filter( [ 'excerpt', 'content', 'post_title', 'secondary_title', 'byline_text' ] )
    ->add_filter( 'year', [ 'key' => function($e) { return substr( $e['byline_date'],0,4); } ] )
    ->add_filter( 'programme', ['mapper' => $self->get_title_map( 'programme' ),
                    'key'    => function($e) { return array_values(
                      $e['related_programmes'] ? unserialize( $e['related_programmes'] ) : []
     ); } ] )
    ->add_filter( 'type',      [ 'key' => function($e) { return array_merge(
                  $e['options_news_type']   ? unserialize( $e['options_news_type']   ) : [],
                  $e['options_card_topics'] ? unserialize( $e['options_card_topics'] ) : [] );
    } ] )
    ->finish_up(1)
    ->out();
},
```

## Example 2 - Sanger people

```php
'pre' => function($entries,$self) {
  return $this
    ->get_filterer( $entries )
    ->add_letter_filter( 'details_surname' )
    ->add_text_filter( ['details_surname','details_forename','details_middle','job_title','excerpt' ] )
    ->add_filter( 'alumni', [ 'mapper' => [ 'alumni' => 'Alumni', 'current' => 'Current staff' ],
      'key' => function($e) { return $e['alumni'] == 'archived' ? 'alumni' : 'current'; }, ] )
    ->add_filter( 'type', [ 'key' => function($e) {
      $aow = isset( $e['category_area_of_work'] )  ? unserialize( $e['category_area_of_work']  ) : [];
      $flv = isset( $e['category_faculty_level'] ) ? unserialize( $e['category_faculty_level'] ) : [];
      if( !$aow ) { $aow=[]; }
      if( !$flv ) { $flv=[]; }
      return array_merge( $aow, $flv );
    } ] )
    ->add_filter( 'programme', [ 'mapper' => $this->get_title_map( 'programme' ),
                                 'key'    => function($e) { return $e['programmes']; } ] )
    ->finish_up(1)
    ->out();
},
```
