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

    if(isset($_POST['genre']) && isset($_POST['title']) && isset($_POST['date']) && isset($_POST['score']) && isset($_POST['imdb_id']) && isset($_FILES['poster'])) {

        $operation = add_movie($_POST['genre'], $_POST['title'], $_POST['date'], $_POST['score'], $_POST['imdb_id'], $_FILES['poster']);
    }

}

echo $twig->render('add_movie.html.twig', array(
    'is_logged_in' => is_logged_in(),
    'user' => get_user_from_session(),
    'genre_ids' => get_movie_genres()
));