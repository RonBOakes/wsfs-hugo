<?PHP
/* Written by Ronald B. Oakes, copyright 2014, Updated 2015, 2018
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/

  define('WSFS_HUGO_VOTE_URL','https://www.worldcon76.org/hugo/admin/manualVote.php');
  define('WSFS_HUGO_FILE_URL','https://www.worldcon76.org/hugo');

  chdir('..');
  include('./hugovote.php');
  chdir('./admin/');

?>
<HTML>
  <HEAD>
    <TITLE>Hugo Nomination Administration</TITLE>
    <!-- TODO: Rest of HEAD code -->
    <?PHP print(getHugoVoteHeader($_SERVER['PHP_SELF'],false,true)); ?>
  </HEAD>
  <BODY>
    <!-- $_POST:
    <?PHP var_dump($_POST); ?>
    -->
    <BR/>
    <?PHP print(getHugoVoteForm($_SERVER['PHP_SELF'],false,true)); ?>
  </BODY>
</HTML>
