<?php
include('../common.inc.php');

//Check to see if we have a logged in user to be logged out

if(is_logged_in()) {
    session_destroy();
}

header('Location: index.php');


