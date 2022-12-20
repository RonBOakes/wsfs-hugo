<!--
/* Written by Ronald B. Oakes, copyright 2014, Updated 2015
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
 -->
<HTML>
  <HEAD>
    <TITLE>Orphan Hugo Award Ballots</TITLE>
  </HEAD>
  <BODY>
  <?PHP
    session_start();
    require_once('library.php');
    print("<!-- Current Directory: ");
    print(getcwd());
    print(" -->\n");

    $db = new database(getYear());

    $orphanBallotInfo = $db->getOrphanBallots();

    // get the information from the import .csv files related to each PIN
    foreach($orphanBallotInfo as $pin => $info)
    {
      $orphanBallotInfo[$pin]['Import records'] = `grep -i SQ$pin ../import/*.csv 2>&1`; // */
    }

    print("    <PRE><BR/>\n");
    var_dump($orphanBallotInfo);
    print("\n    </PRE>\n");

  ?>
  </BODY>
</HTML>
