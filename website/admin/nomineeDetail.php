<!--
/* Written by Ronald B. Oakes, copyright 2014, Updated 2015
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
-->
<?PHP
chdir('..');
require_once('./database.php');
chdir('./admin/');

  if(isset($_POST))
  {
    print("<!-- \$_POST:\n");
    var_dump($_POST);
    print("-->\n");
  }

  if(isset($_GET))
  {
    print("<!-- \$_GET:\n");
    var_dump($_GET);
    print("-->\n");
  }

  $db = new database();

  function loadParams()
  {
    global $db;
    global $nomineeData;
    global $categoryData;

    $nomineeData = array();

    if(isset($_GET['id']))
    {
      if($_GET['id'] == -1)
      {
        $nomineeData['nominee_id']    = -1;
        $nomineeData['category_id']   = -1;
        $nomineeData['primary_datum'] = '';
        $nomineeData['datum_2']       = '';
        $nomineeData['datum_3']       = '';

        $categoryData['name']                      = 'error';
        $categoryData['primary_datum_description'] = 'error';
        $categoryData['datum_2_description']       = 'error';
        $categoryData['datum_3_description']       = 'error';
      }
      else
      {
        $nomineeData  = $db->getNomineeInfo($_GET['id']);
        $categoryInfo = $db->getCategoryInfo();
        $categoryData = $categoryInfo[$nomineeData['category_id']];
      }
    }
    elseif(isset($_POST['nominee_id'])) // Indicates submitted data
    {
      $nomineeData['nominee_id']     = $_POST['nominee_id'];
      $nomineeData['category_id']    = $_POST['category_id'];
      $nomineeData['primary_datum']  = $_POST['primary_datum'];
      $nomineeData['datum_2']        = $_POST['datum_2'];
      $nomineeData['datum_3']        = $_POST['datum_3'];

      $categoryInfo = $db->getCategoryInfo();
      $categoryData = $categoryInfo[$nomineeData['category_id']];
    }
    else
    {
        $nomineeData['nominee_id']    = -1;
        $nomineeData['category']      = '';
        $nomineeData['primary_datum'] = '';
        $nomineeData['datum_2']       = '';
        $nomineeData['datum_3']       = '';

        $categoryData['name']                      = 'error';
        $categoryData['primary_datum_description'] = 'error';
        $categoryData['datum_2_description']       = 'error';
        $categoryData['datum_3_description']       = 'error';
    }
  }

  function buildNomineeForm()
  {
    global $nomineeData;
    global $categoryData;

?>
    <FORM NAME="nomineeDetail" ID="nomineeDetail" ACTION="nomineeDetail.php" METHOD="post">
      <INPUT TYPE="HIDDEN" NAME="nominee_id" VALUE="<?PHP print($nomineeData['nominee_id']); ?>" />
      <INPUT TYPE="HIDDEN" NAME="category_id" VALUE="<?PHP print($nomineeData['category_id']); ?>" />
      <TABLE BORDER=1>
        <TR>
    <TD>Hugo Award Category:</TD>
    <TD><INPUT TYPE="TEXT" NAME="category" VALUE="<?PHP print($categoryData['name']); ?>" READONLY /></TD>
  </TR>
        <TR>
    <TD><?PHP print(trim($categoryData['primary_datum_description'])); ?>:</TD>
    <TD><INPUT TYPE="TEXT" NAME="primary_datum" VALUE="<?PHP print($nomineeData['primary_datum']); ?>" /></TD>
  </TR>
        <TR>
    <TD><?PHP print(trim($categoryData['datum_2_description'])); ?>:</TD>
    <TD><INPUT TYPE="TEXT" NAME="datum_2" VALUE="<?PHP print($nomineeData['datum_2']); ?>" /></TD>
  </TR>
        <TR>
    <TD><?PHP print(trim($categoryData['datum_3_description'])); ?>:</TD>
    <TD><INPUT TYPE="TEXT" NAME="datum_3" VALUE="<?PHP print($nomineeData['datum_3']); ?>" /></TD>
  </TR>
  <TR>
    <TD/>
    <TD><INPUT TYPE="SUBMIT" NAME="button_pushed" VALUE="Update Nominee" /></TD>
      </TABLE>
    </FORM>
<?PHP
  }

  loadParams();

  print("<!-- \$nomineeData\n");
  var_dump($nomineeData);
  print("-->\n");
  print("<!-- \$categoryData\n");
  var_dump($categoryData);
  print("-->\n");


  if(isset($_POST['button_pushed']))
  {
    $db->updateNominee($nomineeData['nominee_id'],
                       $nomineeData['primary_datum'],
           $nomineeData['datum_2'],
           $nomineeData['datum_3']);
    print("<P>Nominee Updated</P>\n");
  }

?>
<HTML>
  <HEAD>
    <TITLE>Edit Hugo Nominee</TITLE>
  </HEAD>
  <BODY>
    <?PHP buildNomineeForm(); ?>
  <BODY>
</HTML>
