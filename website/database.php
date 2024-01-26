<?PHP
/*
 * Copyright (C) 2015-2024 Ronald B. Oakes
 *
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of  MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program.  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * This file contains the database class.
 * It encapsulates all of the functions for interacting with the Hugo Award
 * database schema initially developed by Ron Oakes for Chicon 7 and subsequently used for at least Loncon 3, Sasquan,
 * MidAmerCon II, and Worldcon 76 with modifications.
 *
 * Given the intended object oriented design of the overall system, another class that implements the same members and
 * provides the same results should allow for a different database schema, or even type of database, to be used.
 */
require_once ('./db_config.php');

/**
 * Database Handler.
 * All database transactions for the main Hugo Award database are processed through this class.
 */
class database
{
  /**
   * Database handle for accessing the mysql database through mysqli.
   */
  protected static $db;

  /**
   * Root name of the database.
   */
  protected static $dbName;

  /**
   * Constructor for the database class.
   *
   * @param $retro boolean
   *          (Optional)
   *          set to true to indicate that this is the database for the Retro Hugo Awards.
   */
  function database($retro = false)
  {
    $dbRootName = WSFS_HUGO_DB_ROOT_NAME;
    $dbRetroName = WSFS_HUGO_DB_ROOT_NAME_RETRO;
    // $dbYear = WSFS_HUGO_DEFAULT_YEAR;
    if (! $retro)
    {
      self::$dbName = $dbRootName;
    }
    else
    {
      self::$dbName = $dbRetroName;
    }
    // print ('<!-- self::$dbName: ['.self::$dbName.'] -->'."\n");
    // print ('<!-- '); debug_print_backtrace(); print(" -->\n");

    self::$db = new mysqli ( WSFS_HUGO_DB_HOST, WSFS_HUGO_DB_USER, WSFS_HUGO_DB_PASSWORD, self::$dbName );
  }

  /**
   * Returns the information on the database connection.
   *
   * @return array a hash containing the database connection information.
   */
  function getConnectInfo()
  {
    return array (
        'host' => WSFS_HUGO_DB_HOST,
        'user' => WSFS_HUGO_DB_USER,
        'password' => WSFS_HUGO_DB_PASSWORD,
        'name' => self::$dbName
    );
  }

  /**
   * Shortcut function to get a handle to the database.
   *
   *  @warning Use with caution
   * @return object The handle to the database.
   */
  function getDb()
  {
    return self::$db;
  }

  /**
   * Function to add or update a Hugo Award category within the database.
   *
   * @param $name string
   *          Short name of the category, must be unique
   * @param $description String
   *          The description of the category
   * @param $ballotPosition int
   *          how far down the ballot with this category be listed
   * @param $primary_datum_description string
   *          what is the description of the primary datum Title (for most works), or individual (for individual awards)
   * @param $datum_2_description string
   *          what is the description of the second datum Author for most works, writer/director for Dramatic Presentation, etc.
   * @param $datum_3_description string
   *          what is the description of the thrid datum
   *          
   * @return int The Assigned category ID for this category if it successfully was added or updated, -1 if anything failed.
   */
  function addUpdateCategory($name, $description, $ballotPosition, $primary_datum_description, $datum_2_description, $datum_3_description)
  {
    $sql = <<<EOT
SELECT category_id
FROM   award_categories
WHERE  category_name = ?
EOT;

    $query = self::$db->prepare ( $sql );

    $query->bind_param ( 's', $name );
    $query->execute ();

    $query->bind_result ( $category_id );

    if ($query->fetch ()) // Existing category update
    {
      $query->close ();

      $sql = <<<EOT
UPDATE award_categories
SET    category_description      = ?,
       ballot_position           = ?,
       primary_datum_description = ?,
       datum_2_description       = ?,
       datum_3_description       = ?
WHERE  category_id = ?
EOT;
      $query = self::$db->prepare ( $sql );
      $query->bind_param ( 'sisssi', $description, $ballotPosition, $primary_datum_description, $datum_2_description, $datum_3_description, $category_id );
      $query->execute ();

      return $category_id;
    }
    else // Add
    {
      $query->close ();

      $sql = <<<EOT
INSERT INTO award_categories
(category_name,category_description,ballot_position,primary_datum_description,datum_2_description,datum_3_description)
VALUES (?,?,?,?,?,?)
EOT;
      $query = self::$db->prepare ( $sql );
      print ("<!-- " . self::$db->error . " -->\n") ;
      $query->bind_param ( 'ssisss', $name, $description, $ballotPosition, $primary_datum_description, $datum_2_description, $datum_3_description );
      $query->execute ();

      $sql = <<<EOT
SELECT category_id
FROM   award_categories
WHERE  category_name = ?
EOT;

      $query = self::$db->prepare ( $sql );

      $query->bind_param ( 's', $name );
      $query->execute ();

      $query->bind_result ( $category_id );

      if ($query->fetch ())
      {
        return $category_id;
      }
      else
      {
        return - 1;
      }
    }
  }

  /**
   * Function to get the Hugo Award Categories.
   *
   * @return array A hash containing the Name, Description, Ballot Position, Data descriptions, the number of items on the short list (finalists), if the description of this category should be included in final voting ballots, and if this is a category for an individual person (or team).
   */
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
    $query = self::$db->prepare ( $sql );
    $query->execute ();

    $query->bind_result ( $id, $name, $description, $includeDescription, $ballotPosition, $primary_datum_description, $datum_2_description, $datum_3_description, $personal_category );

    $categories = array ();

    while ( $query->fetch () )
    {
      $categoryInfo = array ();
      $categoryInfo ['id'] = $id;
      $categoryInfo ['name'] = $name;
      $categoryInfo ['description'] = $description;
      $categoryInfo ['include_description'] = $includeDescription;
      $categoryInfo ['ballot_position'] = $ballotPosition;
      $categoryInfo ['primary_datum_description'] = $primary_datum_description;
      $categoryInfo ['datum_2_description'] = $datum_2_description;
      $categoryInfo ['datum_3_description'] = $datum_3_description;
      $categoryInfo ['personal_category'] = $personal_category;

      $categories [$id] = $categoryInfo;
    }

    $query->close ();

    $sql = <<<EOT
SELECT COUNT(shortlist_id) AS shortlist_count
FROM   hugo_shortlist
WHERE  category_id = ?
EOT;
    $query = self::$db->prepare ( $sql );

    // Loop through the retrieved categories to get the number of items on the short list.
    foreach ( $categories as $id => $categoryInfo )
    {
      $query->bind_param ( 'i', $id );
      $query->execute ();
      $query->bind_result ( $shortlistCount );
      if (! $query->fetch ())
      {
        $shortlistCount = 0;
      }
      $categories [$id] ['shortlist_count'] = $shortlistCount;
    }

    return $categories;
  }

  /**
   * Returns the count of unique individuals who have nominating ballots
   *
   * @return int The number of unique individuals with nominating ballots.
   */
  function countBallots()
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT(nominator_id))
FROM   nominations
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $count );
    $query->fetch ();

    return $count;
  }

  /**
   * Gets the number of nominations an individual nominator has submitted in a
   * given category.
   *
   * @param $nominatorId int
   *          Unique ID for the nominator. Normally this is their Unique Hugo Award PIN.
   * @param $awardCategoryId int
   *          Database Key/ID for the award category.
   * @param $reviewedOnly boolean
   *          (optional) Unused.
   * @return int Count of nominations for this unique nominator.
   */
  function countNominationsByNominator($nominatorId, $awardCategoryId, $reviewedOnly = false)
  {
    if (preg_match ( '(\\d+)', $nominatorId, $matches ))
    {
      $nominatorId = $matches [0];
    }

    $sql = <<<EOT
SELECT COUNT(nomination_id)
FROM  nominations
WHERE nominator_id = ?
  AND award_category_id = ?
  AND nomination_deleted != 1
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'ii', $nominatorId, $awardCategoryId );
    $query->execute ();
    $query->bind_result ( $count );
    $query->fetch ();
    $query->close ();
    return ($count);
  }

  /**
   * Adds a new nomination record.
   *
   * @param $nominatorId int
   *          Unique ID for the nominator. Normally this is their Unique Hugo Award PIN.
   * @param $awardCategoryId int
   *          Database Key/ID for the award category.
   * @param $primary_datum string
   *          Primary datum for this category (Title, Individual, etc.)
   * @param $datum_2 string
   *          Second datum for this category
   * @param $datum_3 string
   *          Third datum for this category
   * @return string An HTML/XML encoded comment describing the results of this function.
   */
  function addNomination($nominatorId, $awardCategoryId, $primary_datum, $datum_2 = '', $datum_3 = '')
  {
    $primary_datum = stripslashes ( $primary_datum );
    $datum_2 = stripslashes ( $datum_2 );
    $datum_3 = stripslashes ( $datum_3 );

    if (preg_match ( '(\\d+)', $nominatorId, $matches ))
    {
      $nominatorId = $matches [0];
    }

    $return = '';
    $return .= "<!-- Adding nomination for: $nominatorId, $awardCategoryId, $primary_datum -->\n";

    // Confirm that this individual has not placed too many nominations.
    // TODO: Maximum nominations is a hard coded value here.
    if ($this->countNominationsByNominator ( $nominatorId, $awardCategoryId ) >= 5)
    {
      $return .= "<!-- Too many nominations already present for $nominatorId --!>\n";
      return $return;
    }

    // Query to see if this nomination already exists (exact text)
    $sql = <<<EOT
SELECT COUNT(nomination_id)
FROM   nominations
WHERE  nominator_id = ?
  AND  award_category_id = ?
  AND  primary_datum = ?
  AND  nomination_deleted != 1;
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'iis', $nominatorId, $awardCategoryId, $primary_datum );
    $query->execute ();
    $query->bind_result ( $count );
    $query->fetch ();
    $return .= "<!-- Found $count Matching Nominations -->\n";
    if ($count != 0)
    {
      $return .= "<!-- Not adding $primary_data, matching record for $nominatorId --!>\n";
      return $return;
    }

    $query->close ();

    $return .= "<!-- Proceed to add -->\n";

    // Always add the nominee duplicates will be accounted for OK
    $nominee_id = $this->addNominee ( $awardCategoryId, $primary_datum, $datum_2, $datum_3, $return );

    $return .= "<!-- Nominee ID: $nominee_id -->\n";

    $return .= "<!--\nAdding:\nNominator ID (PIN): $nominatorId\nAward Category ID: $awardCategoryId\nPrimary Datum: $primary_datum\n";
    $return .= "2nd Datum: $datum_2\n3rd Datum: $datum_3\nNominee ID: $nominee_id\n";
    $return .= "User's IP: " . $_SERVER ['REMOTE_ADDR'] . "\n-->\n";

    // Add the nomination record into the database
    $sql = <<<EOT
INSERT INTO nominations
(nominator_id,award_category_id,primary_datum,datum_2,datum_3,nominee_id,ip_added_from,nomination_approved,nomination_deleted)
VALUES (?, ?, ?, ?, ?, ?, ?, 0, 0)
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'iisssis', $nominatorId, $awardCategoryId, $primary_datum, $datum_2, $datum_3, $nominee_id, $_SERVER ['REMOTE_ADDR'] );
    $query->execute ();
    $return .= "<!-- Database Error before fetch: " . self::$db->error . " -->\n";
    $query->fetch ();
    $return .= "<!-- Database Error after fetch: " . self::$db->error . " -->\n";
    $query->close ();

    return $return;
  }

  /**
   * Clears the entered nominations for the specified nominator ID and category
   *
   * @param $nominatorId int
   *          Unique ID for the nominator. Normally this is their Unique Hugo Award PIN.
   * @param $awardCategoryId int
   *          Database Key/ID for the award category.
   * @return string An HTML/XML encoded comment describing the results of this function.
   */
  function clearNominations($nominatorId, $awardCategoryId)
  {
    $pageData = '';

    if (preg_match ( '(\\d+)', $nominatorId, $matches ))
    {
      $nominatorId = $matches [0];
    }

    $pageData .= "<!-- Attempting to clear nomiantions for $nominatorId, in $awardCategoryId -->\n";

    $sql = <<<EOT
DELETE FROM nominations
WHERE nominator_id = ?
  AND award_category_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    if ($query)
    {
      $query->bind_param ( 'ii', $nominatorId, $awardCategoryId );
      $query->execute ();
    }
    else
    {
      $pageData .= "<!-- Unable to clear nominations:\n";
      $pageData .= self::$db->error;
      $pageData .= "\n-->\n";
    }

    return $pageData;
  }

  /**
   * Adds a nominee to the nominees' table.
   * This table contains all of the unique strings (primary datum)
   * entered for each category to facilitate normalization.
   *
   *  @warning The nomination normalization feature has not been used since Chicon 7, if then.
   *
   * @param $awardCategoryId int
   *          Database Key/ID for the award category.
   * @param $primary_datum string
   *          Primary datum for this category (Title, Individual, etc.)
   * @param $datum_2 string
   *          Second datum for this category
   * @param $datum_3 string
   *          Third datum for this category
   * @return int ID for the selected nominee, -1 if an error occured.
   */
  function addNominee($awardCategoryId, $primary_datum, $datum_2, $datum_3, &$return)
  {
    $return .= "<!-- Adding Nominee: $awardCategoryId, $primary_datum -->\n";

    // Search to see if a matching nominee record already exists.
    $sql = <<<EOT
SELECT nominee_id
FROM   nominee
WHERE  category_id = ?
  AND  primary_datum = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'is', $awardCategoryId, $primary_datum );
    $query->execute ();
    $query->bind_result ( $nomineeId );

    // If a match is found, return its ID.
    if ($query->fetch ())
    {
      $return .= "<!-- Found matching nominee $nomineeId -->\n";
      return $nomineeId;
    }
    $query->close ();

    // If not, add the new record.
    $sql = <<<EOT
INSERT INTO nominee
(category_id,primary_datum,datum_2,datum_3)
VALUES (?,?,?,?)
EOT;
    $query = self::$db->prepare ( $sql );
    $return .= "<!-- insert prepare DB Error: [" . self::$db->error . "] -->\n";

    $query->bind_param ( 'isss', $awardCategoryId, $primary_datum, $datum_2, $datum_3 );
    $query->execute ();
    $return .= "<!-- insert execute DB Error: [" . self::$db->error . "] -->\n";

    $return .= "<!-- Added nominee.  Next fetching Nominee ID --!>\n";

    $query->close ();

    // Get the ID for the newly added record.
    print ($return . "\n\n") ;
    $sql = <<<EOT
SELECT nominee_id
FROM   nominee
WHERE  category_id = ?
  AND  primary_datum = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'is', $awardCategoryId, $primary_datum );
    $query->execute ();
    $query->bind_result ( $nomineeId );
    if ($query->fetch ())
    {
      $return .= "<!-- Nominee Id: $nomineeId -->\n";
      return $nomineeId;
    }
    $return .= "<!-- Error, could not find Nominee Id -->\n";
    return - 1;
  }

  /**
   * Update the nominee record with new information.
   *
   * This can be used during nomination normalization.
   *
   *  @warning The nomination normalization feature has not been used since Chicon 7, if then.
   *
   * @param $nomineeId int
   *          Unique ID for the nominee record to be updated.
   * @param $primary_datum string
   *          Primary datum for this category (Title, Individual, etc.)
   * @param $datum_2 string
   *          Second datum for this category
   * @param $datum_3 string
   *          Third datum for this category
   */
  function updateNominee($nomineeId, $primaryDatum, $datum2, $datum3)
  {
    $sql = <<<EOT
UPDATE nominee
SET    primary_datum = ?,
       datum_2       = ?,
       datum_3       = ?
WHERE  nominee_id    = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'sssi', $primaryDatum, $datum2, $datum3, $nomineeId );
    $query->execute ();
  }

  /**
   * Count the number of nominations that match a given nominee record (ID)
   *
   *  @warning The nomination counting functionality has not been used since Chicon 7, if then. It is based on the WSFS Constitution rules prior to 2017, and does not support the current process for determining the Hugo Award Finalists.
   *
   * @param $nomineeId int
   *          Unique ID for the nominee record to be updated.
   * @return int the number of nominations for the supplied nominee ID.
   */
  function countNominations($nomineeId, $reviewedOnly = false)
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
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $nomineeId );
    $query->execute ();
    $query->bind_result ( $nomination_count );
    if ($query->fetch ())
    {
      return $nomination_count;
    }

    return 0;
  }

  /**
   * Gets the information contained in the Nominee record
   *
   *  @warning The nomination counting functionality has not been used since Chicon 7, if then. It is based on the WSFS Constitution rules prior to 2017, and does not support the current process for determining the Hugo Award Finalists.
   *
   * @param $nomineeId int
   *          Unique ID for the nominee record to be updated.
   * @return array A hash containing the nominee data for the selected nominee.
   *        
   */
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
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $nominee_id );
    $query->execute ();
    $query->bind_result ( $category_id, $category_name, $primary_datum, $datum_2, $datum_3 );

    $info = array ();
    if ($query->fetch ())
    {
      $info ['nominee_id'] = $nominee_id;
      $info ['category_id'] = $category_id;
      $info ['category_name'] = $category_name;
      $info ['primary_datum'] = $primary_datum;
      $info ['datum_2'] = $datum_2;
      $info ['datum_3'] = $datum_3;

      $query->close ();
      $info ['nomination_count_reviewed'] = $this->countNominations ( $nominee_id, true );
      $info ['nomination_count_all'] = $this->countNominations ( $nominee_id, false );
    }

    return $info;
  }

  /**
   * Gets a list of the nominees in a given category
   *
   *  @warning The nomination counting functionality has not been used since Chicon 7, if then. It is based on the WSFS Constitution rules prior to 2017, and does not support the current process for determining the Hugo Award Finalists.
   *
   * @param $nomineeId int
   *          Unique ID for the nominee record to be updated.
   * @param $max int
   *          The maximum number of records to return. (Optional)
   * @return array A list of the nominees, sorted lexagraphically.
   */
  function listNominees($award_category, $max = - 1)
  {
    $sql = <<<EOT
SELECT nominee_id
FROM   nominee
WHERE  category_id = ?
ORDER BY primary_datum ASC
EOT;

    if ($max > 0)
    {
      $sql .= "\nLIMIT $max";
    }

    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $award_category );
    $query->execute ();
    $query->bind_result ( $id );

    $nomineeIdList = array ();
    while ( $query->fetch () )
    {
      $nomineeIdList [] = $id;
    }

    $query->close ();

    $nomineeList = array ();
    foreach ( $nomineeIdList as $id )
    {
      $nomineeList [] = $this->getNomineeInfo ( $id );
    }

    return $nomineeList;
  }

  /**
   * Gets a list of the nominees in a given category, ordered by the number of nominations received.
   *
   *  @warning The nomination counting functionality has not been used since Chicon 7, if then. It is based on the WSFS Constitution rules prior to 2017, and does not support the current process for determining the Hugo Award Finalists.
   *
   * @param $nomineeId int
   *          Unique ID for the nominee record to be updated.
   * @param $max int
   *          The maximum number of records to return. (Optional)
   * @return array A list of the nominees, sorted lexagraphically.
   */
  function listNomineesByCount($award_category, $max = - 1)
  {
    $sql = <<<EOT
SELECT nominee_id
FROM   nominee
WHERE  category_id = ?
EOT;

    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $award_category );
    $query->execute ();
    $query->bind_result ( $id );

    $nomineeIdList = array ();
    while ( $query->fetch () )
    {
      $nomineeIdList [] = $id;
    }

    $query->close ();

    $nomineeList = array ();
    foreach ( $nomineeIdList as $id )
    {
      $nomineeList [] = $this->getNomineeInfo ( $id );
    }

    usort ( $nomineeList, 'sortNomineesByCount' );

    $returnNomineeList = array ();

    if ($max > 0)
    {
      for($i = 0; $i < $max; $i ++)
      {
        $returnNomineeList [$i] = $nomineeList [$i];
      }
    }
    else
    {
      $returnNomineeList = $nomineeList;
    }

    return $returnNomineeList;
  }

  /**
   * Empties and repopulates the Nominees table based on the contents of the Nominations table.
   *
   *  @warning The nomination counting functionality has not been used since Chicon 7, if then. It is based on the WSFS Constitution rules prior to 2017, and does not support the current process for determining the Hugo Award Finalists.
   *
   */
  function regenerateNominations()
  {
    // Delete all current nominee records.
    $sql = <<<EOT
DELETE
FROM  nominee
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->close ();

    // Get a list of all nominations
    $sql = <<<EOT
SELECT nomination_id,
       award_category_id,
       primary_datum,
       datum_2,
       datum_3
FROM   nominations
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $nominationId, $categoryId, $datum1, $datum2, $datum3 );

    $nominations = array ();
    while ( $query->fetch () )
    {
      $nominationRecord = array ();
      $nominationRecord ['nomination_id'] = $nominationId;
      $nominationRecord ['category_id'] = $categoryId;
      $nominationRecord ['datum_1'] = $datum1;
      $nominationRecord ['datum_2'] = $datum2;
      $nominationRecord ['datum_3'] = $datum3;
      $nominations [] = $nominationRecord;
    }

    $query->close ();

    $sql = <<<EOT
UPDATE nominations
SET    nominee_id = ?
WHERE  nomination_id = ?
EOT;
    $query = self::$db->prepare ( $sql );

    // Readd all of the Nominees.
    foreach ( $nominations as $nominationRecord )
    {
      $return = '';
      $nomineeId = $this->addNominee ( $nominationRecord ['category_id'], $nominationRecord ['datum_1'], $nominationRecord ['datum_2'], $nominationRecord ['datum_3'], $return );
      $query->bind_param ( 'ii', $nomineeId, $nominationRecord ['nomination_id'] );
      $query->execute ();
    }
    $query->close ();
  }

  /**
   * Update the nomination configuration information that is the dates when the system will open and close
   * Hugo Award nominations.
   *
   * @param $nominationsOpen string
   *          Date and time when Hugo Award Nominations will open (in system time)
   * @param $nominationsClose string
   *          Date and time when Hugo Award Nominations will close (in system time)
   */
  function updateNominationConfig($nominationsOpen, $nominationsClose)
  {
    // Only the most recent entry is used, so just add new entries
    $sql = <<<EOT
INSERT INTO nomination_configuration
(nomination_open,nomination_close)
VALUES (?,?)
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'ss', $nominationsOpen, $nominationsClose );
    $query->execute ();
  }

  /**
   * Get the current configuration for the Hugo Award nomination that is the date when nominations open and close,
   * and when the preview mode closes.
   *
   * @return array A hash containing the open and closing dates for Hugo Award Nominations. Safe dates are returned if an error occurs.
   */
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
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $nominationOpen, $nominationClose, $previewEnds, $eligibilityText );

    if ($query->fetch ())
    {
      $nominationConfiguration = array (
          'open' => $nominationOpen,
          'close' => $nominationClose,
          'preview_ends' => $previewEnds,
          'eligibility_txt' => $eligibilityText
      );
      return ($nominationConfiguration);
    }
    return array (
        'open' => '2012-01-01 00:00:00',
        'close' => '2011-02-29 23:59:59'
    );
  }

  /**
   * Used to determine if the Hugo Award nominations are currently open.
   *
   * @return string A String indicating the state of the Hugo Award Nominations.
   */
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
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $nominationStatus );
    if ($query->fetch ())
    {
      return ($nominationStatus);
    }
    return ('Hugo Award nominations are not open yet');
  }

  /**
   * Used to determine if the Hugo Award voting is currently open.
   *
   * @return string A String indicating the state of the Hugo Award voting.
   */
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
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $votingStatus );
    if ($query->fetch ())
    {
      return $votingStatus;
    }
    return 'BeforeOpen';
  }

  /**
   * Used to determine if the Hugo Award nomitions are in administrator preview mode.
   *
   * @return boolean 1 (true) if previews are open, 0 (false) otherwise.
   */
  function inPreview()
  {
    $sql = <<<EOT
SELECT (NOW() <= nomination_configuration.preview_ends) AS preview_open
FROM   nomination_configuration
ORDER BY update_time ASC
LIMIT 1
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $inPreview );
    if ($query->fetch ())
    {
      return $inPreview;
    }
    return 0;
  }

  /**
   * Gets the text used to describe who is eligable to nominate for Hugo Awards.
   *
   * @return string A string containing the text used to describe who is eligable to nominate for Hugo Awards.
   */
  function getEligibilityText()
  {
    $sql = <<<EOT
SELECT eligibility_text_blob
FROM   nomination_configuration
ORDER BY update_time ASC
LIMIT 1
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute;
    $query->bind_result ( $eligibilityText );
    if ($query->fetch ())
    {
      return $eligibilityText;
    }
    return '<!-- Unable to fecth Eligibility Text -->';
  }

  /**
   * Approves the nominations for a given nominator.
   *
   *  @warning The nomination counting functionality has not been used since Chicon 7, if then. It is based on the WSFS Constitution rules prior to 2017, and does not support the current process for determining the Hugo Award Finalists.
   *
   * @param $nominatorId int
   *          The unique ID of the nominator (Hugo Award PIN)
   * @param $userId int
   *          The database key for the user approving the nominations.
   */
  function approveNominations($nominatorId, $userId)
  {
    $sql = <<<EOT
UPDATE nominations
SET    nomination_approved = 1
WHERE  nominator_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $nominatorId );
    $query->execute ();

    $query->close ();

    $sql = <<<EOT
UPDATE nominator
SET    ballot_reviewed = 1
       ballot_reviewed_by = ?
       ballot_review = NOW()
WHERE  nominator_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'ii', $userId, $nominatorId );
    $query->execute ();

    $query->close ();
  }

  /**
   * Returns the full nomination ballot for a given nominator
   *
   * @param $nominatorId int
   *          The unique ID of the nominator (Hugo Award PIN)
   * @return array An array of hashes containing the nominating ballot for the specified individual
   */
  function getBallot($nominatorId)
  {
    if (preg_match ( '(\\d+)', $nominatorId, $matches ))
    {
      $nominatorId = $matches [0];
    }

    $categoryInfo = $this->getCategoryInfo ();

    $sql = <<<EOT
SELECT primary_datum,
       datum_2,
       datum_3
FROM   nominations
WHERE  nominator_id = ?
  AND  award_category_id = ?
EOT;
    $query = self::$db->prepare ( $sql );

    $ballot ['nominations'] = array ();

    foreach ( $categoryInfo as $categoryId => $categoryRecord )
    {
      $ballot ['nominations'] [$categoryId] = array ();
      $query->bind_param ( 'ii', $nominatorId, $categoryId );
      $query->execute ();
      $query->bind_result ( $datum1, $datum2, $datum3 );
      while ( $query->fetch () )
      {
        $nominationRec = array ();
        $nominationRec [1] = $datum1;
        $nominationRec [2] = $datum2;
        $nominationRec [3] = $datum3;
        $ballot ['nominations'] [$categoryId] [] = $nominationRec;
      }
    }

    return $ballot;
  }

  /**
   * Returns the provisioinal nomination ballot for a given nominator
   *
   *  @warning The provisional nominations functionality has not been used since Chicon 7, if then.
   *
   * @param $nominatorId int
   *          The unique ID of the nominator (Hugo Award PIN)
   * @return string An array of hashes containing the provisitional nominating ballot for the specified individual
   */
  function getProvisionalBallot($nominatorId)
  {
    $categoryInfo = $this->getCategoryInfo ();

    $sql = <<<EOT
SELECT primary_datum,
       datum_2,
       datum_3
FROM   provisional_nominations
WHERE  nominator_id = ?
  AND  award_category_id = ?
EOT;
    $query = self::$db->prepare ( $sql );

    $ballot ['nominations'] = array ();

    foreach ( $categoryInfo as $categoryId => $categoryRecord )
    {
      $ballot ['nominations'] [$categoryId] = array ();
      $query->bind_param ( 'ii', $nominatorId, $categoryId );
      $query->execute ();
      $query->bind_result ( $datum1, $datum2, $datum3 );
      while ( $query->fetch () )
      {
        $nominationRec = array ();
        $nominationRec [1] = $datum1;
        $nominationRec [2] = $datum2;
        $nominationRec [3] = $datum3;
        $ballot ['nominations'] [$categoryId] [] = $nominationRec;
      }
    }

    return $ballot;
  }

  /**
   * This function is supposed to remove any slash characters from the data fields in the nomination table.
   */
  function cleanNominationsOfSlashes()
  {
    $sql = <<<EOT
SELECT nomination_id,
       primary_datum,
       datum_2,
       datum_3
FROM   nominations
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $nominationId, $datum1, $datum2, $datum3 );

    $nominations = array ();

    while ( $query->fetch () )
    {
      $nominations [$nominationId] = array (
          'datum1' => $datum1,
          'datum2' => $datum2,
          'datum3' => $datum3
      );
    }

    $query->close ();

    $sql = <<<EOT
UPDATE nominations
SET    primary_datum = ?,
       datum_2       = ?,
       datum_3       = ?
WHERE  nomination_id = ?
EOT;
    $query = self::$db->prepare ( $sql );

    foreach ( $nominations as $nominationId => $data )
    {
      $query->bind_param ( 'sssi', $data ['datum1'], $data ['datum2'], $data ['datum3'], $nominationId );
      $query->execute ();
    }

    $query->close ();
  }

  /**
   * Get the nominations for a given nominator and category
   *
   * @param $nominatorId int
   *          The unique ID of the nominator (Hugo Award PIN)
   * @param $categoryId int
   *          The database key for the Hugo Award Category
   *  @returns array An array of hashes containing the nomination data for the specified nominator and category.
   */
  function getNominationsForNominatior($nominatorId, $categoryId)
  {
    if (preg_match ( '(\\d+)', $nominatorId, $wsfs_hugo_matches ))
    {
      $nominatorId = $wsfs_hugo_matches [0];
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
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'ii', $nominatorId, $categoryId );
    $query->execute ();
    $query->bind_result ( $nominationId, $datum1, $datum2, $datum3, $deleted );

    $nominations = array ();

    while ( $query->fetch () )
    {
      $nominationRecord = array (
          'id' => $nominationId,
          'datum1' => $datum1,
          'datum2' => $datum2,
          'datum3' => $datum3,
          'deleted' => $deleted
      );
      $nominations [] = $nominationRecord;
    }

    $query->close ();

    return $nominations;
  }

  /**
   * Get the provisional nominations for a given nominator and category
   *
   *  @warning The provisional nominations functionality has not been used since Chicon 7, if then.
   *
   * @param $nominatorId int
   *          The unique ID of the nominator (Hugo Award PIN)
   * @param $categoryId int
   *          The database key for the Hugo Award Category
   *  @returns array An array of hashes containing the provisional nomination data for the specified nominator and category.
   */
  function getProvisionalNominationsForNominatior($nominatorId, $categoryId)
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
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'ii', $nominatorId, $categoryId );
    $query->execute ();
    $query->bind_result ( $nominationId, $datum1, $datum2, $datum3, $deleted );

    $nominations = array ();

    while ( $query->fetch () )
    {
      $nominationRecord = array (
          'id' => $nominationId,
          'datum1' => $datum1,
          'datum2' => $datum2,
          'datum3' => $datum3,
          'deleted' => $deleted
      );
      $nominations [] = $nominationRecord;
    }

    $query->close ();

    return $nominations;
  }

  /**
   * Approves the nominating ballot for a given nominator.
   *
   *  @warning The nomination counting functionality has not been used since Chicon 7, if then. It is based on the WSFS Constitution rules prior to 2017, and does not support the current process for determining the Hugo Award Finalists.
   *
   * @param $nominatorId string
   *          The unique ID of the nominator (Hugo Award PIN)
   * @param $sessionKey string
   *          The PHP session key for the administrative session, which can be used to get the administrative user.
   */
  function approveBallot($reviewId, $sessionKey)
  {
    $userId = $this->getSessionUser ( $sessionKey );

    print ("<!-- Found user id $userId from key $sessionKey -->\n") ;

    $sql = <<<EOT
UPDATE nominator
SET    ballot_reviewed    = 1,
       ballot_reviewed_by = ?,
       ballot_review      = NOW()
WHERE  nominator_id       = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'ii', $userId, $reviewId );
    $query->execute ();
    $query->close ();

    $sql = <<<EOT
UPDATE nominations
SET    nomination_approved = 1
WHERE  nominator_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $reviewId );
    $query->execute ();
    $query->close ();
  }

  /**
   * Logs an email that has been sent by the system.
   *
   * @param $nominatorId string
   *          Unique ID (Hugo Award PIN) of the person the email is intended for.
   * @param $emailText string
   *          Text of the email being sent.
   * @param $result string
   *          Result of the send mail operation.
   * @param $emailAddress string
   *          Address the email is being sent to.
   */
  function logEmail($nominatorId, $emailText, $result, $emailAddress)
  {
    global $_SERVER;

    $remoteIp = '127.0.0.1';
    if (isset ( $_SERVER ['REMOTE_ADDR'] ))
    {
      $remoteIp = $_SERVER ['REMOTE_ADDR'];
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
    $query = self::$db->prepare ( $sql );
    print self::$db->error;
    $query->bind_param ( 'sisss', $nominatorId, $result, $emailText, $emailAddress, $remoteIp );
    $query->execute ();
    $query->close ();
  }

  /**
   * Move, or attempt to move, a nomination to a new category.
   *
   *    @warning The nomination normalization functionality has not been used since Chicon 7, if then.
   * @param $nominationId int
   *          The database key for the nomination being moved.
   * @param $newCategoryId int
   *          The database key for the category the nomination is being moved into.
   */
  function moveNomination($nominationId, $newCategoryId)
  {
    print ("<!-- Attempting to move nomination $nominationId to category $newCategoryId -->\n") ;

    $sql = <<<EOT
SELECT nominator_id,
       primary_datum,
       datum_2,
       datum_3
FROM   nominations
WHERE  nomination_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $nominationId );
    $query->execute ();
    $query->bind_result ( $nominatorId, $datum1, $datum2, $datum3 );

    if ($query->fetch ())
    {
      $query->close ();
      $nominationCout = $this->countNominationsByNominator ( $nominatorId, $newCategory, false );
      if ($nominationCount < 5) // Don't transfer unless there is room
      {
        $nomineeId = $this->addNominee ( $newCategoryId, $datum1, $datum2, $datum3, $return );

        $sql = <<<EOT
UPDATE nominations
SET    award_category_id = ?,
       nominee_id        = ?
WHERE  nomination_id     = ?
EOT;
        $query = self::$db->prepare ( $sql );
        $query->bind_param ( 'iii', $newCategoryId, $nomineeId, $nominationId );
        $query->execute ();
        $query->close ();
        print ("<!-- Successful -->\n") ;
      }
    }
  }

  /**
   * Delete a record from the nominations table.
   *
   * @param $nominationId int
   *          The database key for the nomination to be deleted.
   */
  function deleteNomination($nominationId)
  {
    $sql = <<<EOT
UPDATE nominations
SET    nomination_deleted = 1
WHERE  nomination_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $nominationId );
    $query->execute ();
    $query->close ();
  }

  /**
   * Add or update an entry in the list of Hugo Award Finalists, a.k.a.
   * the Hugo Award Shortlist.
   *
   * @param $categoryId int
   *          The database key for the Hugo Award Category.
   * @param $datum_1 int
   *          The primary datum for this category (Title, individual, etc.)
   * @param $datum_2 int
   *          The second dataum for this category (author, editor, network, studio, etc.) Optional
   * @param $datum_3 int
   *          The third datum for this category
   *          
   *          The data fields will be programatically modified for safe display under HTML before being placed into the database.
   *          A sortable version of $datum_1 will be used for sorting. This will be generated based on if this is a personal category (Best Editor, etc.) or a works category. Note, this sorting is Eurocentric.
   */
  function addUpdateShortList($categoryId, $datum_1, $datum_2 = '', $datum_3 = '')
  {
    $categoryInfo = $this->getCategoryInfo ();

    $datum_1 = htmlentities ( $datum_1 );
    print ("<!-- Modified \$datum_1: [$datum_1] -->\n") ;

    if ((preg_match ( '/\s*&lt;em&gt;(.+)&lt;\/em&gt;(.*)&quot;(.+)&quot;(.*)/', $datum_1, $matches )) || (preg_match ( '/\s*&lt;em&gt;(.+)&lt;\/em&gt;(.*)\\\&quot;(.+)\\\&quot;(.*))/', $datum_1, $matches )))
    {
      print ("<!-- Matched: [$dataum_1] -->\n") ;
      $datum_1 = '<em>' . $matches [1] . '</em>' . $matches [2] . '&quot;' . $matches [3] . '&quot;' . $matches [4];
    }
    elseif (preg_match ( '/\s*&lt;em&gt;(.+)&lt;\s*\/em&gt;/', $datum_1, $matches ))
    {
      print ("<!-- Matched: [$dataum_1] -->\n") ;
      $datum_1 = '<em>' . $matches [1] . '</em>';
    }

    $datum_1 = preg_replace ( '/&amp;/', '&', $datum_1 );

    $datum_1 = preg_replace ( '/\\\\&quot;/', '&quot;', $datum_1 );

    // Build the sort_value from datum_1

    $sortValue = $datum_1;
    if ($categorData [$category_id] ['personal_category'] != 1)
    {
      if (preg_match ( '<em>(.+)<\/em>.*\&quot;(.+)\&quot;/', $datum_1, $matches ))
      {
        $sortValue = $matches [1] . ' ' . $matches [2];
      }
      elseif (preg_match ( '/<em>(.+)<\/em>/', $datum_1, $matches ))
      {
        $sortValue = $matches [1];
      }
      elseif (preg_match ( '/\&quot;(.+)\&quot;/', $datum_1, $matches ))
      {
        $sortValue = $matches [1];
      }

      if (preg_match ( '/^a\s+(.+)/i', $sortValue, $matches ))
      {
        $sortValue = $matches [1];
      }
      elseif (preg_match ( '/the\s+(.+)/i', $sortValue, $matches ))
      {
        $sortValue = $matches [1];
      }
    }
    else
    {
      if (preg_match ( '/^(.*)\s+(\S+)$', $sortValue, $matches ))
      {
        $sortValue = $matches [2] . ', ' . $matches [1];
      }
    }
    if ($sortValue == 'No Award')
    {
      $sortValue = 'ZZZZZZ';
    }

    print ("<!-- \$sortValue = [$sortValue] -->\n") ;

    $sql = <<<EOT
SELECT shortlist_id
FROM   hugo_shortlist
WHERE  category_id = ?
  AND  datum_1     = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'is', $categoryId, $datum_1 );
    $query->execute ();
    $query->bind_result ( $shortlistId );

    if ($query->fetch ())
    {
      print ("<!-- \$shortlistId = $shortlistId -->\n") ;

      $query->close ();
      $sql = <<<EOT
UPDATE hugo_shortlist
SET    datum_1      = ?,
       sort_value   = ?,
       datum_2      = ?,
       datum_3      = ?
WHERE  shortlist_id = ?
EOT;
      $query = self::$db->prepare ( $sql );
      $query->bind_param ( 'ssssi', $datum_1, $sortValue, $datum_2, $datum_3, $shortlistId );
      $query->execute ();
      $query->close ();
    }
    else
    {
      $sql = <<<EOT
INSERT INTO hugo_shortlist
(category_id,datum_1,sort_value,datum_2,datum_3)
VALUES
(?,?,?,?,?)
EOT;
      $query = self::$db->prepare ( $sql );
      $query->bind_param ( 'issss', $categoryId, $datum_1, $sortValue, $datum_2, $datum_3 );
      $query->execute ();
      $query->close ();

      $sql = <<<EOT
SELECT shortlist_id
FROM   hugo_shortlist
WHERE  category_id = ?
  AND  datum_1     = ?
EOT;
      $query = self::$db->prepare ( $sql );
      $query->bind_param ( 'is', $categoryId, $datum_1 );
      $query->execute ();
      $query->bind_result ( $shortlistId );

      if (! $query->fetch ())
      {
        $shortlistId = - 1;
      }
    }

    return $shortlistId;
  }

  /**
   * Gets the shortlist (Hugo Award Finalists)
   *
   * @param $shortlistId int
   *          The database key for the Hugo Award Finalist
   * @return array A hash containing the short list/finalist data.
   */
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
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $shortlistId );
    $query->execute ();
    $query->bind_result ( $dbId, $datum1, $datum2, $datum3, $categoryId );

    $shortlistInfo = array ();

    if ($query->fetch ())
    {
      $shortlistInfo = array (
          'shortlist_id' => $dbId,
          'datum_1' => $datum1,
          'datum_2' => $datum2,
          'datum_3' => $datum3,
          'category_id' => $categoryId
      );
    }
    $query->close ();

    return $shortlistInfo;
  }

  /**
   * Remove an entry from the shortlist
   *
   * @param $shortlistId int
   *          The database key of the entry to be removed.
   */
  function deleteFromShortlist($shortlistId)
  {
    $sql = <<<EOT
DELETE
FROM   hugo_shortlist
WHERE  shortlist_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $shortlistId );
    $query->execute ();
    $query->fetch ();
    $query->close ();
  }

  /**
   * Get the entire short list for a given Hugo Award Category
   *
   * @param $categoryId int
   *          The database key for the Hugo Award Category
   * @return array An array of hashes containing the short list for the specified category.
   *        
   *         As a side effect, this function will add "No Award" to any category that does not have it.
   */
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
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $categoryId );
    $query->execute ();
    $query->bind_result ( $shortlistId, $datum1, $sortValue, $datum2, $datum3 );

    $shortlist = array ();

    $noneOfTheAbove = array ();

    while ( $query->fetch () )
    {
      if ($datum1 == 'No Award')
      {
        $noneOfTheAbove = array (
            'shortlist_id' => $shortlistId,
            'datum_1' => $datum1,
            'datum_2' => $datum2,
            'datum_3' => $datum3
        );
      }
      else
      {
        $shortlist [$shortlistId] = array (
            'shortlist_id' => $shortlistId,
            'datum_1' => $datum1,
            'datum_2' => $datum2,
            'datum_3' => $datum3
        );
      }
    }

    $query->close ();

    if (count ( $noneOfTheAbove ) == 0)
    {
      $shortlistId = $this->addUpdateShortList ( $categoryId, 'No Award', '', '' );

      $noneOfTheAbove = $shortlistId;
    }

    $shortlist [$noneOfTheAbove ['shortlist_id']] = $noneOfTheAbove;

    return $shortlist;
  }

  /**
   * Counts the unique number of people who made nominations in the specified category.
   *
   * @param $categoryId int
   *          The database key for the Hugo Award Category
   * @return int The number of unique people who made nominations in the specified category.
   */
  function categoryBallotCount($categoryId)
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT nominator_id)
FROM nominations
WHERE award_category_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $categoryId );
    $query->execute ();
    $query->bind_result ( $count );
    $query->fetch (); // Always returns a value, no need to check

    return $count;
  }

  /**
   * Count the number of unique nominations across all of the categories
   *
   *    @warning The nomination counting functionality has not been used since Chicon 7, if then. It is based on the WSFS Constitution rules prior to 2017, and does not support the current process for determining the Hugo Award Finalists.
   *
   * @return array An array of hashes mapping the Hugo Award Category database keys to the number of unique individuals who have nominating ballots in each. These ballots must be in an "approved" state according to the unused functionality.
   */
  function uniqueNominations()
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT nominator_id) AS count
FROM nominations
WHERE nominator_id > 1
   AND nomination_approved = 1
   AND nomination_deleted  = 0
EOT;
    $query = self::$db->prepare ( $sql );

    $query->execute ();

    $query->bind_result ( $fullCount );

    $query->fetch (); // Will always return

    $counts = array (
        'All' => $fullCount
    );

    $query->close ();

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
    $query = self::$db->prepare ( $sql );

    $query->execute ();
    $query->bind_result ( $category, $order, $count );

    while ( $query->fetch () )
    {
      $counts [$category] = $count;
    }

    $query->close ();

    return $counts;
  }

  /**
   * Counts the number of Hugo Award finalists in the specified category
   *
   * @param $categoryId int
   *          The database key for the Hugo Award Category
   * @return int The count of Hugo Award finalists in the specified category.
   */
  function getShortlistCount($categoryId)
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT(shortlist_id))
FROM   hugo_shortlist
WHERE  category_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $categoryId );
    $query->execute ();
    $query->bind_result ( $shortlistCount );
    if ($query->fetch ())
    {
      return $shortlistCount;
    }
  }

  /**
   * Gets the entire Hugo Award shortlist, that is the list of Hugo Award Finalists.
   *
   * @return array A hash of arrays keyed by the Hugo Award category database key, containing a sorted array of the short list sort names.
   */
  function getFullShortlist()
  {
    $categoryInfo = $this->getCategoryInfo ();

    foreach ( $categoryInfo as $id => $info )
    {
      $categoryOrder [$categoryInfo [$id] ['ballot_position']] = $id;
    }

    $sql = <<<EOT
SELECT shortlist_id,
       sort_value
FROM   hugo_shortlist
WHERE category_id = ?
ORDER BY sort_value
EOT;
    $query = self::$db->prepare ( $sql );

    $fullShortList = array ();

    foreach ( $categoryOrder as $postion => $id )
    {
      $shortlist = array ();

      $query->bind_param ( 'i', $id );
      $query->execute ();
      $query->bind_result ( $shortlistId, $name );
      while ( $query->fetch () )
      {
        $shortlist [$shortlistId] = $name;
      }
      $fullShortList [$id] = $shortlist;
    }

    return $fullShortList;
  }

  /**
   * Used to validate a WSFS member for Hugo Award nomination or voting against the internal Hugo Award database membership and PIN table.
   *
   * @param $lastName1 string
   *          The Member's name that is being used for validation purposes. For Eurocentric Worldcons this has been the last name, traditionally the family name.
   * @param $memberId1 string
   *          The Member's Member ID as assigned by the administring Worldcon.
   * @param $PIN1 string
   *          The Hugo Award System unique PIN
   * @param $onlyCurrent boolean
   *          Set to true to only check records that have the "source" field in the nomination_pin_email_info table populated with "CURRENT" Optional
   * @return int 1 if the information validates against the database, 0 otherwise.
   *        
   *         This function utilizes the soundex algorithm to compare the names if they do not initially match. This algorithm is based on United States names, primarily English. Documentation and other information can be found at https://www.php.net/manual/en/function.soundex.php
   */
  function validateMemberHugoDb($lastName1, $memberId1, $PIN1, $onlyCurrent = false)
  {
    $sql = <<<EOT
SELECT second_name,
       member_id,
       pin
FROM nomination_pin_email_info
WHERE pin = ?
EOT;
    if ($onlyCurrent)
    {
      $sql .= "\n  AND source = 'CURRENT'";
    }
    $query = self::$db->prepare ( $sql );

    $query->bind_param ( 's', $PIN1 );
    $query->execute ();
    $query->bind_result ( $secondName2, $memberId2, $PIN2 );
    if ($query->fetch ())
    {
      if ($memberId1 == $memberId2)
      {
        return 1;
      }
      else if (soundex ( $lastName1 ) == soundex ( $secondName2 ))
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

  /**
   * Attempt to match a record in the internal Hugo Award database membership and PIN table based on email address, or by name.
   * Name is matched using two names and, possibly, the soundex algorithm.
   *
   * @param $email1 string
   *          The email address to attempt to match first.
   * @param $firstName1 string
   *          The given name to match
   * @param $lastName1 string
   *          The family name to match
   * @return array A hash containing the matched data from the database.
   */
  function lookupMembership($email1, $firstName1, $lastName1)
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
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 's', $email1 );
    $query->execute ();

    $memberList = array ();

    $query->bind_result ( $firstName2, $lastName2, $memberId2, $PIN2, $email2 );
    if (($email1 != '') && ($query->fetch ()))
    {
      do
      {
        $memberList [] = array (
            'first_name' => $firstName2,
            'last_name' => $lastName2,
            'member_id' => $memberId2,
            'PIN' => $PIN2,
            'email' => $email2
        );
      } while ( $query->fetch () );
    }
    else
    {
      $query->close ();

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

      $query = self::$db->prepare ( $sql );
      $query->bind_param ( 'ss', $firstName1, $lastName1 );
      $query->execute ();
      $query->bind_result ( $firstName2, $lastName2, $memberId2, $PIN2, $email2 );
      if ($query->fetch ())
      {
        do
        {
          $memberList [] = array (
              'first_name' => $firstName2,
              'last_name' => $lastName2,
              'member_id' => $memberId2,
              'PIN' => $PIN2,
              'email' => $email2
          );
        } while ( $query->fetch () );
      }
    }

    return $memberList;
  }

  /**
   * Gets the next record that needs to have an email sent out.
   *
   * @param $mailing int
   *          A value from 1 to 3 that indicates which of three mailings is being sent out.
   * @return array A hash containing the information contained in the internal membership table needed to send out the email.
   */
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
    if ($mailing == 1)
    {
      $sql .= 'WHERE  initial_mail_sent = 0' . "\n";
    }
    elseif ($mailing == 2)
    {
      $sql .= 'WHERE  second_mail_sent = 0' . "\n";
    }
    else
    {
      $sql .= 'WHERE  third_mail_sent = 0' . "\n";
    }

    $sql .= "ORDER BY pin_email_info_id DESC\nLIMIT 1\n";

    $query = self::$db->prepare ( $sql );

    $query->execute ();
    $query->bind_result ( $id, $first_name, $second_name, $member_id, $email, $pin, $source );
    if ($query->fetch ())
    {

      $info ['id'] = $id;
      $info ['first_name'] = $first_name;
      $info ['second_name'] = $second_name;
      $info ['member_id'] = $member_id;
      $info ['email'] = $email;
      $info ['pin'] = $pin;
      $info ['source'] = $source;

      return $info;
    }

    return false;
  }

  /**
   * Mark that the specified individual has had an email sent out during the given round of emails.
   *
   * @param $id int
   *          The key for the internal membership table.
   * @param $mailing int
   *          A value from 1 to 3 that indicates which of three mailings is being sent out.
   */
  function markMailed($id, $mailing)
  {
    $sql = "UPDATE nomination_pin_email_info\n";
    if ($mailing == 1)
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

    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $id );
    $query->execute ();
  }

  /**
   * Updates or adds a record in the internal membership table.
   * In this table the member ID must be unique in addition to the PIN.
   *
   * @param $first_name string
   *          The first (given) name of the individual
   * @param $second_name string
   *          The second (family) name of the individual
   * @param $member_id string
   *          The member ID as assigned by the administering Worldcon
   * @param $pin string
   *          The unique PIN assigned by the administring Worldcon
   * @param $source string
   *          The source of this record. This is used during nominations to distinguish members of the previous Worldcon from members of the administerng Worldcon (and in the past the subsequent Worldcon as well)
   */
  function addUpdatePinEmailRecord($first_name, $second_name, $member_id, $email, $pin, $source)
  {
    // print "email: $email\t";
    $sql = <<<EOT
SELECT pin_email_info_id
FROM   nomination_pin_email_info
WHERE  member_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $member_id );
    $query->execute ();
    $query->bind_result ( $recordId );

    if ($query->fetch ())
    {
      $query->close ();

      $sql = <<<EOT
UPDATE nomination_pin_email_info
SET    first_name  = ?,
       second_name = ?,
       pin         = ?,
       email       = ?,
       source      = ?
WHERE  member_id   = ?
EOT;
      $query = self::$db->prepare ( $sql );
      $query->bind_param ( 'ssssss', $first_name, $second_name, $pin, $email, $source, $member_id );
      $query->execute ();

      // print "Updated $pin\n";
    }
    else
    {
      $query->close ();

      $sql = <<<EOT
INSERT INTO nomination_pin_email_info
(first_name, second_name, member_id, email, pin, source)
VALUES (?,?,?,?,?,?)
EOT;
      $query = self::$db->prepare ( $sql );
      $query->bind_param ( 'ssisss', $first_name, $second_name, $member_id, $email, $pin, $source );
      $query->execute ();

      // print "Addedd $pin\n";
    }
    // Dummy comment
    $query->close ();
  }

  /**
   * Updates or adds a vote for a given member, on a given finalist in a given category.
   *
   * @param $memberId string
   *          The unique ID, the unique Hugo PIN, for the voter.
   * @param $categoryId int
   *          The database key for the Hugo Award Category being voted on.
   * @param $shortlistId int
   *          The database key for the Finalist being voted on.
   * @param $rank int
   *          The rank that this voter is assigning to this finalist.
   * @return string An HTML/XML comment string explainging the operations being carried out.
   */
  function addUpdateVote($memberId, $categoryId, $shortlistId, $rank)
  {
    $return = "<!-- Adding Vote: $memberId, $categoryId, $shortlistId, $rank -->\n";

    $sql = <<<EOT
SELECT ballot_entry_id
FROM   hugo_ballot_entry
WHERE  category_id  = ?
  AND  member_id    = ?
  AND  short_list_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'isi', $categoryId, $memberId, $shortlistId );
    $query->execute ();
    $query->bind_result ( $ballotEntryId );

    if ($query->fetch ())
    {
      $query->close ();

      $return .= "<!-- Found matching vote: $ballotEntryId -->\n";

      $sql = <<<EOT
UPDATE hugo_ballot_entry
SET    rank = ?,
       ip_added_from = ?,
       ballot_approved = 1
WHERE  ballot_entry_id = ?
EOT;
      $query = self::$db->prepare ( $sql );
      $query->bind_param ( 'isi', $rank, $_SERVER ['REMOTE_ADDR'], $ballotEntryId );
      $query->execute ();
      $query->fetch ();
      $query->close ();
    }
    else
    {
      $query->close ();

      $return .= "<!-- No matching vote found -->\n";

      $sql = <<<EOT
INSERT INTO hugo_ballot_entry
(member_id,category_id,short_list_id,rank,ip_added_from,ballot_approved)
VALUES
(?,?,?,?,?,1)
EOT;
      $query = self::$db->prepare ( $sql );
      $query->bind_param ( 'siiis', $memberId, $categoryId, $shortlistId, $rank, $_SERVER ['REMOTE_ADDR'] );
      $query->execute ();
      $query->close ();
    }

    return $return;
  }

  /**
   * Deletes a voters vote for a given category and finalist.
   *
   * @param $memberId string
   *          The unique ID, the unique Hugo PIN, for the voter.
   * @param $categoryId int
   *          The database key for the Hugo Award Category being voted on.
   * @param $shortlistId int
   *          The database key for the Finalist being voted on.
   * @return string An HTML/XML comment string explainging the operations being carried out.
   */
  function deleteVote($memberId, $categoryId, $shortlistId)
  {
    $sql = <<<EOT
SELECT ballot_entry_id
FROM   hugo_ballot_entry
WHERE  category_id  = ?
  AND  member_id    = ?
  AND  short_list_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'isi', $categoryId, $memberId, $shortlistId );
    $query->execute ();
    $query->bind_result ( $ballotEntryId );

    if ($query->fetch ())
    {
      $query->close ();

      $return .= "<!-- Found matching vote: $ballotEntryId -->\n";

      $sql = <<<EOT
DELETE FROM hugo_ballot_entry
WHERE  ballot_entry_id = ?
EOT;
      $query = self::$db->prepare ( $sql );
      $query->bind_param ( 'i', $ballotEntryId );
      $query->execute ();
      $query->fetch ();
      $query->close ();
    }

    return;
  }

  /**
   * Gets the votes for a given category and voter
   *
   * @param $categoryId int
   *          The database key for the Hugo Award Category being voted on.
   * @param $memberId string
   *          The unique ID, the unique Hugo PIN, for the voter.
   * @return array A hash mapping the shortlist database key to the rank.
   */
  function getVotes($categoryId, $voterId)
  {
    $sql = <<<EOT
SELECT short_list_id,
       rank
FROM   hugo_ballot_entry
WHERE  member_id = ?
  AND  category_id = ?
  AND  ballot_approved = 1
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'si', $voterId, $categoryId );
    $query->execute ();
    $query->bind_result ( $shortlistId, $rank );

    $votes = array ();
    while ( $query->fetch () )
    {
      $votes [$shortlistId] = $rank;
    }

    return $votes;
  }

  /**
   * Get the list of files available for the Hugo Voter's Packet
   *
   * @param $fetchAll boolean
   *          If true, all of the files will be returned, otherwise only thoes with show_on_packet_page set to 1 will be returned. Optional
   * @return array An array of hashes containing the Information on the packet files.
   */
  function getPacketFileList($fetchAll = false)
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

    if (! $fetchAll)
    {
      $sql .= "WHERE  show_on_packet_page = 1\n";
    }

    $sql .= 'ORDER BY file_position ASC';
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $id, $shortDescription, $downloadName, $fileFormatNotes, $fileSize, $sha256sum );

    $packetFileList = array ();

    while ( $query->fetch () )
    {
      $fileRecord = array (
          'packet_file_id' => $id,
          'file_short_description' => $shortDescription,
          'file_download_name' => $downloadName,
          'file_format_notes' => $fileFormatNotes,
          'file_size' => $fileSize,
          'sha256sum' => $sha256sum
      );
      $packetFileList [$id] = $fileRecord;
    }

    $query->close ();

    return $packetFileList;
  }

  /**
   * Lookup information on a Hugo Voter Packet file based on its downlad name.
   *
   * @param $downloadName string
   *          Thedownload name of the file
   * @return int The database key (file ID) for the packet file.
   */
  function reversePacketLookup($downloadName)
  {
    $sql = <<<EOT
SELECT `packet_file_id`
FROM   `packet_files`
WHERE  `file_download_name` = ?;
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 's', $downloadName );
    $query->execute ();
    $query->bind_result ( $fileId );
    if ($query->fetch ())
    {
      return $fileId;
    }
    else
    {
      return - 1;
    }
  }

  /**
   * Log that the specified member has downloaded a Hugo Voter Packet file.
   *
   * @param $memberId string
   *          The Unique Member ID (Hugo Voter PIN).
   * @param $downloadIp string
   *          The IP address (IPV4) used to download the file as reported by the web server.
   * @param $fileId int
   *          The database key for the Voter Packet File Information.
   * @param $userAgent string
   *          The user agent information from the web server.
   */
  function logPacketDownload($memberId, $downloadIp, $fileId, $userAgent)
  {
    $sql = <<<EOT
INSERT INTO `packet_download_log`
(`member_id`,`packet_file_id`,`user_agent`,`download_ip`,`download_complete`)
VALUES
(?, ?, ?, ?, NOW())
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'siss', $memberId, $fileId, $userAgent, $downloadIp );
    $query->execute ();
    $query->fetch ();
    $query->close ();
  }

  /**
   * Get the count of downloads for the Hugo Voter Packet files - specifically the total number of downloads, the number of unique members that downloaded files, and the number of unique IP addresses where files were dowloaded to.
   *
   * @return array A hash containing the download count information.
   */
  function getDownloadCounts()
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT `dowload_id`) AS `total_downloads`,
       COUNT(DISTINCT `member_id`) AS `unique_members`,
       COUNT(DISTINCT `download_ip`) AS `unique_ips`
FROM `packet_download_log`
WHERE 1
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $total_downloads, $unique_members, $unique_ips );
    $query->fetch ();

    $counts = array (
        'total_downloads' => $total_downloads,
        'unique_members' => $unique_members,
        'unique_ips' => $unique_ips
    );

    return $counts;
  }

  /**
   * Get the count of downloads for the Hugo Voter Packet files - specifically the total number of downloads, the number of unique members that downloaded files, and the number of unique IP addresses where files were dowloaded to - grouped by user agent.
   *
   * @return array An array of hashes containing the download count information, one for each identified user agent.
   */
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
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $userAgent, $total_downloads, $unique_members, $unique_ips );
    $counts = array ();
    while ( $query->fetch () )
    {
      $agentCount = array (
          'user_agent' => $userAgent,
          'total_downloads' => $total_downloads,
          'unique_members' => $unique_members,
          'unique_ips' => $unique_ips
      );

      $counts [] = $agentCount;
    }

    return $counts;
  }

  /**
   * Get the count of downloads for the Hugo Voter Packet files - specifically the total number of downloads, the number of unique members that downloaded files, and the number of unique IP addresses where files were dowloaded to - grouped by date, as defined by "America/New_York" or Eastern Daylight Time.
   *
   * @return array An array of hashes containing the download count information, one for day.
   */
  function getDownloadCountsByDay()
  {
    date_default_timezone_set ( 'America/New_York' );

    $sql = <<<EOT
SELECT COUNT(dowload_id) AS downloads,
       COUNT(DISTINCT member_id) AS unique_members,
       COUNT(DISTINCT download_ip) AS unique_ip,
       DATE(download_complete) AS download_date
FROM `packet_download_log`
GROUP BY DATE(download_complete)
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $total_downloads, $unique_members, $unique_ips, $date );

    $counts = array ();
    while ( $query->fetch () )
    {
      $textDate = $date;
      if (preg_match ( '/(\d\d\d\d)-(\d\d)-(\d\d)/', $date, $matches ))
      {
        $month = $matches [2];
        $day = $matches [3];
        $year = $matches [1];
        $unixDate = mktime ( 0, 0, 0, $month, $day, $year, true );

        $textDate = date ( 'd-M-Y', $unixDate );
      }

      $dayCount = array (
          'date' => $textDate,
          'total_downloads' => $total_downloads,
          'unique_members' => $unique_members,
          'unique_ips' => $unique_ips
      );

      $counts [] = $dayCount;
    }

    return $counts;
  }

  /**
   * Get the number of unique voters, and the number of unique IPs (IPV4) where votes were cast.
   *
   * @return array A hash containing the vote count information.
   */
  function getVoteCounts()
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT `member_id`) AS `unique_voters`,
       COUNT(DISTINCT `ip_added_from`) AS `unique_ips`
FROM `hugo_ballot_entry`
WHERE 1
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $unique_members, $unique_ips );
    $query->fetch ();

    $counts = array (
        'unique_members' => $unique_members,
        'unique_ips' => $unique_ips
    );

    return $counts;
  }

  /**
   * Get the number of unique voters, and the number of unique IPs (IPV4) where the votes were cast grouped by days (per system setting).
   *
   * @return array An array of hashes containing the vote count information for each day.
   */
  function getVoteCountsByDay()
  {
    date_default_timezone_set ( 'America/New_York' );

    $sql = <<<EOT
SELECT COUNT(DISTINCT `member_id`) AS `unique_voters`,
       COUNT(DISTINCT `ip_added_from`) AS `unique_ips`,
       DATE(`time_added`) AS `vote_date`
FROM `hugo_ballot_entry`
WHERE 1
GROUP BY DATE(`time_added`)
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $unique_members, $unique_ips, $date );

    $counts = array ();
    while ( $query->fetch () )
    {
      $textDate = $date;
      if (preg_match ( '/(\d\d\d\d)-(\d\d)-(\d\d)/', $date, $matches ))
      {
        $month = $matches [2];
        $day = $matches [3];
        $year = $matches [1];
        $unixDate = mktime ( 0, 0, 0, $month, $day, $year, true );

        $textDate = date ( 'd-M-Y', $unixDate );
      }

      $dayCount = array (
          'date' => $textDate,
          'total_downloads' => $total_downloads,
          'unique_members' => $unique_members,
          'unique_ips' => $unique_ips
      );

      $counts [] = $dayCount;
    }

    return $counts;
  }

  /**
   * Gets the list of approved voters for a given category
   *
   * @param $categoryId int
   *          The database key for the Hugo Award Category.
   * @return array An array containing the unique Member IDs (Hugo Award PINs) for the members who voted in this category and have their ballots marked as approved.
   */
  function getVoters($categoryId)
  {
    $sql = <<<EOT
SELECT DISTINCT member_id
FROM hugo_ballot_entry
WHERE category_id = ?
  AND ballot_approved = 1
EOT;

    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $categoryId );
    $query->execute ();
    $query->bind_result ( $memberId );

    $voters = array ();

    while ( $query->fetch () )
    {
      $voters [] = $memberId;
    }

    return $voters;
  }

  /**
   * Gets the remaining voters in a given category after excluding votes for a list of finalists.
   *
   * @param $categoryId int
   *          The databae key for the Hugo Award category being searched
   * @param $excluded string
   *          Either a string containing a comma separated list of database keys for Hugo Award finalists, or an array of said keys. Optional.
   * @return array An array of unique Voter IDs (Hugo Award PINs)
   */
  function getRemainingVoters($categoryId, $excluded = '')
  {
    $sql = <<<EOT
SELECT DISTINCT member_id
FROM   hugo_ballot_entry
WHERE  category_id = ?
  AND  ballot_approved = 1
EOT;

    if (is_array ( $excluded ))
    {
      $excluded = implode ( ',', $excluded );
    }

    if ($excluded != '')
    {
      $sql .= "  AND short_list_id NOT IN ($excluded)\n";
    }

    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $categoryId );
    $query->execute ();

    $query->bind_result ( $memberId );

    $voters = array ();

    while ( $query->fetch () )
    {
      $voters [] = $memberId;
    }

    return $voters;
  }

  /**
   * Gets the full ballot for a given voter and a given category
   *
   * @param $memberId string
   *          A voter's unique Member ID (Hugo PIN)
   * @param $categoryId int
   *          The database key for a Hugo Award Finalist.
   * @return array A hash mapping the short list keys to the rank for this voter and category.
   */
  function getVoteBallot($memberId, $categoryId)
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
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'si', $memberId, $categoryId );
    $query->execute ();

    $query->bind_result ( $datum1, $sortValue, $rank );

    $ballot = array ();
    while ( $query->fetch () )
    {
      $ballot [$datum1] = $rank;
      if ($rank == 'NULL')
      {
        $ballot [$datum1] == '';
      }
    }

    return $ballot;
  }

  /**
   * Gets the "No Award" entry for the specified Hugo Award category
   *
   * @param $categoryId int
   *          The database key for the Hugo Award category
   * @return int The database key for the short list entry corresponding to "No Award" for that category.
   */
  function getNoAward($categoryId)
  {
    $sql = <<<EOT
SELECT shortlist_id
FROM   hugo_shortlist
WHERE  category_id = ?
  AND  datum_1 LIKE 'No Award'
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $categoryId );
    $query->execute ();

    $query->bind_result ( $shortlistId );

    // Should only be one, return first
    if ($query->fetch ())
    {
      $query->close ();
      return ($shortlistId);
    }

    return null;
  }

  /**
   * Gets the rank for a given member and Hugo Award Finalist.
   *
   * @param $memberId string
   *          The voter's unique member ID (Hugo Voter PIN)
   * @param $shortlistId int
   *          Thedatabase key for the Hugo Award Finalist record
   * @return int The rank for this entry. Null if there is no vote for this finalist.
   */
  function getRank($memberId, $shortlistId)
  {
    $sql = <<<EOT
SELECT rank
FROM   hugo_ballot_entry
WHERE  member_id = ?
  AND  short_list_id = ?
  AND  ballot_approved = 1
EOT;
    $query = self::$db->prepare ( $sql );

    $query->bind_param ( 'si', $memberId, $shortlistId );
    $query->execute ();

    $query->bind_result ( $rank );

    if ($query->fetch ())
    {
      return $rank;
    }

    return $null;
  }

  /**
   * Gets the current top vote for a given member in a given category once other finalists have been excluded.
   *
   * @param $memberId string
   *          The voter's unique member ID (Hugo Voter PIN)
   * @param $categoryId int
   *          The databae key for the Hugo Award category being searched
   * @param $excluded string
   *          Either a string containing a comma separated list of database keys for Hugo Award finalists, or an array of said keys. Optional.
   * @return array A hash containing the finalist's database key, and rank that represents the specified voter's highest remaining vote in the specified category.
   */
  function getCurrentVote($memberId, $categoryId, $excluded)
  {
    if (is_array ( $excluded ))
    {
      if (count ( $excluded ) > 0)
      {
        $excluded = implode ( ',', $excluded );
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
    if ($excluded != '')
    {
      $sql .= "\n  AND short_list_id NOT IN ($excluded)";
    }

    $sql .= "\nORDER BY `rank` ASC\n";

    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'si', $memberId, $categoryId );
    $query->execute ();

    $shortlistId = - 1;
    $rank = - 1;

    $query->bind_result ( $shortlistId, $rank );
    $query->store_result ();

    if ($query->num_rows >= 1)
    {
      $query->fetch ();
    }

    return (array (
        'short_list_id' => $shortlistId,
        'rank' => $rank
    ));
  }

  /**
   * Counts the number of first place votes for a given Hugo Award Finalist
   *
   * @param $shortListId int
   *          The database key for the Hugo Award Finalist
   * @return int Count of voters who placed that finalist in first place on their ballot.
   */
  function countFirstPlaceVotes($shortListId)
  {
    $sql = <<<EOT
SELECT COUNT(DISTINCT member_id)
FROM hugo_ballot_entry
WHERE short_list_id = ?
  AND rank = 1
  AND ballot_approved = 1
EOT;
    $query = self::$db->prepare ( $sql );

    $query->bind_param ( 'i', $shortListId );

    $query->execute ();

    $query->bind_result ( $firstPlaceVotes );

    $query->fetch ();

    return ($firstPlaceVotes);
  }

  /**
   * Updates the ballot count table for a given Hugo Award Finalist on a given voting round.
   *
   * @param $shortlistId int
   *          The database key for the Hugo Award Finalist
   * @param $placement int
   *          The place in the ranking for this finalist during this round of the Hugo Award balloting.
   * @param $count int
   *          The number of votes received during this round of counting.
   */
  function addBallotCount($shortlistId, $placement, $round, $count)
  {
    $sql = <<<EOT
DELETE
FROM   hugo_ballot_counts
WHERE  shortlist_id = ?
  AND  placement    = ?
  AND  round        = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'iii', $shortlistId, $placement, $round );
    $query->execute ();
    $query->fetch ();
    $query->close ();

    $sql = <<<EOT
INSERT INTO hugo_ballot_counts
(shortlist_id, placement, round, count)
VALUES (?, ?, ?, ?)
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'iiii', $shortlistId, $placement, $round, $count );
    $query->execute ();
    $query->fetch ();
    $query->close ();
  }

  /**
   * Get the order of results for the Hugo Award Finalist in a given category and placement
   *
   * @param $categoryId int
   *          The database key for the Hugo Award category
   * @param $placement int
   *          The number of the place (1 for Hugo Award, 2 for 1st runner up, etc.)
   * @return array A hash mapping the finalist's database key to their order in this place.
   */
  function getResultsOrder($categoryId, $placement)
  {
    $sql = <<<EOT
SELECT hugo_shortlist.shortlist_id,
       hugo_shortlist.datum_1,
       (SELECT MAX(round) FROM hugo_ballot_counts WHERE shortlist_id = hugo_shortlist.shortlist_id AND placement = ?) AS last_round_present
FROM   hugo_shortlist
WHERE  category_id = ?
ORDER BY last_round_present DESC
EOT;

    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'ii', $placement, $categoryId );
    $query->execute ();

    $query->bind_result ( $shortlistId, $datum1, $lastRound );

    $resultsOrder = array ();

    while ( $query->fetch () )
    {
      if (! is_null ( $lastRound ))
      {
        $resultsOrder [$shortlistId] = $lastRound;
      }
    }

    return $resultsOrder;
  }

  /**
   * TODO - Figure out what this function does for sure.
   *
   * @param $shortlistId int
   *          The database key for the Hugo Award Finalist
   * @param $placement int
   *          The place in the ranking for this finalist during this round of the Hugo Award balloting.
   * @return array A hash mapping the count at each round for this finalist and placement.
   */
  function getBallotCounts($shortlistId, $placement)
  {
    $sql = <<<EOT
SELECT round,
       count
FROM   hugo_ballot_counts
WHERE  shortlist_id = ?
  AND  placement    = ?
EOT;
    $query = self::$db->prepare ( $sql );

    $query->bind_param ( 'ii', $shortlistId, $placement );
    $query->execute ();
    $query->bind_result ( $round, $count );

    $ballotCounts = array ();

    while ( $query->fetch () )
    {
      $ballotCounts [$round] = $count;
    }

    $query->close ();

    return ($ballotCounts);
  }

  /**
   * Get the Hugo Finalist database key for "No Award" for a given Hugo Award Category
   *
   * @param $categoryId int
   *          The database key for the Hugo Award category
   * @return int The Hugo Finalist database key for "No Award" for that category, -1 if it has not been populated.
   */
  function getNoAwardId($categoryId)
  {
    print ("<!-- \$categoryId = $categoryId -->\n") ;

    $sql = <<<EOT
SELECT shortlist_id
FROM   hugo_shortlist
WHERE  category_id = ?
  AND  datum_1 LIKE '%No Award%'
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $categoryId );
    $query->execute ();

    $noAwardId = - 1;

    $query->bind_result ( $noAwardId );

    // Return only 1, should only be one
    if (! $query->fetch ())
    {
      print ("<!-- No Fetch Performed -->\n") ;
    }

    $query->close ();

    return $noAwardId;
  }

  /**
   * Get the data needed to perform the "No Award" comparison required by the WSFS Constitution.
   *
   * @param $winner int
   *          The Hugo Award finalist database key of the presumptive Hugo Award winner in some category
   * @param $noAward int
   *          The Hugo Award finalist database key for the "No Award" in that same category.
   * @return array A hash containing the needed data.
   */
  function getNoAwardCompairson($winner, $noAward)
  {
    $sql = <<<EOT
SELECT `member_id`,
       `short_list_id`,
       `rank`
FROM `hugo_ballot_entry`
WHERE (`short_list_id` = ? OR `short_list_id` = ?)
  AND `ballot_approved` = 1
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'ii', $winner, $noAward );
    $query->execute ();
    $query->bind_result ( $memberId, $shortListId, $rank );

    $voteData = array ();

    while ( $query->fetch () )
    {
      if (! isset ( $voteData [$memberId] ))
      {
        $voteData [$memberId] = array (
            $winner => 999,
            $noAward => 999
        );
      }
      $voteData [$memberId] [$shortListId] = $rank;
    }

    $query->close ();

    $winnerHigher = 0;
    $noAwardHigher = 0;

    foreach ( $voteData as $memberId => $results )
    {
      if ($results [$winner] < $results [$noAward])
      {
        $winnerHigher += 1;
      }
      elseif ($results [$winner] > $results [$noAward])
      {
        $noAwardHigher += 1;
      }
    }

    return array (
        "winner" => $winnerHigher,
        "noAward" => $noAwardHigher
    );
  }

  /**
   * Move the nominations from one Nominee to another nominee
   *
   *  @warning The nomination normalization feature has not been used since Chicon 7, if then.
   * @param $fromId int
   *          The database key for the nominee donating nominations.
   * @param $toId int
   *          The database key for the nominee receiving nominations.
   */
  function transferNominations($fromId, $toId)
  {
    $sql = <<<EOT
SELECT nominee_id
FROM   nominee
WHERE  nominee_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $fromId );
    $query->execute ();
    if (! $query->fetch ())
    {
      return;
    }

    $query->bind_param ( 'i', $toId );
    $query->execute ();
    if (! $query->fetch ())
    {
      return;
    }

    print ("<!-- About to transfer all nominations for id $fromId to id $toId -->\n") ;

    $query->close ();

    $sql = <<<EOT
UPDATE nominations
SET    nominee_id = ?
WHERE  nominee_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'ii', $toId, $fromId );
    $query->execute ();

    $query->close ();

    $sql = <<<EOT
DELETE FROM nominee
WHERE  nominee_id = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $fromId );
    $query->execute ();
  }

  /**
   * Get the email address for a given PIN
   *
   * @param $pin string
   *          The Hugo Voter PIN
   * @return string The email address associated with this PIN.
   */
  function getEmailHugoDb($pin)
  {
    $sql = <<<EOT
SELECT email
FROM   nomination_pin_email_info
WHERE  pin = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 's', $pin );
    $query->execute ();
    $query->bind_result ( $email );
    if ($query->fetch ())
    {
      return $email;
    }
    else
    {
      return '';
    }
  }

  /**
   * Gets the membership information from a PIN
   *
   * @param $pin string
   *          The Hugo Voter PIN
   * @return array A hash containing the membership information.
   */
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
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 's', $pin );
    $query->execute ();
    $query->bind_result ( $firstName, $secondName, $memberId, $email, $source );
    if ($query->fetch ())
    {
      return array (
          'first_name' => $firstName,
          'second_name' => $secondName,
          'member_id' => $memberId,
          'email' => $email,
          'source' => $source
      );
    }
    else
    {
      return array ();
    }
  }

  /**
   * Get all of the membership information contained in the internal membership table
   *
   * @return array An array of hashes containing the membership information.
   */
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
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $firstName, $secondName, $memberId, $email, $source, $pin );
    $memberInfo = array ();
    while ( $query->fetch () )
    {
      $memberRecord = array (
          'first_name' => $firstName,
          'second_name' => $secondName,
          'member_id' => $memberId,
          'email' => $email,
          'source' => $source,
          'pin' => $pin
      );
      $memberInfo [$pin] = $memberRecord;
    }

    $query->close ();
    return $memberInfo;
  }

  /**
   * Get the membership information for selected members
   *
   * @param $pinList array
   *          An array of PINs to get information for
   * @return array An array of hashes containing the memebership information.
   */
  function getSelectMemberInfo($pinList)
  {
    $pinList = implode ( ',', $pinList );

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
    print ("<!-- \$sql\n$sql\n-->\n") ;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $firstName, $secondName, $memberId, $email, $source, $pin );
    $memberInfo = array ();
    while ( $query->fetch () )
    {
      $memberRecord = array (
          'first_name' => $firstName,
          'second_name' => $secondName,
          'member_id' => $memberId,
          'email' => $email,
          'source' => $source,
          'pin' => $pin
      );
      $memberInfo [$pin] = $memberRecord;
    }

    $query->close ();

    return $memberInfo;
  }

  /**
   * Get the list of unique nominators, optionally from a single category.
   *
   * @param $categoryId int
   *          The database key for the Hugo Award category. Optional
   * @return array An array of member IDs (Hugo Voter PINs) for the nominators
   */
  function getNominators($categoryId = - 1)
  {
    $sql = <<<EOT
SELECT DISTINCT `nominator_id`
FROM   `nominations`
EOT;
    if ($categoryId != - 1)
    {
      $sql .= "\nWHERE  `award_category_id` = ?\n";
    }

    $query = self::$db->prepare ( $sql );
    if ($categoryId != - 1)
    {
      $query->bind_param ( 'i', $categoryId );
    }
    $query->execute ();
    $query->bind_result ( $nominatorId );
    $nominators = array ();
    while ( $query->fetch () )
    {
      $nominators [] = $nominatorId;
    }

    return $nominators;
  }

  /**
   * Log the receipt of a Hugo Nominating ballot via HTTP Post
   *
   * This function operates without parameters by interacting directly with the web server
   */
  function logNominationPost()
  {
    global $_POST;
    global $_SERVER;

    $sql = <<<EOT
INSERT INTO nomination_post_summary
(post_contents,server_contents)
VALUES (?, ?)
EOT;
    $query = self::$db->prepare ( $sql );

    ob_start ();
    var_dump ( $_POST );
    $post_contents = ob_get_contents ();
    ob_end_clean ();
    ob_start ();
    var_dump ( $_SERVER );
    $server_contents = ob_get_contents ();
    ob_end_clean ();

    $query->bind_param ( 'ss', $post_contents, $server_contents );
    $query->execute ();
    $query->close ();
  }

  /**
   * Logs the nomination page generated (TODO, confirm)
   *
   * @param $pageText string
   *          Text of the page to be logged.
   */
  function logNominationPage($pageText)
  {
    global $_SERVER;

    $sql = <<<EOT
INSERT INTO nomination_page_log
(nomination_page,ip_received_from,timestamp)
VALUES (?, ?, CURRENT_TIMESTAMP)
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'ss', $pageText, $_SERVER ['REMOTE_ADDR'] );
    $query->execute ();
    $query->close ();
  }

  /**
   * Gets the most recent data when the specified nominator has updated their nominating ballot
   *
   * @param $nominatorId string
   *          The unique member ID for the nominator (Hugo Voter PIN)
   * @return string The date when they last updated their nominating ballot.
   */
  function getLatestNominationDate($nominatorId)
  {
    if (preg_match ( '(\\d+)', $nominatorId, $matches ))
    {
      $nominatorId = $matches [0];
    }

    $sql = <<<EOT
SELECT nomination_date
       FROM   nominations
       WHERE  nominator_id = ?
       ORDER BY nomination_date DESC
       LIMIT 1
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $nominatorId );
    $query->execute ();
    $query->bind_result ( $latestNominationDate );
    $latestNominationDate = '';
    $query->fetch ();
    return $latestNominationDate;
  }

  /**
   * Gets a list of all of the PINs in the internal membership database with their two leftmost characters stripped off.
   *
   * @return array An array containing the list of PINs
   */
  function getCurrentPins()
  {
    $sql = <<<EOT
SELECT DISTINCT SUBSTR( `pin` , 3 ) AS `stripped_pin`
FROM `nomination_pin_email_info`
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $nextPin );

    $currentPins = array ();
    while ( $query->fetch () )
    {
      $currentPins [] = $nextPin;
    }
    $query->close ();

    return $currentPins;
  }

  /**
   * Gets the list of PINs that have been used to vote for the Hugo Award.
   *
   * @return array An array containing the list of PINs used to vote for the Hugo Award.
   */
  function getVotedPins()
  {
    $sql = <<<EOT
SELECT DISTINCT `member_id`
FROM   `hugo_ballot_entry`
EOT;
    $query = self::$db->prepare ( $sql );
    $query->execute ();
    $query->bind_result ( $nextPin );

    $votedPins = array ();
    while ( $query->fetch () )
    {
      $votedPins [] = $nextPin;
    }
    $query->close ();

    return $votedPins;
  }

  /**
   * Gets the list of PINs that have voted, but are not in the current internal membership list - likely due to membership transfers prior to Chicon 8.
   *
   * @return array An array containing hashes of information about ballots that do not have PINs in the current internal membership list
   */
  function getOrphanBallots()
  {
    $currentPins = self::getCurrentPins ();
    $votedPins = self::getVotedPins ();

    // DEBUG
    // print("<!-- \$currentPins:\n");
    // var_dump($currentPins);
    // print("\n\n\$votedPins:\n");
    // var_dump($votedPins);
    // print("\n-->\n");

    $orphanPins = array ();

    foreach ( $votedPins as $pin )
    {
      if (! in_array ( $pin, $currentPins, false ))
      {
        $orphanPins [] = $pin;
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
    $query = self::$db->prepare ( $sql );

    $orphanBallotList = array ();

    foreach ( $orphanPins as $orphan )
    {
      $query->bind_param ( 's', $orphan );
      $query->execute ();
      $query->bind_result ( $email, $sendTime );
      if ($query->fetch ())
      {
        $orphanBallotList [$orphan] = array (
            'email' => $email,
            'send_time' => $sendTime
        );
      }
      else
      {
        $orphanBallotList [$orphan] = array (
            'email' => 'No Info',
            'send_time' => 'No Info'
        );
      }
    }

    return $orphanBallotList;
  }

  /**
   * Get a summary record for the specified voter
   *
   * @param $voterId string
   *          The unique voter ID (Hugo Voter PIN)
   * @return array A Hash containing voter information.
   */
  function getVoterSummary($voterId)
  {
    $voterInfo = array ();

    // Get the personal info from the membership database
    $sql = <<<EOT
SELECT `first_name`,
       `second_name`,
       `member_id`,
       `pin`
FROM   `nomination_pin_email_info`
WHERE  SUBSTR(`pin`,3) = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 's', $voterId );
    $query->execute ();
    $query->bind_result ( $firstName, $secondName, $memberId, $pin );
    if ($query->fetch ())
    {
      $voterInfo = array (
          'first_name' => $firstName,
          'second_name' => $secondName,
          'member_id' => $memberId,
          'pin' => $pin
      );
    }
    else
    {
      $voterInfo = array (
          'first_name' => 'Orphan',
          'second_name' => 'Orphan',
          'member_id' => 'Unknown',
          'pin' => "SQ$voterId",
          'Import records' => `grep -i SQ$voterId ../import/*.csv 2>&1`
      ); // */
    }
    $query->close ();

    $sql = <<<EOT
SELECT `ip_added_from`,
       `time_added`,
       `ballot_approved`
FROM   `hugo_ballot_entry`
WHERE  `member_id` = ?
ORDER BY `time_added` DESC
LIMIT 1
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'i', $voterId );
    $query->execute ();
    $query->bind_result ( $voterInfo ['ip_added_from'], $voterInfo ['time_added'], $voterInfo ['ballot_approved'] );
    if (! $query->fetch ())
    {
      $voterInfo ['ip_added_from'] = 'No Votes';
      $voterInfo ['time_added'] = 'No Votes';
      $voterInfo ['ballot_approved'] = 0;
    }
    $query->close ();

    return $voterInfo;
  }

  /**
   * Set the ballot approval for the specified voter
   *
   * @param $voterId string
   *          The unique ID (Hugo Voter PIN)
   * @param $approved boolean
   *          The new approval setting (0 or 1)
   * @return string A string showing the updated settings.
   */
  function approveVotes($voterId, $approved)
  {
    $sql = <<<EOT
UPDATE `hugo_ballot_entry`
SET    `ballot_approved` = ?
WHERE  `member_id` = ?
EOT;
    $query = self::$db->prepare ( $sql );
    $query->bind_param ( 'ii', $approved, $voterId );
    $query->execute ();
    $query->close ();

    return "\$voterId = $voterId\n\$approved = $approved\n";
  }

  /**
   * Empties the internal membership table.
   *
   * @return string The results of the SQL query to empty the table.
   */
  function emptyMembership()
  {
    $sql = <<<EOT
TRUNCATE `nomination_pin_email_info`
EOT;
    $return = self::$db->query ( $sql );

    return $return;
  }
}

/**
 * Comparison function for sorting nominations by their count.
 *
 *    @warning The nomination counting functionality has not been used since Chicon 7, if then. It is based on the WSFS Constitution rules prior to 2017, and does not support the current process for determining the Hugo Award Finalists.
 *
 * @return int The values needed to compare the two records.
 */
function sortNomineesByCount($a, $b)
{
  return ($a ['nomination_count_reviewed'] == $b ['nomination_count_reviewed']) ? 0 : (($a ['nomination_count_reviewed'] > $b ['nomination_count_reviewed']) ? - 1 : 1);
}


