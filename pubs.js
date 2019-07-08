function show_pubs(x) {
  console.log( x );
  console.log( $(x) );
  $(x).load('/component/references?pars='+encodeURIComponent($(x).data('ids')) );
}
/*
$('.ajax_publications').each(function() {
  if( $(body).has_class('ajax_pub_processed') ) {
    return;
  }
  console.log( '/component/references?pars='+encodeURIComponent($(this).data('ids')) );
  show_pubs(this);
  $(body).add_class('ajax_pub_processed');  
});

*/