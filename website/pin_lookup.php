<?PHP
/*
 * Written by Ronald B. Oakes, copyright 2015-2022
 * Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
 * For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
 * All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
 */
/**
 * Sample of how to create a PIN lookup page, based on the Worldcon 76 model.
 * This will need to be customized based
 * on the membership database and validation.
 */

// Comment out the following once debuging has been completed.
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// End comment out section.

// Worldcon 76 Data Info
define ( 'WSFS_MAC2_MEMBER_DB_HOST', 'ihsrv.com' );
define ( 'WSFS_MAC2_MEMBER_DB_USER', 'hugo' );
define ( 'WSFS_MAC2_MEMBER_DB_PASSWORD', 'atwiwc76' );
define ( 'WSFS_MAC2_MEMBER_DB_NAME', 'hugo_membership' ); //

# $oldRoot = getcwd();
# chdir("/home3/swocorg/public_html/sasquan/hugo/");
# print("<!-- New Directory: "); echo getcwd(); print(" -->\n");

require_once ('database.php');
define ( 'WSFS_HUGO_PIN_LOOKUP_URL', 'https://www.worldcon76.org/hugo/pin_lookup.php' );
function process_form($post, $db)
{
  $memberDb = new mysqli ( WSFS_MAC2_MEMBER_DB_HOST, WSFS_MAC2_MEMBER_DB_USER, WSFS_MAC2_MEMBER_DB_PASSWORD, WSFS_MAC2_MEMBER_DB_NAME );

  $email = $post ['email'];
  $fname = $post ['fname'];
  $lname = $post ['lname'];

  $matches = $db->lookupMembership ( $email, $fname, $lname );
  $sql = <<<EOT
SELECT `MembershipID`,
       `name`,
       `email`,
       `hugopin`
FROM `worldcon76` 
WHERE `email` LIKE ?
  AND  `email` != ''
  AND  `voting` = 1
EOT;

  $query = $memberDb->prepare ( $sql );
  $query->bind_param ( 's', $email );
  $query->execute ();
  $query->bind_result ( $memberId2, $name2, $email2, $PIN2 );

  if (($email != '') && ($query->fetch ()))
  {
    do
    {
      $nameParts = explode ( " ", $name2 );
      print ("<!-- nameParts:\n") ;
      var_dump ( $nameParts );
      print ("--!>\n") ;
      $matches [] = array (
          'first_name' => $nameParts [0],
          'last_name' => $nameParts [1],
          'member_id' => $memberId2,
          'PIN' => $PIN2,
          'email' => $email2
      );
    } while ( $query->fetch () );
  }
  else
  {
    $query->close ();

    $fullName = join ( ' ', array (
        $firstName,
        $lastName
    ) );

    $sql = <<<EOT
SELECT `MembershipID`,
       `name`,
       `email`,
       `hugopin`
FROM `worldcon76`
WHERE `name` LIKE ?
  AND `voting` = 1
EOT;
    $query = $memberDb->prepare ( $sql );
    $query->bind_param ( 's', $fullname );
    $query->execute ();
    $query->bind_result ( $memberId2, $name2, $email2, $PIN2 );
    if ($query->fetch ())
    {
      do
      {
        $nameParts [] = explode ( " ", $name2 );
        print ("<!-- nameParts:\n") ;
        var_dump ( $nameParts );
        print ("--!>\n") ;

        $matches [] = array (
            'first_name' => $nameParts [0],
            'last_name' => $nameParts [1],
            'member_id' => $memberId2,
            'PIN' => $PIN2,
            'email' => $email2
        );
      } while ( $query->fetch () );
    }
  }

  print ("<!-- \$matches:\n") ;
  var_dump ( $matches );
  print ("\n-->") ;

  if (count ( $matches ) == 0)
  {
    ?>
<P>Unable to locate a matching membership. This could mean one of
	several things:
<UL>
	<LI>There is not a valid membership to MidAmeriCon II that matches this
		information</LI>
	<LI>The matching membership does not have an email address</LI>
</UL>
</P>
<P>
	Please contact the Hugo Award PIN administrator at <A
		HREF="mailto:hugopin@worldcon76.org">hugopin@worldcon76.org</A> to
	enquire further.
</P>
<?PHP
  }
  elseif (count ( $matches ) > 1)
  {
    $sendCount = 0;
    foreach ( $matches as $entry )
    {
      if ((soundex ( $entry ['first_name'] ) == soundex ( $fname )) && (soundex ( $entry ['last_name'] ) == soundex ( $lname )))
      {
        print ("<!-- Sending email for " . $entry ['first_name'] . " " . $entry ['last_name'] . " -->\n") ;
        send_email ( $entry, $db );
        $sendCount += 1;
      }
    }
    if ($sendCount == 1)
    {
      ?>
<P>An email has been sent to the email address on record for the
	selected member. Please check your email for a message from
	hugopin@worldcon76.org which will contain your 2018 Hugo Award, Award
	for Best Young Adult Book, and John W. Campbell Award nominating
	information. This match was made exclusively by name, so it is possible
	that the matching record belongs to a different person, and the email
	was sent to them instead.</P>
<?PHP
    }
    else
    {
      ?>
<P>An email has been sent to the email address on record for the
	selected member. Please check your email for a message from
	hugopin@worldcon76.org which will contain your 2018 Hugo Award, Award
	for Best Young Adult Book, and John W. Campbell Award nominating
	information. This match was made exclusively by name, so it is possible
	that the matching record belongs to a different person, and the email
	was sent to them instead.</P>
<?PHP
    }
  }
  else // count($matches) == 1
  {
    send_email ( $matches [0], $db );
    ?>
<P>An email has been sent to the email address on record for the
	selected member. Please check your email for a message from
	hugopin@worldcon76.org which will contain your 2018 Hugo Award, Award
	for Best Young Adult Book, and John W. Campbell Award nominating
	information.</P>
<?PHP
  }
}
function send_email($entry, $db)
{
  $fname = $entry ['first_name'];
  $lname = $entry ['last_name'];
  $email = $entry ['email'];
  $member = $entry ['member_id'];
  $PIN = $entry ['PIN'];
  $emailText = <<<EOT
As requested, your 2018 Hugo Award and John W. Campbell Award nominating information is below:
    First Name:       $fname
    Last Name:        $lname
    Member Number:    $member
    Nomination PIN:   $PIN

Thank You
   Worldcon 76  Hugo Award Administrators
EOT;

  $sendername = 'hugopin@worldcon76.org';
  $fromemail = 'Hugo PIN Recovery';
  $senderemail = 'hugopin@worldcon76.org';
  DEFINE ( 'MAIL_DOMAIN', '@worldcon76.org' );

  $headers = "From: " . $sendername . "\n";

  print ("<!-- \$headers = $headers -->\n") ;

  $result = mail ( $email, '2018 Hugo Award and John W. Campbell Nominating Information', $emailText, $headers );

  print ("<!-- \$result = $result -->\n") ;

  $db->logEmail ( $PIN, $emailText, $result ? 1 : 0, $email );
}
function build_form($url)
{
  ?>
<P>
	<B>All fields are required for a successful lookup.</B> Your Hugo PIN
	and membership number will be emailed to you. If you do not received
	your PIN, you may email <A HREF="mailto:hugopin@worldcon76.org">hugopin@worldcon76.org</A>
	for assistance.
</P>
<FORM ACTION="<?PHP print($url); ?>" METHOD="POST">
	<INPUT TYPE="HIDDEN" NAME="phase" VALUE="lookupMembership`" />
	<TABLE>
		<TR>
			<TD>First Name:</TD>
			<TD><INPUT TYPE="TEXT" NAME="fname" SIZE=40 MAXLENGTH=64 /></TD>
		</TR>
		<TR>
			<TD>Last Name:</TD>
			<TD><INPUT TYPE="TEXT" NAME="lname" SIZE=40 MAXLENGTH=64 /></TD>
		</TR>
		<TR>
			<TD>Email:</TD>
			<TD><INPUT TYPE="TEXT" NAME="email" SIZE=40 MAXLENGTH=64 /></TD>
		</TR>
	</TABLE>
	<TABLE>
		<TR>
			<TD><INPUT TYPE="SUBMIT" NAME="phase_1" VALUE="Lookup PIN" /></TD>
			<TD><INPUT TYPE="RESET" VALUE="Clear" /></TD>
		</TR>
	</TABLE>
</FORM>
<?PHP
}

print ("<!-- \$_POST:\n") ;
var_dump ( $_POST );
print ("\n-->\n") ;

$wsfs_hugo_db = new Database ( false );

if (isset ( $_POST ['phase_1'] ))
{
  process_form ( $_POST, $wsfs_hugo_db );
}
else
{
  build_form ( WSFS_HUGO_PIN_LOOKUP_URL );
}
chdir ( $oldRoot );

?>
