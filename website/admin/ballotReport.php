<?PHP
/*
 * Show a report of the raw Hugo Award voting ballots.
 * Copyright (C) 2014-2024, Ronald B. Oakes
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
 * Show a report of the raw Hugo Award voting ballots.
 */
session_start ();
require_once ('library.php');

$db = new database ( (isset ( $_POST ['retro_hugo'] )) );

/**
 * Generate a menu for selecting the category being displayed.
 *
 * @param $categoryId int
 *          The currently selected category
 * @return string The HTML for the category selection form.
 */
function categoryMenu($categoryId)
{
  global $db;

  $categoryData = $db->getCategoryInfo ();

  print ('    <FORM NAME="categories" ID="categories" ACTION="ballotReport.php" METHOD="post" >' . "\n") ;
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

/**
 * Build the ballot table
 *
 * @param $categoryId int
 *          The selected Hugo Award category
 * @return string The HTML for the ballot table.
 */
function getBallot($categoryId)
{
  global $db;

  // Get the category information and votes for the selected category.
  $categoryInfo = $db->getCategoryInfo ();

  $voters = $db->getVoters ( $categoryId );

  print ('<p>' . $categoryInfo [$categoryId] ['name'] . ' Votes</p>' . "\n") ;
  print ('<P>Total Ballots Received: ' . count ( $voters ) . '</p>' . "\n") ;

  // Loop over the voters.
  foreach ( $voters as $memberId )
  {
    $votes = $db->getVoteBallot ( $memberId, $categoryId );

    /*
     * print("<!-- \$votes:\n");
     * var_dump($votes);
     * print("\$memberId: $memberId\n");
     * print("\$categoryId: $categoryId\n");
     * print("-->\n");
     */

    print ("<P>PIN: $memberId</P>\n") ;

    print ('<TABLE BORDER=1>' . "\n") ;
    print ('  <TR>' . "\n") ;
    print ('    <TH>' . $categoryInfo [$categoryId] ['primary_datum_description'] . '</TH>' . "\n") ;
    print ('    <TH>Vote</TH>' . "\n") ;
    print ('  </TR>' . "\n") ;

    foreach ( $votes as $nominee => $rank )
    {
      print ('  <TR>' . "\n") ;
      print ('    <TD>' . $nominee . '</TD>' . "\n") ;
      print ('    <TD>' . $rank . '</TD>' . "\n") ;
      print ('  </TR>' . "\n") ;
    }

    print ('</TABLE>' . "\n") ;
    print ('<HR/>' . "\n") ;
  }
}

$categoryId = 1;

if ((isset ( $_POST ['button_pressed'] )) and ($_POST ['button_pressed'] == 'New Category'))
{
  $categoryId = $_POST ['category_id'];
}

?>
<!-- Written by Ronald B. Oakes, copyright 2012-2022
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property. -->
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
    <?PHP getBallot($categoryId);  ?>
  </BODY>
</HTML>
