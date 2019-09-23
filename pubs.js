(function($){
  'use strict';

  function show_pubs(x) {
    URL = '/component/references?pars='+encodeURIComponent($(x).data('ids'));
    $.get( URL, function( h ) {
      $(x).html(h);
      if( h.match(/<label class="earlier">/) ) {
        $(x).find('label').last().after('<label class="earlier visible"><span>Earlier</span></label>');
      }
      var n = $(x).find('.references input[checked="checked"]').closest("label").prevAll("label").length;
      $(x).find('ul').eq(n).show().siblings("ul").hide();
    });
  }

  $(function(){
    $('body').on("change",".references input",function(){
      var n = $(this).closest("label").prevAll("label").length;
      $(this).closest("div").find("ul").eq(n).show().siblings("ul").hide();
    });
    $('body').on('click','.earlier',function(){
      console.log( "CLICKED ON EARLIER TAB - BRING UP DROPDOWN!" );
      $(this).find('span').append('<p style="position: absolute; margin-top: -3em">POPUP!<br />POPUP!<br />POPUP!<br />POPUP!<br />POPUP!<br />POPUP!<br /></p>');
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
