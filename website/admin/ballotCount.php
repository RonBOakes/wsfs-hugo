<?PHP
/*
 * Reports the number of unique nominators for each Hugo Award category.
 * Copyright (C) 2012-2024, Ronald B. Oakes
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
