define(['jquery', 'core/str'], function($, str) {
    return {
        init: function() {
            $('.rangedeletebutton').click(function() {
                var id = $(this).data('id');
                var courseid = $(this).data('course');
                $.ajax({
                    url: "ajax.php",
                    data: {
                        action: 'delete',
                        courseid: courseid,
                        rangeid: id
                    }
                }).done(function( msg ) {
                    if (msg.success) {
                        $(".range" + id).fadeOut(250);
                    } else {
                        // Deletion failed. Display an error message.
                        var moodleStringPromise = str.get_string('deleteerror', 'local_visibility');
                        $.when(moodleStringPromise).done(function(errorMsg) {
                            window.alert(errorMsg);
                        });
                    }
                });
            });
        }
    };
});