<?php
include('../common.inc.php');

IF(!ISSET($_SESSION)){
    session_start();
}

//Check if we are getting POST data
if (isset($_POST['submit'])) {

    //Extract the post data from the form and check it against the database, if we have a match start a session and set the user as logged in
    $email = $_POST['email'];
    $password = $_POST['password'];

    $user = get_user($email, $password);

    if($user) {
        session_start();
        $_SESSION['id'] = session_id();
        $_SESSION['user'] = $email;
        header('Location: movies.php');
    } else {

        echo $twig->render('login.html.twig', array(
            'messages' => array(
                array('type' => 'danger', 'text' => 'Incorrect Username or Password, please try again')
            )
        ));
    }

} else {
    echo $twig->render('login.html.twig', array());
}
?>

