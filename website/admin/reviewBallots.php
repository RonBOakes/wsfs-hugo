<?PHP
// Originally Writen by Ronald B. Oakes, Copyright 2011 assigned to Chicago Worldcon Bid Inc.

/* Written by Ronald B. Oakes, copyright 2014-2022
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
/**
 * Provides the main page for managing the nomination nomrmalization and approval process
 * @warning @warning The nomination normalization functionality has not been used since Chicon 7, if then.
 * 
 * This whole page relies on Chicon 7 membership data formats and database functions that no longer exist.
 */

  session_start();
  require_once('library.php');

  confirmPrivlidge();

  $db = new Database();

  /**
   * Get the memberships for the current set of nominations to look at.
   * @warning This function will fail due to calling a missing function.
   * @return array A List of members
   */
  function fetchMemberships()
  {
    global $db;
    global $initial;
    global $startingEntry;
    global $memberList;
    global $notApproved;

    print("<!-- \$startingEntry: $startingEntry -->\n");

    if($memberList == '')
    {
      if($startingEntry == -1)
      {
        $membershipList = $db->listBallots('initial',$initial,$notApproved);
      }
      else
      {
        $membershipList = $db->listBallots('count',$startingEntry,$notApproved);
      }
    }
    elseif ($_POST['search_nominations'] == '')
    {
      $membershipList = $db->listSelectNominators($memberList);
    }
    else // Search by nominations
    {
      $membershipList = $db->listSelectNominatorsByNomination($memberList,$_POST['search_nominations']);
    }

    return $membershipList;
  }

  /**
   * Build the form used to search by member name.
   */
  function buildSearchForm()
  {
?>
    <FORM NAME="searchForm" ID="searchForm" ACTION="reviewBallots.php" METHOD="POST">
      <TABLE BORDER=1>
        <TR>
    <TD COLSPAN=2><P ALIGN="CENTER">Search</P></TD>
  </TR>
  <TR>
    <TD>Last&nbsp;Name:&nbsp;<INPUT TYPE="TEXT" NAME="search_last_name" /></TD>
    <TD>First&nbsp;Name:&nbsp;<INPUT TYPE="TEXT" NAME="search_first_name" /></TD>
  </TR>
  <TR>
    <TD>E-Mail&nbsp;Address:&nbsp;<INPUT TYPE="TEXT" NAME="search_email" /></TD>
    <TD>
      <TABLE>
        <TR><TD>Membership&nbsp;Number:&nbsp;<INPUT TYPE="TEXT" NAME="search_membership_no" /></TD></TR>
        <TR>
          <TD>
      Convention:&nbsp;
      <EM>Chicon 7</EM>&nbsp;<INPUT TYPE="RADIO" NAME="search_field" VALUE="chicago_number" />&nbsp;
      <EM>Renovation</EM>&nbsp;<INPUT TYPE="RADIO" NAME="search_field" VALUE="reno_number" />&nbsp;
      <EM>LoneStarCon 3</EM>&nbsp;<INPUT TYPE="RADIO" NAME="search_field" VALUE="texas_number" />
    </TD>
        </TR>
      </TABLE>
    </TD>
  </TR>
  <TR><TD COLSPAN="2">Work/Person Nominated:&nbsp;<INPUT TYPE="TEXT" NAME="search_nominations" /></TD></TR>
  <TR>
    <TD><INPUT TYPE="SUBMIT" NAME="button_pressed" VALUE="Search" /></TD>
    <TD/>
  </TR>
      </TABLE>
    </FORM>
<?PHP
  }

  /**
   * Build the membership table
   */
  function buildMembershipTable()
  {
    global $db;
    global $_POST;
    global $initial;
    global $count;
    global $memberList;

    $membershipList = fetchMemberships();
    $categories = $db->getCategoryInfo();

?>
    <FORM NAME="ballotReview" ID="ballotReview" ACTION="reviewBallots.php" METHOD="POST">
      <INPUT TYPE="HIDDEN" NAME="last_initial" VALUE="<?PHP print($initial); ?>" />
      <INPUT TYPE="HIDDEN" NAME="button_pressed" VALUE="Review Ballot" />
      <INPUT TYPE="HIDDEN" NAME="count" VALUE="<?PHP print($count); ?>" />
      <INPUT TYPE="HIDDEN" NAME="review_id" VALUE="-1" />
      <INPUT TYPE="HIDDEN" NAME="member_list" VALUE="<?PHP print($memberList); ?>"  />
      <table id="membership_table" border=1 class="sortable">
         <TR>
     <?PHP
    if($_POST['search_nomination'] != '')
    {
      print('    <TH>Matching Award Categories</TH>'."\n");
    }
     ?>
     <TH>First Name</TH>
     <TH>Last Name</TH>
     <TH>E-Mail Address</TH>
     <TH>Nomination Date</TH>
     <TH>Total Nominations Made</TH>
     <TH>Categories Nominated</TH>
     <TH>Reviewed On</TH>
     <TH>Reviewed By</TH>
     <TH/>
     <TH>Chicon 7 Membership Number</TH>
     <TH>Renovation Membership Number</TH>
     <TH>LoneStarCon 3 Membership Number</TH>
   </TR>
<?PHP

    foreach($membershipList as $id => $membershipRecord)
    {
      if($membershipRecord['total_nominations'] > 0)
      {
        print("         <TR>\n");
  if($_POST['search_nominations'] != '')
  {
    print('           <TD>'."\n");
    foreach($membershipRecord['matching_categories'] as $categoryName)
    {
      print('             '.$categoryName."<BR/>\n");
    }
    print('           </TD>'."\n");
  }
        print('           <TD>'.$membershipRecord['first_name'].'</TD>'."\n");
        print('           <TD>'.$membershipRecord['last_name'].'</TD>'."\n");
        print('           <TD>'.$membershipRecord['email_address'].'</TD>'."\n");
  print('           <TD>'.$membershipRecord['nomination_date'].'</TD>'."\n");
        print('           <TD>'.$membershipRecord['total_nominations'].'</TD>'."\n");
        print('           <TD>'.$membershipRecord['nomination_categories'].'</TD>'."\n");
        print('           <TD>'.$membershipRecord['review_date'].'</TD>'."\n");
        print('           <TD>'.$membershipRecord['reviewed_by'].'</TD>'."\n");
        print('           <TD><INPUT TYPE="BUTTON" onclick="reviewBallot('.$id.');" VALUE="Review Ballot" /></TD>'."\n");
        print('           <TD>'.$membershipRecord['chicago_number'].'</TD>'."\n");
        print('           <TD>'.$membershipRecord['reno_number'].'</TD>'."\n");
        print('           <TD>'.$membershipRecord['texas_number'].'</TD>'."\n");
        print("         </TR>\n");
      }
    }

?>
      </TABLE>
    </FORM>
<?PHP

  }

  /**
   * Builds a menu for searching by last initial
   * @param string $lastInitial The initial from the family name.
   */
  function initialMenu($lastInitial)
  {
    print('    <FORM NAME="initials" ID="initials" ACTION="reviewBallots.php" METHOD="post" >'."\n");
    print('      <INPUT TYPE="HIDDEN" NAME="button_pressed" VALUE="New Initial" />'."\n");
    print('      Last Names Starting With:&nbsp;'."\n");
    print('      <SELECT NAME="last_initial" onchange="updateMembershipInitial()" >'."\n");

    print('        <OPTION VALUE=""');

    if($lastInitial == '')
    {
      print(' SELECTED');
    }

    print(' > -- </OPTION>'."\n");

    for ($letter = 'A'; $letter != 'AA'; $letter++)
    {
      print('        <OPTION VALUE="'.$letter.'"');
      if ($letter == $lastInitial)
      {
        print(' SELECTED');
      }
      print(' >'."$letter</OPTION>\n");
    }

    print('      </SELECT>'."\n");
    print('    </FORM>'."\n");

  }

  /**
   * Build a navigation menu
   */
  function buildNavMenu()
  {
    global $initial;
    global $startingEntry;
    global $notApproved;
    global $db;

    $totalNominations = $db->countBallots();

    $last = ((int) $totalNominations/100) * 100;

    $prev = $startingEntry - 100;
    if($prev < 0)
    {
      $prev = 0;
    }

    $next = $startingEntry + 100;
    if($next > $last)
    {
      $next = $last;
    }

?>
    <FORM NAME="navMenu" ID="navMenu" ACTION="reviewBallots.php" METHOD="POST">
      <INPUT TYPE="HIDDEN" NAME="last_initial" VALUE="<?PHP print($initial); ?>" />
      <INPUT TYPE="HIDDEN" NAME="button_pressed" VALUE="Navigate" />
      <INPUT TYPE="HIDDEN" NAME="count" VALUE="<?PHP print($count); ?>" />
      <INPUT TYPE="HIDDEN" NAME="review_id" VALUE="-1" />
      <INPUT TYPE="HIDDEN" NAME="form" VALUE="navMenu" />
      <INPUT TYPE="BUTTON" onclick="navigate(0);" VALUE="FIRST" />&nbsp;
      <INPUT TYPE="BUTTON" onclick="navigate(<?PHP print($prev); ?>);" VALUE="PREVIOUS" />&nbsp;
      <INPUT TYPE="BUTTON" onclick="navigate(<?PHP print($next); ?>);" VALUE="NEXT" />&nbsp;
      <INPUT TYPE="BUTTON" onclick="navigate(<?PHP print($last); ?>);" VALUE="LAST" />
      <BR/>
      Ballots&nbsp;Needing&nbsp;Review&nbsp;Only:&nbsp;
      <INPUT TYPE="CHECKBOX" onchange="submit()"  NAME="not_approved" <?PHP if($notApproved){print('CHECKED ');} ?>/>
    </FORM>
<?PHP
  }

  function buildMoveDrop($categoryId,$categoryInfo)
  {
    print('      <SELECT NAME="move_for_'.$categoryId.'">'."\n");

    foreach($categoryInfo as $id => $info)
    {
      if($id != $categoryId)
      {
        print('        <OPTION VALUE="'.$id.'">'.$info['name'].'</OPTION>'."\n");
      }
    }
    print('      </SELECT>'."\n");

  }

  function buildReviewForm($reviewId)
  {
    global $db;
    global $initial;
    global $memberList;

    $nominatorInfo = $db->getNominator($reviewId);
?>
    <P>Please Review this ballot from <?PHP print($nominatorInfo['first_name'].' '.$nominatorInfo['last_name']);
?> and accept or keep it pending</P>
    <FORM ACTION="reviewBallots.php" METHOD="post">
      <INPUT TYPE="HIDDEN" NAME="review_id" Value="<?PHP print($reviewId); ?>" />
      <INPUT TYPE="HIDDEN" NAME="last_initial" VALUE="<?PHP print($initial); ?>" />
      <INPUT TYPE="HIDDEN" NAME="member_list" VALUE="<?PHP print($memberList); ?>" />
<?PHP
    $categoryInfo = $db->getCategoryInfo();

    foreach($categoryInfo as $id => $info)
    {
      print("      <!-- Category ID $id -->\n");
      print("      <P><B>".$categoryInfo[$id]['name']."</B></B>\n");
      print("      <TABLE BORDER=1>\n");
      print("        <TR>\n");
      print("          <TH>Select for Move or Delete</TH>\n");
      print("          <TH>".$categoryInfo[$id]['primary_datum_description']."</TH>\n");
      if((!is_null($categoryInfo[$id]['datum_2_description'])) && ($categoryInfo[$id]['datum_2_description'] != ''))
      {
        print("          <TH>".$categoryInfo[$id]['datum_2_description']."</TH>\n");
      }
      if((!is_null($categoryInfo[$id]['datum_3_description'])) && ($categoryInfo[$id]['datum_3_description'] != ''))
      {
        print("          <TH>".$categoryInfo[$id]['datum_3_description']."</TH>\n");
      }
      print("        </TR>\n");

      $nominations = $db->getNominationsForNominatior($reviewId,$id);

      foreach($nominations as $nominationRecord)
      {
        $deleted = ($nominationRecord['deleted'] == 1);

        print("        <TR>\n");
  print('          <TD><INPUT TYPE="CHECKBOX" NAME="selected_for_move_'.$id.'_'.$nominationRecord['id'].'" /></TD>'."\n");
        print("          <TD>".($deleted?'<STRIKE>':'').$nominationRecord['datum1'].($delted?'</STRIKE>':'')."</TD>\n");
        if((!is_null($categoryInfo[$id]['datum_2_description'])) && ($categoryInfo[$id]['datum_2_description'] != ''))
  {
    print("          <TD>".($deleted?'<STRIKE>':'').$nominationRecord['datum2'].($delted?'</STRIKE>':'')."</TD>\n");
  }
        if((!is_null($categoryInfo[$id]['datum_3_description'])) && ($categoryInfo[$id]['datum_3_description'] != ''))
  {
    print("          <TD>".($deleted?'<STRIKE>':'').$nominationRecord['datum3'].($delted?'</STRIKE>':'')."</TD>\n");
  }
        print("        </TR>\n");
      }

      print("      </TABLE>\n");
      print("      Move To:&nbsp;&nbsp;\n");
      buildMoveDrop($id,$categoryInfo);
      print('      &nbsp;<INPUT TYPE="SUBMIT" NAME="button_pressed" VALUE="Move Nominations" />'."\n");
      print('      &nbsp;<INPUT TYPE="SUBMIT" NAME="button_pressed" VALUE="Delete Nominations" />'."\n");
    }
?>
      <BR/>
      <INPUT TYPE="SUBMIT" NAME="button_pressed" VALUE="Approve This Ballot" />&nbsp;
      <INPUT TYPE="SUBMIT" NAME="button_pressed" VALUE="Keep Ballot Pending " />
    </FORM>
<?PHP

  }

  function processSearch()
  {
    global $db;
    global $_POST;

    if($_POST['search_nominations'] != '')
    {
      return $db->searchBallots($_POST['search_nominations']);
    }

    return $db->searchNominators($_POST['search_last_name'],
                                 $_POST['search_first_name'],
         $_POST['search_email'],
         $_POST['search_membership_no'],
         $_POST['search_field']);
   }

   function getBody()
   {
     global $_POST;
     global $db;
     global $initial;

     if(isset($_POST['button_pressed']) && ($_POST['button_pressed'] == 'Review Ballot'))
     {
?>
  <BODY>
    <?PHP menu(); ?>
    <BR/>
    <?PHP buildReviewForm($_POST['review_id']); ?>
  </BODY>
<?PHP
     }
     else
     {
?>
  <BODY>
    <?PHP menu(); ?>
    <BR/>
    <?PHP buildSearchForm(); ?>
    <BR/>
    <?PHP buildNavMenu(); ?>
    <BR/>
    <?PHP initialMenu($initial); ?>
    <BR/>
    <?PHP buildMembershipTable(); ?>
  </BODY>

<?PHP
     }
   }

$initial = 'A';
if(isset($_POST['last_initial']))
{
  $initial = $_POST['last_initial'];
}

$startingEntry = 0;
if(isset($_POST['count']))
{
  $startingEntry = $_POST['count'];
}

$notApproved = true;
if((isset($_POST['form'])) && ($_POST['form'] == 'navMenu'))
{
  $notApproved = (isset($_POST['not_approved']));
}

if((isset($_POST['member_list'])) && ($_POST['member_list'] != ''))
{
  $memberList = $_POST['member_list'];
}

if(isset($_POST['button_pressed']))
{
  if($_POST['button_pressed'] == 'Search')
  {
    $memberList = processSearch();
  }
  elseif($_POST['button_pressed'] == 'Approve This Ballot')
  {
    print("<!-- Approving the ballot for ".$_POST['review_id']." -->\n");
    $db->approveBallot($_POST['review_id'],session_id());
  }
  elseif($_POST['button_pressed'] == 'Move Nominations')
  {
    foreach($_POST as $key => $value)
    {
      if(preg_match('/selected_for_move_(\d+)_(\d+)/',$key,$match))
      {
        print("<!-- \$match:\n");var_dump($match);print("-->\n");
        $oldCategoryId    = $match[1];
  $oldCategoryField = 'move_for_'.$oldCategoryId;
  print("<!-- \$oldCategoryField = [$oldCategoryField] -->\n");

  $nominationId = $match[2];

  $db->moveNomination($nominationId,$_POST[$oldCategoryField]);
      }
    }
    $_POST['button_pressed'] = 'Review Ballot'; // Make sure we return to the review page
  }
  elseif($_POST['button_pressed'] == 'Delete Nominations')
  {
    foreach($_POST as $key => $value)
    {
      if(preg_match('/selected_for_move_(\d+)_(\d+)/',$key,$match))
      {
        print("<!-- \$match:\n");var_dump($match);print("-->\n");

  $nominationId = $match[2];

  $newCategoryId = $_POST[$oldCategoryField];
  $db->deleteNomination($nominationId,$newCategoryId);
      }
    }
    $_POST['button_pressed'] = 'Review Ballot'; // Make sure we return to the review page
  }
  $initial = $_POST['last_initial'];
}



?>
<HTML>
  <HEAD>
    <TITLE>Hugo Nomination Administration</TITLE>
    <SCRIPT TYPE="text/javascript" src="javascript/admin.js"></SCRIPT>
    <script type="text/javascript" src="javascript/sorttable.js"></script>
  </HEAD>
<?PHP
  print("<!-- \$_POST\n");
  var_dump($_POST);
  print("-->\n");

  getBody();

?>
</HTML>
