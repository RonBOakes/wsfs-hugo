<?PHP
/* Written by Ronald B. Oakes, copyright  2016
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
chdir('/var/www/html');
require_once('./database.php');
chdir('admin');

// MACMembership Data Info
define('WSFS_MAC2_MEMBER_DB_HOST','mac2-db02.midamericon2.org');
define('WSFS_MAC2_MEMBER_DB_USER','hugo');
define('WSFS_MAC2_MEMBER_DB_PASSWORD','RSJvNg9n9xMtPZGh');
define('WSFS_MAC2_MEMBER_DB_NAME','hugo_nom_members');

function getMailingList()
{
  $memberDb = new mysqli(WSFS_MAC2_MEMBER_DB_HOST,WSFS_MAC2_MEMBER_DB_USER,WSFS_MAC2_MEMBER_DB_PASSWORD,WSFS_MAC2_MEMBER_DB_NAME);

  $sql = <<<EOT
SELECT `memberkey`,
       `member_number`,
       `firstname`,
       `lastname`,
       `email`,
       `pin`
FROM `membership`
WHERE `last_mailed` < '2016-01-28 00:00:00'
  AND `source` = 'MidAmericon2'
ORDER BY RAND()
LIMIT 25
EOT;

  $query = $memberDb->prepare($sql);
  $query->execute();
  $query->bind_result($memberKey,$member_number,$firstname,$lastname,$email,$pin);
  $mailList = array();

  while ($query->fetch())
  {
    $entry = array('memberkey' => $memberKey,
                   'member_number' => $member_number,
                   'firstname' => $firstname,
                   'lastname' => $lastname,
                   'email' => $email,
                   'pin' => $pin);
    $mailList[] = $entry;
  }

  $query->close();

  return $mailList;
}

function sendEmail($mailData)
{
  $mailText = <<<EOT
MidAmeriCon II is pleased to announce that nominations for the 2016 Hugo Awards and the 1941 Retro Hugo Awards is now open!

As a member of MidAmeriCon II, you are eligible to nominate works for both awards.  The nomination period is now open, and will close on March 31, 2016 at 11:59 pm PDT.  You can find all the details for this process on the MidAmeriCon II website at http://midamericon2.org/the-hugo-awards/hugo-nominations/.

The Hugo Awards are fan-run, fan-given, and fan-supported. We recommend that you nominate whatever works and creators you have personally read or seen that were your favorites from 2015 and 1940.

There are two ways to nominate: either via a paper ballot that you can print out, or through MidAmeriCon II’s online voting system. To use the online system, you will need to enter your MidAmeriCon II membership number, as well as a Personal Identification Number that MidAmeriCon II has assigned to you.

The details we have for you are as follows:


EOT;

  $mailText .= 'First Name: '.$mailData['firstname']."\n";
  $mailText .= 'Last Name: '. $mailData['lastname']."\n";
  $mailText .= 'Convention: MidAmericon II'."\n";
  $mailText .= 'Membership number: ' . $mailData['member_number']."\n";
  $mailText .= 'PIN: ' . $mailData['pin'] . "\n";

  $mailText .= <<<EOT
If you have difficulties accessing the online ballot(s), or you have more general questions on the Hugo process, you can e-mail hugoadmin@midamericon2.org for assistance.

A printable version of the Hugo Nominating ballot is included in Progress Report 2, available for download at http://midamericon2.org/publications/progress-reports/ The progress report also includes other fun and useful information like a biography of Guests of Honor Patrick and Teresa Nielsen Hayden, an update from the Video Archaeology Team digitizing the videos from the 1976 MidAmeriCon, and updates from many of MAC II's departments.

Thank you and we look forward to your participation in the 2016 Hugo and 1941 Retro Hugo processes.

Sincerely,
Dave McCarty
Will Frank
Hugo Award Administrators

EOT;

  $result = mail($mailData['email'],'Hugo Nominations Are Now Open!',$mailText,'From: hugoadmin@midamericon2.org');

  $db = new Database(false);

  $db->logEmail($mailData['pin'],$mailText,$result,$mailData['email']);

  $memberDb = new mysqli(WSFS_MAC2_MEMBER_DB_HOST,WSFS_MAC2_MEMBER_DB_USER,WSFS_MAC2_MEMBER_DB_PASSWORD,WSFS_MAC2_MEMBER_DB_NAME);

  $sql = <<<EOT
UPDATE `membership`
SET    `last_mailed` = NOW()
WHERE  `memberkey` = ?
EOT;

  $query = $memberDb->prepare($sql);
  $query->bind_param('i',$mailData['memberkey']);
  $query->execute();
  $query->close();
}

$mailList = getMailingList();
foreach($mailList as $listEntry)
{
  sendEmail($listEntry);
}


?>
