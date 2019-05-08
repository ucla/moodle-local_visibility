define(['jquery', 'core/notification', 'core/str'], function($, notification, str) {
    // Private variables and functions.

    /** @var {Number} element The current clicked element */
    var element = 0;

    /**
     * Displays a confirmation dialog box for deleting visibility session.
     *
     * @param {String} confirmationText The string that should be used for the confirmation dialogue.
     * @param {Function} action         The callback that performs the ajax action upon confirmation.
     * @method confirmationDialog
     */
    var confirmationDialog = function(confirmationText, action) {
        // Create confirmation string dialog
        str.get_strings([
            { key: 'confirm', component: 'moodle' },
            { key: confirmationText, component: 'local_visibility' },
            { key: 'yes', component: 'core' },
            { key: 'no', component: 'core' }
        ]).done(function(strs) {
            notification.confirm(
                strs[0], // Confirm
                strs[1], // Are you absolutely sure?
                strs[2], // Yes
                strs[3], // No
                action // On Confirm
            );
        }.bind(this)).fail(notification.exception);
    };

    /**
     * Deletes selected visibility session via ajax
     *
     * @method deleteSession
     */
    var deleteSession = function(){
        var id = element.data('id');
        var courseId = element.data('course');

        $.ajax({
            url: "ajax.php",
            data: {
                action: 'delete',
                courseid: courseId,
                rangeid: id
            }
        }).done(function( msg ) {
            if (msg.success) {
                $(".range" + id).fadeOut(250);
                $(".notifications").remove();   // Remove any notices if any.
                notification.alert(
                    null,
                    msg.successmsg
                );
                if (msg.count == 0) {
                    location.reload(); // Reload the page so Visibility dropdown is unlocked.
                }
            } else {
                // Deletion failed. Display an error message.
                var moodleStringPromise = str.get_string('deleteerror', 'local_visibility');
                $.when(moodleStringPromise).done(function(errorMsg) {
                    window.alert(errorMsg);
                });
            }
        });
    };

    /**
     * Deletes all visibility sessions via ajax
     *
     * @method deleteAllSessions
     */
    var deleteAllSessions = function(){
        var courseId = element.data('course');

        $.ajax({
            url: "ajax.php",
            data: {
                action: 'deleteall',
                courseid: courseId
            }
        }).done(function( msg ) {
            if (msg.success) {
                $(".visibility-session").fadeOut(250);
                $(".notifications").remove();   // Remove any notices if any.
                notification.alert(
                    null,
                    msg.successmsg
                );
                location.reload(); // Reload the page so Visibility dropdown is unlocked.
            } else {
                // Deletion failed. Display an error message.
                var moodleStringPromise = str.get_string('deleteallerror', 'local_visibility');
                $.when(moodleStringPromise).done(function(errorMsg) {
                    window.alert(errorMsg);
                });
            }
        });
    };

    return {
        // Public variables and functions

        /**
         * Initialise the module.
         * @method init
         */
        init: function() {
            $('.rangedeletebutton').click(function(ev) {
                element = $(this);
                ev.preventDefault();
                confirmationDialog('confirmremovevisibilitysession', deleteSession);
            });
            $('#id_rangedeleteallbutton').click(function(ev) {
                element = $(this);
                ev.preventDefault();
                confirmationDialog('confirmdeleteallsessions', deleteAllSessions);
            });
        }
    };
});