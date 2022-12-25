<!--
/* Written by Ronald B. Oakes, Copyright 2014-2022
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
 -->
<?PHP
/**
 * AJAX backend processing for approving Hugo Award votes.
 */
require_once ('library.php');
?>
<HTML>
<HEAD>
<TITLE>Hugo Award Ballot Approval Report</TITLE>
<script
	src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
<script type="text/javascript" src="javascript/sorttable.js"></script>
<SCRIPT type="text/JavaScript">
/**
* Display the alert message
* @param message Text to be displayed
*/
function displayAlertMessage(message)
{
    var timeOut = 10;
    jQuery('.messageBox').text(message).fadeIn('slow').css("display", "block");
    setTimeout(function() {
            jQuery('.messageBox').fadeOut('slow').css("display", "none");
        }, timeOut * 1000);
}


/**
* Processes the change in the Hugo Award Vote
* @param pin The Unique Hugo Award Voter PIN
* @param checkbox The checkbox from the HTML form
*/
function ProcessVoteApprovalChange(pin,checkbox)
{

  if(checkbox.checked)
  {
    approved = 1;
  }
  else
  {
    approved = 0;
  }

  var $data="pin="+pin+"&approved="+approved+"&year=2015";
  var $callback = function(response)
  {
  };
  $.post('disapprove_votes.php',$data,$callback,'json');
}
    </SCRIPT>
</HEAD>
<BODY>
  <?PHP
  print ("<!-- \$_GET:\n") ;
  var_dump ( $_GET );
  print ("\n-->\n") ;

  $firstRecord = 0;
  $page = 1;
  $recordsPerPage = 500;

  if (isset ( $_GET ['page'] ))
  {
    $page = $_GET ['page'];
    $firstRecord = $recordsPerPage * ($page - 1);
  }

  $db = new database();

  $votedPins = $db->getVotedPins ();

  $voterCount = sizeof ( $votedPins );
  $pages = ceil ( $voterCount / $recordsPerPage );

  print ("    <P>Jump to page:&nbsp;") ;
  for($index = 1; $index <= $pages; $index ++)
  {
    if ($index != $page)
    {
      print ('<A HREF="voteApprovalReport.php?page=' . $index . '">' . $index . '</A>&nbsp;') ;
    }
    else
    {
      print ("$index&nbsp;") ;
    }
  }
  print ("</P>") ;

  print ("    <FORM ID='approval_form' ACTION='voteApprovalReport.php' METHOD='post'>\n") ;
  print ("    <TABLE BORDER='1' CLASS='sortable'>\n") ;
  print ("      <TR>\n") ;
  print ("        <TH>Vote Approved?</TH><TH>First Name</TH><TH>Last Name</TH><TH>Member ID</TH><TH>PIN</TH><TH>IP Cast From</TH><TH>Last Vote Cast</TH><TH>Additional Info</TH>\n") ;
  print ("      </TR>\n") ;

  for($index = $firstRecord; $index < ($firstRecord + $recordsPerPage); $index ++)
  {
    $pin = $votedPins [$index];
    $voterInfo = $db->getVoterSummary ( $pin );

    print ("      <TR>\n") ;
    print ("        <TD>\n") ;
    print ("          <INPUT TYPE='CHECKBOX' NAME='approve_" . $voterInfo ['pin'] . "' ID='approve_" . $voterInfo ['pin'] . "' OnClick='ProcessVoteApprovalChange(" . $pin . ",this)'") ;
    if ($voterInfo ['ballot_approved'] == 1)
    {
      print (" CHECKED ") ;
    }
    print ("/>\n") ;
    print ("        </TD>\n") ;
    print ("        <TD>" . $voterInfo ['first_name'] . "</TD>\n") ;
    print ("        <TD>" . $voterInfo ['second_name'] . "</TD>\n") ;
    print ("        <TD>" . $voterInfo ['member_id'] . "</TD>\n") ;
    print ("        <TD>" . $voterInfo ['pin'] . "</TD>\n") ;
    print ("        <TD>" . $voterInfo ['ip_added_from'] . "</TD>\n") ;
    print ("        <TD>" . $voterInfo ['time_added'] . "</TD>\n") ;
    if (isset ( $voterInfo ['Import records'] ))
    {
      print ("        <TD>" . $voterInfo ['Import records'] . "</TD>\n") ;
    }
    else
    {
      print ("        <TD/>\n") ;
    }
    print ("      </TR>\n") ;
  }

  print ("    </TABLE>\n") ;
  print ("    </FORM>\n") ;

  ?>
  </BODY>
</HTML>
