<?PHP
/* Written by Ronald B. Oakes, copyright  2015
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/

// Database Routines:

require_once('./db_config.php');

// Database handler
class database
{
  protected static $db;
  protected static $dbName;

  function database($retro = false)
  {
    $dbRootName = WSFS_HUGO_DB_ROOT_NAME;
    $dbRetroName = WSFS_HUGO_DB_ROOT_NAME_RETRO;
//    $dbYear = WSFS_HUGO_DEFAULT_YEAR;
    if(!$retro)
    {
      self::$dbName = $dbRootName;
    }
    else
    {
        self::$dbName = $dbRetroName;
    }
//    print ('<!-- self::$dbName: ['.self::$dbName.'] -->'."\n");
//    print ('<!-- '); debug_print_backtrace(); print(" -->\n");

    self::$db = new mysqli(WSFS_HUGO_DB_HOST,WSFS_HUGO_DB_USER,WSFS_HUGO_DB_PASSWORD,self::$dbName);
  }

  function getConnectInfo()
  {
    return array('host'     => WSFS_HUGO_DB_HOST,
                 'user'     => WSFS_HUGO_DB_USER,
                 'password' => WSFS_HUGO_DB_PASSWORD,
                 'name'     => self::$dbName);
  }

  function getDb()
  {
    return self::$db;
  }

  function addUpdateCategory($name,$description,$ballotPosition,$primary_datum_description,$datum_2_description, $datum_3_description)
  {
    $sql = <<<EOT
SELECT category_id
FROM   award_categories
WHERE  category_name = ?
EOT;

    $query = self::$db->prepare($sql);

    $query->bind_param('s',$name);
    $query->execute();

    $query->bind_result($category_id);

    if($query->fetch())  // Existing category - update
    {
      $query->close();

      $sql = <<<EOT
UPDATE award_categories
SET    category_description      = ?,
       ballot_position           = ?,
       primary_datum_description = ?,
       datum_2_description       = ?,
       datum_3_description       = ?
WHERE  category_id = ?
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('sisssi',$description,$ballotPosition,$primary_datum_description,$datum_2_description,$datum_3_description,$category_id);
      $query->execute();

      return $category_id;
    }
    else // Add
    {
      $query->close();

      $sql = <<<EOT
INSERT INTO award_categories
(category_name,category_description,ballot_position,primary_datum_description,datum_2_description,datum_3_description)
VALUES (?,?,?,?,?,?)
EOT;
      $query = self::$db->prepare($sql);
      print("<!-- ".self::$db->error." -->\n");
      $query->bind_param('ssisss',$name,$description,$ballotPosition,$primary_datum_description,$datum_2_description,$datum_3_description);
      $query->execute();

      $sql = <<<EOT
SELECT category_id
FROM   award_categories
WHERE  category_name = ?
EOT;

      $query = self::$db->prepare($sql);

      $query->bind_param('s',$name);
      $query->execute();

      $query->bind_result($category_id);

      if($query->fetch())
      {
        return $category_id;
      }
      else
      {
        return -1;
      }
    }
  }

  function getCategoryInfo()
  {
    $sql = <<<EOT
SELECT category_id,
       category_name,
       category_description,
       include_description_on_vote,
       ballot_position,
       primary_datum_description,
       datum_2_description,
       datum_3_description,
      personal_category
FROM   award_categories
ORDER BY ballot_position ASC
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();

    $query->bind_result($id,$name,$description,$includeDescription,$ballotPosition,$primary_datum_description,$datum_2_description,$datum_3_description,$personal_category);

    $categories = array();

    while($query->fetch())
    {
      $categoryInfo = array();
      $categoryInfo['id']                        = $id;
      $categoryInfo['name']                      = $name;
      $categoryInfo['description']               = $description;
      $categoryInfo['include_description']       = $includeDescription;
      $categoryInfo['ballot_position']           = $ballotPosition;
      $categoryInfo['primary_datum_description'] = $primary_datum_description;
      $categoryInfo['datum_2_description']       = $datum_2_description;
      $categoryInfo['datum_3_description']       = $datum_3_description;
      $categoryInfo['personal_category']         = $personal_category;

      $categories[$id] = $categoryInfo;
    }

    $query->close();

    $sql = <<<EOT
SELECT COUNT(shortlist_id) AS shortlist_count
FROM   hugo_shortlist
WHERE  category_id = ?
EOT;
    $query = self::$db->prepare($sql);

    foreach($categories as $id => $categoryInfo)
    {
      $query->bind_param('i',$id);
      $query->execute();
      $query->bind_result($shortlistCount);
      if(!$query->fetch())
      {
        $shortlistCount = 0;
      }
      $categories[$id]['shortlist_count'] = $shortlistCount;
    }

    return $categories;
  }

  function countBallots()
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT(nominator_id))
FROM   nominations
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($count);
    $query->fetch();

    return $count;
  }

  function countNominationsByNominator($nominatorId,$awardCategoryId,$reviewedOnly=false)
  {
    if(preg_match('(\\d+)',$nominatorId,$matches))
    {
      $nominatorId = $matches[0];
    }

    $sql = <<<EOT
SELECT COUNT(nomination_id)
FROM  nominations
WHERE nominator_id = ?
  AND award_category_id = ?
  AND nomination_deleted != 1
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('ii',$nominatorId,$awardCategoryId);
    $query->execute();
    $query->bind_result($count);
    $query->fetch();
    $query->close();
    return($count);
  }

  function addNomination($nominatorId,$awardCategoryId,$primary_datum,$datum_2='',$datum_3='')
  {
    $primary_datum = stripslashes($primary_datum);
    $datum_2       = stripslashes($datum_2);
    $datum_3       = stripslashes($datum_3);

    if(preg_match('(\\d+)',$nominatorId,$matches))
    {
      $nominatorId = $matches[0];
    }

    $return = '';
    $return .= "<!-- Adding nomination for: $nominatorId, $awardCategoryId, $primary_datum -->\n";
    if($this->countNominationsByNominator($nominatorId,$awardCategoryId) >= 5)
    {
      $return .= "<!-- Too many nominations already present for $nominatorId --!>\n";
      return $return;
    }

    $sql = <<<EOT
SELECT COUNT(nomination_id)
FROM   nominations
WHERE  nominator_id = ?
  AND  award_category_id = ?
  AND  primary_datum = ?
  AND  nomination_deleted != 1;
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('iis',$nominatorId,$awardCategoryId,$primary_datum);
    $query->execute();
    $query->bind_result($count);
    $query->fetch();
    $return .= "<!-- Found $count Matching Nominations -->\n";
    if($count != 0)
    {
      $return .= "<!-- Not adding $primary_data, matching record for $nominatorId --!>\n";
      return $return;
    }

    $query->close();

    $return .= "<!-- Proceed to add -->\n";

    // Always add the nominee - duplicates will be accounted for OK
    $nominee_id = $this->addNominee($awardCategoryId,$primary_datum,$datum_2,$datum_3,$return);

    $return .= "<!-- Nominee ID: $nominee_id -->\n";

    $return .= "<!--\nAdding:\nNominator ID (PIN): $nominatorId\nAward Category ID: $awardCategoryId\nPrimary Datum: $primary_datum\n";
    $return .= "2nd Datum: $datum_2\n3rd Datum: $datum_3\nNominee ID: $nominee_id\n";
    $return .= "User's IP: ".$_SERVER['REMOTE_ADDR']."\n-->\n";

    $sql = <<<EOT
INSERT INTO nominations
(nominator_id,award_category_id,primary_datum,datum_2,datum_3,nominee_id,ip_added_from,nomination_approved,nomination_deleted)
VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('iisssis',$nominatorId,$awardCategoryId,$primary_datum,$datum_2,$datum_3,$nominee_id,$_SERVER['REMOTE_ADDR']);
    $query->execute();
    $return .= "<!-- Database Error before fetch: ".self::$db->error." -->\n";
    $query->fetch();
    $return .= "<!-- Database Error after fetch: ".self::$db->error." -->\n";
    $query->close();

    return $return;
  }

  function addProvisionalNomination($nominatorId,$awardCategoryId,$primary_datum,$datum_2='',$datum_3='')
  {
    $primary_datum = stripslashes($primary_datum);
    $datum_2       = stripslashes($datum_2);
    $datum_3       = stripslashes($datum_3);

    if(preg_match('(\\d+)',$nominatorId,$matches))
    {
      $nominatorId = $matches[0];
    }

    $return = '';
    $return .= "<!-- Adding nomination for: $nominatorId, $awardCategoryId, $primary_datum -->\n";
    if($this->countNominationsByNominator($nominatorId,$awardCategoryId) >= 5)
    {
      $return .= "<!-- Too many nominations already present for $nominatorId --!>\n";
      return $return;
    }

    $sql = <<<EOT
SELECT COUNT(nomination_id)
FROM   provisional_nominations
WHERE  nominator_id = ?
  AND  award_category_id = ?
  AND  primary_datum = ?
  AND  nomination_deleted != 1;
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('iis',$nominatorId,$awardCategoryId,$primary_datum);
    $query->execute();
    $query->bind_result($count);
    $query->fetch();
    $return .= "<!-- Found $count Matching Nominations -->\n";
    if($count != 0)
    {
      $return .= "<!-- Not adding $primary_data, matching record for $nominatorId --!>\n";
      return $return;
    }

    $query->close();

    $return .= "<!-- Proceed to add -->\n";

    $return .= "<!-- nominator ID: $nominatorId -->\n";

    $sql = <<<EOT
INSERT INTO provisional_nominations
(nominator_id,award_category_id,primary_datum,datum_2,datum_3,nomination_approved,nomination_deleted)
VALUES (?, ?, ?, ?, ?, 0, 0)
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('iisss',$nominatorId,$awardCategoryId,$primary_datum,$datum_2,$datum_3);
    $query->execute();
    $return .= "<!-- ".self::$db->error." -->\n";
    $query->fetch();
    $query->close();

    return $return;
  }

  function addProvisionalNominator($first_name,$second_name,$membership,$email,$pin)
  {
    $sql = <<<EOT
INSERT INTO provisional_nominator
(first_name,second_name,membership_number,email,pin)
VALUES(?, ?, ?, ?, ?)
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('sssss',$first_name,$second_name,$membership,$email,$pin);
    $query->execute();
    $query->fetch();
    $query->close();

    $sql = <<<EOT
SELECT provisional_nominator_id
FROM provisional_nominator
WHERE first_name = ?
 AND  second_name = ?
 AND  membership_number = ?
 AND  pin = ?
ORDER BY provisional_nominator_id DESC
LIMIT 1
EOT;
    $nominatorId = -1;
    $query = self::$db->prepare($sql);
    print("<!-- Database error: ".self::$db->error." -->\n");
    $query->bind_param('ssss',$first_name,$second_name,$membership,$pin);
    print("<!-- Database error: ".self::$db->error." -->\n");
    $query->execute();
    print("<!-- Database error: ".self::$db->error." -->\n");
    $query->bind_result($nominatorId);
    print("<!-- Database error: ".self::$db->error." -->\n");
    $query->fetch();
    print("<!-- Database error: ".self::$db->error." -->\n");
    $query->close();

    return $nominatorId;
  }

  function clearNominations($nominatorId,$awardCategoryId)
  {
    $pageData = '';

    if(preg_match('(\\d+)',$nominatorId,$matches))
    {
      $nominatorId = $matches[0];
    }

  $pageData .= "<!-- Attempting to clear nomiantions for $nominatorId, in $awardCategoryId -->\n";

    $sql = <<<EOT
DELETE FROM nominations
WHERE nominator_id = ?
  AND award_category_id = ?
EOT;
  $query = self::$db->prepare($sql);
  if($query)
  {
      $query->bind_param('ii',$nominatorId,$awardCategoryId);
    $query->execute();
  }
  else
  {
    $pageData .=  "<!-- Unable to clear nominations:\n";
    $pageData .=  self::$db->error;
    $pageData .=  "\n-->\n";
  }

  return $pageData;
  }

  function clearProvisionalNominations($nominatorId,$awardCategoryId)
  {
    $pageData = '';

    if(preg_match('(\\d+)',$nominatorId,$matches))
    {
      $nominatorId = $matches[0];
    }

    $pageData .= "<!-- Attempting to clear nomiantions for $nominatorId, in $awardCategoryId -->\n";

    $sql = <<<EOT
DELETE FROM provisional_nominations
WHERE nominator_id = ?
  AND award_category_id = ?
EOT;
    $query = self::$db->prepare($sql);
    if($query)
    {
      $query->bind_param('ii',$nominatorId,$awardCategoryId);
      $query->execute();
    }
    else
    {
        $pageData .=  "<!-- Unable to clear nominations:\n";
        $pageData .=  self::$db->error;
        $pageData .=  "\n-->\n";
    }

    return $pageData;
  }

  function addNominee($awardCategoryId,$primary_datum,$datum_2,$datum_3,&$return)
  {
    $return .= "<!-- Adding Nominee: $awardCategoryId, $primary_datum -->\n";
    $sql = <<<EOT
SELECT nominee_id
FROM   nominee
WHERE  category_id = ?
  AND  primary_datum = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('is',$awardCategoryId,$primary_datum);
    $query->execute();
    $query->bind_result($nomineeId);
    if($query->fetch())
    {
      $return .= "<!-- Found matching nominee $nomineeId -->\n";
      return $nomineeId;
    }
    $query->close();

    $sql = <<<EOT
INSERT INTO nominee
(category_id,primary_datum,datum_2,datum_3)
VALUES (?,?,?,?)
EOT;
    $query = self::$db->prepare($sql);
    $return .= "<!-- insert prepare DB Error: [".self::$db->error."] -->\n";

    $query->bind_param('isss',$awardCategoryId,$primary_datum,$datum_2,$datum_3);
    $query->execute();
    $return .= "<!-- insert execute DB Error: [".self::$db->error."] -->\n";

    $return .= "<!-- Added nominee.  Next fetching Nominee ID --!>\n";

    $query->close();
    print($return."\n\n");
    $sql = <<<EOT
SELECT nominee_id
FROM   nominee
WHERE  category_id = ?
  AND  primary_datum = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('is',$awardCategoryId,$primary_datum);
    $query->execute();
    $query->bind_result($nomineeId);
    if($query->fetch())
    {
      $return .= "<!-- Nominee Id: $nomineeId -->\n";
      return $nomineeId;
    }
    $return .= "<!-- Error, could not find Nominee Id -->\n";
    return -1;
  }

  function updateNominee($nomineeId,$primaryDatum,$datum2,$datum3)
  {
    $sql = <<<EOT
UPDATE nominee
SET    primary_datum = ?,
       datum_2       = ?,
       datum_3       = ?
WHERE  nominee_id    = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('sssi',$primaryDatum,$datum2,$datum3,$nomineeId);
    $query->execute();
  }

  function countNominations($nomineeId,$reviewedOnly=false)
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT nominator_id) AS count
FROM   nominations
WHERE  nominee_id = ?
  AND  nomination_deleted != 1

EOT;
  if ($reviewedOnly)
  {
    $sql .= "  AND  nomination_approved = 1\n";
  }
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$nomineeId);
    $query->execute();
    $query->bind_result($nomination_count);
    if($query->fetch())
    {
      return $nomination_count;
    }

    return 0;
  }

  function getNomineeInfo($nominee_id)
  {
    $sql = <<<EOT
SELECT award_categories.category_id,
       award_categories.category_name,
       nominee.primary_datum,
       nominee.datum_2,
       nominee.datum_3
FROM   award_categories,
       nominee
WHERE  award_categories.category_id = nominee.category_id
  AND  nominee_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$nominee_id);
    $query->execute();
    $query->bind_result($category_id,$category_name,$primary_datum,$datum_2,$datum_3);

    $info = array();
    if($query->fetch())
    {
      $info['nominee_id']    = $nominee_id;
      $info['category_id']   = $category_id;
      $info['category_name'] = $category_name;
      $info['primary_datum'] = $primary_datum;
      $info['datum_2']       = $datum_2;
      $info['datum_3']       = $datum_3;

      $query->close();
      $info['nomination_count_reviewed'] = $this->countNominations($nominee_id,true);
      $info['nomination_count_all']      = $this->countNominations($nominee_id,false);
    }

    return $info;
  }

  function listNominees($award_category,$max=-1)
  {
    $sql = <<<EOT
SELECT nominee_id
FROM   nominee
WHERE  category_id = ?
ORDER BY primary_datum ASC
EOT;

    if($max > 0)
    {
      $sql .= "\nLIMIT $max";
    }

    $query = self::$db->prepare($sql);
    $query->bind_param('i',$award_category);
    $query->execute();
    $query->bind_result($id);

    $nomineeIdList = array();
    while($query->fetch())
    {
      $nomineeIdList[] = $id;
    }

    $query->close();

    $nomineeList = array();
    foreach ($nomineeIdList as $id)
    {
      $nomineeList[] = $this->getNomineeInfo($id);
    }

    return $nomineeList;
  }

  function listNomineesByCount($award_category,$max=-1)
  {
    $sql = <<<EOT
SELECT nominee_id
FROM   nominee
WHERE  category_id = ?
EOT;

    $query = self::$db->prepare($sql);
    $query->bind_param('i',$award_category);
    $query->execute();
    $query->bind_result($id);

    $nomineeIdList = array();
    while($query->fetch())
    {
      $nomineeIdList[] = $id;
    }

    $query->close();

    $nomineeList = array();
    foreach ($nomineeIdList as $id)
    {
      $nomineeList[] = $this->getNomineeInfo($id);
    }

    usort($nomineeList,'sortNomineesByCount');

    $returnNomineeList = array();

    if($max > 0)
    {
      for($i = 0; $i < $max; $i++)
      {
        $returnNomineeList[$i] = $nomineeList[$i];
      }
    }
    else
    {
      $returnNomineeList = $nomineeList;
    }

    return $returnNomineeList;
  }

  function regenerateNominations()
  {
    // Delete all current nominee records.
    $sql = <<<EOT
DELETE
FROM  nominee
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->close();

    // Get a list of all nominations
    $sql = <<<EOT
SELECT nomination_id,
       award_category_id,
       primary_datum,
       datum_2,
       datum_3
FROM   nominations
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($nominationId,$categoryId,$datum1,$datum2,$datum3);

    $nominations = array();
    while($query->fetch())
    {
      $nominationRecord = array();
      $nominationRecord['nomination_id'] = $nominationId;
      $nominationRecord['category_id']   = $categoryId;
      $nominationRecord['datum_1']       = $datum1;
      $nominationRecord['datum_2']       = $datum2;
      $nominationRecord['datum_3']       = $datum3;
      $nominations[] = $nominationRecord;
    }

    $query->close();

    $sql = <<<EOT
UPDATE nominations
SET    nominee_id = ?
WHERE  nomination_id = ?
EOT;
    $query = self::$db->prepare($sql);

    foreach($nominations as $nominationRecord)
    {
      $return = '';
      $nomineeId = $this->addNominee($nominationRecord['category_id'],$nominationRecord['datum_1'],$nominationRecord['datum_2'],$nominationRecord['datum_3'],$return);
      $query->bind_param('ii',$nomineeId,$nominationRecord['nomination_id']);
      $query->execute();
    }
    $query->close();
  }

  function updateNominationConfig($nominationsOpen,$nominationsClose)
  {
    // Only the most recent entry is used, so just add new entries
    $sql = <<<EOT
INSERT INTO nomination_configuration
(nomination_open,nomination_close)
VALUES (?,?)
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('ss',$nominationsOpen,$nominationsClose);
    $query->execute();
  }

  function getCurrentNominationConfig()
  {
    $sql = <<<EOT
SELECT nomination_open,
       nomination_close,
       preview_ends,
       eligibility_text_blob
FROM   nomination_configuration
ORDER BY update_time ASC
LIMIT 1
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($nominationOpen,$nominationClose,$previewEnds,$eligibilityText);

    if($query->fetch())
    {
      $nominationConfiguration = array('open'            => $nominationOpen,
                                       'close'           => $nominationClose,
                                       'preview_ends'    => $previewEnds,
                                       'eligibility_txt' => $eligibilityText);
      return($nominationConfiguration);
    }
    return array('open' => '2012-01-01 00:00:00',
                 'close'=> '2011-02-29 23:59:59');
  }

  function areNominationsOpen()
  {
    $sql = <<<EOT
SELECT IF (NOW() < nomination_open,
            'Hugo Award nominations are not open yet',
            IF(NOW() > nomination_close,
               'Hugo Award nominations have closed',
               'Hugo Award nominations are open'))
FROM   nomination_configuration
ORDER BY update_time ASC
LIMIT 1
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($nominationStatus);
    if($query->fetch())
    {
      return($nominationStatus);
    }
    return('Hugo Award nominations are not open yet');
  }

  function getVotingStatus()
  {
    $sql = <<<EOT
SELECT IF(NOW()<`preview_ends`,'Preview',
          IF(NOW()<`voting_open`,'BeforeOpen',
             IF(NOW()<`voting_close`,'Open',
                'Closed')))
FROM voting_configuration
ORDER BY update_time DESC
LIMIT 1
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($votingStatus);
    if($query->fetch())
    {
      return $votingStatus;
    }
    return 'BeforeOpen';
  }

  function inPreview()
  {
    $sql = <<<EOT
SELECT (NOW() <= nomination_configuration.preview_ends) AS preview_open
FROM   nomination_configuration
ORDER BY update_time ASC
LIMIT 1
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($inPreview);
    if($query->fetch())
    {
      return $inPreview;
    }
    return 0;
  }

  function getEligibilityText()
  {
    $sql = <<<EOT
SELECT eligibility_text_blob
FROM   nomination_configuration
ORDER BY update_time ASC
LIMIT 1
EOT;
    $query = self::$db->prepare($sql);
    $query->execute;
    $query->bind_result($eligibilityText);
    if($query->fetch())
    {
      return $eligibilityText;
    }
    return '<!-- Unable to fecth Eligibility Text -->';
  }

  function approveNominations($nominatorId,$userId)
  {
    $sql = <<<EOT
UPDATE nominations
SET    nomination_approved = 1
WHERE  nominator_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$nominatorId);
    $query->execute();

    $query->close();

    $sql = <<<EOT
UPDATE nominator
SET    ballot_reviewed = 1
       ballot_reviewed_by = ?
       ballot_review = NOW()
WHERE  nominator_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('ii',$userId,$nominatorId);
    $query->execute();

    $query->close();
  }

  function getBallot($nominatorId)
  {

    if(preg_match('(\\d+)',$nominatorId,$matches))
    {
      $nominatorId = $matches[0];
    }

    $categoryInfo = $this->getCategoryInfo();

    $sql = <<<EOT
SELECT primary_datum,
       datum_2,
       datum_3
FROM   nominations
WHERE  nominator_id = ?
  AND  award_category_id = ?
EOT;
    $query = self::$db->prepare($sql);

    $ballot['nominations'] = array();

    foreach($categoryInfo as $categoryId => $categoryRecord)
    {
      $ballot['nominations'][$categoryId] = array();
      $query->bind_param('ii',$nominatorId,$categoryId);
      $query->execute();
      $query->bind_result($datum1,$datum2,$datum3);
      while($query->fetch())
      {
        $nominationRec = array();
        $nominationRec[1] = $datum1;
        $nominationRec[2] = $datum2;
        $nominationRec[3] = $datum3;
        $ballot['nominations'][$categoryId][] = $nominationRec;
      }
    }

    return $ballot;
  }

  function getProvisionalBallot($nominatorId)
  {
    $categoryInfo = $this->getCategoryInfo();

    $sql = <<<EOT
SELECT primary_datum,
       datum_2,
       datum_3
FROM   provisional_nominations
WHERE  nominator_id = ?
  AND  award_category_id = ?
EOT;
    $query = self::$db->prepare($sql);

    $ballot['nominations'] = array();

    foreach($categoryInfo as $categoryId => $categoryRecord)
    {
      $ballot['nominations'][$categoryId] = array();
      $query->bind_param('ii',$nominatorId,$categoryId);
      $query->execute();
      $query->bind_result($datum1,$datum2,$datum3);
      while($query->fetch())
      {
        $nominationRec = array();
    $nominationRec[1] = $datum1;
    $nominationRec[2] = $datum2;
    $nominationRec[3] = $datum3;
    $ballot['nominations'][$categoryId][] = $nominationRec;
      }
    }

    return $ballot;
  }

  function cleanNominationsOfSlashes()
  {
    $sql = <<<EOT
SELECT nomination_id,
       primary_datum,
       datum_2,
       datum_3
FROM   nominations
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($nominationId,$datum1,$datum2,$datum3);

    $nominations = array();

    while($query->fetch())
    {
      $nominations[$nominationId] = array('datum1' => $datum1, 'datum2' => $datum2, 'datum3' => $datum3);
    }

    $query->close();

    $sql = <<<EOT
UPDATE nominations
SET    primary_datum = ?,
       datum_2       = ?,
       datum_3       = ?
WHERE  nomination_id = ?
EOT;
    $query = self::$db->prepare($sql);

    foreach($nominations as $nominationId => $data)
    {
      $query->bind_param('sssi',$data['datum1'],$data['datum2'],$data['datum3'],$nominationId);
      $query->execute();
    }

    $query->close();
  }

  function getNominationsForNominatior($nominatorId,$categoryId)
  {
    if(preg_match('(\\d+)',$nominatorId,$wsfs_hugo_matches))
    {
      $nominatorId = $wsfs_hugo_matches[0];
    }
    $sql = <<<EOT
SELECT nomination_id,
       primary_datum,
       datum_2,
       datum_3,
       nomination_deleted
FROM   nominations
WHERE  nominator_id = ?
  AND  award_category_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('ii',$nominatorId,$categoryId);
    $query->execute();
    $query->bind_result($nominationId,$datum1,$datum2,$datum3,$deleted);

    $nominations = array();

    while($query->fetch())
    {
      $nominationRecord = array('id' => $nominationId, 'datum1' => $datum1, 'datum2' => $datum2, 'datum3' => $datum3, 'deleted' => $deleted);
      $nominations[] = $nominationRecord;
    }

    $query->close();

    return $nominations;
  }

  function getProvisionalNominationsForNominatior($nominatorId,$categoryId)
  {
    $sql = <<<EOT
SELECT nomination_id,
       primary_datum,
       datum_2,
       datum_3,
       nomination_deleted
FROM   provisional_nominations
WHERE  nominator_id = ?
  AND  award_category_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('ii',$nominatorId,$categoryId);
    $query->execute();
    $query->bind_result($nominationId,$datum1,$datum2,$datum3,$deleted);

    $nominations = array();

    while($query->fetch())
    {
      $nominationRecord = array('id' => $nominationId, 'datum1' => $datum1, 'datum2' => $datum2, 'datum3' => $datum3, 'deleted' => $deleted);
      $nominations[] = $nominationRecord;
    }

    $query->close();

    return $nominations;
  }

  function approveBallot($reviewId,$sessionKey)
  {
    $userId = $this->getSessionUser($sessionKey);

    print("<!-- Found user id $userId from key $sessionKey -->\n");

    $sql = <<<EOT
UPDATE nominator
SET    ballot_reviewed    = 1,
       ballot_reviewed_by = ?,
       ballot_review      = NOW()
WHERE  nominator_id       = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('ii',$userId,$reviewId);
    $query->execute();
    $query->close();

    $sql = <<<EOT
UPDATE nominations
SET    nomination_approved = 1
WHERE  nominator_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$reviewId);
    $query->execute();
    $query->close();
  }

  function logEmail($nominatorId,$emailText,$result,$emailAddress)
  {
    global $_SERVER;

    $remoteIp = '127.0.0.1';
    if (isset($_SERVER['REMOTE_ADDR']))
    {
      $remoteIp = $_SERVER['REMOTE_ADDR'];
    }
    else
    {
      $remoteIp = '127.0.0.1';
    }

    $sql = <<<EOT
INSERT INTO email_log
(nominator_pin,send_time,send_result,email_text,email_address,server_ip)
VALUES (?,NOW(),?,?,?,?)
EOT;
    $query = self::$db->prepare($sql);
    print self::$db->error;
    $query->bind_param('sisss',$nominatorId,$result,$emailText,$emailAddress,$remoteIp);
    $query->execute();
    $query->close();
  }

  function moveNomination($nominationId,$newCategoryId)
  {
    print("<!-- Attempting to move nomination $nominationId to category $newCategoryId -->\n");

    $sql = <<<EOT
SELECT nominator_id,
       primary_datum,
       datum_2,
       datum_3
FROM   nominations
WHERE  nomination_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$nominationId);
    $query->execute();
    $query->bind_result($nominatorId,$datum1,$datum2,$datum3);

    if($query->fetch())
    {
      $query->close();
      $nominationCout = $this->countNominationsByNominator($nominatorId,$newCategory,false);
      if($nominationCount < 5)  // Don't transfer unless there is room
      {
  $nomineeId = $this->addNominee($newCategoryId,$datum1,$datum2,$datum3,$return);

        $sql = <<<EOT
UPDATE nominations
SET    award_category_id = ?,
       nominee_id        = ?
WHERE  nomination_id     = ?
EOT;
  $query = self::$db->prepare($sql);
  $query->bind_param('iii',$newCategoryId,$nomineeId,$nominationId);
  $query->execute();
  $query->close();
  print("<!-- Successful -->\n");
      }
    }
  }

  function deleteNomination($nominationId)
  {
    $sql = <<<EOT
UPDATE nominations
SET    nomination_deleted = 1
WHERE  nomination_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$nominationId);
    $query->execute();
    $query->close();
  }

  function getNewUnverifieds()
  {
    $sql = <<<EOT
SELECT nominator_id
FROM   unverified_nominator
WHERE  nominator_id IN (SELECT DISTINCT (0 - nominator_id) AS inverse_id FROM nominations WHERE nominator_id < 0)
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($id);

    $newUnverifiedIds = array();

    while($query->fetch())
    {
      $newUnverifiedIds[] = $id;
    }

    $query->close();

    $unverifiedNomineeInfo = array();

    foreach($newUnverifiedIds as $id)
    {
      $unverifiedNomineeInfo[$id] = $this->getUnverifiedNominationInfo($id);
    }

    return $unverifiedNomineeInfo;
  }


  function getNewUnverifiedVotes()
  {
    $sql = <<<EOT
SELECT nominator_id
FROM   unverified_voter
WHERE  nominator_id IN (SELECT DISTINCT (0 - member_id) AS inverse_id FROM hugo_ballot_entry WHERE member_id < 0)
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($id);

    $newUnverifiedIds = array();

    while($query->fetch())
    {
      $newUnverifiedIds[] = $id;
    }

    $query->close();

    $unverifiedVoteInfo = array();

    foreach($newUnverifiedIds as $id)
    {
      $unverifiedVoteInfo[$id] = $this->getUnverifiedVoteInfo($id);
    }

    return $unverifiedVoteInfo;
  }

  function getUnverifiedNominationInfo($nominatorId)
  {
    $sql = <<<EOT
SELECT first_name,
       last_name,
       address,
       city,
       state,
       postal_code,
       country,
       email_address,
       chicago_number,
       texas_number,
       reno_number,
       pin,
       time_added,
       (0 - nominator_id) AS inverse_id,
       (SELECT COUNT(nomination_id) FROM nominations WHERE nominator_id = inverse_id) AS total_nominations,
       (SELECT COUNT(DISTINCT award_category_id) FROM nominations WHERE nominator_id = inverse_id) AS categories_nominated
FROM unverified_nominator
WHERE nominator_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$nominatorId);
    $query->execute();

    $info = array();
    $query->bind_result($info['first_name'],
                        $info['last_name'],
      $info['address'],
      $info['city'],
      $info['state'],
      $info['postal_code'],
      $info['country'],
      $info['email_address'],
      $info['chicago_number'],
      $info['texas_number'],
      $info['reno_number'],
      $info['pin'],
      $info['time_added'],
      $info['inverse_id'],
      $info['total_nominations'],
      $info['categories_nominated']);
    $query->fetch();

    $query->close();

    return $info;
  }

  function getUnverifiedVoteInfo($nominatorId)
  {
    $sql = <<<EOT

SELECT first_name,
       last_name,
       address,
       city,
       state,
       postal_code,
       country,
       email_address,
       chicago_number,
       pin,
       time_added,
       (0 - nominator_id) AS inverse_id,
       (SELECT COUNT(ballot_entry_id) FROM hugo_ballot_entry WHERE member_id = inverse_id) AS total_votes,
       (SELECT COUNT(DISTINCT category_id) FROM hugo_ballot_entry WHERE member_id = inverse_id) AS categories_voted
FROM unverified_voter
WHERE nominator_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$nominatorId);
    $query->execute();

    $info = array();
    $query->bind_result($info['first_name'],
                        $info['last_name'],
      $info['address'],
      $info['city'],
      $info['state'],
      $info['postal_code'],
      $info['country'],
      $info['email_address'],
      $info['membership_number'],
      $info['pin'],
      $info['time_added'],
      $info['inverse_id'],
      $info['votes_cast'],
      $info['categories_voted']);
    $query->fetch();

    $query->close();

    return $info;
  }

  function transferUnverifiedNominations($fromId,$toId,$sessionKey)
  {
    $userId = $this->getSessionUser($sessionKey);

    $output = '';

    if($toId <= 1)
    {
      return '<P><STRONG><FONT COLOR="RED">Invalid Selection Made</FONT></STRONG></P>'."\n";
    }

    $inverseFromId = - $fromId;

    $categoryInfo = $this->getCategoryInfo();

    foreach($categoryInfo as $categoryId => $categoryData)
    {
      $existingCount = $this->countNominationsByNominator($toId,$categoryId,false);
      $newCount      = $this->countNominationsByNominator($inverseFromId,$categoryId,false);

      if(($existingCount > 0) && ($newCount > 0))
      {
        $output .= '<P>Replacing Nominations in '.$categoryData['name'].'</P>'."\n";
  $this->clearNominations($fromId,$categoryId);
      }
      else
      {
        $output .= '<P>Moving Nominations in '.$categoryData['name'].'</P>'."\n";
      }

      $sql = <<<EOT
UPDATE  nominations
SET     nominator_id = ?
WHERE   nominator_id = ?
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('ii',$toId,$inverseFromId);
      $query->execute();
      $query->close();
    }

    $sql = <<<EOT
UPDATE unverified_nominator
SET    ballot_reassigned = NOW(),
       reassigned_by = ?,
       reassigned_to = ?
WHERE  nominator_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('iii',$userId,$toId,$fromId);
    $query->execute();
    $query->close();


    return $output;
  }

  function addUpdateShortList($categoryId,$datum_1,$datum_2='',$datum_3='')
  {

    $categoryInfo = $this->getCategoryInfo();

    $datum_1 = htmlentities($datum_1);
    print("<!-- Modified \$datum_1: [$datum_1] -->\n");

    if((preg_match('/\s*&lt;em&gt;(.+)&lt;\/em&gt;(.*)&quot;(.+)&quot;(.*)/',$datum_1,$matches)) ||
       (preg_match('/\s*&lt;em&gt;(.+)&lt;\/em&gt;(.*)\\\&quot;(.+)\\\&quot;(.*))/',$datum_1,$matches)))
    {
      print("<!-- Matched: [$dataum_1] -->\n");
      $datum_1 = '<em>'.$matches[1].'</em>'.$matches[2].'&quot;'.$matches[3].'&quot;'.$matches[4];
    }
    elseif (preg_match('/\s*&lt;em&gt;(.+)&lt;\s*\/em&gt;/',$datum_1,$matches))
    {
      print("<!-- Matched: [$dataum_1] -->\n");
      $datum_1 = '<em>'.$matches[1].'</em>';
    }

    $datum_1 = preg_replace('/&amp;/','&',$datum_1);

    $datum_1 = preg_replace('/\\\\&quot;/','&quot;',$datum_1);

    // Build the sort_value from datum_1

    $sortValue = $datum_1;
    if ($categorData[$category_id]['personal_category'] != 1)
    {
      if (preg_match('<em>(.+)<\/em>.*\&quot;(.+)\&quot;/', $datum_1, $matches))
      {
        $sortValue = $matches[1] . ' ' . $matches[2];
      }
      elseif (preg_match('/<em>(.+)<\/em>/',$datum_1,$matches))
      {
        $sortValue = $matches[1];
      }
      elseif (preg_match('/\&quot;(.+)\&quot;/',$datum_1,$matches))
      {
        $sortValue = $matches[1];
      }

      if (preg_match('/^a\s+(.+)/i',$sortValue,$matches))
      {
        $sortValue = $matches[1];
      }
      elseif (preg_match('/the\s+(.+)/i',$sortValue,$matches))
      {
        $sortValue = $matches[1];
      }
    }
    else
    {
      if (preg_match('/^(.*)\s+(\S+)$',$sortValue,$matches))
      {
        $sortValue = $matches[2].', '.$matches[1];
      }

      }
    if($sortValue == 'No Award')
    {
      $sortValue = 'ZZZZZZ';
    }

    print("<!-- \$sortValue = [$sortValue] -->\n");

    $sql = <<<EOT
SELECT shortlist_id
FROM   hugo_shortlist
WHERE  category_id = ?
  AND  datum_1     = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('is',$categoryId,$datum_1);
    $query->execute();
    $query->bind_result($shortlistId);

    if($query->fetch())
    {
      print("<!-- \$shortlistId = $shortlistId -->\n");

      $query->close();
      $sql = <<<EOT
UPDATE hugo_shortlist
SET    datum_1      = ?,
       sort_value   = ?,
       datum_2      = ?,
       datum_3      = ?
WHERE  shortlist_id = ?
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('ssssi',$datum_1,$sortValue,$datum_2,$datum_3,$shortlistId);
      $query->execute();
      $query->close();
    }
    else
    {
      $sql = <<<EOT
INSERT INTO hugo_shortlist
(category_id,datum_1,sort_value,datum_2,datum_3)
VALUES
(?,?,?,?,?)
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('issss',$categoryId,$datum_1, $sortValue,$datum_2,$datum_3);
      $query->execute();
      $query->close();

      $sql = <<<EOT
SELECT shortlist_id
FROM   hugo_shortlist
WHERE  category_id = ?
  AND  datum_1     = ?
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('is',$categoryId,$datum_1);
      $query->execute();
      $query->bind_result($shortlistId);

      if(!$query->fetch())
      {
        $shortlistId = -1;
      }
    }

    return $shortlistId;
  }

  function getShortlistInfo($shortlistId)
  {
    $sql = <<<EOT
SELECT shortlist_id,
       datum_1,
       datum_2,
       datum_3,
       category_id
FROM   hugo_shortlist
WHERE  shortlist_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$shortlistId);
    $query->execute();
    $query->bind_result($dbId,$datum1,$datum2,$datum3,$categoryId);

    $shortlistInfo = array();

    if($query->fetch())
    {
      $shortlistInfo = array('shortlist_id'=>$dbId,'datum_1'=>$datum1,'datum_2'=>$datum2,'datum_3'=>$datum3,'category_id'=>$categoryId);
    }
    $query->close();

    return $shortlistInfo;
  }

  function deleteFromShortlist($shortlistId)
  {
    $sql = <<<EOT
DELETE
FROM   hugo_shortlist
WHERE  shortlist_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$shortlistId);
    $query->execute();
    $query->fetch();
    $query->close();
  }

  function getShortlist($categoryId)
  {
    $sql = <<<EOT
SELECT shortlist_id,
       datum_1,
       sort_value,
       datum_2,
       datum_3
FROM   hugo_shortlist
WHERE  category_id = ?
ORDER BY sort_value ASC
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$categoryId);
    $query->execute();
    $query->bind_result($shortlistId,$datum1,$sortValue,$datum2,$datum3);

    $shortlist = array();

    $noneOfTheAbove = array();

    while($query->fetch())
    {
      if($datum1 == 'No Award')
      {
        $noneOfTheAbove = array('shortlist_id' => $shortlistId, 'datum_1' => $datum1, 'datum_2' => $datum2, 'datum_3' => $datum3);
      }
      else
      {
        $shortlist[$shortlistId] = array('shortlist_id' => $shortlistId, 'datum_1' => $datum1, 'datum_2' => $datum2, 'datum_3' => $datum3);
      }
    }

    $query->close();

    if(count($noneOfTheAbove) == 0)
    {
      $shortlistId = $this->addUpdateShortList($categoryId,'No Award','','');

      $noneOfTheAbove = $shortlistId;
    }

    $shortlist[$noneOfTheAbove['shortlist_id']] = $noneOfTheAbove;

    return $shortlist;
  }

  function countGoTNominations()
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT nominator_id)
FROM   nominations
WHERE  award_category_id = ?
  AND  nomination_approved = 1
  AND  (primary_datum LIKE '%Game of Thrones%' OR datum_2 LIKE '%Game of Thrones%')
  AND nominator_id NOT IN (SELECT DISTINCT nominator_id
                           FROM nominations
                           WHERE award_category_id = ?
                             AND (primary_datum LIKE '%Game of Thrones%' OR datum_2 LIKE '%Game of Thrones%'))

EOT;
    $query = self::$db->prepare($sql);

    $longForm  = 7;
    $shortForm = 8;
    $query->bind_param('ii',$longForm,$shortForm);  // Long Form
    $query->execute();

    $query->bind_result($longCount);
    $query->fetch();

    $shortForm = 8;
    $query->bind_param('ii',$shortForm,$longForm); // Short Form
    $query->execute();

    $query->bind_result($shortCount);
    $query->fetch();

    $goTNominations = array('Long Form' => $longCount, 'Short Form' => $shortCount);

    return $goTNominations;
  }

  function categoryBallotCount($categoryId)
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT nominator_id)
FROM nominations
WHERE award_category_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$categoryId);
    $query->execute();
    $query->bind_result($count);
    $query->fetch();  // Always returns a value, no need to check

    return $count;
  }

  function getVerifiedPartialSubmissions()
  {
    $sql = <<<EOT
SELECT nominator_id,
       MAX(date_received),
       phase_1_data
FROM phase_1_log
WHERE nominator_id > 0
  AND nominator_id NOT IN (SELECT DISTINCT nominator_id FROM nominations WHERE nominator_id > 0)
GROUP BY nominator_id
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($nominatorId,$dateReceived,$phase1Data);

    $partialSubmissions = array();

    while($query->fetch())
    {
      $partialSubmissions[] = array('nominator_id'=>$nominatorId,'date_received'=>$dateReceived,'phase_1_data'=>$phase1Data);
    }

    return $partialSubmissions;
  }

  function getUnverifiedPartialSubmissions()
  {
    $sql = <<<EOT
SELECT nominator_id,
       MAX(date_received),
       phase_1_data
FROM phase_1_log
WHERE nominator_id < 0
  AND nominator_id NOT IN (SELECT DISTINCT nominator_id FROM nominations WHERE nominator_id < 0)
GROUP BY nominator_id
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($nominatorId,$dateReceived,$phase1Data);

    $possiblePartialSubmissions = array();

    while($query->fetch())
    {
      $possiblePartialSubmissions[] = array('nominator_id'=>$nominatorId,'date_received'=>$dateReceived,'phase_1_data'=>$phase1Data);
    }

    $query->close();

    $partialSubmissions = array();

    $sql = <<<EOT
SELECT nominator_id,
       nomination_date,
       ABS(TIMESTAMPDIFF(SECOND,nomination_date,?)) AS timediff
FROM   nominations
WHERE ABS(TIMESTAMPDIFF(SECOND,nomination_date,?)) < 10
EOT;
    $query = self::$db->prepare($sql);

    foreach($possiblePartialSubmissions as $submission)
    {
      $query->bind_param('ss',$submission['date_received'],$submission['date_received']);
      $query->execute();
      $query->bind_result($id,$date,$diff);  // Disposable

      if(!($query->fetch()))  // No match is what we are looking for
      {
        $partialSubmissions[] = $submission;
      }
    }

    $query->close();

    return $partialSubmissions;
  }

  function uniqueNominations()
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT nominator_id) AS count
FROM nominations
WHERE nominator_id > 1
   AND nomination_approved = 1
   AND nomination_deleted  = 0
EOT;
    $query = self::$db->prepare($sql);

    $query->execute();

    $query->bind_result($fullCount);

    $query->fetch();  // Will always return

    $counts = array('All' => $fullCount);

    $query->close();

    $sql = <<<EOT
SELECT award_categories.category_name,
       award_categories.ballot_position,
       COUNT(DISTINCT nominations.nominator_id) AS count
FROM award_categories, nominations
WHERE award_categories.category_id = nominations.award_category_id
   AND nominations.nominator_id > 1
   AND nominations.nomination_approved = 1
   AND nominations.nomination_deleted  = 0
GROUP BY award_categories.category_name
ORDER BY award_categories.ballot_position
EOT;
    $query = self::$db->prepare($sql);

    $query->execute();
    $query->bind_result($category,$order,$count);

    while($query->fetch())
    {
      $counts[$category] = $count;
    }

    $query->close();

    return $counts;
  }

  function getCrossCategoryEnteries()
  {
    // Query 1: Get the possible candidate entries
    $sql = <<<EOT
SELECT primary_datum,
       category_count,
       nomination_count
FROM   (SELECT primary_datum,
               COUNT(category_id) AS category_count,
               (SELECT COUNT(nomination_id) AS nomination_count
                FROM   nominations
      WHERE  primary_datum = nominee.primary_datum
        AND  nomination_approved = 1
      AND  nomination_deleted  = 0) AS nomination_count
        FROM nominee
        WHERE TRIM(primary_datum) NOT LIKE ''
        GROUP BY primary_datum
        ORDER BY category_count DESC) AS subquery
WHERE  category_count > 1
  AND  nomination_count >= 1
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($datum1,$categoryCount,$nominationCount);

    $crossCategoryEnteries = array();

    while($query->fetch())
    {
      $crossCategoryEnteries[] = array('datum_1'=>$datum1,'category_count'=>$categoryCount,'total_nomination_count'=>$nominationCount);
    }

    $query->close();

    // Query 2: Get the matching enteries
    $sql = <<<EOT
SELECT nominee.nominee_id,
       nominee.primary_datum,
       nominee.datum_2,
       nominee.datum_3,
       award_categories.category_id,
       award_categories.category_name,
       (SELECT COUNT(nomination_id)
        FROM   nominations
  WHERE  nominee_id = nominee.nominee_id
    AND  nomination_approved = 1
    AND  nomination_deleted  = 0) AS nomination_count
FROM   nominee, award_categories
WHERE  nominee.category_id = award_categories.category_id
  AND  nominee.primary_datum = ?
EOT;
    $query = self::$db->prepare($sql);

    foreach($crossCategoryEnteries as $index => $entry)
    {
      $query->bind_param('s',$entry['datum_1']);
      $query->execute();
      $query->bind_result($nomineeId,$datum1,$datum2,$datum3,$categoryId,$categoryName,$nominationCount);

      $crossCategoryEnteries[$index]['nomination_records'] = array();

      while($query->fetch())
      {
        $crossCategoryEnteries[$index]['nomination_records'][] = array('nominee_id'=>$nomineeId,'datum_1'=>$datum1,'datum_2'=>$datum2,'datum_3'=>$datum3,'category_id'=>$categoryId,'category_name'=>$categoryName,'nomination_count'=>$nominationCount);
      }
    }

    $query->close();

    // Query 3 - Get the count of unique nominators for each category - i.e. the number of people who have only nominated the nominee in one category
    $sql = <<<EOT
SELECT COUNT(DISTINCT nominator_id) as unique_nominators
FROM   nominations
WHERE  primary_datum       = ?
  AND  award_category_id   = ?
  AND  nomination_approved = 1
  AND  nomination_deleted  = 0
  AND  nominator_id NOT IN (SELECT DISTINCT nominator_id
                            FROM nominations
                            WHERE award_category_id != ?
            AND primary_datum = ?)
EOT;
    $query = self::$db->prepare($sql);

    foreach($crossCategoryEnteries as $index => $entry)
    {
      foreach($entry['nomination_records'] as $subIndex => $subEntry)
      {
        $query->bind_param('siis',$subEntry['datum_1'],$subEntry['category_id'],$subEntry['category_id'],$subEntry['datum_1']);
  $query->execute();
  $query->bind_result($uniqueNominators);
  $query->fetch();  // One result
  $crossCategoryEnteries[$index]['nomination_records'][$subIndex]['unique_nominators'] = $uniqueNominators;
      }
    }

    $query->close();

    return $crossCategoryEnteries;
  }

  function getShortlistCount($categoryId)
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT(shortlist_id))
FROM   hugo_shortlist
WHERE  category_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$categoryId);
    $query->execute();
    $query->bind_result($shortlistCount);
    if($query->fetch())
    {
      return $shortlistCount;
    }
  }

  function getFullShortlist()
  {
    $categoryInfo = $this->getCategoryInfo();

    foreach ($categoryInfo as $id => $info)
    {
      $categoryOrder[$categoryInfo[$id]['ballot_position']] = $id;
    }

    $sql = <<<EOT
SELECT shortlist_id,
       sort_value
FROM   hugo_shortlist
WHERE category_id = ?
ORDER BY sort_value
EOT;
    $query = self::$db->prepare($sql);

    $fullShortList = array();

    foreach($categoryOrder as $postion => $id)
    {
      $shortlist = array();

      $query->bind_param('i',$id);
      $query->execute();
      $query->bind_result($shortlistId,$name);
      while($query->fetch())
      {
        $shortlist[$shortlistId] = $name;
      }
      $fullShortList[$id] = $shortlist;
    }

    return   $fullShortList;
  }

  function validateMemberHugoDb($lastName1,$memberId1,$PIN1,$onlyCurrent = false)
  {
    $sql = <<<EOT
SELECT second_name,
       member_id,
       pin
FROM nomination_pin_email_info
WHERE pin = ?
EOT;
    if($onlyCurrent)
    {
      $sql .= "\n  AND source = 'CURRENT'";
    }
    $query = self::$db->prepare($sql);

    $query->bind_param('s',$PIN1);
    $query->execute();
    $query->bind_result($secondName2,$memberId2,$PIN2);
    if($query->fetch())
    {
      if($memberId1 == $memberId2)
      {
        return 1;
      }
      else if(soundex($lastName1) == soundex($secondName2))
      {
        return 1;
      }
      else
      {
        return 0;
      }
    }
    return 0;
  }

  function lookupMembership($email1,$firstName1,$lastName1)
  {
    $sql = <<<EOT
SELECT `first_name`,
       `second_name`,
       `member_id`,
       `pin`,
       `email`
FROM   `nomination_pin_email_info`
WHERE  `email` LIKE ?
  AND  `email` != ''
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('s',$email1);
    $query->execute();

    $memberList = array();

    $query->bind_result($firstName2,$lastName2,$memberId2,$PIN2,$email2);
    if(($email1 != '') && ($query->fetch()))
    {
      do
      {
        $memberList[] = array('first_name'=>$firstName2,'last_name'=>$lastName2,'member_id'=>$memberId2,'PIN'=>$PIN2,'email'=>$email2);
      }
      while($query->fetch());
    }
    else
    {
      $query->close();

      $sql = <<<EOT
SELECT `first_name`,
       `second_name`,
       `member_id`,
       `pin`,
       `email`
FROM   `nomination_pin_email_info`
WHERE  SOUNDEX(`first_name`) LIKE SOUNDEX(?)
  AND  SOUNDEX(`second_name`) LIKE SOUNDEX(?)
EOT;

      $query = self::$db->prepare($sql);
      $query->bind_param('ss',$firstName1,$lastName1);
      $query->execute();
      $query->bind_result($firstName2,$lastName2,$memberId2,$PIN2,$email2);
      if($query->fetch())
      {
        do
        {
          $memberList[] = array('first_name'=>$firstName2,'last_name'=>$lastName2,'member_id'=>$memberId2,'PIN'=>$PIN2,'email'=>$email2);
        }
        while($query->fetch());
      }
    }

    return $memberList;
  }

  function getNextUnmailed($mailing)
  {
    $sql = <<<EOT
SELECT pin_email_info_id,
       first_name,
       second_name,
       member_id,
       email,
       pin,
       source
FROM   nomination_pin_email_info

EOT;
    if($mailing == 1)
    {
      $sql .= 'WHERE  initial_mail_sent = 0'."\n";
    }
    elseif($mailing == 2)
    {
      $sql .= 'WHERE  second_mail_sent = 0'."\n";
    }
    else
    {
      $sql .= 'WHERE  third_mail_sent = 0'."\n";
    }

    $sql .= "ORDER BY pin_email_info_id DESC\nLIMIT 1\n";

    $query = self::$db->prepare($sql);

    $query->execute();
    $query->bind_result($id,$first_name,$second_name,$member_id,$email,$pin,$source);
    if($query->fetch())
    {

      $info['id']          = $id;
      $info['first_name']  = $first_name;
      $info['second_name'] = $second_name;
      $info['member_id']   = $member_id;
      $info['email']       = $email;
      $info['pin']         = $pin;
      $info['source']      = $source;

      return $info;
    }

    return false;
  }

    function markMailed($id,$mailing)
    {
      $sql = "UPDATE nomination_pin_email_info\n";
      if($mailing == 1)
      {
        $sql .= "SET initial_mail_sent = 1\n";
      }
      elseif ($mailing == 2)
      {
        $sql .= "SET second_mail_sent = 1\n";
      }
      else
      {
        $sql .= "SET third_mail_sent = 1\n";
      }
      $sql .= "WHERE pin_email_info_id = ?\n";

      $query = self::$db->prepare($sql);
      $query->bind_param('i',$id);
      $query->execute();
    }

    function addUpdatePinEmailRecord($first_name, $second_name, $member_id, $email, $pin, $source)
    {
//      print "email: $email\t";

      $sql = <<<EOT
SELECT pin_email_info_id
FROM   nomination_pin_email_info
WHERE  member_id = ?
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('i',$member_id);
      $query->execute();
      $query->bind_result($recordId);

      if($query->fetch())
      {
        $query->close();

        $sql = <<<EOT
UPDATE nomination_pin_email_info
SET    first_name  = ?,
       second_name = ?,
       pin         = ?,
       email       = ?,
       source      = ?
WHERE  member_id   = ?
EOT;
        $query = self::$db->prepare($sql);
        $query->bind_param('ssssss',$first_name,$second_name,$pin,$email,$source,$member_id);
        $query->execute();

//        print "Updated $pin\n";
      }
      else
      {
        $query->close();

        $sql = <<<EOT
INSERT INTO nomination_pin_email_info
(first_name, second_name, member_id, email, pin, source)
VALUES (?,?,?,?,?,?)
EOT;
        $query = self::$db->prepare($sql);
        $query->bind_param('ssisss',$first_name,$second_name,$member_id,$email,$pin,$source);
        $query->execute();

//        print "Addedd $pin\n";
      }
// Dummy comment
      $query->close();
    }

    function addUpdateVote($memberId,$categoryId,$shortlistId,$rank)
    {
      $return = "<!-- Adding Vote: $memberId, $categoryId, $shortlistId, $rank -->\n";

      $sql = <<<EOT
SELECT ballot_entry_id
FROM   hugo_ballot_entry
WHERE  category_id  = ?
  AND  member_id    = ?
  AND  short_list_id = ?
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('isi',$categoryId,$memberId,$shortlistId);
      $query->execute();
      $query->bind_result($ballotEntryId);

      if($query->fetch())
      {
        $query->close();

        $return .= "<!-- Found matching vote: $ballotEntryId -->\n";

        $sql = <<<EOT
UPDATE hugo_ballot_entry
SET    rank = ?,
       ip_added_from = ?,
       ballot_approved = 1
WHERE  ballot_entry_id = ?
EOT;
        $query = self::$db->prepare($sql);
        $query->bind_param('isi',$rank,$_SERVER['REMOTE_ADDR'],$ballotEntryId);
        $query->execute();
        $query->fetch();
        $query->close();
      }
      else
      {
        $query->close();

        $return .= "<!-- No matching vote found -->\n";

        $sql = <<<EOT
INSERT INTO hugo_ballot_entry
(member_id,category_id,short_list_id,rank,ip_added_from,ballot_approved)
VALUES
(?,?,?,?,?,1)
EOT;
        $query = self::$db->prepare($sql);
        $query->bind_param('siiis',$memberId,$categoryId,$shortlistId,$rank,$_SERVER['REMOTE_ADDR']);
        $query->execute();
        $query->close();
      }

      return $return;
    }

    function deleteVote($memberId,$categoryId,$shortlistId)
    {
      $sql = <<<EOT
SELECT ballot_entry_id
FROM   hugo_ballot_entry
WHERE  category_id  = ?
  AND  member_id    = ?
  AND  short_list_id = ?
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('isi',$categoryId,$memberId,$shortlistId);
      $query->execute();
      $query->bind_result($ballotEntryId);

      if($query->fetch())
      {
        $query->close();

        $return .= "<!-- Found matching vote: $ballotEntryId -->\n";

        $sql = <<<EOT
DELETE FROM hugo_ballot_entry
WHERE  ballot_entry_id = ?
EOT;
        $query = self::$db->prepare($sql);
        $query->bind_param('i',$ballotEntryId);
        $query->execute();
        $query->fetch();
        $query->close();
      }

      return;
    }

    function getVotes($categoryId,$voterId)
    {
      $sql = <<<EOT
SELECT short_list_id,
       rank
FROM   hugo_ballot_entry
WHERE  member_id = ?
  AND  category_id = ?
  AND  ballot_approved = 1
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('si',$voterId,$categoryId);
      $query->execute();
      $query->bind_result($shortlistId,$rank);

      $votes = array();
      while($query->fetch())
      {
        $votes[$shortlistId] = $rank;
      }

      return $votes;
    }

    function getPacketFileList($fetchAll=false)
    {
      $sql = <<<EOT
SELECT packet_file_id,
       file_short_description,
       file_download_name,
       file_format_notes,
       file_size,
       sha256sum
FROM   packet_files

EOT;

      if(!$fetchAll)
      {
        $sql .= "WHERE  show_on_packet_page = 1\n";
      }

      $sql .= 'ORDER BY file_position ASC';
      $query = self::$db->prepare($sql);
      $query->execute();
      $query->bind_result($id,$shortDescription,$downloadName,$fileFormatNotes,$fileSize,$sha256sum);

      $packetFileList = array();

      while($query->fetch())
      {
        $fileRecord = array('packet_file_id'         => $id,
                            'file_short_description' => $shortDescription,
                            'file_download_name'     => $downloadName,
                            'file_format_notes'      => $fileFormatNotes,
                            'file_size'              => $fileSize,
                            'sha256sum'              => $sha256sum);
        $packetFileList[$id] = $fileRecord;
      }

      $query->close();

      return $packetFileList;
    }

    function reversePacketLookup($downloadName)
    {
      $sql = <<<EOT
SELECT `packet_file_id`
FROM   `packet_files`
WHERE  `file_download_name` = ?;
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('s',$downloadName);
      $query->execute();
      $query->bind_result($fileId);
      if($query->fetch())
      {
        return $fileId;
      }
      else
      {
        return -1;
      }
    }

    function logPacketDownload($memberId,$downloadIp,$fileId,$userAgent)
    {
      $sql = <<<EOT
INSERT INTO `packet_download_log`
(`member_id`,`packet_file_id`,`user_agent`,`download_ip`,`download_complete`)
VALUES
(?, ?, ?, ?, NOW())
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('siss',$memberId,$fileId,$userAgent,$downloadIp);
      $query->execute();
      $query->fetch();
      $query->close();
    }

    function getDownloadCounts()
    {
      $sql = <<<EOT
SELECT COUNT(DISTINCT `dowload_id`) AS `total_downloads`,
       COUNT(DISTINCT `member_id`) AS `unique_members`,
       COUNT(DISTINCT `download_ip`) AS `unique_ips`
FROM `packet_download_log`
WHERE 1
EOT;
      $query = self::$db->prepare($sql);
      $query->execute();
      $query->bind_result($total_downloads,$unique_members,$unique_ips);
      $query->fetch();

      $counts = array('total_downloads' => $total_downloads,
                      'unique_members'  => $unique_members,
                      'unique_ips'      => $unique_ips);

      return $counts;
    }

    function getDownloadCountsByUserAgent()
    {
      $sql = <<<EOT
SELECT DISTINCT `user_agent` AS `user_agent`,
       COUNT(DISTINCT `dowload_id`) AS `total_downloads`,
       COUNT(DISTINCT `member_id`) AS `unique_members`,
       COUNT(DISTINCT `download_ip`) AS `unique_ips`
FROM `packet_download_log`
WHERE `user_agent` NOT LIKE ''
GROUP BY `user_agent`
EOT;
      $query = self::$db->prepare($sql);
      $query->execute();
      $query->bind_result($userAgent,$total_downloads,$unique_members,$unique_ips);
      $counts = array();
      while($query->fetch())
      {
        $agentCount = array('user_agent'      => $userAgent,
                            'total_downloads' => $total_downloads,
                            'unique_members'  => $unique_members,
                            'unique_ips'      => $unique_ips);

        $counts[] = $agentCount;
      }

      return $counts;
    }

    function getDownloadCountsByDay()
    {
      date_default_timezone_set('America/New_York');

      $sql = <<<EOT
SELECT COUNT(dowload_id) AS downloads,
       COUNT(DISTINCT member_id) AS unique_members,
       COUNT(DISTINCT download_ip) AS unique_ip,
       DATE(download_complete) AS download_date
FROM `packet_download_log`
GROUP BY DATE(download_complete)
EOT;
      $query = self::$db->prepare($sql);
      $query->execute();
      $query->bind_result($total_downloads,$unique_members,$unique_ips,$date);

      $counts = array();
      while($query->fetch())
      {
        $textDate = $date;
        if(preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/',$date,$matches))
        {
          $month = $matches[2];
          $day   = $matches[3];
          $year  = $matches[1];
          $unixDate = mktime(0,0,0,$month,$day,$year,true);

          $textDate = date('d-M-Y',$unixDate);
        }

        $dayCount = array('date'            => $textDate,
                          'total_downloads' => $total_downloads,
                          'unique_members'  => $unique_members,
                          'unique_ips'      => $unique_ips);

        $counts[] = $dayCount;
      }

      return $counts;
    }
    function getVoteCounts()
    {
      $sql = <<<EOT
SELECT COUNT(DISTINCT `member_id`) AS `unique_voters`,
       COUNT(DISTINCT `ip_added_from`) AS `unique_ips`
FROM `hugo_ballot_entry`
WHERE 1
EOT;
      $query = self::$db->prepare($sql);
      $query->execute();
      $query->bind_result($unique_members,$unique_ips);
      $query->fetch();

      $counts = array('unique_members'  => $unique_members,
                      'unique_ips'      => $unique_ips);

      return $counts;
    }

    function getVoteCountsByDay()
    {
      date_default_timezone_set('America/New_York');

      $sql = <<<EOT
SELECT COUNT(DISTINCT `member_id`) AS `unique_voters`,
       COUNT(DISTINCT `ip_added_from`) AS `unique_ips`,
       DATE(`time_added`) AS `vote_date`
FROM `hugo_ballot_entry`
WHERE 1
GROUP BY DATE(`time_added`)
EOT;
      $query = self::$db->prepare($sql);
      $query->execute();
      $query->bind_result($unique_members,$unique_ips,$date);

      $counts = array();
      while($query->fetch())
      {
        $textDate = $date;
        if(preg_match('/(\d\d\d\d)-(\d\d)-(\d\d)/',$date,$matches))
        {
          $month = $matches[2];
          $day   = $matches[3];
          $year  = $matches[1];
          $unixDate = mktime(0,0,0,$month,$day,$year,true);

          $textDate = date('d-M-Y',$unixDate);
        }

        $dayCount = array('date'            => $textDate,
                          'total_downloads' => $total_downloads,
                          'unique_members'  => $unique_members,
                          'unique_ips'      => $unique_ips);

        $counts[] = $dayCount;
      }

      return $counts;
    }

    function getVoters($categoryId)
    {
      $sql = <<<EOT
SELECT DISTINCT member_id
FROM hugo_ballot_entry
WHERE category_id = ?
  AND ballot_approved = 1
EOT;

      $query = self::$db->prepare($sql);
      $query->bind_param('i',$categoryId);
      $query->execute();
      $query->bind_result($memberId);

      $voters = array();

      while($query->fetch())
      {
        $voters[] = $memberId;
      }

      return $voters;
    }

    function getRemainingVoters($categoryId,$excluded='')
    {
      $sql = <<<EOT
SELECT DISTINCT member_id
FROM   hugo_ballot_entry
WHERE  category_id = ?
  AND  ballot_approved = 1
EOT;

      if(is_array($excluded))
      {
        $excluded = implode(',',$excluded);
      }

      if($excluded != '')
      {
        $sql .= "  AND short_list_id NOT IN ($excluded)\n";
      }

      $query = self::$db->prepare($sql);
      $query->bind_param('i',$categoryId);
      $query->execute();

      $query->bind_result($memberId);

      $voters = array();

      while($query->fetch())
      {
        $voters[] = $memberId;
      }

      return $voters;
    }

    function getVoteBallot($memberId,$categoryId)
    {
      $sql = <<<EOT
SELECT hugo_shortlist.datum_1,
       hugo_shortlist.sort_value,
       ballot_entry.rank
FROM   (hugo_shortlist LEFT OUTER JOIN
       (SELECT * FROM hugo_ballot_entry WHERE member_id = ?) AS ballot_entry
       ON hugo_shortlist.shortlist_id = ballot_entry.short_list_id)
WHERE  hugo_shortlist.category_id = ?
ORDER BY hugo_shortlist.sort_value

EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('si',$memberId,$categoryId);
      $query->execute();

      $query->bind_result($datum1,$sortValue,$rank);

      $ballot = array();
      while($query->fetch())
      {
        $ballot[$datum1] = $rank;
        if($rank == 'NULL')
        {
          $ballot[$datum1] == '';
        }
      }

      return $ballot;
    }

    function getNoAward($categoryId)
    {
      $sql = <<<EOT
SELECT shortlist_id
FROM   hugo_shortlist
WHERE  category_id = ?
  AND  datum_1 LIKE 'No Award'
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('i',$categoryId);
      $query->execute();

      $query->bind_result($shortlistId);

      // Should only be one, return first
      if($query->fetch())
      {
        $query->close();
        return($shortlistId);
      }

      return null;
    }

    function getRank($memberId,$shortlistId)
    {
      $sql = <<<EOT
SELECT rank
FROM   hugo_ballot_entry
WHERE  member_id = ?
  AND  short_list_id = ?
  AND  ballot_approved = 1
EOT;
      $query = self::$db->prepare($sql);

      $query->bind_param('si',$memberId,$shortlistId);
      $query->execute();

      $query->bind_result($rank);

      if($query->fetch())
      {
        return $rank;
      }

      return $null;
    }

    protected static $vote231Count = 0;

    function getCurrentVote($memberId,$categoryId,$excluded)
    {
      if(is_array($excluded))
      {
        if(count($excluded) > 0)
        {
          $excluded = implode(',',$excluded);
        }
        else
        {
          $excluded = '';
        }
      }

      $sql = <<<EOT
SELECT `short_list_id`,
       `rank`
FROM `hugo_ballot_entry`
WHERE `member_id`= ?
  AND `category_id` = ?
  AND `ballot_approved` = 1
EOT;
      if($excluded != '')
      {
        $sql .= "\n  AND short_list_id NOT IN ($excluded)";
      }

      $sql .= "\nORDER BY `rank` ASC\n";

      $query = self::$db->prepare($sql);
      $query->bind_param('si',$memberId,$categoryId);
      $query->execute();

      $shortlistId = -1;
      $rank = -1;

      $query->bind_result($shortlistId,$rank);
      $query->store_result();

      if($query->num_rows >= 1)
      {
        $query->fetch();
      }

      return(array('short_list_id' => $shortlistId, 'rank'=> $rank));
    }


    function countFirstPlaceVotes($shortListId)
    {
      $sql = <<<EOT
SELECT COUNT(DISTINCT member_id)
FROM hugo_ballot_entry
WHERE short_list_id = ?
  AND rank = 1
  AND ballot_approved = 1
EOT;
      $query = self::$db->prepare($sql);

      $query->bind_param('i',$shortListId);

      $query->execute();

      $query->bind_result($firstPlaceVotes);

      $query->fetch();

      return($firstPlaceVotes);
    }

    function addBallotCount($shortlistId,$placement,$round,$count)
    {
      $sql = <<<EOT
DELETE
FROM   hugo_ballot_counts
WHERE  shortlist_id = ?
  AND  placement    = ?
  AND  round        = ?
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('iii',$shortlistId,$placement,$round);
      $query->execute();
      $query->fetch();
      $query->close();

      $sql = <<<EOT
INSERT INTO hugo_ballot_counts
(shortlist_id, placement, round, count)
VALUES (?, ?, ?, ?)
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('iiii',$shortlistId,$placement,$round,$count);
      $query->execute();
      $query->fetch();
      $query->close();
    }

    function getResultsOrder($categoryId,$placement)
    {
      $sql = <<<EOT
SELECT hugo_shortlist.shortlist_id,
       hugo_shortlist.datum_1,
       (SELECT MAX(round) FROM hugo_ballot_counts WHERE shortlist_id = hugo_shortlist.shortlist_id AND placement = ?) AS last_round_present
FROM   hugo_shortlist
WHERE  category_id = ?
ORDER BY last_round_present DESC
EOT;

      $query = self::$db->prepare($sql);
      $query->bind_param('ii',$placement,$categoryId);
      $query->execute();

      $query->bind_result($shortlistId,$datum1,$lastRound);

      $resultsOrder = array();

      while($query->fetch())
      {
        if(!is_null($lastRound))
        {
          $resultsOrder[$shortlistId] = $lastRound;
        }
      }

      return $resultsOrder;
    }

    function getBallotCounts($shortlistId,$placement)
    {
      $sql = <<<EOT
SELECT round,
       count
FROM   hugo_ballot_counts
WHERE  shortlist_id = ?
  AND  placement    = ?
EOT;
      $query = self::$db->prepare($sql);

      $query->bind_param('ii',$shortlistId,$placement);
      $query->execute();
      $query->bind_result($round,$count);

      $ballotCounts = array();

      while($query->fetch())
      {
        $ballotCounts[$round] = $count;
      }

      $query->close();

      return($ballotCounts);
    }

    function getNoAwardId($categoryId)
    {
      print("<!-- \$categoryId = $categoryId -->\n");

      $sql = <<<EOT
SELECT shortlist_id
FROM   hugo_shortlist
WHERE  category_id = ?
  AND  datum_1 LIKE '%No Award%'
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('i',$categoryId);
      $query->execute();

      $noAwardId = -1;

      $query->bind_result($noAwardId);

      // Return only 1, should only be one
      if(!$query->fetch())
      {
        print("<!-- No Fetch Performed -->\n");
      }

      $query->close();

      return $noAwardId;
    }

    function getNoAwardCompairson($winner,$noAward)
    {
      $sql = <<<EOT
SELECT `member_id`,
       `short_list_id`,
       `rank`
FROM `hugo_ballot_entry`
WHERE (`short_list_id` = ? OR `short_list_id` = ?)
  AND `ballot_approved` = 1
EOT;
      $query = self::$db->prepare($sql);
      $query->bind_param('ii',$winner,$noAward);
      $query->execute();
      $query->bind_result($memberId,$shortListId,$rank);

      $voteData = array();

      while($query->fetch())
      {
        if(!isset($voteData[$memberId]))
        {
          $voteData[$memberId] = array($winner => 999, $noAward => 999);
        }
        $voteData[$memberId][$shortListId] = $rank;
      }

      $query->close();

      $winnerHigher  = 0;
      $noAwardHigher = 0;

      foreach($voteData as $memberId=>$results)
      {
        if($results[$winner] < $results[$noAward])
        {
          $winnerHigher += 1;
        }
        elseif($results[$winner] > $results[$noAward])
        {
          $noAwardHigher += 1;
        }
      }

      return array("winner" => $winnerHigher, "noAward" => $noAwardHigher);
    }
  function transferNominations($fromId,$toId)
  {
    $sql = <<<EOT
SELECT nominee_id
FROM   nominee
WHERE  nominee_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$fromId);
    $query->execute();
    if(!$query->fetch())
    {
      return;
    }

    $query->bind_param('i',$toId);
    $query->execute();
    if(!$query->fetch())
    {
      return;
    }

    print("<!-- About to transfer all nominations for id $fromId to id $toId -->\n");

    $query->close();

    $sql = <<<EOT
UPDATE nominations
SET    nominee_id = ?
WHERE  nominee_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('ii',$toId,$fromId);
    $query->execute();

    $query->close();

    $sql= <<<EOT
DELETE FROM nominee
WHERE  nominee_id = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$fromId);
    $query->execute();
  }

  function getEmailHugoDb($pin)
  {
    $sql = <<<EOT
SELECT email
FROM   nomination_pin_email_info
WHERE  pin = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('s',$pin);
    $query->execute();
    $query->bind_result($email);
    if($query->fetch())
    {
      return $email;
    }
    else
    {
      return '';
    }
  }

  function getMemberInfoFromPin($pin)
  {
    $sql = <<<EOT
SELECT `first_name`,
       `second_name`,
       `member_id`,
       `email`,
       `source`
FROM   nomination_pin_email_info
WHERE  pin = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('s',$pin);
    $query->execute();
    $query->bind_result($firstName,$secondName,$memberId,$email,$source);
    if($query->fetch())
    {
      return array('first_name'  => $firstName,
                   'second_name' => $secondName,
                   'member_id'   => $memberId,
                   'email'       => $email,
                   'source'      => $source);
    }
    else
    {
      return array();
    }
  }

  function getAllMemberInfo()
  {
    $sql = <<<EOT
SELECT `first_name`,
       `second_name`,
       `member_id`,
       `email`,
       `source`,
       `pin`
FROM   nomination_pin_email_info
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($firstName,$secondName,$memberId,$email,$source,$pin);
    $memberInfo = array();
    while($query->fetch())
    {
      $memberRecord = array('first_name'  => $firstName,
                   'second_name' => $secondName,
                   'member_id'   => $memberId,
                   'email'       => $email,
                   'source'      => $source,
                   'pin'         => $pin);
      $memberInfo[$pin] = $memberRecord;
    }

    $query->close();
    return $memberInfo;
  }

  function getSelectMemberInfo($pinList)
  {
    $pinList = implode(',',$pinList);

    $sql = <<<EOT
SELECT `first_name`,
       `second_name`,
       `member_id`,
       `email`,
       `source`,
       `pin`
FROM   `nomination_pin_email_info`
WHERE  `pin` in ($pinList)
EOT;
    print("<!-- \$sql\n$sql\n-->\n");
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($firstName,$secondName,$memberId,$email,$source,$pin);
    $memberInfo = array();
    while($query->fetch())
    {
      $memberRecord = array('first_name'  => $firstName,
                   'second_name' => $secondName,
                   'member_id'   => $memberId,
                   'email'       => $email,
                   'source'      => $source,
                   'pin'         => $pin);
      $memberInfo[$pin] = $memberRecord;
    }

    $query->close();

    return $memberInfo;
  }

  function getNominators($categoryId = -1)
  {
    $sql = <<<EOT
SELECT DISTINCT `nominator_id`
FROM   `nominations`
EOT;
    if($categoryId != -1)
    {
      $sql .= "\nWHERE  `award_category_id` = ?\n";
    }

    $query = self::$db->prepare($sql);
    if($categoryId != -1)
    {
      $query->bind_param('i',$categoryId);
    }
    $query->execute();
    $query->bind_result($nominatorId);
    $nominators = array();
    while($query->fetch())
    {
      $nominators[] = $nominatorId;
    }

    return $nominators;
  }

  function logNominationPost()
  {
    global $_POST;
    global $_SERVER;

    $sql = <<<EOT
INSERT INTO nomination_post_summary
(post_contents,server_contents)
VALUES (?, ?)
EOT;
    $query = self::$db->prepare($sql);

    ob_start();
    var_dump($_POST);
    $post_contents = ob_get_contents();
    ob_end_clean();
    ob_start();
    var_dump($_SERVER);
    $server_contents = ob_get_contents();
    ob_end_clean();

    $query->bind_param('ss',$post_contents,$server_contents);
    $query->execute();
    $query->close();
  }

  function logNominationPage($pageText)
  {
    global $_SERVER;

    $sql = <<<EOT
INSERT INTO nomination_page_log
(nomination_page,ip_received_from,timestamp)
VALUES (?, ?, CURRENT_TIMESTAMP)
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('ss',$pageText,$_SERVER['REMOTE_ADDR']);
    $query->execute();
    $query->close();
  }

  function getLatestNominationDate($nominatorId)
  {
    if(preg_match('(\\d+)',$nominatorId,$matches))
    {
      $nominatorId = $matches[0];
    }

    $sql = <<<EOT
SELECT nomination_date
       FROM   nominations
       WHERE  nominator_id = ?
       ORDER BY nomination_date DESC
       LIMIT 1
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$nominatorId);
    $query->execute();
    $query->bind_result($latestNominationDate);
    $latestNominationDate = '';
    $query->fetch();
    return $latestNominationDate;
  }

  function getCurrentPins()
  {
    $sql = <<<EOT
SELECT DISTINCT SUBSTR( `pin` , 3 ) AS `stripped_pin`
FROM `nomination_pin_email_info`
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($nextPin);

    $currentPins = array();
    while($query->fetch())
    {
      $currentPins[] = $nextPin;
    }
    $query->close();

    return $currentPins;
  }

  function getVotedPins()
  {
    $sql = <<<EOT
SELECT DISTINCT `member_id`
FROM   `hugo_ballot_entry`
EOT;
    $query = self::$db->prepare($sql);
    $query->execute();
    $query->bind_result($nextPin);

    $votedPins = array();
    while($query->fetch())
    {
      $votedPins[] = $nextPin;
    }
    $query->close();

    return $votedPins;
  }

  function getOrphanBallots()
  {
    $currentPins = self::getCurrentPins();
    $votedPins   = self::getVotedPins();

    // DEBUG
 //print("<!-- \$currentPins:\n");
 //var_dump($currentPins);
 //print("\n\n\$votedPins:\n");
 //var_dump($votedPins);
 //print("\n-->\n");

    $orphanPins = array();

    foreach($votedPins as $pin)
    {
      if(!in_array($pin,$currentPins,false))
      {
        $orphanPins[] = $pin;
      }
    }

    $sql = <<<EOT
SELECT `email_address`,
       `send_time`
FROM   `email_log`
WHERE  SUBSTR(`nominator_pin`,3) = ?
ORDER BY `send_time` DESC
LIMIT 1
EOT;
    $query = self::$db->prepare($sql);

    $orphanBallotList = array();

    foreach($orphanPins as $orphan)
    {
      $query->bind_param('s',$orphan);
      $query->execute();
      $query->bind_result($email,$sendTime);
      if($query->fetch())
      {
        $orphanBallotList[$orphan] = array('email' => $email, 'send_time' => $sendTime);
      }
      else
      {
        $orphanBallotList[$orphan] = array('email' => 'No Info', 'send_time' => 'No Info');
      }
    }

    return $orphanBallotList;
  }

  function getVoterSummary($voterId)
  {
    $voterInfo = array();

    // Get the personal info from the membership database
    $sql = <<<EOT
SELECT `first_name`,
       `second_name`,
       `member_id`,
       `pin`
FROM   `nomination_pin_email_info`
WHERE  SUBSTR(`pin`,3) = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('s',$voterId);
    $query->execute();
    $query->bind_result($firstName,$secondName,$memberId,$pin);
    if($query->fetch())
    {
      $voterInfo = array('first_name'  => $firstName,
                         'second_name' => $secondName,
                         'member_id'   => $memberId,
                         'pin'         => $pin);
    }
    else
    {
      $voterInfo = array('first_name'     => 'Orphan',
                         'second_name'    => 'Orphan',
                         'member_id'      => 'Unknown',
                         'pin'            => "SQ$voterId",
                         'Import records' => `grep -i SQ$voterId ../import/*.csv 2>&1`); // */
    }
    $query->close();

    $sql = <<<EOT
SELECT `ip_added_from`,
       `time_added`,
       `ballot_approved`
FROM   `hugo_ballot_entry`
WHERE  `member_id` = ?
ORDER BY `time_added` DESC
LIMIT 1
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('i',$voterId);
    $query->execute();
    $query->bind_result($voterInfo['ip_added_from'],$voterInfo['time_added'],$voterInfo['ballot_approved']);
    if(!$query->fetch())
    {
      $voterInfo['ip_added_from']   = 'No Votes';
      $voterInfo['time_added']      = 'No Votes';
      $voterInfo['ballot_approved'] = 0;
     }
    $query->close();

    return $voterInfo;
  }

  function approveVotes($voterId,$approved)
  {
    $sql = <<<EOT
UPDATE `hugo_ballot_entry`
SET    `ballot_approved` = ?
WHERE  `member_id` = ?
EOT;
    $query = self::$db->prepare($sql);
    $query->bind_param('ii',$approved,$voterId);
    $query->execute();
    $query->close();

    return "\$voterId = $voterId\n\$approved = $approved\n";

  }

  function emptyMembership()
  {
    $sql = <<<EOT
TRUNCATE `nomination_pin_email_info`
EOT;
    $return = self::$db->query($sql);

    return $return;
  }
}

// Remove automatically added escape characters
function cleanEntry($string)
{
  $newString = stripslashes($string);

  return $string;
}

function sortNomineesByCount($a, $b)
{
  return ($a['nomination_count_reviewed'] == $b['nomination_count_reviewed']) ? 0 : (($a['nomination_count_reviewed'] > $b['nomination_count_reviewed']) ? -1 : 1);
}


