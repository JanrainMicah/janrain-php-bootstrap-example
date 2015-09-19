<?php
/**
 *  Endpoint to sign in an end-user. JSON response.
 */

require_once("config.php");
require_once("janrain.php");

header('Content-Type: application/json');
ini_set('display_errors', False);
session_start();

if (!empty($_POST['token'])) {
    // Use the auth token from Janrain's Social Login widget to complete a the
    // social sign in.
    trigger_error("Authenticating user with Social Login token: {$_POST['token']}");
    $response = janrain_social_auth($_POST['token']);
    echo json_encode(handle_auth_response($response));

} elseif (!empty($_POST['email']) && !empty($_POST['password'])) {
    // Use a traditional set of user credentials to sign in. In this case the
    // credentials are an email and password pair.
    trigger_error("Authenticating user with traditional credentials");
    $response = janrain_traditional_auth($_POST['email'], $_POST['password']);
    echo json_encode(handle_auth_response($response));

} else {
    echo json_encode(array(
        'status' => 'error',
        'message' => "Please enter your credentials."
    ));
}

function handle_auth_response($response) {
    if ($response['stat'] == "ok") {
        trigger_error("Authenticated user. UUID: {$response['capture_user']['uuid']}");
        trigger_error("Exchanging authorization code: {$response['authorization_code']}");
        $tokens = janrain_exchange_authorization_code($response['authorization_code']);
        janrain_set_session_data($tokens, $response['capture_user']);

        return array(
            'status' => 'success',
            'data' => janrain_get_client_side_session_data()
        );
    } else {
        if ($response['error'] == 'invalid_credentials') {
            return array(
                'status' => 'error',
                'message' => $response['invalid_fields']['signInForm'][0],
                'code' => $response['code'],
                'data' => $response
            );
        } else {
            return array(
                'status' => 'error',
                'message' => $response['error_description'],
                'code' => $response['code'],
                'data' => $response
            );
        }
    }
}
?>