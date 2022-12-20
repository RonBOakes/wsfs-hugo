<?php
/* Written by Ronald B. Oakes, copyright  2016
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
      define('WSFS_HUGO_VOTE_URL','https://www.worldcon76.org/hugo/retro_vote.php');
      define('WSFS_HUGO_FILE_URL','https://www.worldcon76.org/hugo');


      // Comment out the following once debuging has been completed.
//      error_reporting(E_ALL);
//      ini_set('display_errors', '1');
      // End comment out section.

      include "./hugovote.php";
      print("<!-- This is a line after the include -->\n");
?>
<HEAD>
<TITLE>Worldcon 76 Retrospective Hugo Award Voting</TITLE>
<?php
      print getHugoVoteHeader($_SERVER['PHP_SELF'],true,false);
?>
</HEAD>
<BODY>
<?php
      print getHugoVoteForm($_SERVER['PHP_SELF'],true,false);
?>
</BODY>

