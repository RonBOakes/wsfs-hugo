<?PHP
/* Written by Ronald B. Oakes, copyright  2015-2022
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
/**
 * Generates a report showing the progress of Hugo Award voting trends.
 */
  session_start();
  require_once('library.php');

  $db = new database();
?>
<HTML>
  <HEAD>
    <TITLE>Hugo Nomination Administration</TITLE>
    <!-- TODO: Rest of HEAD code -->
    <SCRIPT TYPE="text/javascript" src="javascript/admin.js"></SCRIPT>
  </HEAD>
  <BODY>
    <?PHP menu(); ?>
    <BR/>
    <?PHP
      $counts = $db->getVoteCounts();
     ?>
    <P>Total</P>
    <TABLE BORDER=1>
       <TR>
         <TD>Unique Members who have cast at least one vote</TD>
         <TD><?PHP print($counts['unique_members']); ?></TD>
       </TR>
      <TR>
        <TD>Unique IPs used to cast one or more votes</TD>
        <TD><?PHP print($counts['unique_ips']); ?></TD>
      </TR>
    </TABLE>
    <P>By Date</P>
    <TABLE BORDER=1>
      <TR>
        <TH>Date</TH>
        <TH>Unique Members</TH>
        <TH>Unique IPs</TH>
      </TR>
      <?PHP
          $dayCounts = $db->getVoteCountsByDay();

          foreach($dayCounts as $dayIndex => $dayCount)
          {
       ?>

         <TR>
           <TD><?PHP print($dayCount['date']);?></TD>
           <TD><?PHP print($dayCount['unique_members']);?></TD>
           <TD><?PHP print($dayCount['unique_ips']);?></TD>
         </TR>
       <?PHP
          }
       ?>

     </TABLE>
  </BODY>
</HTML>
