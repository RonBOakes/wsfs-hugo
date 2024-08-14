<?PHP
/*
 * Main program for generating the Hugo Award voting results.
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
 * Main program for generating the Hugo Award voting results.
 * This program can be used to cross-check the voting results
 * generated from other programs under the theory that if two programs developed independently by different developers produce
 * the same results, then the results are good and hand checking is not required.
 *
 * This program can be run from the command line. This option is recommended. It is also recommended that, if possible, that
 * it is run on an off-line server with its own copy of the database to avoid impacting any other services using either the web
 * server or the database server. In past experience running on a PC class machine it can take several hours to generate results
 * for an entire Hugo Awards.
 */
define ( 'WSFS_HUGO_FILE_URL', 'http://midamericon2.org/hugo' );
define ( 'WSFS_HUGO_FORM_URL', 'http://midamericon2.org/hugo-awards/voting' );

require_once ('library.php');

$db = new database ( (isset ( $_POST ['retro_hugo'] )) );

$commandLineSession = true;

// Check and see if this is being called from the command line
if (! isset ( $_SERVER ['HTTP_HOST'] )) // Hopefully good
{
  $commandLineSession = true;
  $_POST ['show_extra_detail'] = 1;
}

$pid = - 1;

// If not running at the command line, attempt to fork off and run in the background.
if (! $commandLineSession)
{
  $pid = pcntl_fork ();

  if ($pid == - 1)
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
    fwrite ( $fptr, $html );
    exit ( 0 );
  }
  elseif ($pid != 0)
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
    fwrite ( $fptr, $html );
    exit ( 0 );
  }
  else
  {
    $commandLineProcess = true;
  }
}

/**
 *
 * @param int $categoryId
 *          Database key for the Hugo Award category being processed.
 */
function getVoteReport($categoryId)
{
  global $db, $fptr;

  fwrite ( $fptr, "<!-- Getting the vote report for $categoryId -->\n" );
  // Get the category information and write the header into the report.
  $categoryInfo = $db->getCategoryInfo ();

  fwrite ( $fptr, "<H2>Results for " . $categoryInfo [$categoryId] ['name'] . "</H2>\n" );

  $shortList = $db->getShortList ( $categoryId );

  // Initialize the list of finalists ranked higher to an empty array.
  $rankedHigher = array ();

  $placementTags = array (
      1 => "Hugo Award Winner: ",
      2 => "2nd Place: ",
      3 => "3rd Place: ",
      4 => "4th Place: ",
      5 => "5th Place: ",
      6 => "6th Place: ",
      7 => "7th Place: ",
      8 => "8th Place: "
  );

  $awardWinner = - 1;

  // Loop over the number of expected places.
  for($placement = 1; $placement < count ( $shortList );)
  {
    fwrite ( $fptr, "<!-- Placement: $placement -->\n" );
    fwrite ( $fptr, "<HR/>\n" );

    $done = false;

    // Exclude all of the entries already ranked higher.
    $excluded = $rankedHigher;
    fwrite ( $fptr, "<!-- excluding: " . implode ( $excluded, "," ) . "-->\n" );

    $round = 1;

    do
    {
      $voteDetail = array ();
      $debugText = "";
      // Vote a round.
      $voteTally = voteRound ( $categoryId, $excluded, $voteDetail, $maxRank, $debugText );
      fwrite ( $fptr, $debugText );
      fwrite ( $fptr, "<!-- Raw results of the round:\n" . var_export ( $voteTally, true ) . "\n-->\n" );
      fwrite ( $fptr, "<!-- Vote Detail of the round:\n" . var_export ( $voteDetail, true ) . "\n-->\n" );
      $votesCast = 0;

      // Count the votes cast in this round and determine the majority.
      foreach ( $voteTally as $shortlistId => $count )
      {
        $votesCast += $count;
      }

      $majority = ceil ( $votesCast * 0.5 );

      // Sort the results.
      asort ( $voteTally );
      $voteTally = array_reverse ( $voteTally, true );

      $rankOrder = array_keys ( $voteTally );
      $html = <<<EOT
      <TABLE BORDER=1>
       <TR>
       <TH>Work/Person</TH>
       <TH>Count</TH>
       </TR>

EOT;
      fwrite ( $fptr, $html );
      // Loop over the results and write the counts out.
      foreach ( $voteTally as $shortlistId => $count )
      {
        fwrite ( $fptr, "        <TR>\n" );
        fwrite ( $fptr, "        <TD>" . $shortList [$shortlistId] ['datum_1'] . "</TD>\n" );
        fwrite ( $fptr, "        <TD>" . $count . "</TD>\n" );
        fwrite ( $fptr, "        </TR>\n" );

        // Update the database with the ballot count.
        $db->addBallotCount ( $shortlistId, $placement, $round, $count );
      }
      $html = <<<EOT
      </TABLE>
      <TABLE BORDER = 1>
        <TR>
          <TH>Work/Person</TH>

EOT;
      fwrite ( $fptr, $html );
      for($index = 1; $index <= $maxRank; $index ++)
      {
        fwrite ( $fptr, "<TH>$index</TH>\n" );
      }
      $html = <<<EOT
        </TR>

EOT;
      fwrite ( $fptr, $html );
      // Loop over the finalists and write out the vote detail.
      foreach ( $voteTally as $shortlistId => $count )
      {
        fwrite ( $fptr, "                <TR>\n" );
        fwrite ( $fptr, "                  <TD>" . $shortList [$shortlistId] ['datum_1'] . "</TD>\n" );
        for($index = 1; $index <= $maxRank; $index ++)
        {
          fwrite ( $fptr, '<TD>' . $voteDetail [$shortlistId] [$index] . '</TD>' . "\n" );
        }
        fwrite ( $fptr, "                </TR>" );
      }
      fwrite ( $fptr, "      </TABLE>\n" );

      // Determine the highest
      $highest = array_shift ( $rankOrder );

      // Determine the lowest, breaking ties.
      $lowest = array_pop ( $rankOrder );
      $nextLowest = array_pop ( $rankOrder );
      fwrite ( $fptr, "<!-- highest: $highest, lowest: $lowest, next lowest: $nextLowest --> \n" );
      fwrite ( $fptr, "<!-- VoteTally lowest: " . $voteTally [$lowest] . " Next Lowest: " . $voteTally [$nextLowest] . " -->\n" );
      if ($voteTally [$lowest] == $voteTally [$nextLowest]) // Tie for lowest, need to break
      {
        $tied = array (
            $lowest
        );

        $tieValue = $voteTally [$lowest];

        while ( $voteTally [$nextLowest] == $tieValue )
        {
          $tied [] = $nextLowest;
          $nextLowest = array_pop ( $rankOrder );
        }

        $firstPlaceVotes = array ();
        foreach ( $tied as $inx => $shortListId )
        {
          $firstPlaceVotes [$shortListId] = $db->countFirstPlaceVotes ( $shortListId );
        }

        asort ( $firstPlaceVotes );
        $firstPlaceVoteRankOrder = array_keys ( $firstPlaceVotes );

        $lowest = array_shift ( $firstPlaceVoteRankOrder );
      }

      // If the highest has met the criteria for winning this round - that is has more current top votes than the majority.
      if ($voteTally [$highest] >= $majority)
      {
        $rankOrder = array_keys ( $voteTally ); // Regenerate original
        $highest = array_shift ( $rankOrder ); // Value will be unchanged
        $nextHighest = array_shift ( $rankOrder );

        $placed = array ();
        if ($voteTally [$highest] == $voteTally [$nextHighest]) // Tie
        {
          $placed [] = $highest;
          $tieValue = $voteTally [$highest];

          while ( $voteTalley [$nextHighest] == $tieValue )
          {
            $placed [] = $nextHighest;
            $nextHighest = array_shift ( $rankOrder );
          }
        }
        else
        {
          $placed [] = $highest;
        }
        fwrite ( $fptr, "        <P>Majority Found:\n" );
        foreach ( $placed as $placedId )
        {
          fwrite ( $fptr, $placementTags [$placement] . $shortList [$placedId] ['datum_1'] . "<br/>\n" );
          // Do the No Award test for winners - only needed on first round. TODO: Fix this.
          $noAwardId = $db->getNoAward ( $categoryId );

          $noAwardTestResults = $db->getNoAwardCompairson ( $placedId, $noAwardId );
          fwrite ( $fptr, $noAwardTestResults ["winner"] . " Ballots place <strong>" . $shortList [$placedId] ['datum_1'] . "</strong> higher than <strong>No Award</strong><br/>\n" );
          fwrite ( $fptr, $noAwardTestResults ["noAward"] . " Ballots place <strong>No Award</strong> higher than <strong>" . $shortList [$placedId] ['datum_1'] . "</strong><br/>\n" );
          if ($noAwardTestResults ["winner"] > $noAwardTestResults ["noAward"])
          {
            fwrite ( $fptr, "<strong>" . $shortList [$placedId] ['datum_1'] . "</strong> is confirmed as the winner<br/>\n" );
          }
          else
          {
            fwrite ( $fptr, "<strong>" . $shortList [$placedId] ['datum_1'] . "</strong> <em>fails</em> the No Award test and is not a winner<br/>\n" );
          }
          // Add the count to the database, then add the newly placed into $rankedHigher.
          $db->addBallotCount ( $placedId, $placement, $round + 1, 0 );
          $rankedHigher [] = $placedId;
        }
        fwrite ( $fptr, "        </P>\n" );
        $done = true;
        if ($placement == 1)
        {
          $awardWinner = $highest;
        }
        $placement += count ( $placed );
      }
      else // Highest was winner of this place, eliminate the lowest.
      {
        fwrite ( $fptr, "        <P>No Majority: Eliminting " . $shortList [$lowest] ['datum_1'] . "</P>\n" );
        flush ();

        $excluded [] = $lowest;
      }

      $round += 1;
    } while ( ! $done );
  }
}

// If we get here, we should be writing to a file
$fptr = fopen ( 'voteReport.html', 'w' );

if (! $fptr)
{
  exit ( 1 );
}

$html = <<<EOT
<!-- Written by Ronald B. Oakes, copyright 2011 - 2022
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
 -->
<HTML>
<HEAD>
<TITLE>Hugo Nomination Administration</TITLE>
<!-- TODO: Rest of HEAD code -->
<SCRIPT TYPE="text/javascript" src="javascript/admin.js"></SCRIPT>
             </HEAD>
             <BODY>

<BR/>
EOT;
fwrite ( $fptr, $html );

$categoryData = $db->getCategoryInfo ();

date_default_timezone_set ( 'America/Los_Angeles' );
$startDate = date ( 'l jS \of F Y h:i:s A' );
fwrite ( $fptr, "<p>Report Generation started at: $startDate</p>\n" );

foreach ( $categoryData as $id => $data )
{
  getVoteReport ( $id );
  fwrite ( $fptr, "<HR/>\n" );
}

// Report done, notify hugo admins
$emailText = "A Hugo Award Voting Report was generate starting at $startDate.\n\n";
$emailText .= "It is now available at " . WSFS_HUGO_FILE_URL . "/admin/voteReport.html\n";

// Update the email information to send this information to and from the approprate people.

$email = 'ron@ron-oakes.us';

$sendername = 'hugadmin@midamericon2.org';
$fromemail = 'Hugo Vote Report Generated';
$senderemail = 'hugopin@midamericon2.org';
DEFINE ( 'MAIL_DOMAIN', '@midamericon2.org' );

$headers = "From: " . $sendername . " <" . trim ( $fromemail ) . ">";

fwrite ( $fptr, "<!-- \$headers = $headers -->\n" );

$result = mail ( $email, '2015 Hugo Vote Report', $emailText, $headers );

$html = <<<EOT
</BODY>
</HTML>

EOT;
fwrite ( $fptr, $html );

?>
