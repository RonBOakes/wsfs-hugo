<?PHP
/*
 * Form for editing the Hugo Award Category details.
 * Copyright (C) 2015-2024, Ronald B. Oakes
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
/*
 * Written by Ronald B. Oakes, copyright 2015-2022
 * Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
 * For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
 * All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
 */
/**
 * Form for editing the Hugo Award Category details.
 */
require_once ('library.php');

$db = new database ( (isset ( $_POST ['retro_hugo'] )) );

/**
 * Load the category data from the $_GET or $_POST and the database.
 */
function loadParams()
{
  global $db;
  global $categoryData;

  $categoryData = array ();

  // If we're coming in from a GET submission, look at the $_GET structure.
  if (isset ( $_GET ['id'] ))
  {
    // If ID is -1, this is an empty category
    if ($_GET ['id'] == - 1)
    {
      $categoryData ['id'] = - 1;
      $categoryData ['name'] = '';
      $categoryData ['description'] = '';
      $categoryData ['ballot_position'] = - 1;
      $categoryData ['primary_datum_description'] = '';
      $categoryData ['datum_2_description'] = '';
      $categoryData ['datum_3_description'] = '';
    }
    else // This is one that should come from the database
    {
      $categoryInfo = $db->getCategoryInfo ();
      $categoryData = $categoryInfo [$_GET ['id']];
    }
  }
  elseif (isset ( $_POST ['id'] )) // Indicates submitted data
  {
    $categoryData ['id'] = $_POST ['id'];
    $categoryData ['name'] = $_POST ['name'];
    $categoryData ['description'] = $_POST ['description'];
    $categoryData ['ballot_position'] = $_POST ['ballot_position'];
    $categoryData ['primary_datum_description'] = $_POST ['primary_datum_description'];
    $categoryData ['datum_2_description'] = $_POST ['datum_2_description'];
    $categoryData ['datum_3_description'] = $_POST ['datum_3_description'];
  }
  else // Another way to get a blank category.
  {
    $categoryData ['id'] = - 1;
    $categoryData ['name'] = '';
    $categoryData ['description'] = '';
    $categoryData ['ballot_position'] = - 1;
    $categoryData ['primary_datum_description'] = '';
    $categoryData ['datum_2_description'] = '';
    $categoryData ['datum_3_description'] = '';
  }
}

/**
 * Builds the category editing form.
 */
function buildCategoryForm()
{
  global $categoryData;

  ?>
<FORM NAME="categoryDetail" ID="categoryDetail"
	ACTION="categoryDetail.php" METHOD="post">
	<INPUT TYPE="HIDDEN" NAME="id"
		VALUE="<?PHP print($catagoryData['id']); ?>" /> <INPUT TYPE="HIDDEN"
		NAME="ballot_position"
		VALUE="<?PHP print($categoryData['ballot_position']); ?>" />
	<TABLE BORDER=1>
		<TR>
			<TD>Name:</TD>
			<TD><INPUT TYPE="TEXT" NAME="name"
				VALUE="<?PHP print($categoryData['name']); ?>" /></TD>
		</TR>
		<TR>
			<TD>Description (HTML):</TD>
			<TD><TEXTAREA NAME="description" COLS=60 ROWS=5><?PHP print($categoryData['description']); ?></TEXTAREA>
			</TD>
		</TR>
		<TR>
			<TD>Primary Datum Description:</TD>
			<TD><INPUT TYPE="TEXT" NAME="primary_datum_description"
				VALUE="<?PHP print($categoryData['primary_datum_description']); ?>" /></TD>
		</TR>
		<TR>
			<TD>Datum 2 Description:</TD>
			<TD><INPUT TYPE="TEXT" NAME="datum_2_description"
				VALUE="<?PHP print($categoryData['datum_2_description']); ?>" /></TD>
		</TR>
		<TR>
			<TD>Datum 3 Description:</TD>
			<TD><INPUT TYPE="TEXT" NAME="datum_3_description"
				VALUE="<?PHP print($categoryData['datum_3_description']); ?>" /></TD>
		</TR>
		<TR>
			<TD />
			<TD><INPUT TYPE="SUBMIT" NAME="button_pushed" VALUE="Update Category" /></TD>
	
	</TABLE>
</FORM>
<?PHP
}

loadParams ();

// If this is the result of pushing the submition button, update the category information.
if (isset ( $_POST ['button_pushed'] ))
{
  $categoryData ['id'] = $db->addUpdateCategory ( $categoryData ['name'], $categoryData ['description'], $categoryData ['ballot_position'], $categoryData ['primary_datum_description'], $categoryData ['datum_2_description'], $categoryData ['datum_3_description'] );
  print ("<P>Category Updated</P>\n") ;
}

?>
<HTML>
<HEAD>
<TITLE>Edit Hugo Category</TITLE>
</HEAD>
<BODY>
    <?PHP buildCategoryForm(); ?>
  


<BODY>

</HTML>