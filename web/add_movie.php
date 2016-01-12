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

    //Grab the values from the request, validate and save to DB then redirect to movies page

    $errors = array();
    $flash = '';

    if(isset($_POST['genre_id'])) {
        if(isset($_POST['title'])) {
            if(isset($_POST['date'])) {
                $operation = add_movie($_POST['genre_id'], $_POST['title'], $_POST['date']);

                if($operation) {
                    header("Location: movies.php");
                } else {
                    $flash = 'Database Error';
                }
            } else {
                $errors[] = 'genre_id';
            }
        } else {
            $errors[] = 'title';
        }
    } else {
        $errors[] = 'date';
    }

    echo $twig->render('add_movie.html.twig', array(
        'is_logged_in' => is_logged_in(),
        'user' => get_user_from_session(),
        'genre_ids' => get_movie_genres(),
        'errors' => $errors,
        'flash' => $flash
    ));

} else {
    echo $twig->render('add_movie.html.twig', array(
        'is_logged_in' => is_logged_in(),
        'user' => get_user_from_session(),
        'genre_ids' => get_movie_genres()
    ));
}