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

    //Check to see if this user already exists in the database
    $exists = is_email_unique($email);

    if($exists) {
        echo $twig->render('register.html.twig', array(
            'messages' => array(
                'type' => 'danger',
                'text' => 'Email address already in use'
            )
        ));
    } else {

        //Email does not exist, register the user then redirect
        $user = create_user($email, $password);

        session_start();
        $_SESSION['user'] = $email;

        header('Location: profile.php');
    }

} else {
    echo $twig->render('register.html.twig', array());
}
?>

