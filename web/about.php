<?php

include('../common.inc.php');

echo $twig->render('about.html.twig', array('logged_in' => is_logged_in()));