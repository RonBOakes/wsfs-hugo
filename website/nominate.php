<?php
/*
 * Written by Ronald B. Oakes, copyright 2016, 2022
 * Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
 * For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
 * All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
 */
/**
 * Sample PHP page for a Hugo Award Nomination page.
 * This shows how to include and set up a basic, barebones, webpage containing the Hugo Awards nomination form.
 */
define ( 'WSFS_HUGO_VOTE_URL', 'http://mac2-hugo01.midamericon2.org/nominate.php' );

// Comment out the following once debuging has been completed.
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// End comment out section.

include "./hugonom.php";
print ("<!-- This is a line after the include -->\n") ;
print getHugoNomForm ( $_SERVER ['PHP_SELF'], false, false );
?>

