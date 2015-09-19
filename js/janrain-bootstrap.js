(function() {
    if (typeof window.janrain !== 'object') window.janrain = {};
    if (typeof window.janrain.settings !== 'object') window.janrain.settings = {};

    // token action must be 'event' so that the token can be handled via AJAX
    // rather than a redirect to a token URL.
    janrain.settings.tokenAction = 'event';

    // These settings are visual aspects to make the Social Login widget
    // fit better in the default Bootstrap theme.
    janrain.settings.width = '370';
    janrain.settings.borderColor = '#FFFFFF';
    janrain.settings.actionText = ' ';
    janrain.settings.showAttribution = false;

    function isReady() { janrain.ready = true; };
    if (document.addEventListener) {
      document.addEventListener("DOMContentLoaded", isReady, false);
    } else {
      window.attachEvent('onload', isReady);
    }

    var e = document.createElement('script');
    e.type = 'text/javascript';
    e.id = 'janrainAuthWidget';

    if (document.location.protocol === 'https:') {
      e.src = 'https://rpxnow.com/js/lib/maple/engage.js';
    } else {
      e.src = 'http://widget-cdn.rpxnow.com/js/lib/maple/engage.js';
    }

    var s = document.getElementsByTagName('script')[0];
    s.parentNode.insertBefore(e, s);
})();

var JanrainBootstrap = (function($, janrain) {

    var signInUrl = 'sign_in.php';
    var signOutUrl = 'sign_out.php';
    var sessionDataUrl = 'session_data.php';

    var addBootstrapEventHandlers = function() {
        // Hide all errors in the modal dialogs when the modal is hidden
        $('.janrain-modal').on('hidden.bs.modal', function () {
            $('.janrain-error').hide();
        });

        // Bind any element with .janrain-sign-out to the signOut() method
        $('.janrain-sign-out').on('click', function(e) {
            signOut();
        });

        // Bind traditional sign in forms to signIn() method
        $('.janrain-sign-in-form').submit(function(e) {
            // TODO: this becomes signIn()
            console.log($(this).serialize());
            $.post(signInUrl, $(this).serialize(), function(response) {
                console.log(response);
                if (response.status == "success") {
                    refreshSessionState(response.data);
                    $('.janrain-modal').modal('hide');
                } else if (response.status == "error") {
                    var alert = $('#janrainSignInScreen .janrain-form-error:first');
                    alert.text("Authentication error: " + response.message);
                    alert.show();
                }
            });

            e.preventDefault();
        });
    };

    /*
    Add Janrain Event Handlers

    Connect event handlers to the Janrain Social Login widget for processing
    social authentication related events.
    */
    var addJanrainEventHandlers = function() {
        // An authentication event which, if successfull, includes the one-time
        // engage token that needs to be passed to the token URL on the server.
        janrain.events.onProviderLoginToken.addHandler(function(janrainResponse) {
            console.log("onProviderLoginToken", janrainResponse);
            $.post(signInUrl, {
                'token': janrainResponse.token
            }, function(response) {
                console.log(response);
                if (response.status == "success") {
                    refreshSessionState(response.data);
                    $('.janrain-modal').modal('hide');
                } else if (response.status == "error") {
                    if (response.data) {
                        for (var i=0; response.data.length; i++) {
                            console.log
                        }
                    } else {
                        var message = "Authentication error: " + response.message;
                        $('#janrainEngageError').text(message);
                        $('#janrainEngageError').show();
                    }
                }
                janrain.engage.signin.cancelLogin();
            });
        });
    };

    /*
    Initialize
    */
    var initialize = function() {
        // The 'hide' class is used on the initial page load but is replaced
        // with jQuery show()/hide() functionality here.
        $('.janrain-show-if-session,.janrain-error').hide().removeClass('hide');

        addBootstrapEventHandlers();
        addJanrainEventHandlers();
        refreshSession();
    };

    var refreshSession = function() {
        $.get(sessionDataUrl, function(response) {
            console.log('refreshSession', response)
            refreshSessionState(response.data);
        });
    };

    /*
    Refresh Session State

    Update the Bootstrap UI elements which indicate the state of the user's
    signed in/signed out state.
    */
    var refreshSessionState = function(sessionData) {
        if (sessionData && sessionData.uuid) {
            $('.janrain-display-name').text(sessionData.displayName);
            $('.janrain-hide-if-session').hide();
            $('.janrain-show-if-session').show();
        } else {
            $('.janrain-display-name').text("User");
            $('.janrain-hide-if-session').show();
            $('.janrain-show-if-session').hide();
        }
    };

    var signOut = function() {
        $.get(signOutUrl, function(response) {
            console.log(response);
            refreshSessionState(null);
        });
    };

    return {
        // public properties
        signInUrl: signInUrl,
        signOutUrl: signOutUrl,
        sessionDataUrl: sessionDataUrl,

        // public methods
        initialize: initialize,
        refreshSession: refreshSession,
        signOut: signOut
    };

})(jQuery, janrain);

function janrainWidgetOnload() {
    console.log("janrainWidgetOnload", janrain.settings);
    JanrainBootstrap.initialize();
}