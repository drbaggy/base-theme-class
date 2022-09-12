<?php 
/**
+----------------------------------------------------------------------
| Copyright (c) 2022 Genome Research Ltd.
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

# Support functions for generating data-structures for the
# filters sections...
#
# Maintainer     : js5
# Created        : 2022-09-09

 * @package   BaseThemeClass\Filterer
 * @author    JamesSmith james@jamessmith.me.uk
 * @license   GLPL-3.0+
 * @link      https://jamessmith.me.uk/base-theme-class/
 * @copyright 2022 James Smith
 *
 * Part of Website Base Theme Class
 * Version:     0.1.0
 * Author:      James Smith
 * Author URI:  https://jamessmith.me.uk
 * Text Domain: base-theme-class-locale
 * License:     GNU Lesser General Public v3
 * License URI: https://www.gnu.org/licenses/lgpl.txt
 * Domain Path: /lang

# Change log
# ==========

 * Version 0.1.0 - Initial build

**/


namespace BaseThemeClass;

class Filterer {
  var $entries;
  var $filters;
  var $btc;

  function __construct( $btc, &$entries ) {
    $this->entries = $entries;
    $this->filters = [];
    $this->btc     = $btc;
  }

  function add_filter( $name, $pars ) {
    // Valid pars are:
    //   key        => function
    //   value      => function
    //   mapper     => []
    if( !isset($this->filters[$name]) ) {  // Initialise filter if not
      $this->filters[$name]=[];            // already done so
    }

    if( isset( $pars['key'] ) ) {          // Compute the key for each entry
      foreach( $this->entries as &$e ) {   // entry
        $e[$name] = $pars['key']($e);      // apply key function...
        if( is_array( $e[$name] ) ) {
          $e[$name] = array_map( function($_) { return ''.$_; }, $e[$name] );
        } else {
          $e[$name]=''.$e[$name];          // ''. stringifies numbers
        }
      }
    }
    $mapper = [];                          // Compute key->value mapping
    foreach( $this->entries as $e ) {
      $Q = is_array( $e[$name] ) ? $e[$name] : [ $e[$name ] ];
      $keys = [];
      foreach( $Q as $k ) {
        if( array_key_exists( $k, $mapper ) ) {
          if( !is_null( $mapper[$k] ) ) {
            $keys[]=$k;
            $this->filters[$name][$k][1]++;
          }
          continue;
        }
        $label = null;
        if( isset( $pars['value'] ) ) {
          $label = $pars['value']( $k, $e );
        } elseif( isset( $pars['mapper'] ) ) {
          $label = isset($pars['mapper'][$k]) ? $pars['mapper'][$k] : null;
        } else {
          $label = $k;
        }
        $mapper[$k] = $label;
        if( !is_null($label) ) {
          $this->filters[$name][$k] = [ $label, 1 ];
          $keys[] = $k;
        }
      }
      if( is_array( $e[$name] ) ) {
        $e[$name] = $keys;
      } else {
        $e[$name] = $keys[0];
      }
    }
    return $this;
  }

  function add_letter_filter( $name ) {
    // Initialise filter if not already defined...
    if( !isset($this->filters['letter']) ) {
      $this->filters['letter']=[ '#'=>['#',0] ];
      foreach( range('a','z') as $_ ) {
        $this->filters['letter'][$_] = [ strtoupper($_), 0 ];
      }
    }
    foreach( $this->entries as &$e ) {
      $letter = strtolower( substr( $e[$name], 0, 1 ) );
      if( $letter < 'a' || $letter > 'z' ) {
        $letter = '#';
      }
      $e['letter'] = $letter;
      $this->filters['letter'][$letter][1]++;
    }
    return $this;
  }

  function add_text_filter( $keys ) {
    foreach( $this->entries as &$e ) {
      $e['text'] = strtolower( implode( ' ', array_unique( explode(' ',
        preg_replace( ['/<.*?>/','/[^-\p{L}]+/u'],['',' '], html_entity_decode(
          implode( ' ', array_map(
            function( $_ ) use ( $e ){ return $e[$_]; }, $keys
          ) )
        ) )
      ))));
    }
    return $this;
  }

  function finish_up( $inc_num) {
    // Generate markup...
    foreach( $this->filters as $k => $array ) {
      $this->filters[$k] = array_map( function($a,$b) use($inc_num) {
        return [ 'val'=>$a,
                 'text' => $inc_num
                        ? sprintf('%s (%d)', $b[0], $b[1]) : $b[0]
               ];
      }, array_keys($array), array_values($array) );
    }
    foreach( $this->entries as &$e ) {
      $e['filter_markup'] = implode( ' ',
        array_map( function( $_ ) use ( $e ) {
          return 'data-'.$_.'="'.(
            htmlentities( is_array( $e[$_] ) ? json_encode( $e[$_] ) : $e[$_] )
          ).'"';
        }, array_merge( ['text'],array_keys( $this->filters )) )
      );
    }
    // Set rid
    $this->filters['rid'] = '_'.$this->btc->sequence_id();
    return $this;
  }

  function out() {
    return [ 'filter' => $this->filters, 'entries' => $this->entries ];
  }
}
  

/**
# Filterer

## Methods:

  __construct( BaseThemeClass object, $entries[] )

  * add_filter( $name, $pars )

    * adds a filter called `$name`
    * If $pars contains an entry "key" then this is used to compute the value for "$name" in the entry
    * If $pars contains an entry "mapper" then this is used as a map function to map "key" to value
    * If $pars contains an entry "value" then this is used to compute the value based on the entry
    * If it contains neither "mapper" or "value" then the key value is used as the value

  * add_letter_filter( $name )
    
    adds a letter index using the first letter of the `$name` fields

  * add_text_filter( $keys[] )

    adds a text fied which is the union of all the text in fields with given keys
  
  * `finish_up( $flag )`
  
    * tidies up the filters - converts the structure into the key and value pairs required for rendering
    * if `$flag` is true then we include counts in the select boxes
    * adds "filter markup" to each element of entries which contains the values for each of the index fields.
    * adds a "unique" ID for use in elements

  * out()

    returns the filters and entries in a standard way [ 'filter' => $filters, 'entries' => $entries ]

## Example 1

```php
    'pre' => function($entries,$self) {
      return $this->get_filterer( $entries )
        ->add_text_filter( [ 'excerpt', 'content', 'post_title', 'secondary_title', 'byline_text' ] )
        ->add_filter( 'year', [
                        'key' => function($e) { return substr( $e['byline_date'],0,4); }
                      ] )
        ->add_filter( 'programme', [
                        'mapper' => $self->get_title_map( 'programme' ),
                        'key'    => function($e) { return array_values( $e['related_programmes']
                                                 ? unserialize( $e['related_programmes'] )
                                                 : [] ); }
                      ] )
        ->add_filter( 'type',      [
                        'key' => function($e) { return array_merge(
                          $e['options_news_type']   ? unserialize( $e['options_news_type']   ) : [],
                          $e['options_card_topics'] ? unserialize( $e['options_card_topics'] ) : [] );
                        }
                      ] )
        ->finish_up(1)
        ->out();
    },
```

## Example 2

```php
    'pre' => function($entries,$self) {
      // Get programme mapping...
      return $this
        ->get_filterer( $entries )
        ->add_letter_filter( 'details_surname' )
        ->add_text_filter( ['details_surname','details_forename','details_middle','job_title','excerpt' ] )
        ->add_filter( 'alumni', [
          'key' => function($e) { return $e['alumni'] == 'archived' ? 'alumni' : 'current'; },
          'mapper' => [ 'alumni' => 'Alumni', 'current' => 'Current staff' ],
        ] )
        ->add_filter( 'type', [
          'key' => function($e) {
            $aow = isset( $e['category_area_of_work'] )  ? unserialize( $e['category_area_of_work']  ) : [];
            $flv = isset( $e['category_faculty_level'] ) ? unserialize( $e['category_faculty_level'] ) : [];
            if( !$aow ) { $aow=[]; }
            if( !$flv ) { $flv=[]; }
            return array_merge( $aow, $flv );
          }
        ] )
        ->add_filter( 'programme', [
          'mapper' => $this->get_title_map( 'programme' ),
          'key' => function($e) { return $e['programmes']; }
        ] )
        ->finish_up(1)
        ->out();
    },
```
**/
  
