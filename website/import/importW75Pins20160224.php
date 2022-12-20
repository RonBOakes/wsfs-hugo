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

function importMembership($firstname,$lastname,$memberNumber,$pin,$email)
{
  global $memberDb;

  $sql = <<<EOT
INSERT INTO `membership`
(`firstname`,`lastname`,`member_number`,`pin`,`email`,`source`,`last_update`)
VALUES (?,?,?,?,?,'Worldcon75',NOW())
EOT;

  $query = $memberDb->prepare($sql);
  $query->bind_param('sssss',$firstname,$lastname,$memberNumber,$pin,$email);
  $query->execute();
  $query->fetch();
  $query->close();

  print("Added: $firstname $lastname ($memberNumber, $pin, $email)\n");
}

$fptr = fopen('MissingW75.csv','r');

if($fptr)
{
  $readData = fgetcsv($fptr,255,',','"','\\');   // Eat the header

  while ($readData = fgetcsv($fptr,255,',','"','\\'))
  {
    //First Name,Last Name,Membership #,PIN,e-mail
    importMembership($readData[0],$readData[1],$readData[2],$readData[3],$readData[4]);
  }
}

/*
SELECT `pin`,`member_number`,`firstname`,`lastname`,`source` FROM `membership`
WHERE `pin` IN (SELECT `pin` FROM (SELECT `pin`,COUNT(`memberkey`) AS `pin_count` FROM `membership` GROUP BY `pin`
ORDER BY `pin_count`  DESC) AS `pin_counts` WHERE `pin_count` > 1) ORDER BY `pin`, `member_number`
*/

?>
