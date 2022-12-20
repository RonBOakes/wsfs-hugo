<?PHP
/* Written by Ronald B. Oakes, copyright 2014, Updated 2015
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
  session_start();
  require_once('library.php');

  $db = new database((isset($_POST['retro_hugo'])));

  function categoryMenu($categoryId)
  {
    global $db;

    $categoryData = $db->getCategoryInfo();

    print('    <FORM NAME="categories" ID="categories" ACTION="ballotReport.php" METHOD="post" >'."\n");
    print('      <INPUT TYPE="HIDDEN" NAME="button_pressed" VALUE="New Category" />'."\n");
    print('      <SELECT NAME="category_id" onchange="updateNomineeCategory()" >'."\n");

    foreach ($categoryData as $id => $data)
    {
      print('        <OPTION VALUE="'.$id.'"');
      if ($id == $categoryId)
      {
        print(' SELECTED');
      }
      print(' >'.$categoryData[$id]['name']."</OPTION>\n");
    }

    print('      </SELECT>'."\n");
    print('    </FORM>'."\n");
  }

  function getBallot($categoryId)
  {
    global $db;

    $categoryInfo = $db->getCategoryInfo();

    $voters = $db->getVoters($categoryId);

    print('<p>'.$categoryInfo[$categoryId]['name'].' Votes</p>'."\n");
    print('<P>Total Ballots Received: '.count($voters).'</p>'."\n");

    foreach ($voters as $memberId)
    {
      $votes = $db->getVoteBallot($memberId,$categoryId);

/*      print("<!-- \$votes:\n");
      var_dump($votes);
      print("\$memberId:   $memberId\n");
      print("\$categoryId: $categoryId\n");
      print("-->\n");  */

      print("<P>PIN: $memberId</P>\n");

      print('<TABLE BORDER=1>'."\n");
      print('  <TR>'."\n");
      print('    <TH>'.$categoryInfo[$categoryId]['primary_datum_description'].'</TH>'."\n");
      print('    <TH>Vote</TH>'."\n");
      print('  </TR>'."\n");

      foreach ($votes as $nominee => $rank)
      {
        print('  <TR>'."\n");
  print('    <TD>'.$nominee.'</TD>'."\n");
  print('    <TD>'.$rank.'</TD>'."\n");
  print('  </TR>'."\n");
      }

      print('</TABLE>'."\n");
      print('<HR/>'."\n");
    }
  }

  $categoryId = 1;

  if((isset($_POST['button_pressed'])) and ($_POST['button_pressed'] == 'New Category'))
  {
    $categoryId = $_POST['category_id'];
  }

?>
<!-- Writen by Ronald B. Oakes, Copyright 2012 assigned to Chicago Worldcon Bid Inc. -->
<HTML>
  <HEAD>
    <TITLE>Hugo Nomination Administration</TITLE>
    <!-- TODO: Rest of HEAD code -->
    <SCRIPT TYPE="text/javascript" src="javascript/admin.js"></SCRIPT>
  </HEAD>
  <BODY>
    <?PHP menu(); ?>
    <BR/>
    <?PHP categoryMenu($categoryId); ?>
    <BR/>
    <?PHP getBallot($categoryId);  ?>
  </BODY>
</HTML>
