<?php
include('../common.inc.php');

//Check to see if we have a logged in user to be logged out

IF(!ISSET($_SESSION)){
    session_start();
}

if(is_logged_in()) {
    unset($_SESSION['user']);
    session_unset();
    session_destroy();
}

header('cache-control: no-cache,no-store,must-revalidate'); // HTTP 1.1.
header('pragma: no-cache'); // HTTP 1.0.
header('expires: 0'); // Proxies.
header('Location: index.php');


