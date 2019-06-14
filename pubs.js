$('.ajax_publications').each(function() {
  console.log( '/component/references?pars='+encodeURIComponent($(this).data('ids')) );
  $(this).load('/component/references?pars='+encodeURIComponent($(this).data('ids')) );
});
