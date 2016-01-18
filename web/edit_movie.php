<?php
include('../common.inc.php');

IF(!ISSET($_SESSION)){
    session_start();
}

//If a user is not logged in, kick them out of this page
if(!is_logged_in()) {
    header('Location: index.php', 501);
}

//Check the request type

if (isset($_POST['submit'])) {
    //Form is being submitted

    $errors = array();
    $messages = array();



} else {

    //Pull the values from the database

    if(isset($_GET['movie_id'])) {
        echo $twig->render('edit_movie.html.twig', array(
            'is_logged_in' => is_logged_in(),
            'user' => get_user_from_session(),
            'genre_ids' => get_movie_genres(),
            'movie' => get_movie_by_id($_GET['movie_id'])
        ));
    } else {
        header("Location: index.php?error=invalid_movie_id");
    }
}