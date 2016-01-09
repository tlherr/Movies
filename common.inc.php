<?php

ini_set('session.save_path',__DIR__.'/sessions');

require_once '../lib/Twig/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem('../templates');
$twig = new Twig_Environment($loader, array(
    'cache' => false,
    'debug' => true,
));
$twig->addExtension(new Twig_Extension_Debug());

require('db.inc.php');


/**
 * Check the current session variable against a user
 */
function is_logged_in() {
    return (isset($_SESSION['user']));
}

function is_email_unique($email) {
    $pdo = get_PDO();

    if($pdo) {
        $query = $pdo->prepare('SELECT * from users where email = :email');
        $query->bindParam(':email', $email);
        $query->execute();

        $result = $query->rowCount();
        return $result;
    }
}

function get_user_from_session() {
    $pdo = get_PDO();

    $query = $pdo->prepare('select * from users where email = :email');
    $query->bindParam(':email', $_SESSION['user']);
    $query->execute();

    return $query->fetchObject();
}

function get_user($email, $password) {
    $pdo = get_PDO();

    $query = $pdo->prepare('SELECT * FROM users WHERE email=:email');
    $query->bindParam(':email', $email, PDO::PARAM_STR);
    $query->execute();

    $user = $query->fetchObject();

    if(password_verify($password, $user->password)) {
        return $user;
    } else {
        return null;
    }
}

function create_user($email, $password) {
    $pdo = get_PDO();

    $query = $pdo->prepare('INSERT INTO users SET email = :email, password = :password, date_joined = NOW(), last_login = NOW()');
    $query->bindParam(':email', $email);
    $query->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
    $result = $query->execute();
    return $result;
}

function get_movie_genres() {
    $pdo = get_PDO();

    $query = $pdo->query('select * from genres');
    return $query->fetchAll();
}

function get_movies($params, $resultsPerPage, $page) {

    $pdo = get_PDO();


    $countQuery = $pdo->query("SELECT * from `movie_data` ORDER BY `title`");
    $query = $pdo->prepare("SELECT * FROM `movie_data` ORDER BY `title` LIMIT :limit OFFSET :offset");

    $offset = $page*$resultsPerPage;

    $query->bindParam(':limit', $resultsPerPage, PDO::PARAM_INT);
    $query->bindParam(':offset', $offset , PDO::PARAM_INT);
    $query->execute();

    return array(
        'results' => $query->fetchAll(),
        'current_page' => $page+1,
        'pages' => ceil($countQuery->rowCount()/$resultsPerPage),
        'params' => $params
    );
}