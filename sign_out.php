<?php
/**
 *  Endpoint to destroys PHP session. JSON response.
 */

header('Content-Type: application/json');
ini_set('display_errors', False);

session_start();
$_SESSION = array();
session_destroy();

echo json_encode(array(
    'status' => 'success'
));

?>