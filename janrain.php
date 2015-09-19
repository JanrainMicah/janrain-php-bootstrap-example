<?php
/**
 *  Functions for interfacing with Janrain APIs and managing the user's session.
 */
require_once("config.php");

/**
 *  Perform a Janrain social authentication.
 *
 *  @param string $auth_token returned from Janrain Social Login widget
 *
 *  @return array associative array representation of the JSON response from
 *                the Janrain API
 */
function janrain_social_auth($auth_token) {
    $params = array(
        'client_id' => JANRAIN_LOGIN_CLIENT_ID,
        'locale' => 'en-US',
        'response_type' => 'code',
        'redirect_uri' => 'https://localhost',
        'token' => $auth_token
    );

    return janrain_api('/oauth/auth_native', $params);
}


/**
 *  Perform a Janrain traditional authentication.
 *
 *  @param string $email     user's email address
 *  @param string $password  user's password
 *
 *  @return array associative array representation of the JSON response from
 *                the Janrain API
 */
function janrain_traditional_auth($email, $password) {
    $params = array(
        'client_id' => JANRAIN_LOGIN_CLIENT_ID,
        'locale' => 'en-US',
        'response_type' => 'code',
        'redirect_uri' => 'https://localhost',
        'form' => 'signInForm',
        'signInEmailAddress' => $email,
        'currentPassword' => $password
    );

    return janrain_api('/oauth/auth_native_traditional', $params);
}


/**
 *  Exchange authorization code for access token and refresh token
 *
 *  @param string $authorization_code authorization code returned from one of
 *                                    the Janrain auth endpoints
 *
 *  @return array associative array representation of the JSON response from
 *                the Janrain API
 */
function janrain_exchange_authorization_code($authorization_code) {
    $params = array(
        'client_id' => JANRAIN_LOGIN_CLIENT_ID,
        'client_secret' => JANRAIN_LOGIN_CLIENT_SECRET,
        'grant_type' => 'authorization_code',
        'code' => $authorization_code,
        'redirect_uri' => 'https://localhost'
    );

    return janrain_api('/oauth/token', $params);
}


/**
 *  Save information about the authenticated Janrain user in the PHP session.
 *
 *  @param string $tokens   an array of Janrain tokens as returned from calling
 *                          janrain_exchange_authorization_code()
 *  @param array  $profile  an array representing a Janrain user profile object
 */
function janrain_set_session_data($tokens, $profile) {
    // The Janrain UUID is the unique identifier of the user profile in Janrain
    $_SESSION['janrain_uuid'] = $profile['uuid'];

    // Saving the access token allows for additional API calls to be made to the
    // Janrain API. This access token is scoped for this user and is typically
    // used with calls to /entity and /entity.update.
    $_SESSION['janrain_access_token'] = $tokens['access_token'];

    // Saving the refresh token allows the access token to be refreshed if the
    // PHP session lasts longer than the access token which is stored in the
    // 'expires' variable (default 1 hour).
    $expires = strtotime("+{$tokens['expires_in']} seconds");
    $_SESSION['janrain_refresh_token'] = $tokens['refresh_token'];
    $_SESSION['janrain_token_expires'] = $expires;

    // Any data in the Janrain user profile that is needed by the application
    // can also be saved in the PHP session. In this case, saving the display
    // name will allow client-side code to present a personalized experience.
    $_SESSION['janrain_displayName'] = $profile['displayName'];
}


/**
 *  Get data about the Janrain user which can be exposed client-side. Secure
 *  information (such as the refresh token) should not be exposed client-side.
 *
 *  @return array associative array containing key => value pairs
 */
function janrain_get_client_side_session_data() {
    if (!empty($_SESSION['janrain_uuid'])) {
        return array(
            'uuid' => $_SESSION['janrain_uuid'],
            'displayName' => $_SESSION['janrain_displayName']
        );
    } else {
        return array(
            'uuid' => null,
            'displayName' => null
        );
    }
}


/**
 *  Make a call to the Janrain API
 *
 *  @param string $call    the relative endpoint of the API call. Eg. "/entity"
 *  @param array  $params  parameters to pass to the Janrain API
 *
 *  @return array associative array representation of the JSON response from
 *                the Janrain API
 */
function janrain_api($call, $params) {
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, JANRAIN_CAPTURE_API_URL.$call);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($params));

    $response = curl_exec($curl);
    curl_close($curl);

    return json_decode($response, true);
}
?>