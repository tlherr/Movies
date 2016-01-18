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

    if($user) {
        if(password_verify($password, $user->password)) {
            return $user;
        } else {
            return null;
        }
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

function update_movie($id, array $genres, $score, $title, $date, $imdb_id) {

    $pdo = get_PDO();

    //Step one, delete all the old Genres matching this movie ID
    $q1 = "DELETE FROM `movie_genres` WHERE movie_id= :movie_id";

    //Step two insert the new genre IDs
    $q2 = "INSERT INTO `movie_genres` (movie_id,genre_id) VALUES (:movie_id, :genre_id)";

    //Step three, update any values in the movie_data table
    $q3 = "UPDATE `movie_data`
                 SET score=:score, title=:title, release_date=:release_date, imdb_id=:imdb_id
                 WHERE id= :movie_id";

    try {

        //Initiate a transaction
        $pdo->beginTransaction();

        $deleteQuery = $pdo->prepare($q1);
        $deleteQuery->bindParam(':movie_id', $id, PDO::PARAM_INT);
        $deleteQuery->execute();

        foreach($genres as $genre) {
            $insertQuery = $pdo->prepare($q2);
            $insertQuery->bindParam(':movie_id', $id, PDO::PARAM_INT);
            $insertQuery->bindParam(':genre_id', $genre, PDO::PARAM_INT);
            $insertQuery->execute();
        }

        $updateQuery = $pdo->prepare($q3);
        $updateQuery->bindParam(':movie_id', $id, PDO::PARAM_INT);
        $updateQuery->bindParam(':score', $score, PDO::PARAM_STR);
        $updateQuery->bindParam(':title', $title, PDO::PARAM_STR);
        $updateQuery->bindParam(':release_date', $date, PDO::PARAM_INT);
        $updateQuery->bindParam(':imdb_id', $imdb_id, PDO::PARAM_INT);
        $updateQuery->execute();

        $commit = true;

    } catch(PDOException $e) { //If the update or select query fail, we can't commit any changes to the database
        $commit = false;
    }

    if(!$commit){
        $pdo->rollback();
    } else {
        $pdo->commit();
        //Return true or something
    }

}


function get_movie_by_id($id) {
    $pdo = get_PDO();

    $sql = "SELECT movie_data.id, title, release_date, score, imdb_id, GROUP_CONCAT(genres.id) AS genre_id FROM `movie_data`";
    $sql.=" JOIN movie_genres ON movie_data.id = movie_genres.movie_id JOIN genres ON genres.id = movie_genres.genre_id WHERE movie_data.id=:movie_id";
    $query = $pdo->prepare($sql);
    $query->bindParam(':movie_id', $id, PDO::PARAM_INT);

    $query->execute();

    return $query->fetchObject();

}


/**
 *
 * SELECT movie_data.id, title, release_date, score, imdb_id, GROUP_CONCAT(genres.genre) AS genre_id FROM `movie_data`
JOIN movie_genres ON movie_data.id = movie_genres.movie_id
JOIN genres ON genres.id = movie_genres.genre_id
WHERE movie_data.title="Rome"
GROUP BY movie_data.id ORDER BY `title` LIMIT 30 OFFSET 0
 *
 *
 * @param $params
 * @param $resultsPerPage
 * @param $page
 * @return array
 */

function get_movies($params, $resultsPerPage, $page) {

    $pdo = get_PDO();

    $sql = "SELECT movie_data.id, title, release_date, score, imdb_id, GROUP_CONCAT(genres.genre) AS genre_id FROM `movie_data`";

    $sql.=" JOIN movie_genres ON movie_data.id = movie_genres.movie_id JOIN genres ON genres.id = movie_genres.genre_id";

    if(!empty($params)) {
        $sql.=" WHERE ";
        $paramLength = sizeof($params);
        $paramCount = 0;
    }

    if(isset($params['genre_id'])) {
        $paramCount++;
        $sql.=sprintf("%s=%s", 'genres.id', intval($params['genre_id']));
        if($paramCount<$paramLength) {
            $sql.=" AND ";
        }
    }

    if(isset($params['title'])) {
        $paramCount++;
        $sql.=sprintf("%s='%s'", 'movie_data.title', $params['title']);
        if($paramCount<$paramLength) {
            $sql.=" AND ";
        }
    }

    if(isset($params['releasedFrom']) && isset($params['releasedTo'])) {
        $sql.=sprintf("%s BETWEEN '%s' AND '%s'", 'movie_data.release_date', $params['releasedFrom'], $params['releasedTo']);
    }


    $sql.=" GROUP BY movie_data.id ORDER BY `title`";


    $countQuery = $pdo->query($sql);

    $sql.=" LIMIT :limit OFFSET :offset";

    $query = $pdo->prepare($sql);

    $offset = $page*$resultsPerPage;

    $query->bindParam(':limit', $resultsPerPage, PDO::PARAM_INT);
    $query->bindParam(':offset', $offset , PDO::PARAM_INT);

    $query->execute();

    return array(
        'results' => $query->fetchAll(),
        'result_count' => $countQuery->rowCount(),
        'genres' => get_movie_genres(),
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