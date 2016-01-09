<?php
include('../common.inc.php');

//Check if the user is logged in or not
IF(!ISSET($_SESSION)){
    session_start();
}

echo $twig->render('index.html.twig', array('logged_in' => is_logged_in()));
