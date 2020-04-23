/* eslint-env jquery */
/*
 +----------------------------------------------------------------------
 | Copyright (c) 2019,2020 Genome Research Ltd.
 | This file is part of the wordpress support scripts in Base Theme
 | Class.
 +----------------------------------------------------------------------
 | This framework is free software: you can redistribute
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

 Implements in-page navigation and tabbing for publications inside
 wordpress

 * Author         : js5
 * Maintainer     : js5
 * Created        : 2019-10-20
*/
(function($){
  'use strict';

  function tab_wrap_html( tabs ) {
    var prim  = $(tabs).find('.col-tabs > label');
    var sec   = $(tabs).find('.js-sec > label');
    var more  = $(tabs).find('.js-more');
    prim.hide(); more.show();
    var space = $(tabs).find('.col-tabs').outerWidth() - more.outerWidth();
    prim.show(); more.hide();
    var q = prim.length - 1;
    prim.each(function(i){
      var p = $(this);
      if(p.outerWidth() <= space ) {
        space -= p.outerWidth();
      } else {
        if( i == q && space ) {
          space = 1;
        } else {
          space = 0;
        }
      }
      if( space == 0 ) {
        p.hide();
        sec.eq(i).show();
      } else {
        sec.eq(i).hide();
      }
    });
    if( space == 0 ) {
      more.show();
    }

  }
  function show_pubs(x) {
    URL = '/component/references?pars='+encodeURIComponent($(x).data('ids'));
    $.get( URL, function( h ) {
      $(x).html( h.replace(/([A-Z]+: )(<a href=".*?")>/g,'$2 target="_blank" title="* this link opens in a new window">$1').replace(/a>; <a/g,'a> <a').replace(/\s+<\/a>/g,'</a>') );
      var n = $(x).find('.references input[checked="checked"]').closest("label").prevAll("label").length;
      $(x).find('ul').eq(n).show().siblings("ul").hide();
      if( ! $(x).find('.col-tabs').length ) {
        return;
      }
      var cont = $(x).find('.col-tabs').eq(0);
      var primhtml = cont.html().replace(/checked="checked"/,'');
      cont.append('<span class="js-more"><span>Archive</span><div class="js-sec">'+primhtml+'</div></span>').addClass('js-fied');
      $(x).find('.js-more').on('click', function() {
        $(this).toggleClass('js-expand');
      });
      tab_wrap_html( x );
      // Hide primary tabs that don't fit...
      // Hide secondary links which do fit!
      $(window).on('resize',function() {
        tab_wrap_html( x );
      });
    });
  }

  $(function(){
    $('body').on("change",".references input",function(){
      var n = $(this).closest("label").prevAll("label").length, txt = $(this).parent().children('span').html();
      $(this).closest("div.references").find("ul").eq(n).show().siblings("ul").hide();
      if( $(this).closest('div').hasClass('js-sec') ) {
        $(this).closest('.js-more').addClass('pub-active').removeClass('js-expand').children('span').html( txt );
      } else {
        $(this).closest('div').find('.js-more').removeClass('pub-active').removeClass('js-expand').children('span').html('Archive');
      }
    });
    $('body').on("click",".references h3",function(){
      var n = $(this).prevAll("h3").length;
      $(this).closest("div.references").find("ul").eq(n).show().siblings("ul").hide();
    });
    if( $('body').hasClass('ajax_pub_processed') ) {
      return;
    }
    $('.ajax_publications').each(function() {
      show_pubs(this);

    });
    $('body').addClass('ajax_pub_processed');
  });
  window.show_pubs = show_pubs;
}(jQuery));
