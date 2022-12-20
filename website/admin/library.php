<?PHP

// Orignally Written by Ronald B. Oakes, copyright 2011 assigned to Chicago Worldcon Bid

/* Written by Ronald B. Oakes, copyright 2014, Updated 2015
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/

chdir('..');
require_once('./database.php');
require_once('./memberValidator.php');
chdir('./admin/');

// Any page using this library will redirect to login.html if not logged in.

$sessionKey = session_id();

$db = new database((isset($_POST['retro_hugo'])));

$commandLineSession = false;

// Admin Library routines

function menu($year = 2015)
{
  print("<!-- \$_POST:\n");
  var_dump($_POST);
  print("\n-->\n");

  $phase = (!isset($_POST['award_phase']) || ($_POST['award_phase'] == 'Vote'))?'Vote':'Nominate';
  $wsfs_retro = (isset($_POST['retro_hugo']));

  $db = new Database($wsfs_retro);

  ?>
  <FORM NAME="topMenu" ID="topMenu" ACTION="index.php" METHOD="post">
    Hugo Award Phase:&nbsp;

<?PHP
  print('        <INPUT TYPE="radio"  onclick="refreshMenu()" NAME="award_phase" VALUE="Nomination"');
  if($phase == 'Nominate')
  {
    print(' CHECKED');
  }
  print(" />Nomination&nbsp;\n");

  print('        <INPUT TYPE="radio"  onclick="refreshMenu()" NAME="award_phase" VALUE="Vote"');
  if($phase == 'Vote')
  {
    print(' CHECKED');
  }
  print(" />Voting<BR/>");


  print('        <INPUT TYPE="checkbox"  onclick="refreshMenu()" NAME="retro_hugo" VALUE="RetroHugo"');
  if ($wsfs_retro)
  {
    print(' CHECKED');
  }
  print(" /> Retro Hugos<BR/>\n");

  if($phase == 'Vote')
  {
?>
    <INPUT TYPE="hidden" NAME="button_pressed" VALUE="" />
    <BR/>
    <INPUT TYPE="button" onclick="viewEditCategories();" value="View and Edit Award Categories" />&nbsp;
    <INPUT TYPE="button" onclick="manageShortlist();" value="Manage Hugo Award Shortlist" />&nbsp;
    <INPUT TYPE="button" onclick="packetDownloadReport();" value="View Packet Download Report" />&nbsp;
    <INPUT TYPE="button" onclick="voterCountReport();" value="View Voter Count Report" />&nbsp;
    <INPUT TYPE="button" onclick="ballotReport();" value="Get Raw Ballots" />&nbsp
    <INPUT TYPE="button" onclick="votingReport();" value="Generate Voting Report" />&nbsp;
    <INPUT TYPE="button" onclick="votingExport();" value="Export Voting Data" />&nbsp;
    <INPUT TYPE="button" onclick="manualVote();" value="Enter Votes at Any Time" />&nbsp;
<?PHP
  }
  else // $phase == 'Nominate'
  {
?>
    <INPUT TYPE="hidden" NAME="button_pressed" VALUE="" />
    <INPUT TYPE="button" onclick="viewEditCategories();" value="View and Edit Award Categories" />&nbsp;
    <INPUT TYPE="button" onclick="viewEditNominee();" value="View and Edit Nominee Information" />&nbsp;
    <INPUT TYPE="button" onclick="nomineeReport();" value="Generate Nomination Report" />&nbsp;
    <INPUT TYPE="button" onclick="regenerateNominees();" value="Regenerate Nomination Data" />&nbsp;
    <INPUT TYPE="button" onclick="manualNominate();" value="Enter Nominations at Any Time" />&nbsp;
    <BR/>
    <INPUT TYPE="button" onclick="ballotCount();" value="Generate Ballot Report" />&nbsp;
    <INPUT TYPE="button" onclick="crossCategory();" value="Generate Cross Category Nomination Report" />&nbsp;
<?PHP
  }
?>
  </FORM>
<?PHP
}

function countTable($categoryId)
{
  global $db;

  $count = $db->categoryBallotCount($categoryId);

?>
    <TABLE BORDER=1>
      <TR>
        <TD>Ballots received for this catagory:</TD>
  <TD><?PHP print($count); ?></TD>
      </TR>
      <TR>
        <TD>5% of ballots</TD>
  <TD><?PHP print(round($count/20)); ?></TD>
      </TR>
    </TABLE>
<?PHP
}

function voteRound($categoryId,$excluded,&$voteDetail,&$maxRank)
{
  global $db;

  if(!is_array($excluded))
  {
    $excluded = explode(',',$excluded);
  }

//  print("<!-- \$excluded\n");
//  var_dump($excluded);
//  print("-->\n");

  $shortList = $db->getShortList($categoryId);

  $voteTally = array();
//  $voteDetail = array();

  foreach ($shortList as $id => $info)
  {
    if(!in_array($id,$excluded))
    {
      $voteTally[$id] = 0;
      $voteDetail[$id] = array();
    }
  }

//  print("<!-- \$voteTally (before count):\n");
//  var_dump($voteTally);
//  print("<!--\n");

  $maxRank = -1;

  $remainingVoters = $db->getRemainingVoters($categoryId,$excluded);

  foreach ($remainingVoters as $voterId)
  {
    $voteData = $db->getCurrentVote($voterId,$categoryId,$excluded);

//    print("\$voterId: $voterId, \$vote: $vote<br/>\n");

    if(($voteData['rank'] > 0) && (!in_array($voteData['short_list_id'],$excluded)))
    {
      if(!isset($voteDetail[$voteData['short_list_id']][$voteData['rank']]))
      {
        $voteDetail[$voteData['short_list_id']][$voteData['rank']] = 0;
      }
      $voteTally[$voteData['short_list_id']] += 1;
      $voteDetail[$voteData['short_list_id']][$voteData['rank']] += 1;
      if($voteData['rank'] > $maxRank)
      {
        $maxRank = $voteData['rank'];
      }
    }
  }

  return $voteTally;
}

  function listBallots($byWhat,$reference,$notApproved)
  {
    global $db;

    print("<!-- listBallots: $byWhat, $reference -->\n");

    if (($byWhat != 'initial') && ($byWhat != 'count'))
    {
      return;
    }

    $nominatorPins = $db->getNominators();


    $memberInfo = getSelectMemberInfo($nominatorPins);
    print("<!-- \$memberInfo:\n");
    var_dump($memberInfo);
    print("\n-->\n");
    $nominatorInfo = array();

    $categoryList = $db->getCategoryInfo();


    foreach($memberInfo as $nominatorId => $nominatorRecord)
    {
      $nominationCount = 0;
      $categoryCount   = 0;

      foreach($categoryList as $categoryId => $categoryRecord)
      {
        $categoryNominationCount = $db->countNominationsByNominator($nominatorId,$categoryId,false);
        $nominationCount += $categoryNominationCount;
        if($categoryNominationCount > 0)
        {
          $categoryCount += 1;
        }

        $memberInfo[$nominatorId]['total_nominations']     = $nominationCount;
        $memberInfo[$nominatorId]['nomination_categories'] = $categoryCount;
      }

      $memberInfo[$nominatorId]['latest_nomination_date'] = $db->getLatestNominationDate($nominatorId);
    }

    if($byWhat == 'initial')
    {
      $preSort = array();
      foreach($memberInfo as $pin => $memberRecord)
      {
        print("<!-- Processing ".$memberRecord['second_name']." [".strtolower(substr($memberRecord['second_name'],0,1))."] -->\n");
        if(strtolower(substr($memberRecord['second_name'],0,1)) == strtolower($reference))
        {
          $preSort[$memberRecord['second_name'].$memberRecord['first_name'].$pin] = $memberRecord;
          print("<!-- Adding $pin to the selected records -->\n");
        }
      }
      ksort($preSort);
      foreach($preSort as $key => $memberRecord)
      {
        $nominatorInfo[$memberRecord['pin']] = $memberRecord;
      }
    }
    else // $byWhat == 'count'
    {
      $preSort = array();
      foreach($memberInfo as $pin => $memberRecord)
      {
        if(preg_match('(\\d+)',$pin,$matches))
        {
          $pin = $matches[0];
        }

        $timeStamp = new DateTime($memberRecord['latest_nomination_date']);
        $key = $timeStamp->getTimestamp().'.'.$pin;
        $preSort[$key] = $memberRecord;
      }
      ksort($preSort,SORT_DESC);
      $countoff = 1;
      foreach($preSort as $key => $memberRecord)
      {
        if(($countoff >= $reference) && ($countoff < $reference+100))
        {
          $nominatorInfo[$memberRecord['pin']] = $memberRecord;
        }
        $countoff += 1;
      }
    }

    print("<!-- \$nominatorInfo:\n");
    var_dump($nominatorInfo);
    print("-->\n");


    $awardCategories = $db->getCategoryInfo();

    foreach($nominatorInfo as $nominatorId => $nominatorRecord)
    {
      $nominationCount = 0;
      $categoryCount   = 0;

      foreach($awardCategories as $categoryId => $categoryRecord)
      {
        $categoryNominationCount = $db->countNominationsByNominator($nominatorId,$categoryId,false);
        $nominationCount += $categoryNominationCount;
        if($categoryNominationCount > 0)
        {
          $categoryCount += 1;
        }

        $nominatorInfo[$nominatorId]['total_nominations']     = $nominationCount;
        $nominatorInfo[$nominatorId]['nomination_categories'] = $categoryCount;
      }
    }

    return $nominatorInfo;
  }


?>
