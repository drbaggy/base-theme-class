/* eslint-env jquery */
/*
 +----------------------------------------------------------------------
 | Copyright (c) 2016,2017,2018,2019,2020 Genome Research Ltd.
 | This file is part of the Pagesmith web framework
 +----------------------------------------------------------------------
 | The Pagesmith web framework is free software: you can redistribute
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

 * Functions to filter/paginte a list of entries...

 * This has been written in a generic way - in that the user has complete
   freedom to render the news-items, and controls in any way they wish.
   The only requirements are on the "functional" classes and data attributes
   and viewing.

 * Note the filters and items have to appear in the same ".tags" -
   but that is the only requirement

 * Author         : js5
 * Maintainer     : js5
 * Created        : 2016-10-20

<div class="list-container" data-key="?" data-batch-size="?">
  <!-- Filters at the top of the page! -->
  <div class="page-margin">
    <select class="list-filter minimal" data-filter-type="lookup" data-filter="country">
    </selcet>
    <select class="list-filter minimal" data-filter-type="array" data-filter="type">
    </selcet>
    <input type="hidden" data-filter-type="lookup" data-filter="letter" data-filter-bind="list-id" />
    <ul id="list-id">...</ul>
  </div>

  <!-- List items... -->
  <div class="list-item" data-letter="#" data-type="[&quot;11&quot;,&quot;9&quot;]" data-country="GB">....

  </div>

  <div><p class="list-none">THere are no matches for your search criteria</p></div>

  <div class="box-pagination">
    <input type="hidden" value="0" class="list-filter" data-filter="page" data-filter-type="page"/>
    <div class="pagination">&nbsp;</div>
    </div>
  </div>
</div>

 */

(function ($) {
  'use strict';
$(function(){
  var first_load = true;
  // Add handlers to links to arbitrarily set filters elsewhere on the page.
  //$('.change-filter').on('click', function () {
  $('.reset-filters').on('click',function(){
    var $list = $(this).closest('.list-container'), new_filters = {};
    $list.find('.list-filter').each(function(){
      $(this).val('');
      new_filters[ $(this).data('filter') ] = '';
    });
    $list.find('.multi-filter').each( function(){
      $(this).prop('checked',false);
      new_filters[ $(this).data('filter') ] = {};
    });
    $list.data('filters',new_filters);
    list_update_results($list, 'reset' );
  });
  $('body').on('click', '.change-filter', function () {
    $.each( $(this).data('filter'), function (k, v) {
      $('#' + k).val(v).trigger('change');
    });
  });

  // Add handlers to the filters - they just have to be in the same container as the list
  // - and only one such list in the container...
  $('.multi-block').on('click','h6',function() {
    $(this).closest('div').toggleClass('collapsed');
  });
  $('.list-filter, .multi-filter').on('change keyup', function () {
    var $self       = $(this),
        new_filters = {},
        $list       = $self.closest('.list-container'),
        $ptr_reset  = false,
        multi       = {},
        filters     = $list.data('filters');
    $list.find('.list-filter').each(function () {
      if ($(this).data('filter-type') !== 'page' && filters[ $(this).data('filter') ] !== $(this).val()) {
        $ptr_reset = true;
      }
      new_filters[ $(this).data('filter') ] = $(this).val();
    });
    if( $self.hasClass('multi-filter') ) {
      if( $self.is(':checked') ) {
        $self.closest('li').addClass('selected');
      } else {
        $self.closest('li').removeClass('selected');
      }
    }
    // Loop through all multi filters and get the checked values....
    $list.find('.multi-filter').each(function() {
      var t = $(this).data('filter');
      if( ! multi.hasOwnProperty( t ) ) {
        multi[t] = {};
      }
      if( $(this).is(':checked') ) {
        multi[t][ $(this).val() ] = 1;
      }
    });
    $.each( multi, function( k, v ) {
      if( Object.keys( v ).sort().join('::') !== Object.keys( filters[ k ] ).sort().join('::') ) {
        $ptr_reset = true;
      }
      new_filters[ k ] = v;
    });
    // Now loop through the arry - check they are different - if so set ptr_reset and then
    // copy then to new_filters...

    if( $ptr_reset ) {
      new_filters.page = 0;
    }
    $list.data('filters', new_filters);
    list_update_results( $list, 'reset' );
  });

  // Add functions to letters in A-Z panel to change the letter filter...
  $('.selectLetter li').on('click', function () {
    $('ul.selectLetter li').removeClass('active');
    $(this).closest('.list-alpha').find('input').val(
      $(this).addClass('active').text().match( /^.$/ ) ? $(this).addClass('active').text() : ''
    ).trigger('change');
  });

  // Actions on pagination clicks...
  $('.pagination').on('click', 'span', function () {
    var t = $(this).text();
    if (t === '...') {
      return;
    }
    $(this).closest('.list-container').find('input[data-filter="page"]').val(
        t === '«'                     ? 0
      : t === '»'                     ? 10000000
      : $(this).text().match(/^\d+$/) ? (parseInt($(this).text(),10) - 1)
      :                                 0
    ).trigger('change');
  });

  // 1) This just makes the whole news item click-able!
  // Now functionality on the actual list-load div...
  $('.list-container').on('click', '.list-item:not(".no-click")', function () {
    // What to do when the user clicks on a feed item.
    // This opens a new window if there is a target set!!
    var x = $(this).find('a.btn-more').prop('href'),
        t = $(this).find('a.btn-more').prop('target');
    if( !x ) {
      x = $(this).find('a').last().prop('href'),
      t = $(this).find('a').last().prop('target');
    }
    if (x) {
      if (t) {
        window.open(x, '_blank');
      } else {
        document.location.href = x;
      }
    }
    return false;
    // 2) This adds functionality to load in another "n" entries....
  }).on('click', '.list-more a', function () {
    // Displaying more data IF not in pagination mode - i.e we have a more link at the bottom....
    list_update_results($(this).closest('.list-load'), '');
    // 3) This grabs any pre-existing filter values (probably none!), and fetches the news/jobs/events list...
  }).each(function () {
    // Part 1 - find the container and get any associated filters....
    var $self = $(this), filters = {}, filter_info = {};
    // Need to modify this so it can cope with an array of filters!!
    $self.find('.list-filter').each(function () {
      filters[ $(this).data('filter') ] = $(this).val();
      filter_info[ $(this).data('filter') ] = $(this).data('filter-type');
    });
    $self.find('.multi-filter').each(function () {
      var t = $(this).data('filter');
      if( ! filter_info.hasOwnProperty( t ) ) {
        filters[ t     ] = {};
        filter_info[ t ] = 'multi';
      }
      if( $(this).is(':checked') ) {
        filters[ t ][ $(this).val() ] = 1;
      }
    });

    // Nasty hack - we store filters in the URL so that we have a unique hash....
    // which we then use to re-populate the filters array when back is pressed...
    if( $self.data('key') && document.location.hash.match(/^#\{/) ) {
      var t = JSON.parse( decodeURI( document.location.hash.substr(1) ) );
      if( t.hasOwnProperty( $self.data('key') ) ) {
        $.extend(filters,t[$self.data('key')]);
        $self.find('.multi-filter').each(function() {
// Add code to check boxes and update filters!!
          if( filters[ $(this).data('filter') ].hasOwnProperty( ''+$(this).val() ) ) {
            $(this).prop('checked',true); $(this).closest('li').addClass('selected');
          } else {
            $(this).closest('li').removeClass('selected');
          }
        });
        $self.find('.list-filter').each(function () {
          $(this).val( filters[ $(this).data('filter') ]);
          if( $(this).data('filter-bind') ) {
            // We need to update elements...
            var f = 0, v = filters[$(this).data('filter')], ns = $('#'+$(this).data('filter-bind')).find('li');
            ns.each(function() {
              if( $(this).text() === v ) {
                f = 1;
                $(this).addClass('active');
              } else {
                $(this).removeClass('active');
              }
            });
            if( !f ) { ns.eq(0).addClass('active'); }
          }
        });
      }
    }
    // Initialize entry - by setting ptr to 0, setting results to empty list and filters to those defined!
    $self.data('filters',     filters);
    $self.data('filter-info', filter_info);
    $self.data('ptr',     0);
    // Now fetch the data from the server - and once complete - remove the ajax loading message
    // and display the results...
    list_update_results( $self, 'reset' );            // Finally update what we see on the page....
  });
/* support functions for update_filters
  hash_sorted - returns a sorted version of a hash based on values { returns as an array of k,v pairs }
  trim_list - given the output of hash_sorted {e.g.} replaces all the values (except tne first)
*/
    function hash_sorted( h ) {
      var tarr = [];
      $.each(h, function(k,v) {
        tarr.push([k,v]);
      });
      tarr.sort( function(a,b) { return a[1].toLowerCase()<b[1].toLowerCase() ? -1 : a[1].toLowerCase()>b[1].toLowerCase() ? 1 : 0; } );
      return tarr;
    }
    function trim_list( list_name, current_value, values ) {
      var fl = $(list_name).find('option').first();
      $(list_name).html('').append(fl);
      $.each(values,function(i,x){
        var n = $('<option />').prop('value',x[0]).text(x[1]);
        if( x[0] === current_value ) {
          n.prop('selected','selected');
        }
        $(list_name).append( n );
      });
      return;
    }
    function update_history( ky, flt ) {
      var t = {};
      $.each(flt,function(k,v) {
        if(!v || v === '0') { delete flt[k]; }
      });
      if(JSON.stringify(flt)==='{}') {
        document.location.replace( '#' );
        return;
      }
      t[ky] = flt;
      var s = '#'+JSON.stringify(t);
      if( s === '#{"-":{}}' ) {
        if( document.location.hash.match(/^#{/) ) {
          document.location.replace( '#' );
        }
      } else {
        document.location.replace( s );
      }
    }

/* list_update_results

   Now the meat of the module ... updating the results page when the data is received/filters changed

*/
  function isEmpty(obj) {
    for(var key in obj) {
      if(obj.hasOwnProperty(key)) {
        return false;
      }
    }
    return true;
  }

  function list_update_results($list, reset_flag) {
    // If reset_flag is reset we clear the display and reset the pointer.
    if (reset_flag && reset_flag === 'reset') {
      $list.data('ptr', 0);
      $list.find('.list-more').show();
    }
      // Generate a list of the results - filtered....
    var ptr     = $list.data('ptr'),
        ky      = $list.data('key')?$list.data('key'):'-',
        flt     = $list.data('filters'),
        sz      = $list.data('batch-size'),
        f_info  = $list.data('filter-info'),
// Tweak this to filter on dom elements not entries in hash using data() / text() on nodes...
        c       = 0,
        t       = 0,
        cat_counts = {};
    $list.find('.list-item').each( function () {
      t++;
      var el      = $(this),
          skip    = 0,
          skip_filter = {},
          skip_filter_n = 0;
      $.each(flt, function (k, v) {
        var t = f_info[k];
        if( ( 'object' === typeof v ) ? ! isEmpty( v ) : (v !== '') ) { // Apply filter...
          if( t === 'multi' ) {
            if( Array.isArray( el.data(k) ) ) {
              // We have an array of data - so we need to see if none of the elements in el.data(k) are in the list
              if( ! jQuery.grep( el.data(k), function(val) { return v.hasOwnProperty( ''+val ); } ).length ) {
                skip = 1;
                skip_filter[ k ] = 1;
                skip_filter_n ++;
                return true;
              }
            } else {
              // We have a single value need to see if it is in the list...
              if( ! v.hasOwnProperty( ''+el.data(k) ) ) {
                skip = 1;
                skip_filter[ k ] = 1;
                skip_filter_n ++;
                return true;
              }
            }
          } else if(
            t === 'lookup' && ( el.data(k) != v ) ||
            t === 'array'  && ( el.data(k).indexOf(v) < 0 ) ||
            t === 'text'   && (
              el.text().toLowerCase().indexOf(v.toLowerCase()) < 0 &&
              ( el.data('text') ? el.data('text').toLowerCase().indexOf(v.toLowerCase()) < 0 : 1 )
            )
          ) {
            skip = 1;
            skip_filter[ k ] = 1;
            skip_filter_n ++;
            return true;
          }
        }
        return true;
      });
      if( skip ) { // We need to see if this was
        el.data('show', 0);
        $.each( el.data(), function( k,v ) {
          var fv = flt[k];
          if( skip_filter_n === 1 && skip_filter.hasOwnProperty( k ) ) { // Only one filter
            if( ( 'object' === typeof fv ) ? ! isEmpty( fv ) : (fv !== '') ) {
              if( Array.isArray( v ) ) {
                $.each( v, function( kk,vv ) {
                  if( ! cat_counts.hasOwnProperty( ''+vv ) ) {
                    cat_counts[ ''+vv ] = 1;
                  } else {
                    cat_counts[ ''+vv ] +=1;
                  }
                });
              } else {
                if( ! cat_counts.hasOwnProperty( ''+v ) ) {
                  cat_counts[ ''+v ] = 1;
                } else {
                  cat_counts[ ''+v ] +=1;
                }
              }
            }
          }
        });
      } else {
        el.data('show', 1 );
        $.each( el.data(), function( k,v ) {
          if( k === 'id' || k === 'show' ) {
            return;
          }
          if( Array.isArray( v ) ) {
            $.each( v, function( kk,vv ) {
              if( ! cat_counts.hasOwnProperty( ''+vv ) ) {
                cat_counts[ ''+vv ] = 1;
              } else {
                cat_counts[ ''+vv ] +=1;
              }
            });
          } else {
            if( ! cat_counts.hasOwnProperty( ''+v ) ) {
              cat_counts[ ''+v ] = 1;
            } else {
              cat_counts[ ''+v ] +=1;
            }
          }
        });
        c++;
      }
      el.hide();
    });
    // Update the count of entries matched if .filter-count exists...
    $list.find('.filter-count').text(c);
    $list.find('.filter-total').text(t);
    if( c ) {
      $list.find('.filter-count-block').removeClass('count-no-entries');
    } else {
      $list.find('.filter-count-block').addClass('count-no-entries');
    }

    $list.find('.multi-block li').each( function(){
      var id = $(this).find('input').val();
      if( cat_counts.hasOwnProperty( id ) ) {
        $(this).removeClass('no-entries');
        $(this).find('span').text( cat_counts[id]+'/'+$(this).data('count') );
      } else {
        $(this).find('span').text('0/'+$(this).data('count'));
        $(this).addClass('no-entries');
      }
    });
      // Deal with the case when there are NO entries ....
      //    Show any "no entries" message and hide pagination if there is any...
    if( c === 0 ) {
      $list.find('.list-none').show();
      update_history( ky, flt );
      $list.find('.pagination').html('');
      return;
    }

      // We have entries so we hide the "no entries" message...
    $list.closest('.list-container').find('.list-none').hide();
      // We have pagination so we deal with this type of sub-nav
      //
    if( $list.closest('.list-container').find('.pagination').length ) {
      // Get current ptr, and clear the display and pagination.....
      ptr  = flt.page * sz;
      var $p = $list.closest('.list-container').find('.pagination');
      // If we have less than one page we just display items, and tidy up!
      if( c <= sz ) {
        $list.find('.list-item').filter( function() { return !!$(this).data('show'); } ).show();
        flt.page = 0;
        $list.find('.pagination').html('');
        update_history( ky, flt );
        if( !first_load ) {
          $('html').animate({ scrollTop: $list.closest('.list-container').offset().top-20 },500);
        }
        first_load = false;
        return;
      }
      // If ptr points to past end of list then we write ptr back to correct value...
      if( ptr >= c ) {
        ptr = sz * Math.ceil(c/sz - 1);
        flt.page = ptr / sz;
      }
      // Now we render that page of hits!
      $list.find('.list-item')
           .filter( function()  { return !!$(this).data('show'); } )
           .filter( function(i) { return i >= ptr && i < ptr +sz;}  )
           .show();
        // Now we will render the pagination panel...
        // $bn is the current page
        // $bs is the start of the list - we are going to display 5 pages worth of numbers
        //     which by default is centered on $bn
        // We display a "<", optional ..., the numbered links, optional ..., ">"
        var BESIDE = 2,
            COUNT  = BESIDE * 2 + 1,
            $bn    = ptr / sz,
            $bs    = $bn - BESIDE, max_page = Math.ceil(c/sz),
            _;

        if( ( $bn + BESIDE ) > max_page ) {  // Push bs back if we are going to go past the end of the list..
          $bs = max_page - COUNT;
        }
        if ($bs < 0) {                  // Push bs forward if we are starting before the list...
          $bs = 0;
        }

        $p.html($('<span>«</span>'));
        if ($bs > 0) {
          $p.append($('<span>...</span>'));
        }
        for ( _ = $bs; _ < max_page && _ < $bs + COUNT; _++) {
          if (_ >= max_page) {
            break;
          }
          $p.append('<span' + ((ptr === _ * sz) ? ' class="active"' : '') + '>' + (_ + 1) + '</span>');
        }
        if ( $bs + COUNT < max_page ) {
          $p.append($('<span>...</span>'));
        }
        $p.append($('<span>»</span>'));
        // Finally a little bit of tidying up!
        update_history( ky, flt );
        if( !first_load ) {
          $('html').animate({ scrollTop: $list.closest('.list-container').offset().top-20 },500);
        }
        first_load = false;
        return;
      } // END OF PAGINATION BLOCK!!!

      // Facebook more style navigation...
      // We have results - we haven't shown them all - so display the next batch!
      if (c > ptr) {
        $list.find('.list-item')
             .filter( function()  { return !!$(this).data('show'); } )
             .filter( function(i) { return i >= ptr && i < ptr +sz;}  )
             .show();
        ptr += sz;
        $list.data( 'ptr', ptr ); // reset pointer!
      }
      // If we have no more results - hide the "more link"
      if( ptr >= c ) {
        $list.find('.list-more').hide();
      }
      update_history( ky, flt );
      if( !first_load ) {
        $('html').animate({ scrollTop: $list.closest('.list-container').offset().top-20 },500);
      }
      first_load = false;
    }
});
}(jQuery));
