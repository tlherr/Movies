<?php

//Saving sessions in local "project" directory to avoid any issues on a shared host etc
ini_set('session.save_path',__DIR__.'/sessions');

//Load Twig (use this for templating vs raw php, find it cleaner)
require_once '../lib/Twig/lib/Twig/Autoloader.php';
Twig_Autoloader::register();

$loader = new Twig_Loader_Filesystem('../templates');
$twig = new Twig_Environment($loader, array(
    'cache' => false,
    'debug' => true,
));
//Disable this if project were ever to actually be used (which it shouldnt be)
$twig->addExtension(new Twig_Extension_Debug());

//Include the database connection file (this is intentionally seperated so it can be removed from
//version control and placed outside of the web directory
require('db.inc.php');

/**
 * Helper function to check if a specified table in the database actually exists
 * Useful for the installer to check and see if it needs to be run if invoked
 *
 * @param PDO $pdo
 * @param $id
 * @return bool
 */
function tableExists(PDO $pdo, $id)
{
    $query = $pdo->prepare("SHOW TABLES LIKE :id");
    $query->bindParam(":id", $id, PDO::PARAM_INT);

    $results = $query->execute();

    return ($results->rowCount()>0);
}

/**
 * Check the session variable to see if a user has been set
 * Most pages use this as a general check to see if an action is allowed or
 * something should be displayed or hidden
 *
 * @return bool
 */
function is_logged_in() {
    return (isset($_SESSION['user']));
}

/**
 * Helper function to check to see if a specified email address already exists in the database
 *
 * @param $email
 * @return int
 */
function is_email_unique($email) {
    $pdo = get_PDO();

    if($pdo) {
        $query = $pdo->prepare('SELECT * FROM `users` WHERE email = :email');
        $query->bindParam(':email', $email);
        $query->execute();

        $result = $query->rowCount();
        return $result;
    }

    return false;
}

/**
 * Session variables just hold basic information
 * This method will return the database information for a user that has been
 * set in the session
 *
 * @return mixed
 */
function get_user_from_session() {
    $pdo = get_PDO();

    $query = $pdo->prepare('SELECT * FROM `users` where email = :email');
    $query->bindParam(':email', $_SESSION['user']);
    $query->execute();

    return $query->fetchObject();
}

/**
 * Helper function to retrieve a user from the database
 *
 * @param $email
 * @param $password
 * @return mixed|null
 */
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

/**
 * Helper function to insert a new user into database
 *
 * Using php built in hashing libraries to avoid storing passwords in plaintext
 *
 * @param $email
 * @param $password
 * @return bool
 */
function create_user($email, $password) {
    $pdo = get_PDO();

    $query = $pdo->prepare('INSERT INTO users SET email = :email, password = :password, date_joined = NOW(), last_login = NOW()');
    $query->bindParam(':email', $email);
    $query->bindParam(':password', password_hash($password, PASSWORD_DEFAULT));
    $result = $query->execute();
    return $result;
}

/**
 * Return an array of all currently defined movie genres from the database
 *
 * @return array
 */
function get_movie_genres() {
    $pdo = get_PDO();

    $query = $pdo->query("SELECT * FROM `genres`");
    return $query->fetchAll();
}

/**
 * Update a movie in the database.
 *
 * Using PDO transactions for this as we first have to clear out any old genre data
 * add in the new genre data and then update the values in the movies_data table
 *
 * Transactions allow us to only commit changes when we know everything has succeeded
 * If any of the operations throw a SQL execption the database will automatically roll back
 * avoiding orphaned join data
 *
 * @param $id
 * @param array $genres
 * @param $score
 * @param $title
 * @param $date
 * @param $imdb_id
 */
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

/**
 * Given a specified ID retrieve a movie from the database and return it as an object
 *
 * @param $id
 * @return mixed
 */
function get_movie_by_id($id) {
    $pdo = get_PDO();

    $sql = "SELECT movie_data.id, title, release_date, score, imdb_id, GROUP_CONCAT(genres.genre) AS genre_id FROM `movie_data`";
    $sql.=" JOIN movie_genres ON movie_data.id = movie_genres.movie_id JOIN genres ON genres.id = movie_genres.genre_id WHERE movie_data.id=:movie_id";
    $query = $pdo->prepare($sql);
    $query->bindParam(':movie_id', $id, PDO::PARAM_INT);

    $query->execute();

    return $query->fetchObject();

}


/**
 * Main function of the application
 *
 * This will retrieve movie data from the database based on user specified filters.
 *
 * The actual query looks like this:
 *
 * SELECT movie_data.id, title, release_date, score, imdb_id, GROUP_CONCAT(genres.genre) AS genre_id FROM `movie_data`
 * JOIN movie_genres ON movie_data.id = movie_genres.movie_id
 * JOIN genres ON genres.id = movie_genres.genre_id
 * WHERE filters
 * GROUP BY movie_data.id ORDER BY `title` LIMIT 30 OFFSET 0
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

/**
 * Insert a movie into the database.
 *
 * Also using transactions model because our genre data is in a seperate table.
 * Either all the SQL queries work or a rollback will be performed
 *
 * @param array $genres
 * @param $title
 * @param $release_date
 * @param $score
 * @param $imdb_id
 * @param $poster
 * @return bool|null
 */
function add_movie(array $genres, $title, $release_date, $score, $imdb_id, $poster) {
    $pdo = get_PDO();


    $relPath = '/img/'.basename($poster["name"]);
    $target_file = __DIR__.'/web'.$relPath;

    if (file_exists($target_file)) {
        echo "Sorry, file already exists.";
        return null;
    }
    // Check file size
    if ($poster["size"] > 500000) {
        echo "Sorry, your file is too large.";
        return null;
    }

    $file = move_uploaded_file($poster["tmp_name"], $target_file);

    if(!$file) {
        return null;
    }


    try {

        //Initiate a transaction
        $pdo->beginTransaction();

        $query = $pdo->prepare("INSERT INTO `movie_data` (title, release_date, score, imdb_id, image_file_path)
                                VALUES (:title, :release_date, :score, :imdb_id, :image_file_path)");
        $query->bindParam(':title', $title, PDO::PARAM_STR);
        $query->bindParam(':release_date', $release_date, PDO::PARAM_INT);
        $query->bindParam(':score', $score, PDO::PARAM_STR);
        $query->bindParam(':imdb_id', $imdb_id, PDO::PARAM_INT);
        $query->bindParam(':image_file_path', $relPath, PDO::PARAM_STR);


        if($query->execute()) {

            $id = $pdo->lastInsertId();

            foreach($genres as $genre) {
                $insertQuery = $pdo->prepare("INSERT INTO `movie_genres` (movie_id,genre_id) VALUES (:movie_id, :genre_id)");
                $insertQuery->bindParam(':movie_id', $id, PDO::PARAM_INT);
                $insertQuery->bindParam(':genre_id', $genre, PDO::PARAM_INT);
                $insertQuery->execute();
            }

            $commit = true;
        } else {
            $commit = false;
        }


    } catch(PDOException $e) { //If the update or select query fail, we can't commit any changes to the database
        $commit = false;
    }

    if(!$commit){
        $pdo->rollback();
    } else {
        $pdo->commit();
        //Return true or something

        return true;
    }

    return false;

    //Insert the movie into the database, get the ID it was given and then insert the genre stuff

}


function delete_movie($id) {
    $pdo = get_PDO();

    try {
        //Initiate a transaction
        $pdo->beginTransaction();

        $genreDeleteQuery = $pdo->prepare("DELETE FROM `movie_genres` WHERE movie_id = :movie_id");
        $genreDeleteQuery->bindParam(":movie_id", $id, PDO::PARAM_INT);

        if($genreDeleteQuery->execute()) {
            $movieDeleteQuery = $pdo->prepare("DELETE FROM `movie_data` WHERE id = :movie_id");
            $movieDeleteQuery->bindParam(":movie_id", $id, PDO::PARAM_INT);
            $genreDeleteQuery->execute();

            $commit = true;
        } else {
            $commit = false;
        }


    } catch(PDOException $ex) {
        $commit = false;
    }


    if(!$commit){
        $pdo->rollback();
    } else {
        $pdo->commit();
        //Return true or something

        return true;
    }

    return false;
}