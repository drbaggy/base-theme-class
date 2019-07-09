function show_pubs(x) {
  $(x).load('/component/references?pars='+encodeURIComponent($(x).data('ids')) );
}

$(function(){
  if( $('body').hasClass('ajax_pub_processed') ) { return; }
  $('.ajax_publications').each(function() { show_pubs(this); });
  $('body').addClass('ajax_pub_processed');
});
