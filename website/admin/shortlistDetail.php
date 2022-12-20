<!--
/* Written by Ronald B. Oakes, copyright 2014, Updated 2015, 2016
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
 -->
<?PHP
  require_once('library.php');
?>
<HTML>
<HEAD>
    <TITLE>Edit Hugo Shortlist Nominee</TITLE>
    <SCRIPT TYPE="text/JavaScript">
        function refreshParent()
        {
            var parent = window.opener;
            parent.location.reload(true);
        }

        function closeMe()
        {
            window.close();
        }
    </SCRIPT>
</HEAD>
<?PHP
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

  $wsfs_retro = 0;

  if (isset($_GET['wsfs_retro']))
  {
    $wsfs_retro = $_GET['wsfs_retro'];
  }
  elseif (isset($_POST['wsfs_retro']))
  {
    $wsfs_retro = $_POST['wsfs_retro'];
  }

  $db = new Database($wsfs_retro);

  if (!$wsfs_retro)
  {
    $wsfs_retro = 0;
  }

  function loadParams()
  {
    global $db;
    global $wsfs_retro;
    global $shortlistData;
    global $categoryData;

    $shortlistData = array();

    if(isset($_GET['id']))
    {
      if(($_GET['id'] == -1) && (!isset($_GET['categoryId'])))
      {
        $shortlistData['shortlist_id']    = -1;
        $shortlistData['category_id']   = -1;
        $shortlistData['datum_1'] = '';
        $shortlistData['datum_2']       = '';
        $shortlistData['datum_3']       = '';

        $categoryData['name']                      = 'error';
        $categoryData['primary_datum_description'] = 'error';
        $categoryData['datum_2_description']       = 'error';
        $categoryData['datum_3_description']       = 'error';
      }
      elseif ($_GET['id'] == -1)
      {
        $categoryInfo = $db->getCategoryInfo();
        $categoryData = $categoryInfo[$_GET['categoryId']];
        print("<!-- \$categoryData LOOK HERE\n");
        var_dump($categoryData);
        print("-->\n");

        $shortlistData['shortlist_id']    = -1;
        $shortlistData['category_id']   = $_GET['categoryId'];
        $shortlistData['datum_1'] = '';
        $shortlistData['datum_2']       = '';
        $shortlistData['datum_3']       = '';
      }
      else
      {
        $shortlistData  = $db->getShortlistInfo($_GET['id']);
        $categoryInfo = $db->getCategoryInfo();
        $categoryData = $categoryInfo[$shortlistData['category_id']];
      }
    }
    elseif(isset($_POST['shortlist_id'])) // Indicates submitted data
    {
      $shortlistData['shortlist_id']   = $_POST['shortlist_id'];
      $shortlistData['category_id']    = $_POST['category_id'];
      $shortlistData['datum_1']        = $_POST['datum_1'];
      $shortlistData['datum_2']        = $_POST['datum_2'];
      $shortlistData['datum_3']        = $_POST['datum_3'];

      $categoryInfo = $db->getCategoryInfo();
      $categoryData = $categoryInfo[$shortlistData['category_id']];
    }
    else
    {
      $shortlistData['shortlist_id']  = -1;
      $shortlistData['category']      = '';
      $shortlistData['datum_1']       = '';
      $shortlistData['datum_2']       = '';
      $shortlistData['datum_3']       = '';

      $categoryData['name']                      = 'error';
      $categoryData['primary_datum_description'] = 'error';
      $categoryData['datum_2_description']       = 'error';
      $categoryData['datum_3_description']       = 'error';
    }
  }

  function buildShortlistForm()
  {
    global $shortlistData;
    global $categoryData;
    global $wsfs_retro;

?>

<FORM NAME="shortlistDetail" ID="shortlistDetail" ACTION="shortlistDetail.php" METHOD="post">
    <INPUT TYPE="HIDDEN" NAME="shortlist_id" VALUE="<?PHP print($shortlistData['shortlist_id']); ?>" />
    <INPUT TYPE="HIDDEN" NAME="category_id" VALUE="<?PHP print($shortlistData['category_id']); ?>" />
    <INPUT TYPE="HIDDEN" NAME="wsfs_retro" VALUE="<?PHP print($wsfs_retro); ?>" />
    <TABLE BORDER="1">
        <TR>
            <TD>Hugo Award Category:</TD>
            <TD>
                <INPUT TYPE="TEXT" NAME="category" VALUE="<?PHP print($categoryData['name']); ?>" READONLY />
            </TD>
        </TR>
        <TR>
            <TD><?PHP print(trim($categoryData['primary_datum_description'])); ?>:</TD>
            <TD>
                <INPUT TYPE="TEXT" NAME="datum_1" VALUE="<?PHP print($shortlistData['datum_1']); ?>" SIZE="80" />
            </TD>
        </TR>
        <TR>
            <TD><?PHP print(trim($categoryData['datum_2_description'])); ?>:</TD>
            <TD>
                <INPUT TYPE="TEXT" NAME="datum_2" VALUE="<?PHP print($shortlistData['datum_2']); ?>" SIZE="80" />
            </TD>
        </TR>
        <TR>
            <TD><?PHP print(trim($categoryData['datum_3_description'])); ?>:</TD>
            <TD>
                <INPUT TYPE="TEXT" NAME="datum_3" VALUE="<?PHP print($shortlistData['datum_3']); ?>" SIZE="80" />
            </TD>
        </TR>
        <TR>
            <TD/>
            <TD>
                <INPUT TYPE="SUBMIT" NAME="button_pushed" VALUE="Update Shortlist" />
            </TD>
    </TABLE>
</FORM>
<?PHP
  }

  loadParams();

  print("<!-- \$shortlistData\n");
  var_dump($shortlistData);
  print("-->\n");
  print("<!-- \$categoryData\n");
  var_dump($categoryData);
  print("-->\n");


  if(isset($_POST['button_pushed']))
  {
    $db->addUpdateShortList($shortlistData['category_id'],
                            $shortlistData['datum_1'],
                    $shortlistData['datum_2'],
                    $shortlistData['datum_3']);
    print("<P>Shortlist Nominee Updated</P>\n");
  }

?>
<BODY<?PHP
        if(isset($_POST['button_pushed']))
        {
            print (' ONLOAD="refreshParent();closeMe();" ');
        }
       ?>>
    <?PHP buildShortlistForm(); ?>
<BODY>
</HTML>
