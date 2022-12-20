<?PHP
  session_start();
  require_once('library.php');

  $db = new database(getYear());
?>
<!--
/* Written by Ronald B. Oakes, copyright 2014, Updated 2015
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
-->
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
      $counts = $db->getDownloadCounts();
     ?>
    <P>Total</P>
    <TABLE BORDER=1>
       <TR>
         <TD>Total Downlads</TD>
         <TD><?PHP print($counts['total_downloads']); ?></TD>
       </TR>
       <TR>
         <TD>Unique Members who have downloaded one or more packet files</TD>
         <TD><?PHP print($counts['unique_members']); ?></TD>
       </TR>
      <TR>
        <TD>Unique IPs used to download one or more packet files</TD>
        <TD><?PHP print($counts['unique_ips']); ?></TD>
      </TR>
    </TABLE>
    <?PHP
     ?>
    <P>By User Agent (Browser)</P>
    <TABLE BORDER=1>
       <TR>
         <TH>User Agent</TH>
         <TH>Total Downloads</TH>
         <TH>Unique Members</TH>
         <TH>Unique IPs</TH>
       </TR>
       <?PHP
           $userAgentCounts = $db->getDownloadCountsByUserAgent();

           foreach($userAgentCounts as $agentIndex => $agentCount)
           {
             ?>

               <TR>
                 <TD><?PHP print($agentCount['user_agent']);?></TD>
                 <TD><?PHP print($agentCount['total_downloads']);?></TD>
                 <TD><?PHP print($agentCount['unique_members']);?></TD>
                 <TD><?PHP print($agentCount['unique_ips']);?></TD>
               </TR>
             <?PHP
           }
        ?>
    </TABLE>
    <P>By Date</P>
    <TABLE BORDER=1>
      <TR>
        <TH>Date</TH>
        <TH>Total Downlads</TH>
        <TH>Unique Members</TH>
        <TH>Unique IPs</TH>
      </TR>
      <?PHP
          $dayCounts = $db->getDownloadCountsByDay();

          foreach($dayCounts as $dayIndex => $dayCount)
          {
       ?>

         <TR>
           <TD><?PHP print($dayCount['date']);?></TD>
           <TD><?PHP print($dayCount['total_downloads']);?></TD>
           <TD><?PHP print($dayCount['unique_members']);?></TD>
           <TD><?PHP print($dayCount['unique_ips']);?></TD>
         </TR>
       <?PHP
          }
       ?>

     </TABLE>
  </BODY>
</HTML>
