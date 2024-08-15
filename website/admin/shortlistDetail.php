<!--
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
 -->
<?PHP
/**
 * Page for managing the Hugo Finalist.
 */
require_once ('library.php');
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
if (isset ( $_POST ))
{
  print ("<!-- \$_POST:\n") ;
  var_dump ( $_POST );
  print ("-->\n") ;
}

if (isset ( $_GET ))
{
  print ("<!-- \$_GET:\n") ;
  var_dump ( $_GET );
  print ("-->\n") ;
}

$wsfs_retro = 0;

if (isset ( $_GET ['wsfs_retro'] ))
{
  $wsfs_retro = $_GET ['wsfs_retro'];
}
elseif (isset ( $_POST ['wsfs_retro'] ))
{
  $wsfs_retro = $_POST ['wsfs_retro'];
}

$db = new Database ( $wsfs_retro );

if (! $wsfs_retro)
{
  $wsfs_retro = 0;
}

/**
 * Load the parameters from $_GET or $_POST and the database
 */
function loadParams()
{
  global $db;
  global $wsfs_retro;
  global $shortlistData;
  global $categoryData;

  $shortlistData = array ();

  if (isset ( $_GET ['id'] ))
  {
    if (($_GET ['id'] == - 1) && (! isset ( $_GET ['categoryId'] )))
    {
      $shortlistData ['shortlist_id'] = - 1;
      $shortlistData ['category_id'] = - 1;
      $shortlistData ['datum_1'] = '';
      $shortlistData ['datum_2'] = '';
      $shortlistData ['datum_3'] = '';

      $categoryData ['name'] = 'error';
      $categoryData ['primary_datum_description'] = 'error';
      $categoryData ['datum_2_description'] = 'error';
      $categoryData ['datum_3_description'] = 'error';
    }
    elseif ($_GET ['id'] == - 1)
    {
      $categoryInfo = $db->getCategoryInfo ();
      $categoryData = $categoryInfo [$_GET ['categoryId']];
      print ("<!-- \$categoryData LOOK HERE\n") ;
      var_dump ( $categoryData );
      print ("-->\n") ;

      $shortlistData ['shortlist_id'] = - 1;
      $shortlistData ['category_id'] = $_GET ['categoryId'];
      $shortlistData ['datum_1'] = '';
      $shortlistData ['datum_2'] = '';
      $shortlistData ['datum_3'] = '';
    }
    else
    {
      $shortlistData = $db->getShortlistInfo ( $_GET ['id'] );
      $categoryInfo = $db->getCategoryInfo ();
      $categoryData = $categoryInfo [$shortlistData ['category_id']];
    }
  }
  elseif (isset ( $_POST ['shortlist_id'] )) // Indicates submitted data
  {
    $shortlistData ['shortlist_id'] = $_POST ['shortlist_id'];
    $shortlistData ['category_id'] = $_POST ['category_id'];
    $shortlistData ['datum_1'] = $_POST ['datum_1'];
    $shortlistData ['datum_2'] = $_POST ['datum_2'];
    $shortlistData ['datum_3'] = $_POST ['datum_3'];

    $categoryInfo = $db->getCategoryInfo ();
    $categoryData = $categoryInfo [$shortlistData ['category_id']];
  }
  else
  {
    $shortlistData ['shortlist_id'] = - 1;
    $shortlistData ['category'] = '';
    $shortlistData ['datum_1'] = '';
    $shortlistData ['datum_2'] = '';
    $shortlistData ['datum_3'] = '';

    $categoryData ['name'] = 'error';
    $categoryData ['primary_datum_description'] = 'error';
    $categoryData ['datum_2_description'] = 'error';
    $categoryData ['datum_3_description'] = 'error';
  }
}

/**
 * Build the form for editing the finalist.
 */
function buildShortlistForm()
{
  global $shortlistData;
  global $categoryData;
  global $wsfs_retro;

  ?>

<FORM NAME="shortlistDetail" ID="shortlistDetail"
	ACTION="shortlistDetail.php" METHOD="post">
	<INPUT TYPE="HIDDEN" NAME="shortlist_id"
		VALUE="<?PHP print($shortlistData['shortlist_id']); ?>" /> <INPUT
		TYPE="HIDDEN" NAME="category_id"
		VALUE="<?PHP print($shortlistData['category_id']); ?>" /> <INPUT
		TYPE="HIDDEN" NAME="wsfs_retro" VALUE="<?PHP print($wsfs_retro); ?>" />
	<TABLE BORDER="1">
		<TR>
			<TD>Hugo Award Category:</TD>
			<TD><INPUT TYPE="TEXT" NAME="category"
				VALUE="<?PHP print($categoryData['name']); ?>" READONLY /></TD>
		</TR>
		<TR>
			<TD><?PHP print(trim($categoryData['primary_datum_description'])); ?>:</TD>
			<TD><INPUT TYPE="TEXT" NAME="datum_1"
				VALUE="<?PHP print($shortlistData['datum_1']); ?>" SIZE="80" /></TD>
		</TR>
		<TR>
			<TD><?PHP print(trim($categoryData['datum_2_description'])); ?>:</TD>
			<TD><INPUT TYPE="TEXT" NAME="datum_2"
				VALUE="<?PHP print($shortlistData['datum_2']); ?>" SIZE="80" /></TD>
		</TR>
		<TR>
			<TD><?PHP print(trim($categoryData['datum_3_description'])); ?>:</TD>
			<TD><INPUT TYPE="TEXT" NAME="datum_3"
				VALUE="<?PHP print($shortlistData['datum_3']); ?>" SIZE="80" /></TD>
		</TR>
		<TR>
			<TD />
			<TD><INPUT TYPE="SUBMIT" NAME="button_pushed"
				VALUE="Update Shortlist" /></TD>
	
	</TABLE>
</FORM>
<?PHP
}

loadParams ();

print ("<!-- \$shortlistData\n") ;
var_dump ( $shortlistData );
print ("-->\n") ;
print ("<!-- \$categoryData\n") ;
var_dump ( $categoryData );
print ("-->\n") ;

if (isset ( $_POST ['button_pushed'] ))
{
  $db->addUpdateShortList ( $shortlistData ['category_id'], $shortlistData ['datum_1'], $shortlistData ['datum_2'], $shortlistData ['datum_3'] );
  print ("<P>Shortlist Nominee Updated</P>\n") ;
}

?>
<BODY
	<?PHP
if (isset ( $_POST ['button_pushed'] ))
{
  print (' ONLOAD="refreshParent();closeMe();" ') ;
}
?>>
    <?PHP buildShortlistForm(); ?>



<BODY>

</HTML>
