<?PHP
/* Written by Ronald B. Oakes
 * Copyright (C) 2015-2024.
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program.  If not, see <http://www.gnu.org/licenses/>.
 */
/**
 * Page for managing the Hugo Award categories.
 */
session_start ();
require_once ('library.php');

$db = new database();

/**
 * Build the table showing the categories.
 */
function buildCategoryTable()
{
  global $db;
  global $categoryInfo;

  ?>
<FORM NAME="editCategories" ID="editCategories"
	ACTION="viewEditCategories.php" METHOD="post">
	<INPUT TYPE="HIDDEN" NAME="button_pressed" VALUE="Edit" />
	<TABLE BORDER=1>
		<TR>
			<TH>Category</TH>
			<TH>Ballot Position</TH>
			<TH>1st Datum</TH>
			<TH>2nd Datum</TH>
			<TH>3rd Datum</TH>
			<TH />
		</TR>
<?PHP
  $categoryOrder = array ();

  foreach ( $categoryInfo as $id => $info )
  {
    $categoryOrder [$categoryInfo [$id] ['ballot_position']] = $id;
  }

  foreach ( $categoryOrder as $ballotOrder => $id )
  {
    print ("        <TR>\n") ;
    print ('          <TD>' . $categoryInfo [$id] ['name'] . "</TD>\n") ;
    print ('          <TD><INPUT TYPE="TEXT" NAME="ballot_order_' . $id . '" VALUE="' . $ballotOrder . '" /></TD>' . "\n") ;
    print ('          <TD>' . $categoryInfo [$id] ['primary_datum_description'] . "</TD>\n") ;
    print ('        <TD>' . $categoryInfo [$id] ['datum_2_description'] . "</TD>\n") ;
    print ('        <TD>' . $categoryInfo [$id] ['datum_3_description'] . "</TD>\n") ;
    print ('          <TD><INPUT TYPE="button" onclick="editCategory(' . $id . ');" VALUE="Edit Category" /></TD>' . "\n") ;
    print ("        </TR>\n") ;
  }
  ?>
        <TR>
			<TD />
			<TD><INPUT TYPE="button" onclick="updateBallotOrder();"
				VALUE="Update Ballot Order" /></TD>
			<TD COLSPAN=3 />
			<TD><INPUT TYPE="button" onclick="editCategory(-1);"
				VALUE="Add New Category" /></TD>
		</TR>
	</TABLE>
</FORM>
<?PHP
}

$categoryInfo = $db->getCategoryInfo ();

print ("<!-- \$_POST:\n") ;
var_dump ( $_POST );
print ("-->\n") ;

if ((isset ( $_POST ['button_pressed'] )) and ($_POST ['button_pressed'] == 'Update Ballot Order'))
{
  foreach ( $categoryInfo as $id => $info )
  {
    $db->addUpdateCategory ( $categoryInfo [$id] ['name'], $categoryInfo [$id] ['description'], $_POST ['ballot_order_' . $id], $categoryInfo [$id] ['primary_datum_description'], $categoryInfo [$id] ['datum_2_description'], $categoryInfo [$id] ['datum_3_description'] );
  }

  $categoryInfo = $db->getCategoryInfo ();
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
