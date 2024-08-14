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
<?PHP
/**
 * Generates the report of ballots that do not belong to PINs currently owned by valid WSFS members.
 */
session_start ();
require_once ('library.php');
?>
 -->
<HTML>
<HEAD>
<TITLE>Orphan Hugo Award Ballots</TITLE>
</HEAD>
<BODY>
  <?PHP
  print ("<!-- Current Directory: ") ;
  print (getcwd ()) ;
  print (" -->\n") ;

  $db = new database();

  // Get the orphan ballot report.
  $orphanBallotInfo = $db->getOrphanBallots ();

  // get the information from the import .csv files related to each PIN
  // NOTE: This is hard coded based on Sasquan procedures.
  foreach ( $orphanBallotInfo as $pin => $info )
  {
    $orphanBallotInfo [$pin] ['Import records'] = `grep -i SQ$pin ../import/*.csv 2>&1`; // */
  }

  print ("    <PRE><BR/>\n") ;
  var_dump ( $orphanBallotInfo );
  print ("\n    </PRE>\n") ;

  ?>
  </BODY>
</HTML>
