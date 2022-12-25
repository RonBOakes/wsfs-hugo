<!--
/* Written by Ronald B. Oakes, copyright 2014-2022
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
<?PHP
/**
 * Generates the report of ballots that do not belong to PINs currently owned by valid WSFS members.
 */
session_start ();
require_once ('library.php');
?>
 -->
<HTML>
<HEAD>
<TITLE>Orphan Hugo Award Ballots</TITLE>
</HEAD>
<BODY>
  <?PHP
  print ("<!-- Current Directory: ") ;
  print (getcwd ()) ;
  print (" -->\n") ;

  $db = new database();

  // Get the orphan ballot report.
  $orphanBallotInfo = $db->getOrphanBallots ();

  // get the information from the import .csv files related to each PIN
  // NOTE: This is hard coded based on Sasquan procedures.
  foreach ( $orphanBallotInfo as $pin => $info )
  {
    $orphanBallotInfo [$pin] ['Import records'] = `grep -i SQ$pin ../import/*.csv 2>&1`; // */
  }

  print ("    <PRE><BR/>\n") ;
  var_dump ( $orphanBallotInfo );
  print ("\n    </PRE>\n") ;

  ?>
  </BODY>
</HTML>
