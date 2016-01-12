(function(window, $) {

    function createGenresTable() {
        console.log('Creating Genres Table Request');
        return $.ajax({
            url: "create_database.php",
            async:false,
            data: {
                "action": "createGenresTable"
            },
            cache: false,
            type: "GET"
        }).done(function() {
            console.log('Genres Table Request Success');
            $('#DatabaseProgress').css('width','20%');
        }).fail(function() {
            console.log('Genres Table Request Failure');
        });
    }


    function createMovieDataTable(data, textStatus, jqXHR) {
        console.log('Creating Movie Data Table Request');

        return $.ajax({
            url: "create_database.php",
            async:false,
            data: {
                "action": "createMovieDataTable"
            },
            cache: false,
            type: "GET"
        }).done(function() {
            $('#DatabaseProgress').css('width','40%');
        }).fail(function() {
            console.log('Movie Data Table Request Failure');
        });
    }

    function createMovieBridgeGenreTable(data, textStatus, jqXHR) {
        console.log('Creating Genre Bridge Table Request');

        return $.ajax({
            url: "create_database.php",
            async:false,
            data: {
                "action": "createMovieBridgeGenreTable"
            },
            cache: false,
            type: "GET"
        }).done(function() {
            $('#DatabaseProgress').css('width','60%');
        }).fail(function() {
            console.log('Genre Bridge Table Request Failure');
        });
    }

    function createUsersDataTable(data, textStatus, jqXHR) {
        console.log('Creating Users Table Request');

        return $.ajax({
            url: "create_database.php",
            async:false,
            data: {
                "action": "createUsersDataTable"
            },
            cache: false,
            type: "GET"
        }).done(function() {
            $('#DatabaseProgress').css('width','80%');
        }).fail(function() {
            console.log('Users Table Request Failure');
        });
    }

    function importGenreData(data, textStatus, jqXHR) {
        console.log('Creating Import Genre Data Request');

        return $.ajax({
            url: "create_database.php",
            async:false,
            data: {
                "import_data": "genres"
            },
            cache: false,
            type: "GET"
        }).done(function() {
            $('#DataProgress').css('width','20%');
        }).fail(function() {
            console.log('Import Genre Data Request Failure');
        });
    }

    function importMovieData(data, textStatus, jqXHR) {
        console.log('Creating Import Movie Data Request');

        return $.ajax({
            url: "create_database.php",
            async:false,
            data: {
                "import_data": "movies"
            },
            cache: false,
            type: "GET"
        }).done(function() {
            $('#DataProgress').css('width','40%');
        }).fail(function() {
            console.log('Import Movie Data Request Failure');
        });
    }

    function importGenreBridgeData(data, textStatus, jqXHR) {
        console.log('Creating Import Genre Bridge Data Request');

        return $.ajax({
            url: "create_database.php",
            async:false,
            data: {
                "import_data": "genre_bridge"
            },
            cache: false,
            type: "GET"
        }).done(function() {
            $('#DataProgress').css('width','60%');
        }).fail(function() {
            console.log('Import Genre Bridge Data Request Failure');
        });
    }

    $('#installButton').click(function() {
        createGenresTable()
            .then(createMovieDataTable)
            .then(createMovieBridgeGenreTable)
            .then(createUsersDataTable)
            .then(importGenreData)
            .then(importMovieData)
            .then(importGenreBridgeData);
    });

}(window, jQuery));
