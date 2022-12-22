<?PHP
/*
 * Written by Ronald B. Oakes, copyright 2015
 * Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
 * For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
 * All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
 */
/**
 * Webpage for managing the Hugo Award finalists.
 */
session_start ();
require_once ('library.php');

$me = 'HTTP://' . $_SERVER ['SERVER_NAME'] . '/' . $_SERVER ['PHP_SELF'];

print ("<!-- \$_POST\n") ;
var_dump ( $_POST );
print ("-->\n") ;
print ("<!-- \$_GET\n") ;
var_dump ( $_GET );
print ("-->\n") ;

if ((isset ( $_POST ['retro_hugo'] )))
{
  $wsfs_retro = 1;
}
else
{
  $wsfs_retro = 0;
}

$db = new Database ( $wsfs_retro );

/**
 * Get the finalist data from the database.
 *
 * @param int $categoryId
 *          Hugo Award category ID
 * @return array Hash with the finalist information.
 */
function fetchShortlist($categoryId)
{
  global $db;

  $shortlist = $db->getShortlist ( $categoryId );

  return $shortlist;
}

/**
 * Fetch the nominee information
 *
 * @warning The nominee counting and reporting information is deprecated and should not be used.
 *
 * @param int $categoryId
 *          Hugo Award category ID
 * @return array Hash containing nominee information.
 */
function fetchNominees($categoryId)
{
  global $db;

  $nomineeList = $db->listNomineesByCount ( $categoryId, 15 );

  return $nomineeList;
}

/**
 * Build a table containing the finalists in the specified category.
 *
 * @param int $categoryId
 *          Hugo Award category ID
 */
function buildShortlistTable($categoryId)
{
  global $db;
  global $me;
  global $wsfs_retro;

  $shortlist = fetchShortlist ( $categoryId );

  print ("<!-- \$shortlist:\n") ;
  var_dump ( $shortlist );
  print ("-->\n") ;

  $categories = $db->getCategoryInfo ();
  $categoryInfo = $categories [$categoryId];

  ?>
<FORM NAME="shortlistData" ID="shortlistData"
	ACTION="manageShortlist.php" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="category_id"
		VALUE="<?PHP print($categoryId); ?>" /> <INPUT TYPE="HIDDEN"
		NAME="shortlist_id" VALUE=-1 /> <INPUT TYPE="HIDDEN"
		NAME="button_pressed" VALUE="" /> <INPUT TYPE="HIDDEN"
		NAME="wsfs_retro" VALUE="<?PHP print($wsfs_retro); ?>" />
	<TABLE BORDER=1>
		<TR>
			<TH><?PHP print($categoryInfo['primary_datum_description']); ?></TH>
<?PHP
  if ($categoryInfo ['datum_2_description'] != '')
  {
    print ('    <TH>' . $categoryInfo ['datum_2_description'] . "</TH>\n") ;
    $datumCount += 1;
  }
  if ($categoryInfo ['datum_3_description'] != '')
  {
    print ('    <TH>' . $categoryInfo ['datum_3_description'] . "</TH>\n") ;
    $datumCount += 1;
  }
  ?>
      <TH></TH>
			<TH></TH>
		</TR>
<?PHP

  // Loop over all of the finalists.
  foreach ( $shortlist as $shortlistId => $shortlistInfo )
  {
    print ('          <TR>' . "\n") ;
    print ('            <TD>' . $shortlistInfo ['datum_1'] . '</TD>' . "\n") ;

    if ($categoryInfo ['datum_2_description'] != '')
    {
      print ('            <TD>' . $shortlistInfo ['datum_2'] . '</TD>' . "\n") ;
    }

    if ($categoryInfo ['datum_3_description'] != '')
    {
      print ('            <TD>' . $shortlistInfo ['datum_3'] . '</TD>' . "\n") ;
    }

    print ('            <TD><INPUT TYPE="BUTTON" onclick="shortlistEdit(' . $shortlistInfo ['shortlist_id'] . ',' . $wsfs_retro . ');" VALUE="Edit" /></TD>' . "\n") ;
    print ('            <TD><INPUT TYPE="BUTTON" onclick="shortlistDelete(' . $shortlistInfo ['shortlist_id'] . ',' . $wsfs_retro . ');" VALUE="Delete" /></TD>' . "\n") ;

    print ('          </TR>' . "\n") ;
  }

  ?>
          <TR>
			<TD COLSPAN="<?PHP print($datumCount + 3); ?>"><INPUT TYPE="BUTTON"
				onclick="shortlistAdd(<?PHP print($categoryId).','.$wsfs_retro; ?>);"
				VALUE="Add New Entry" /></TD>
		</TR>
	</TABLE>
</FORM>
<?PHP
}

/**
 * Generate the menu for selecting the category.
 *
 * @param int $categoryId
 *          Hugo Award category ID
 */
function categoryMenu($categoryId)
{
  global $db;

  $categoryData = $db->getCategoryInfo ();

  print ('    <FORM NAME="categories" ID="categories" ACTION="manageShortlist.php" METHOD="post" >' . "\n") ;
  print ('      <INPUT TYPE="HIDDEN" NAME="button_pressed" VALUE="New Category" />' . "\n") ;
  print ('      <SELECT NAME="category_id" onchange="updateNomineeCategory()" >' . "\n") ;

  foreach ( $categoryData as $id => $data )
  {
    print ('        <OPTION VALUE="' . $id . '"') ;
    if ($id == $categoryId)
    {
      print (' SELECTED') ;
    }
    print (' >' . $categoryData [$id] ['name'] . "</OPTION>\n") ;
  }

  print ('      </SELECT>' . "\n") ;
  print ('    </FORM>' . "\n") ;
}

$categoryId = 1;

if ((isset ( $_POST ['button_pressed'] )) and ($_POST ['button_pressed'] == 'Delete'))
{
  $db->deleteFromShortlist ( $_POST ['shortlist_id'] );
  $categoryId = $_POST ['category_id'];
}
elseif ((isset ( $_POST ['button_pressed'] )) and ($_POST ['button_pressed'] == 'New Category'))
{
  $categoryId = $_POST ['category_id'];
}
elseif ((isset ( $_GET ['button_pressed'] )) and ($_GET ['button_pressed'] == 'New Category'))
{
  $categoryId = $_POST ['category_id'];
}
elseif (isset ( $_SESSION ['category_id'] ))
{
  $categoryId = $_SESSION ['category_id'];
}

$_SESSION ['category_id'] = $categoryId;

?>
<HTML>
<HEAD>
<TITLE>Hugo Nomination Administration</TITLE>
<!-- TODO: Rest of HEAD code -->
<SCRIPT TYPE="text/javascript" src="javascript/admin.js"></SCRIPT>
</HEAD>
<BODY>
    <?PHP menu(); ?>
    <BR />
    <?PHP categoryMenu($categoryId); ?>
    <BR />
    <?PHP buildShortlistTable($categoryId); ?>
  </BODY>
</HTML>
