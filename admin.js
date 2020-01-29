(function($) {
  $('#searchbox').on('change, keyup',function() {
    if( $(this).val() && $(this).val().length > 2 ) {
      $.ajax('/wp-json/base/search/'+$(this).val(),{
        'success': function(r) {
          $('#search-results').html('');
          for( var i in r ) {
            $('#search-results').append(
              '<li><a href="/wp-admin/post.php?action=edit&post='+r[i][3]+'">[EDIT]</a> ('+r[i][2]+') <a href="'+r[i][0]+'">'+r[i][1]+'</a></li>'
            );
          }
        }
      });
    }
  } );
}(jQuery));

