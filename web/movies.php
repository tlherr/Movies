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

    if(isset($_POST['genre_id']) && !empty($_POST['genre_id'])) {
        $genre_id = $_POST['genre_id'];
        $_SESSION['genre_id'] = $genre_id;
        $params['genre_id'] = $genre_id;
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
        'genre_ids' => get_movie_genres(),
        'movies' => $movies
    ));
} else {

    //Check for get params
    if(isset($_GET['page']) && !empty($_GET['page'])) {
        $page = $_GET['page'];

        $params = array();

        if(isset($_SESSION['genre_id'])) {
            $params['genre_id'] = $_SESSION['genre_id'];
        }

        if(isset($_SESSION['title'])) {
            $params['title'] = $_SESSION['title'];
        }

        if(isset($_SESSION['releasedFrom'])) {
            $params['releasedFrom'] = $_SESSION['releasedFrom'];
        }

        if(isset($_SESSION['releasedTo'])) {
            $params['releasedTo'] = $_SESSION['releasedTo'];
        }

        if(isset($_SESSION['pageResults'])) {
            $pageResults = $_SESSION['pageResults'];
        } else {
            $pageResults = 30;
        }

        $movies = get_movies($params, intval($pageResults), $page);
        echo $twig->render('movies.html.twig', array(
            'is_logged_in' => is_logged_in(),
            'user' => get_user_from_session(),
            'genre_ids' => get_movie_genres(),
            'movies' => $movies
        ));
    } else {
        $movies = get_movies(null, 30, 0);
        echo $twig->render('movies.html.twig', array(
            'is_logged_in' => is_logged_in(),
            'user' => get_user_from_session(),
            'genre_ids' => get_movie_genres(),
            'movies' => $movies
        ));
    }
}

//Check the session state for form values, if we have them and a GET that just means new pagination

