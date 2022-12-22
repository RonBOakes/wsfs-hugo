<?PHP
/*
 * Written by Ronald B. Oakes, copyright 2015, 2018, 2022
 * Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
 * For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
 * All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
 */

/**
 * Sample code for interfacing the Hugo Award system with the convention's membership database.
 * This sample is the one
 * used for Worldcon 76 in San Jose.
 */
require_once ('./database.php');

// Worldcon 76 Data Info
define ( 'WSFS_MAC2_MEMBER_DB_HOST', 'ihsrv.com' );
define ( 'WSFS_MAC2_MEMBER_DB_USER', 'hugo' );
define ( 'WSFS_MAC2_MEMBER_DB_PASSWORD', 'atwiwc76' );
define ( 'WSFS_MAC2_MEMBER_DB_NAME', 'hugo_membership' ); //
function getRegistrationMembership($lastName1, $memberId1, $PIN1, &$memberInfo)
{
  $memberDb = new mysqli ( WSFS_MAC2_MEMBER_DB_HOST, WSFS_MAC2_MEMBER_DB_USER, WSFS_MAC2_MEMBER_DB_PASSWORD, WSFS_MAC2_MEMBER_DB_NAME );

  $sql = <<<EOT
SELECT `lastname`,
       `member_number`,
       `pin`,
       `firstname`,
       `email`
FROM   `membership`
WHERE pin = ?
EOT;

  $query = $memberDb->prepare ( $sql );
  $query->bind_param ( 's', $PIN1 );
  $query->execute ();
  $query->bind_result ( $secondName2, $memberId2, $PIN2, $firstname, $email );
  if ($query->fetch ())
  {
    if ($memberId1 == $memberId2)
    {
      $memberInfo = array (
          'firstname' => $firstname,
          'secondname' => $secondName2,
          'member_id' => $memberId2,
          'email' => $email,
          'PIN' => $PIN2
      );
      return 1;
    }
    else if (soundex ( $lastName1 ) == soundex ( $secondName2 ))
    {
      $memberInfo = array (
          'firstname' => $firstname,
          'secondname' => $secondName2,
          'member_id' => $memberId2,
          'email' => $email,
          'PIN' => $PIN2
      );
      return 1;
    }
    else
    {
      return 0;
    }
  }
  return 0;
}
function validateWorldcon76Member($lastname1, $firstname1, $memberId1, $PIN1, &$memberInfo)
{
  $memberDb = new mysqli ( WSFS_MAC2_MEMBER_DB_HOST, WSFS_MAC2_MEMBER_DB_USER, WSFS_MAC2_MEMBER_DB_PASSWORD, WSFS_MAC2_MEMBER_DB_NAME );

  $sql = <<<EOT
SELECT `name`,
       `MembershipID`,
       `email`,
       `hugopin`
FROM   `worldcon76` 
WHERE  `hugopin` = ?
EOT;

  $query = $memberDb->prepare ( $sql );
  $query->bind_param ( 's', $PIN1 );
  $query->execute ();
  $query->bind_result ( $combinedName, $memberId2, $email, $PIN2 );
  if ($query->fetch ())
  {
    $nameSplit = explode ( " ", $combinedName );
    if (sizeof ( $nameSplit ) < 2)
    {
      $nameSplit [0] = "";
      $nameSplit [1] = $combinedName;
    }
    if ($memberId1 == $memberId2)
    {
      $memberInfo = array (
          'firstname' => $nameSplit [0],
          'secondname' => $nameSplit [1],
          'member_id' => $memberId2,
          'email' => $email,
          'PIN' => $PIN2
      );
      return 1;
    }
    else if ((strlen ( trim ( $lastname1 ) ) == 0) && (strlen ( trim ( $firstname1 ) )) == 0)
    {
      return 0;
    }
    else if (soundex ( $nameSplit [1] ) == soundex ( $lastname1 ))
    {
      $memberInfo = array (
          'firstname' => $nameSplit [0],
          'secondname' => $nameSplit [1],
          'member_id' => $memberId2,
          'email' => $email,
          'PIN' => $PIN2
      );
      return 1;
    }
    else if ((trim ( $combinedName ) == trim ( $firstname ) || (trim ( $combinedName )) == trim ( $lastName )))
    {
      $memberInfo = array (
          'firstname' => "",
          'secondname' => $combinedName,
          'member_id' => $memberId2,
          'email' => $email,
          'PIN' => $PIN2
      );
      return 1;
    }
    else
    {
      return 0;
    }
  }
  else
  {
    return 0;
  }
}

/**
 * Function to validate a member.
 * This takes in the membership information from the website, and returns a true or false value. This is the only function in this file that must be implemented for the rest of the package to work.
 *
 * @param $membership string
 *          Memberhsip ID as provided by the administering Worldcon
 * @param $pin string
 *          Unique Hugo Voter PIN
 * @param $lastname string
 *          Family name (Eurocentric)
 * @param $firstname string
 *          Given name (Eurocentric)
 * @return boolean 1 if the membership is valid, 0 otherwise.
 */
function validateMember($membership, $pin, $lastname, $firstname)
{
  $db = new Database ();

  // See if this person has a record in the internal Hugo Award database.
  $result = $db->validateMemberHugoDb ( $lastname, $membership, $pin, true );
  if ($result == 1)
  {
    return $result;
  }

  // If not, see if they can be validated through the full membership database.
  $result = validateWorldcon76Member ( $lastname, $firstname, $membership, $pin, $memberInfo );
  if ($result == 1)
  {
    // If validated, also add the record to the internal table to speed future validation.
    $db->addUpdatePinEmailRecord ( $memberInfo ['firstname'], $memberInfo ['secondname'], $memberInfo ['member_id'], $memberInfo ['email'], $memberInfo ['PIN'], 'CURRENT' );

    return $result;
  }

  return $result;
}
function getMemberEmailFromPin($pin)
{
  $db = new Database ();
  return $db->getEmailHugoDb ( $pin );
}
function getMemberInfoFromPin($pin)
{
  $wsfs_hugo_year = '2015';
  $db = new Database ( $wsfs_hugo_year );
  return $db->getInfoHugoDb ( $pin );
}
function getAllMemberInfo()
{
  $wsfs_hugo_year = '2015';
  $db = new Database ( $wsfs_hugo_year );
  return $db->getAllMemberInfo ();
}
function getSelectMemberInfo($pinList)
{
  foreach ( $pinList as $key => $pinValue )
  {
    $pinList [$key] = "'SQ" . $pinValue . "'";
  }
  $wsfs_hugo_year = '2015';
  $db = new Database ( $wsfs_hugo_year );
  return $db->getSelectMemberInfo ( $pinList );
}

?>
