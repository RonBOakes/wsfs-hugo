<?PHP
/* Written by Ronald B. Oakes, copyright  2016
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/

// MACMembership Data Info
define('WSFS_MAC2_MEMBER_DB_HOST','mac2-db02.midamericon2.org');
define('WSFS_MAC2_MEMBER_DB_USER','hugo');
define('WSFS_MAC2_MEMBER_DB_PASSWORD','RSJvNg9n9xMtPZGh');
define('WSFS_MAC2_MEMBER_DB_NAME','hugo_nom_members');

$memberDb = new mysqli(WSFS_MAC2_MEMBER_DB_HOST,WSFS_MAC2_MEMBER_DB_USER,WSFS_MAC2_MEMBER_DB_PASSWORD,WSFS_MAC2_MEMBER_DB_NAME);

function updateMembership($convention,$memberNumber,$pin,$firstname,$lastname)
{
  global $memberDb;

  $sql = <<<EOT
SELECT `memberKey`
FROM   `membership`
WHERE  `source` = ?
 AND   `member_number` LIKE ?
EOT;

  $query = $memberDb->prepare($sql);
  $query->bind_param('ss',$convention,$memberNumber);
  $query->execute();

  $results = array();

  $query->bind_result($memberKey);

  while ($query->fetch())
  {
    $results[] = $memberKey;
  }

  $memberKeyToUpdate = $results[0];

  if (count($results) != 1)
  {
    print("ERROR: results for $convention -> $memberNumber are incorrect.  Number of results = ".count($results).".  Attempting with name\n");

    $query->close();
    $sql = <<<EOT
SELECT `memberKey`
FROM   `membership`
WHERE  `source` = ?
 AND   `member_number` LIKE ?
 AND   `firstname` LIKE ?
 AND   `lastname` LIKE ?
EOT;

    $query = $memberDb->prepare($sql);
    $query->bind_param('ssss',$convention,$memberNumber,$firstname,$lastname);
    $query->execute();
    $results = array();

    $query->bind_result($memberKey);

    while ($query->fetch())
    {
      $results[] = $memberKey;
    }

    if (count($results) != 1)
    {
      print("ERROR: results for $convention -> $memberNumber, $firstname, $lastname are incorrect.  Number of results = ".count($results).".  Aborting\n");
      $memberKeyToUpdate = -1;
    }
    else
    {
      $memberKeyToUpdate = $results[0];
    }
  }
  else
  {
    $memberKeyToUpdate = $results[0];
  }

  $query->close();

  $sql = <<<EOT
UPDATE `membership`
SET    `pin` = ?,
       `last_update` = NOW()
WHERE  `memberKey` = ?
EOT;

  $query = $memberDb->prepare($sql);
  $query->bind_param('si',$pin,$memberKeyToUpdate);
  $query->execute();
  $query->fetch();
  $query->close();

  print("Updated $memberKeyToUpdate ($memberNumber, $firstname, $lastname, $pin)\n");
}

$fptr = fopen('UpdatedPINs.csv','r');

if($fptr)
{
  $readData = fgetcsv($fptr,255,',','"','\\');   // Eat the header

  while ($readData = fgetcsv($fptr,255,',','"','\\'))
  {
    //First Name,Last Name,Convention,Membership #,PIN
    updateMembership($readData[2],$readData[3],$readData[4],$readData[0],$readData[1]);
  }
}

/*
SELECT `pin`,`member_number`,`firstname`,`lastname`,`source` FROM `membership`
WHERE `pin` IN (SELECT `pin` FROM (SELECT `pin`,COUNT(`memberkey`) AS `pin_count` FROM `membership` GROUP BY `pin`
ORDER BY `pin_count`  DESC) AS `pin_counts` WHERE `pin_count` > 1) ORDER BY `pin`, `member_number`
*/

?>
