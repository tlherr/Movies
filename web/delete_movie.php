<?php
include('../common.inc.php');

ini_set("file_uploads","On");

IF(!ISSET($_SESSION)){
    session_start();
}

//If a user is not logged in, kick them out of this page
if(!is_logged_in()) {
    header('Location: index.php', 501);
}

//Check the request type

if (isset($_POST['submit'])) {

    //Grab the values from the request, validate and save to DB then redirect to movies page

    if(isset($_POST['movie_id'])) {
        delete_movie($_POST['movie_id']);
        header("Location: movies.php");
    }

} else {

    if(isset($_GET['movie_id'])) {
        echo $twig->render('delete_movie.html.twig', array(
            'is_logged_in' => is_logged_in(),
            'user' => get_user_from_session(),
            'movie' => get_movie_by_id($_GET['movie_id'])
        ));
    } else {
        header("Location: index.php?error=invalid_movie_id");
    }

}