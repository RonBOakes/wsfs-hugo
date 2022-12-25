<?php
/*
 * Written by Ronald B. Oakes, copyright 2016
 * Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
 * For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
 * All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
 */
/**
 * Sample PHP page for a Hugo Voters Packet page.
 * This shows how to include and set up a basic, barebones, webpage containing the Hugo Voters Packet download form.
 */
// define('WSFS_HUGO_VOTE_URL','http://www.worldcon76.org/hugo/packet.php');
// define('WSFS_HUGO_FILE_URL','http://www.worldcon76.org/hugo/');
define ( 'WSFS_HUGO_VOTE_URL', '/hugo/packet.php' );
define ( 'WSFS_HUGO_FILE_URL', '/hugo/' );

// Comment out the following once debuging has been completed.
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// End comment out section.

include "./hugopacket.php";
print ("<!-- This is a line after the include -->\n") ;
?>
<HEAD>
<TITLE>Worldcon 76 Hugo Award Packet</TITLE>
<?php
print getHugoPacketHeader ( $_SERVER ['PHP_SELF'], false, false );
?>
</HEAD>
<BODY>
<?php
print getHugoPacketForm ( $_SERVER ['PHP_SELF'], false, false );
?>
</BODY>

