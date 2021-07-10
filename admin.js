(function($) {
  // Make the title readonly and "hide" the input box!
  if( typeof hide_title !== 'undefined' && hide_title  ) { // Hide the input element and replace by HTML...
    $('input#title').prop('readonly',true).parent().hide();
    if($('#wp-admin-bar-view').length) {
      $('#title')
        .parent()
        .parent()
        .append(
          '<h2 style="font-size: 2em; padding: 0 0 5px 0; margin: 0; line-height:1.2em">'+
          $('#title').val()+'</h2>'+
          '<div>'+$('#wp-admin-bar-view a').attr('href')+'</div>'
        );
    }
  }

  // Add quick and dirty search box on main dashboard
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

