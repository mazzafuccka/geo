jQuery(function($){
  //delete element from table
  $('.wp-list-table').find('a.remove').click(function(e) {
    e.preventDefault();

    if (confirm('Your have delete?')) {
      var id = $(this).attr('data-id');
      var data = {
        id: id,
        action: 'delete_action',
        token: ajax_object.nonce,
        user_id: ajax_object.user_id,
        type_object: 'poligon'
      };
      $.post(ajax_object.ajax_url, data, function(response) {
        if (response.state == 'success' && !response.error.length > 0) {
          alert('Row deleted!');
          location.reload()
        }
      }).error(function() {
        alert('Error delete data on server/ try again leter/');
      });
    }
  });

});
