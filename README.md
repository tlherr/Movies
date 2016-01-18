# Movies

Basic PHP Application to manage a database of movies. Using twig for templating, see include in common.inc (not using composer for autoloader etc.)

## Installation

Create a file in the main directory called db.inc.php

This file should have a method called get_PDO(); it will create the database connection.

Example:

```php
function get_PDO() {

    try {
        $pdo = new PDO(
            'mysql:host=localhost;dbname=movies;charset=utf8',
            'myusername',
            'mypassword');

        $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $pdo;
    } catch(PDOException $ex) {
        var_dump($ex);
    }

    return null;
}
```

Once completed navigate to website/install.php and click install.
This will take quite a while and appear frozen, database will be created and data from csv files will be imported.


## Usage

Once databases are installed just register a user and log it in. That is the entire application.


## License

Creative Commons Attribution-ShareAlike 4.0 International (CC BY-SA 4.0)