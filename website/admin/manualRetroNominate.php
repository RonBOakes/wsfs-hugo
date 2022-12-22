<?PHP
/*
 * Written by Ronald B. Oakes, copyright 2015-2022
 * Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
 * For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
 * All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
 */
/**
 * Pull up the form to allow the administrator to enter Retro Hugo Award nominations.
 */
session_start ();
require_once ('library.php');
require_once ('../hugonom.php');
?>
<HTML>
<HEAD>
<TITLE>Hugo Nomination Administration</TITLE>
<!-- TODO: Rest of HEAD code -->
<SCRIPT TYPE="text/javascript" src="javascript/admin.js"></SCRIPT>
</HEAD>
<BODY>
    <?PHP menu(); ?>
    <!-- $_POST:
    <?PHP var_dump($_POST); ?>
    -->
	<BR />
    <?PHP print getHugoNomForm($_SERVER['PHP_SELF'],true,true); ?>
  </BODY>
</HTML>
