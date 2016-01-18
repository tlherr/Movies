<?php
include('../common.inc.php');

IF(!ISSET($_SESSION)){
    session_start();
}

if(!is_logged_in()) {
    header('Location: index.php');
} else {
    echo $twig->render('profile.html.twig', array(
        'is_logged_in' => is_logged_in(),
        'user' => get_user_from_session()
    ));
}


