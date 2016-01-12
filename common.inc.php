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


function tableExists(PDO $pdo, $id)
{
    $results = $pdo->query("SHOW TABLES LIKE '$id'");

    return ($results->rowCount()>0);
}

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

    $query = $pdo->query("select * from `genres`");
    return $query->fetchAll();
}

function get_movies($params, $resultsPerPage, $page) {

    $pdo = get_PDO();

    $sql = "SELECT * FROM `movie_data`";

    if(!empty($params)) {
        $sql.=" WHERE ";
        $paramLength = sizeof($params);
        $paramCount = 0;
    }

    if(isset($params['genre_id'])) {
        $paramCount++;
        $sql.=sprintf("`%s`=%s", 'genre_id', intval($params['genre_id']));
        if($paramCount<$paramLength) {
            $sql.=" AND ";
        }
    }

    if(isset($params['title'])) {
        $paramCount++;
        $sql.=sprintf("`%s`='%s'", 'title', $params['title']);
        if($paramCount<$paramLength) {
            $sql.=" AND ";
        }
    }

    if(isset($params['releasedFrom']) && isset($params['releasedTo'])) {
        $sql.=sprintf("`%s` BETWEEN '%s' AND '%s'", 'release_date', $params['releasedFrom'], $params['releasedTo']);
    }

    $countQuery = $pdo->query($sql);

    $sql.=" ORDER BY `title` LIMIT :limit OFFSET :offset";

    $query = $pdo->prepare($sql);

    $offset = $page*$resultsPerPage;

    $query->bindParam(':limit', $resultsPerPage, PDO::PARAM_INT);
    $query->bindParam(':offset', $offset , PDO::PARAM_INT);

    $query->execute();

    return array(
        'results' => $query->fetchAll(),
        'result_count' => $countQuery->rowCount(),
        'current_page' => $page,
        'pages' => ceil($countQuery->rowCount()/$resultsPerPage),
        'results_per_page' => $resultsPerPage,
        'params' => $params
    );
}

function add_movie($genre_id, $title, $release_date) {
    $pdo = get_PDO();

    $query = $pdo->prepare("INSERT INTO `movie_data` (title, genre_id, release_date) VALUES (:title, :genre_id, :release_date)");
    $query->bindValue('genre_id', $genre_id, PDO::PARAM_INT);
    $query->bindValue('title', $title, PDO::PARAM_STR);
    $query->bindValue('release_date', $release_date, PDO::PARAM_STR);

    return $query->execute();
}