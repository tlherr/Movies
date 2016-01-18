<?php
include('../common.inc.php');

$pdo = get_PDO();

$status = $pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
if($status) {
    //If we already have a working database with the tables we need then abort this and send the user to the main page

    if(isset($_GET['action'])) {

        switch($_GET['action']) {

            case 'createGenresTable':
                $queryString = <<<EOL
    CREATE TABLE IF NOT EXISTS `genres` (
    `id` int NOT NULL AUTO_INCREMENT,
  `genre` text NOT NULL,
    PRIMARY key (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOL;

                break;

            case 'createMovieDataTable':
                $queryString = <<<EOL
    CREATE TABLE IF NOT EXISTS `movie_data` (
    `id` int NOT NULL AUTO_INCREMENT,
  `title` text,
  `release_date` year(4) DEFAULT NULL,
  `score` text DEFAULT NULL,
  `imdb_id` text DEFAULT NULL,
  `image_file_path` text DEFAULT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOL;
                break;

            case 'createMovieBridgeGenreTable':
                $queryString = <<<EOL
        CREATE TABLE IF NOT EXISTS `movie_genres` (
  `id` int NOT NULL AUTO_INCREMENT,
  `movie_id` int NOT NULL,
  `genre_id` int NOT NULL,
  PRIMARY KEY (id),
FOREIGN KEY (movie_id) REFERENCES movie_data(id),
FOREIGN KEY (genre_id) REFERENCES genres(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOL;
                break;

            case 'createUsersDataTable':
                $queryString = <<<EOL
CREATE TABLE IF NOT EXISTS `users` (
  `id` int NOT NULL AUTO_INCREMENT,
  `email` text NOT NULL,
  `password` text NOT NULL,
  `date_joined` date NOT NULL,
  `last_login` date NOT NULL,
    PRIMARY key (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOL;
                break;

        }

        $query = $pdo->query($queryString);
        return $query->execute();
    }

    if(isset($_GET['import_data'])) {

        switch($_GET['import_data'])
        {

            case 'genres':
                $file = __DIR__.'/../data/genres.csv';
                $handle = fopen($file, "r");
                try {
                    // prepare for insertion
                    $query_ip = $pdo->prepare('
                        INSERT INTO genres (
                            genre
                        ) VALUES (
                            :genre
                        )
                        ON DUPLICATE KEY
                            UPDATE genre=:genre
                    ');

                    fgets($handle);

                    while (($data = fgetcsv($handle, 1000, ',')) !== FALSE) {
                        $query_ip->bindParam(':genre', $data[0]);
                        $query_ip->execute();
                    }

                    fclose($handle);

                } catch(PDOException $e) {
                    die($e->getMessage());
                }
            break;

            case 'movies':
                $file = __DIR__.'/../data/movies.csv';
                $handle = fopen($file, "r");
                try {
                    // prepare for insertion
                    $query_ip = $pdo->prepare('
                        INSERT IGNORE INTO movie_data (
                            title, release_date, score, imdb_id
                        ) VALUES (
                            ?, ?, ?, ?
                        )
                    ');

                    fgets($handle);

                    while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
                        unset($data[4]);
                        $query_ip->execute($data);
                    }

                    fclose($handle);

                } catch(PDOException $e) {
                    die($e->getMessage());
                }
            break;

            case 'genre_bridge':

                ini_set('max_execution_time', 300); //300 seconds = 5 minutes

                $file = __DIR__.'/../data/movies.csv';
                $handle = fopen($file, "r");
                try {

                    fgets($handle);

                    while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {

                        $genres = array_unique(explode(',', $data[4]));

                        foreach($genres as $genre) {
                            $genre_query = $pdo->prepare("SELECT * FROM `genres` WHERE genre=:genre");
                            $genre_query->bindParam(':genre', $genre, PDO::PARAM_STR);
                            $genre_query->execute();
                            $genre_id = $genre_query->fetchObject();

                            $movie_query = $pdo->prepare("SELECT * FROM `movie_data` WHERE title=:title");
                            $movie_query->bindParam(':title', $data[0], PDO::PARAM_STR);
                            $movie_query->execute();
                            $movie_id = $movie_query->fetchObject();

                            if(isset($genre_id->id) && isset($movie_id->id)) {
                                $query_ip = $pdo->prepare('
                                INSERT IGNORE INTO movie_genres (
                                    genre_id, movie_id
                                ) VALUES (
                                    ?, ?
                                )
                            ');

                                $query_ip->execute(array($genre_id->id, $movie_id->id));
                            }
                        }
                    }

                    fclose($handle);

                } catch(PDOException $e) {
                    die($e->getMessage());
                }
            break;


        }

    }

} else {
    http_response_code(500);
}