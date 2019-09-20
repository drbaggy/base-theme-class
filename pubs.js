(function($){
  'use strict';

  function show_pubs(x) {
    URL = '/component/references?pars='+encodeURIComponent($(x).data('ids'));
    console.log("FETCH!");
    $.get( URL, function( h ) {
      $(x).html(h);
    });
  }

  $(function(){
    $('body').on("change",".references input",function(){
      console.log("CHANGE");
      var n = $(this).closest("label").prevAll("label").length;
      $(this).closest("div").find("ul").eq(n).show().siblings("ul").hide();
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
