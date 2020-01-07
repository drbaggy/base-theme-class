(function($){
  'use strict';

  function tab_wrap_html( tabs ) {
    var prim  = $(tabs).find('.col-tabs > label');
    var sec   = $(tabs).find('.js-sec > label');
    var more  = $(tabs).find('.js-more');
    prim.hide(); more.show();
    var space = $(tabs).find('.col-tabs').width() - more.width();
    prim.show(); more.hide();
    var q = prim.length - 1;
    prim.each(function(i){
      var p = $(this);
      if(p.width() <= space ) {
        space -= p.width();
      } else {
        if( i == q && space ) {
          space = 1;
        } else {
          space = 0;
        }
      }
      console.log( i, p, p.width(),more.width(), q, space );
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
      $(x).html(h);
      var n = $(x).find('.references input[checked="checked"]').closest("label").prevAll("label").length;
      $(x).find('ul').eq(n).show().siblings("ul").hide();
      var cont = $(x).find('.col-tabs').eq(0);
      var primhtml = cont.html().replace(/checked="checked"/,'');
      cont.append('<span class="js-more"><span>More</span><div class="js-sec">'+primhtml+'</div></span>').addClass('js-fied');
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
        $(this).closest('.js-more').removeClass('js-expand').children('span').html( txt );
      } else {
        $(this).closest('div').find('.js-more').children('span').html('More');
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
