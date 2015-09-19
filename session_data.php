<?php
/**
 *  Endpoint to fetch session data that can be exposed client-side.
 *  JSON response.
 */

require_once("janrain.php");

header('Content-Type: application/json');
ini_set('display_errors', False);

session_start();

echo json_encode(array(
    'status' => 'success',
    'data' => janrain_get_client_side_session_data()
));

?>