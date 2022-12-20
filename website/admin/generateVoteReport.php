<?PHP
/* Written by Ronald B. Oakes, copyright 2015
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
define('WSFS_HUGO_FILE_URL','http://midamericon2.org/hugo');
define('WSFS_HUGO_FORM_URL', 'http://midamericon2.org/hugo-awards/voting');

require_once('library.php');

$db = new database((isset($_POST['retro_hugo'])));

$commandLineSession = true;

if(!isset($_SERVER['HTTP_HOST'])) // Hopefully good
{
  $commandLineSession = true;
  $_POST['show_extra_detail'] = 1;
}

$pid = -1;

if(!$commandLineSession)
{
  $pid = pcntl_fork();

  if($pid == -1)
  {
    $html = <<<EOT
<HTML>
      <HEAD>
          <TITLE>Hugo Vote Report Error</TITLE>
      </HEAD>
      <BODY>
          <P>ERROR: Unable to fork child process.  Report not being generated</P>
      </BODY>
</HTML>
EOT;
    fwrite($fptr,$html);
    exit(0);
  }
  elseif($pid != 0)
  {
    $html = <<<EOT
<HTML>
      <HEAD>
          <TITLE>Hugo Vote Report Generation Started</TITLE>
      </HEAD>
      <BODY>
          <P>Hugo Vote Report (hopefully) Started.  The process will email when done</P>
      </BODY>
</HTML>
EOT;
    fwrite($fptr,$html);
    exit(0);
  }
  else
  {
    $commandLineProcess = true;
  }
}

function getVoteReport($categoryId)
{
  global $db, $fptr;

  $categoryInfo = $db->getCategoryInfo();

  fwrite($fptr,"<H2>Results for ".$categoryInfo[$categoryId]['name']."</H2>\n");

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
    fwrite($fptr,"<HR/>\n");

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
      $html = <<<EOT
      <TABLE BORDER=1>
       <TR>
       <TH>Work/Person</TH>
       <TH>Count</TH>
       </TR>

EOT;
      fwrite($fptr,$html);

      foreach($voteTally as $shortlistId => $count)
      {
        fwrite($fptr,"        <TR>\n");
        fwrite($fptr,"        <TD>".$shortList[$shortlistId]['datum_1']."</TD>\n");
        fwrite($fptr,"        <TD>".$count."</TD>\n");
        fwrite($fptr,"        </TR>\n");

EOT;
        fwrite($fptr,$html);

        $db->addBallotCount($shortlistId,$placement,$round,$count);
      }
      $html = <<<EOT
      </TABLE>
      <TABLE BORDER = 1>
        <TR>
          <TH>Work/Person</TH>

EOT;
      fwrite($fptr,$html);
        for($index = 1; $index <= $maxRank; $index++)
        {
          fwrite($fptr,"<TH>$index</TH>\n");
        }
        $html = <<<EOT
        </TR>

EOT;
        fwrite($fptr,$html);
        foreach($voteTally as $shortlistId => $count)
        {
          fwrite(fptr,"                <TR>\n");
          fwrite(fptr,"                  <TD>".$shortlist[$shortlistId]['datum_1']."</TD>\n");
          for($index = 1; $index <= $maxRank; $index++)
          {
            fwrite(fptr,'<TD>'.$voteDetail[$shortlistId][$index].'</TD>'."\n");
          }
          fwrite(fptr,"                </TR>");
        }
        fwrite($fptr,"      </TABLE>\n");

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
        fwrite($fptr,"        <P>Majority Found:\n");
        foreach($placed as $placedId)
        {
          fwrite($fptr,$placementTags[$placement].$shortList[$placedId]['datum_1']."<br/>\n");
          // Do the No Award test for winners
          $noAwardId = $db->getNoAward($categoryId);

          $noAwardTestResults = $db->getNoAwardCompairson($placedId,$noAwardId);
          fwrite($fptr,$noAwardTestResults["winner"]." Ballots place <strong>".$shortList[$placedId]['datum_1']."</strong> higher than <strong>No Award</strong><br/>\n");
          fwrite($fptr,$noAwardTestResults["noAward"]." Ballots place <strong>No Award</strong> higher than <strong>".$shortList[$placedId]['datum_1']."</strong><br/>\n");
          if($noAwardTestResults["winner"] > $noAwardTestResults["noAward"])
          {
            fwrite($fptr,"<strong>".$shortList[$placedId]['datum_1']."</strong> is confirmed as the winner<br/>\n");
          }
          else
          {
            fwrite($fptr,"<strong>".$shortList[$placedId]['datum_1']."</strong> <em>fails</em> the No Award test and is not a winner<br/>\n");
          }

          $db->addBallotCount($placedId,$placement,$round+1,0);
          $rankedHigher[] = $placedId;
        }
        fwrite($fptr,"        </P>\n");
        $done = true;
        if($placement == 1)
        {
          $awardWinner = $highest;
        }
        $placement += count($placed);
      }
      else
      {
        fwrite($fptr,"        <P>No Majority: Eliminting ".$shortList[$lowest]['datum_1']."</P>\n");
        flush();

        $excluded[] = $lowest;
      }

      $round += 1;
    }
    while (!$done);
  }
}

// If we get here, we should be writing to a file
$fptr = fopen('voteReport.html','w');

if(!$fptr)
{
  exit(1);
}


$html = <<<EOT
<!-- Writen by Ronald B. Oakes, Copyright 2011 assigned to Chicago Worldcon Bid Inc. -->
<HTML>
<HEAD>
<TITLE>Hugo Nomination Administration</TITLE>
<!-- TODO: Rest of HEAD code -->
<SCRIPT TYPE="text/javascript" src="javascript/admin.js"></SCRIPT>
             </HEAD>
             <BODY>

<BR/>
EOT;
fwrite($fptr,$html);


  $categoryData = $db->getCategoryInfo();

  date_default_timezone_set('America/Los_Angeles');
  $startDate = date('l jS \of F Y h:i:s A');
  fwrite($fptr,"<p>Report Generation started at: $startDate</p>\n");

  foreach($categoryData as $id => $data)
  {
    getVoteReport($id);
    fwrite($fptr,"<HR/>\n");
  }

  // Report done, notify hugo admins
  $emailText  = "A Hugo Award Voting Report was generate starting at $startDate.\n\n";
  $emailText .= "It is now available at ".WSFS_HUGO_FILE_URL."/admin/voteReport.html\n";

  $email = 'ron@ron-oakes.us';

  $sendername = 'hugadmin@midamericon2.org';
  $fromemail  = 'Hugo Vote Report Generated';
  $senderemail = 'hugopin@midamericon2.org';
  DEFINE('MAIL_DOMAIN','@midamericon2.org');

  $headers = "From: ".$sendername." <".trim($fromemail).">";

  fwrite($fptr,"<!-- \$headers = $headers -->\n");

  $result = mail($email,'2015 Hugo Vote Report',$emailText,$headers);


$html = <<<EOT
</BODY>
</HTML>

EOT;
fwrite($fptr,$html);

?>
