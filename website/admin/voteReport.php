<?PHP
/* Written by Ronald B. Oakes, copyright 2014-2022
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
/**
 * Main program for generating the Hugo Award voting results.
 * This program can be used to cross-check the voting results
 * generated from other programs under the theory that if two programs developed independently by different developers produce
 * the same results, then the results are good and hand checking is not required.
 *
 * This program can be run from the command line. This option is recommended. It is also recommended that, if possible, that
 * it is run on an off-line server with its own copy of the database to avoid impacting any other services using either the web
 * server or the database server. In past experience running on a PC class machine it can take several hours to generate results
 * for an entire Hugo Awards.
 *
 * @warning It is not known as of December 23, 2022 if this is the working version of this code.
 */

// Update these per the current settings for the administering Worldcon's website
define('WSFS_HUGO_VOTE_URL','http://mac2-hugo01.midamericon2.org/vote.php');
define('WSFS_HUGO_FILE_URL','http://mac2-hugo01.midamericon2.org');

session_start();
require_once('library.php');

$db = null;

$commandLineSession = false;

if(!isset($_SERVER['HTTP_HOST'])) // Hopefully good
{
  $commandLineSession = true;
  $_POST['show_extra_detail'] = 1;
  $db = new database(in_array('retro',$argv));
}
else
{
  $db = new database(false);
}

function categoryMenu($categoryId)
{
  global $db;

  $categoryData = $db->getCategoryInfo();

  print('    <FORM NAME="categories" ID="categories" ACTION="voteReport.php" METHOD="post" >'."\n");
  print('      <INPUT TYPE="HIDDEN" NAME="button_pressed" VALUE="New Category" />'."\n");
  print('      <SELECT NAME="category_id" onchange="updateNomineeCategory()" >'."\n");

  foreach ($categoryData as $id => $data)
  {
    print('        <OPTION VALUE="'.$id.'"');
    if ($id == $categoryId)
    {
      print(' SELECTED');
    }
    print(' >'.$categoryData[$id]['name']."</OPTION>\n");
  }

  print('      </SELECT>'."\n");
  print('      <BR/>'."\n");
  print('      <INPUT TYPE="checkbox" NAME="show_extra_detail" onclick="updateNomineeCategory()"');
  if($_POST['show_extra_detail'])
  {
    print(' CHECKED ');
  }
  print('/>Show Extra Detail (Count of ballot position)<br/>'."\n");
  print('    </FORM>'."\n");
}


function getVoteReport($categoryId)
{
  global $db;

  $categoryInfo = $db->getCategoryInfo();

  print("<H2>Results for ".$categoryInfo[$categoryId]['name']."</H2>\n");

  $shortList = $db->getShortList($categoryId);

  $rankedHigher = array();

  $placementTags = array(1 => "Hugo Award Winner: ",
                         2 => "2nd Place: ",
                         3 => "3rd Place: ",
                         4 => "4th Place: ",
                         5 => "5th Place: ",
                         6 => "6th Place: ",
                         7 => "7th Place: ",
                         8 => "8th Place: ");

  $awardWinner = -1;

  for ($placement = 1 ; $placement < count($shortList) ; )
  {
    print("<HR/>\n");

    $done = false;

    $excluded = $rankedHigher;

    $round = 1;

    do
    {
      $voteDetail = array();
      $voteTally = voteRound($categoryId,$excluded,$voteDetail,$maxRank);

      $votesCast = 0;

      foreach($voteTally as $shortlistId => $count)
      {
        $votesCast += $count;
      }

      $majority = ceil($votesCast * 0.5);

      asort($voteTally);
      $voteTally = array_reverse($voteTally,true);

      $rankOrder = array_keys($voteTally);
      ?>
      <TABLE BORDER=1>
       <TR>
       <TH>Work/Person</TH>
       <TH>Count</TH>
       </TR>
       <?PHP

      foreach($voteTally as $shortlistId => $count)
      {
        ?>
        <TR>
        <TD><?PHP print($shortList[$shortlistId]['datum_1']); ?></TD>
        <TD><?PHP print($count); ?></TD>
        </TR>
        <?PHP

        $db->addBallotCount($shortlistId,$placement,$round,$count);
      }
      ?>
      </TABLE>
      <?PHP

      if($_POST['show_extra_detail'])
      {
        ?>
      <TABLE BORDER = 1>
        <TR>
          <TH>Work/Person</TH>
          <?PHP
        for($index = 1; $index <= $maxRank; $index++)
        {
          print("<TH>$index</TH>\n");
        }
          ?>
        </TR>
          <?PHP
            foreach($voteTally as $shortlistId => $count)
            {
              ?>
                <TR>
                  <TD><?PHP print($shortList[$shortlistId]['datum_1']); ?></TD>
                  <?PHP
                    for($index = 1; $index <= $maxRank; $index++)
                    {
                      print('<TD>'.$voteDetail[$shortlistId][$index].'</TD>'."\n");
                    }
                  ?>
                </TR>
              <?PHP
            }
              ?>
      </TABLE>
        <?PHP
      }

      $highest = array_shift ($rankOrder);

      $lowest     = array_pop($rankOrder);
      $nextLowest = array_pop($rankOrder);

      if ($voteTally[$lowest] == $voteTally[$nextLowest])  // Tie for lowest, need to break
      {
        $tied = array($lowest);

        $tieValue = $voteTally[$lowest];

        while ($voteTally[$nextLowest] == $tieValue)
        {
          $tied[] = $nextLowest;
          $nextLowest = array_pop($rankOrder);
        }

        $firstPlaceVotes = array();
        foreach($tied as $inx => $shortListId)
        {
          $firstPlaceVotes[$shortListId] = $db->countFirstPlaceVotes($shortListId);
        }

        asort($firstPlaceVotes);
        $firstPlaceVoteRankOrder = array_keys($firstPlaceVotes);

        $lowest = array_shift($firstPlaceVoteRankOrder);
      }



      if ($voteTally[$highest] >= $majority)
      {
        $rankOrder   = array_keys($voteTally);  // Regenerate original
        $highest     = array_shift($rankOrder); // Value will be unchanged
        $nextHighest = array_shift($rankOrder);

        $placed = array();
        if ($voteTally[$highest] == $voteTally[$nextHighest]) // Tie
        {
          $placed[] = $highest;
          $tieValue = $voteTally[$highest];

          while ($voteTalley[$nextHighest] == $tieValue)
          {
            $placed[] = $nextHighest;
            $nextHighest = array_shift($rankOrder);
          }
        }
        else
        {
          $placed[] = $highest;
        }
        ?>
        <P>Majority Found:
        <?PHP
        foreach($placed as $placedId)
        {
          print($placementTags[$placement].$shortList[$placedId]['datum_1']."<br/>\n");
          // Do the No Award test for winners
          $noAwardId = $db->getNoAward($categoryId);

          $noAwardTestResults = $db->getNoAwardCompairson($placedId,$noAwardId);
          print($noAwardTestResults["winner"]." Ballots place <strong>".$shortList[$placedId]['datum_1']."</strong> higher than <strong>No Award</strong><br/>\n");
          print($noAwardTestResults["noAward"]." Ballots place <strong>No Award</strong> higher than <strong>".$shortList[$placedId]['datum_1']."</strong><br/>\n");
          if($noAwardTestResults["winner"] > $noAwardTestResults["noAward"])
          {
            print("<strong>".$shortList[$placedId]['datum_1']."</strong> is confirmed as the winner<br/>\n");
          }
          else
          {
            print("<strong>".$shortList[$placedId]['datum_1']."</strong> <em>fails</em> the No Award test and is not a winner<br/>\n");
          }

          $db->addBallotCount($placedId,$placement,$round+1,0);
          $rankedHigher[] = $placedId;
        }
        ?>
        </P>
        <?PHP
        $done = true;
        if($placement == 1)
        {
          $awardWinner = $highest;
        }
        $placement += count($placed);
      }
      else
      {
        ?>
        <P>No Majority: Eliminting <?PHP print($shortList[$lowest]['datum_1']); ?></P>
        <?PHP
        flush();

        $excluded[] = $lowest;
      }

      $round += 1;
    }
    while (!$done);
  }
}

$categoryId = 1;

if ((isset($_POST['button_pressed'])) and ($_POST['button_pressed'] == 'New Category'))
{
  $categoryId = $_POST['category_id'];
}


?>
<!-- Writen by Ronald B. Oakes, Copyright 2016 All rights assigned to Worldcon Intellectual Property, Inc.. -->
<HTML>
<HEAD>
<TITLE>Hugo Nomination Administration</TITLE>
<!-- TODO: Rest of HEAD code -->
<SCRIPT TYPE="text/javascript" src="javascript/admin.js"></SCRIPT>
             </HEAD>
             <BODY>
             <?PHP
if(!$commandLineSession)
{
  menu();
}
?>
<!-- $_POST:
<?PHP var_dump($_POST); ?>
-->
<BR/>
<?PHP
  if(!$commandLineSession)
  {
    categoryMenu($categoryId);
  }
?>
<BR/>
<?PHP

if($commandLineSession)
{

  print("<!-- \$argv:\n");
  var_dump($argv);
  print("\n-->\n");

  $categoryData = $db->getCategoryInfo();

  if($argc > 0)
  {
    $categoryDataTemp = $categoryData;
    $categoryData = array();

    foreach($categoryDataTemp as $id => $data)
    {
      print("<!-- Checking \$id = $id -->\n");

      if(in_array($id,$argv))
      {
        $categoryData[$id] = $data;
      }
    }
  }

  date_default_timezone_set('America/Los_Angeles');
  $startDate = date('l jS \of F Y h:i:s A');
  print("<p>Report Generation started at: $startDate</p>\n");

  foreach($categoryData as $id => $data)
  {
    getVoteReport($id);
    print("<HR/>\n");
  }

  $endDate = date('l jS \of F Y h:i:s A');
  print("<p>Report Generation started at: $endDate</p>\n");

  // Report done, notify hugo admins
  $emailText  = "A Hugo Award Voting Report was generate starting at $startDate.\n\n";
  $emailText .= "It is now available\n";

  $email = 'ron@ron-oakes.us';

  $sendername = 'hugadmin@midamericon2.org';
  $fromemail  = 'Hugo Vote Report Generated';
  $senderemail = 'hugocxxxxxxxxxxxxxxxxxxpin@midamericon2.org';
  DEFINE('MAIL_DOMAIN','@midamericon2.org');

  $headers = "From: ".$sendername." <".trim($fromemail).">";

  print("<!-- \$headers = $headers -->\n");

  $result = mail($email,'2015 Hugo Vote Report',$emailText,$headers);

}
else
{
  getVoteReport($categoryId);
}

?>
</BODY>
</HTML>
