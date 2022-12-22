<?PHP
// Originally Writen by Ronald B. Oakes, Copyright 2012 assigned to Chicago Worldcon Bid Inc.

/* Written by Ronald B. Oakes, copyright 2015, 2022
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/

/**
  Reports the number of unique nominators for each Hugo Award category.
*/

  session_start();
  require_once('library.php');

  function buildCountTable()
  {
    $db = new database((isset($_POST['retro_hugo'])));

    $counts = $db->uniqueNominations();

    print("<!-- \$counts\n");
    var_dump($counts);
    print("\n-->\n");

?>
    <TABLE ID="ballotCount_table" BORDER=1 CLASS="sortable">
      <TR>
        <TH>Category</TH>
        <TH>Number of Unique Nominators</TH>
      </TR>
<?PHP
    foreach($counts as $category => $count)
    {
      print('      <TR>'."\n");
      print('        <TD>'.$category.'</TD>'."\n");
      print('        <TD>'.$count.'</TD>'."\n");
      print('      </TR>'."\n");
    }
?>
    </TABLE>
<?PHP
  }

?>
<HTML>
  <HEAD>
    <TITLE>Hugo Nomination Administration</TITLE>
    <!-- TODO: Rest of HEAD code -->
    <SCRIPT TYPE="text/javascript" src="javascript/admin.js"></SCRIPT>
    <script type="text/javascript" src="javascript/sorttable.js"></script>
  </HEAD>
  <BODY>
    <?PHP menu(); ?>
    <BR/>
    <?PHP buildCountTable(); ?>
  </BODY>
</HTML>
