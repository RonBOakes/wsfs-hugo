<!--
/* Written by Ronald B. Oakes, copyright 2015, 2022
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
-->
<?PHP
/**
 * Generate a report of the raw nominations that attempts to obfuscate who is making the nominations.
 */
require_once ('library.php');

$db = new database ( getYear () );

$database = $db->getDb ();

/**
 * Builds a hash mapping the actual member IDs (Hugo Voter PINs) to sequentially assigned numbers.
 *
 * @return number[] A hash mapping the PIN to the obfuscated ID.
 */
function getMemberIdMap()
{
  global $database;

  $memberIds = array ();

  // Get all of the nominators' PINs
  $sql = <<<EOT
SELECT DISTINCT nominator_id
FROM nominations
EOT;

  $query = $database->prepare ( $sql );
  $query->execute ();
  $query->bind_result ( $memberId );
  while ( $query->fetch () )
  {
    $memberIds [$memberId] = $memberId;
  }

  $query->close ();

  // Get all of the Hugo Voter's PINs
  $sql = <<<EOT
SELECT DISTINCT member_id
FROM hugo_ballot_entry
EOT;

  $query = $database->prepare ( $sql );
  $query->execute ();
  $query->bind_result ( $memberId );
  while ( $query->fetch () )
  {
    $memberIds [$memberId] = $memberId;
  }

  $query->close ();

  // At this point, $memberIds should have the member Id as both the key and the value - so there should only
  // be one copy of each ID.

  // This command will replace the keys - but we're OK with that
  shuffle ( $memberIds );

  $memberIdMap = array ();
  $newId = 10000;

  foreach ( $memberIds as $memberId )
  {
    $memberIdMap [$memberId] = $newId ++;
  }

  return $memberIdMap;
}

$memberIdMap = getMemberIdMap ();

print ("<!-- \$memberIdMap:\n") ;
var_dump ( $memberIdMap );
print ("\n-->\n") ;

/**
 * Build the new table of obfuscated nominations.
 */
function createObsNomData()
{
  global $database, $memberIdMap;

  $sql = <<<EOT
DROP TABLE IF EXISTS obfuscated_nominations
EOT;

  $query = $database->prepare ( $sql );
  $query->execute ();
  $query->close ();

  $sql = <<<EOT
CREATE TABLE IF NOT EXISTS `obfuscated_nominations` (
  `nomination_id` int(11) NOT NULL AUTO_INCREMENT,
  `nominator_id` bigint(128) NOT NULL,
  `award_category_id` int(11) NOT NULL,
  `primary_datum` varchar(256) NOT NULL,
  `datum_2` varchar(256) DEFAULT NULL,
  `datum_3` varchar(256) DEFAULT NULL
  PRIMARY KEY (`nomination_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1
EOT;

  $query = $database->prepare ( $sql );
  $query->execute ();
  $query->close ();

  $sql = <<<EOT
INSERT INTO `obfuscated_nominations`
SELECT  nomination_id,
        nominator_id,
        award_category_id,
        primary_datum,
        datum_2,
        datum_3
FROM   `nominations`
WHERE  `unverified_nominator` != 1
EOT;

  $query = $database->prepare ( $sql );
  $query->execute ();
  $query->close ();

  $sql = <<<EOT
UPDATE obfuscated_nominations
SET    nominator_id = ?
WHERE  nominator_id = ?
EOT;
  $query = $database->prepare ( $sql );

  // Replace the nominator IDs per the map.
  foreach ( $memberIdMap as $oldId => $newId )
  {
    print ("<P>Updating $oldId to $newId</P>\n") ;
    $query->bind_param ( 'ii', $newId, $oldId );
    $query->execute ();
  }
  $query->close ();
}

/**
 * Get the list of nominations listed by obfuscated nominator ID.
 *
 * @return array An array of hashes containing the obfuscated nomination data.
 */
function getNomQuery()
{
  global $database;

  $sql = <<<EOT
SELECT `obfuscated_nominations`.`nominator_id`,
       `award_categories`.`category_name`,
       `obfuscated_nominations`.`primary_datum`,
       `obfuscated_nominations`.`datum_2`,
       `obfuscated_nominations`.`datum_3`,
       `award_categories`.`ballot_position`
FROM `obfuscated_nominations`,
     `award_categories`
WHERE `obfuscated_nominations`.`award_category_id` = `award_categories`.`category_id`
ORDER BY `obfuscated_nominations`.`nominator_id` ASC,
         `award_categories`.`ballot_position` ASC,
         `obfuscated_nominations`.`nomination_date` ASC
EOT;
  $query = $database->prepare ( $sql );
  $query->execute ();
  $query->bind_result ( $nominatorId, $categoryName, $primaryDatum, $datum2, $datum3, $nominationDate, $ballotPosition );

  $nomQuery = array ();
  while ( $query->fetch () )
  {
    $nomQuery [] = array (
        'nominator_id' => $nominatorId,
        'category_name' => $categoryName,
        'primary_datum' => $primaryDatum,
        'datum2' => $datum2,
        'datum3' => $datum3
    );
  }

  return $nomQuery;
}

/**
 * Create a CSV file with the obfuscated data.
 */
function createNomCSV()
{
  $fptr = fopen ( 'ObfuscatedNominationData.csv', 'w' );

  if ($fptr)
  {
    $nomQuery = getNomQuery ();

    fputcsv ( $fptr, array (
        'nominator id',
        'category',
        'title/name',
        'author/editor/etc.',
        'publisher/network/etc.'
    ) );

    foreach ( $nomQuery as $nomRecord )
    {
      fputcsv ( $fptr, $nomRecord );
    }

    fflush ( $fptr );
    fclose ( $fptr );
  }
}

createObsNomData ();
createNomCSV ();

?>
