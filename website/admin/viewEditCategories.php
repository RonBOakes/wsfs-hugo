<?PHP
/* Written by Ronald B. Oakes, copyright  2015
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
  session_start();
  require_once('library.php');

  $db = new database(getYear());

  function buildCategoryTable()
  {
    global $db;
    global $categoryInfo;

?>
    <FORM NAME="editCategories" ID="editCategories" ACTION="viewEditCategories.php" METHOD="post">
      <INPUT TYPE="HIDDEN" NAME="button_pressed" VALUE="Edit" />
      <TABLE BORDER=1>
        <TR>
    <TH>Category</TH>
    <TH>Ballot Position</TH>
    <TH>1st Datum</TH>
    <TH>2nd Datum</TH>
    <TH>3rd Datum</TH>
    <TH/>
        </TR>
<?PHP
    $categoryOrder = array();

    foreach ($categoryInfo as $id => $info)
    {
      $categoryOrder[$categoryInfo[$id]['ballot_position']] = $id;
    }

    foreach ($categoryOrder as $ballotOrder => $id)
    {
      print("        <TR>\n");
      print('          <TD>'.$categoryInfo[$id]['name']."</TD>\n");
      print('          <TD><INPUT TYPE="TEXT" NAME="ballot_order_'.$id.'" VALUE="'.$ballotOrder.'" /></TD>'."\n");
      print('          <TD>'.$categoryInfo[$id]['primary_datum_description']."</TD>\n");
      print('        <TD>'.$categoryInfo[$id]['datum_2_description']."</TD>\n");
      print('        <TD>'.$categoryInfo[$id]['datum_3_description']."</TD>\n");
      print('          <TD><INPUT TYPE="button" onclick="editCategory('.$id.');" VALUE="Edit Category" /></TD>'."\n");
      print("        </TR>\n");
    }
?>
        <TR>
          <TD />
    <TD><INPUT TYPE="button" onclick="updateBallotOrder();" VALUE="Update Ballot Order" /></TD>
    <TD COLSPAN=3 />
    <TD><INPUT TYPE="button" onclick="editCategory(-1);" VALUE="Add New Category" /></TD>
  </TR>
      </TABLE>
    </FORM>
<?PHP
  }

  $categoryInfo = $db->getCategoryInfo();

  print("<!-- \$_POST:\n");
  var_dump($_POST);
  print("-->\n");

  if((isset($_POST['button_pressed'])) and ($_POST['button_pressed'] == 'Update Ballot Order'))
  {
    foreach ($categoryInfo as $id => $info)
    {
      $db->addUpdateCategory($categoryInfo[$id]['name'],
                             $categoryInfo[$id]['description'],
           $_POST['ballot_order_'.$id],
           $categoryInfo[$id]['primary_datum_description'],
           $categoryInfo[$id]['datum_2_description'],
           $categoryInfo[$id]['datum_3_description']);
    }

    $categoryInfo = $db->getCategoryInfo();
  }

?>
<HTML>
  <HEAD>
    <TITLE>Hugo Nomination Administration</TITLE>
    <!-- TODO: Rest of HEAD code -->
    <SCRIPT TYPE="text/javascript" src="javascript/admin.js"></SCRIPT>
  </HEAD>
  <BODY>
    <?PHP menu(); ?>
    <?PHP buildCategoryTable(); ?>
  </BODY>
</HTML>
