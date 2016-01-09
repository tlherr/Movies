<?php
include('../common.inc.php');

IF(!ISSET($_SESSION)){
    session_start();
}

//If a user is not logged in, kick them out of this page
if(!is_logged_in()) {
    header('Location: index.php', 501);
}



//If we get a post request we can assume this is the first submission of a new query

if (isset($_POST['submit'])) {

    $params = array();

    if(isset($_POST['genre']) && !empty($_POST['genre'])) {
        $genre = $_POST['genre'];
        $_SESSION['genre'] = $genre;
        $params['genre'] = $genre;
    }

    if(isset($_POST['title']) && !empty($_POST['title'])) {
        $title = $_POST['title'];
        $_SESSION['title'] = $title;
        $params['title'] = $title;
    }

    if(isset($_POST['releasedFrom']) && !empty($_POST['releasedFrom'])) {
        $releasedFrom = $_POST['releasedFrom'];
        $_SESSION['releasedFrom'] = $releasedFrom;
        $params['releasedFrom'] = $releasedFrom;
    }

    if(isset($_POST['releasedTo']) && !empty($_POST['releasedTo'])) {
        $releasedTo = $_POST['releasedTo'];
        $_SESSION['releasedTo'] = $releasedTo;
        $params['releasedTo'] = $releasedTo;
    }

    if(isset($_POST['pageResults']) && !empty($_POST['pageResults'])) {
        $pageResults = $_POST['pageResults'];
        $_SESSION['pageResults'] = $pageResults;
    } else {
        $pageResults = 30;
    }
    //Prob with pageResults variable, it is just an empty string

    $movies = get_movies($params, intval($pageResults), 0);
    echo $twig->render('movies.html.twig', array(
        'is_logged_in' => is_logged_in(),
        'user' => get_user_from_session(),
        'genres' => get_movie_genres(),
        'movies' => $movies
    ));
} else {

    //Check for get params
    if(isset($_GET['page']) && !empty($_GET['page'])) {
        $page = $_GET['page'];

        echo $twig->render('movies.html.twig', array(
            'is_logged_in' => is_logged_in(),
            'user' => get_user_from_session(),
            'genres' => get_movie_genres()
        ));
    } else {
        echo $twig->render('movies.html.twig', array(
            'is_logged_in' => is_logged_in(),
            'user' => get_user_from_session(),
            'genres' => get_movie_genres()
        ));
    }
}

//Check the session state for form values, if we have them and a GET that just means new pagination

