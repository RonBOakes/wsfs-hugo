<?PHP
/* Written by Ronald B. Oakes, copyright 2014, Updated 2015, 2022
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
/**
  Exports the Hugo Award voting ballots as a CSV (Comma Separate Values) file.
*/
  session_start();
  require_once('library.php');

  $fullShortList = $db->getFullShortList();

  $categoryInfo = $db->getCategoryInfo();

  foreach ($categoryInfo as $id => $info)
  {
    $categoryOrder[$categoryInfo[$id]['ballot_position']] = $id;
  }

  // Set the headers so that the HTTP/HTTPS transaction will register as a CSV file download.
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=\'hugo_ballot.csv\'');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');

  // Loop over each category.
  foreach($categoryOrder as $pos => $catId)
  {
	// Print the category name followed by a comma.
    print($categoryInfo[$catId]['name'].',');

	// Replace any commas in the Finalist name with null characters, then print each finalist
    foreach($fullShortList[$catId] as $name)
    {
      $name = preg_replace('/,/','',$name);
      print($name.',');
    }
  }
  print("chicon member #\n");

  $voters = $db->getAllVoters();

  // Loop over the voters.
  foreach($voters as $memberId => $chiconNo)
  {
    $voterBallot = $db->voterBallot($memberId);
    foreach($categoryOrder as $pos => $catId)
    {
      // Print the comma at the top of the loop.  This will ensure that the first column is a blank at the start of each category.
      print (',');

	  // Loop over the ballot in the order that they appear on the shortlist (and the columns were set above) and print the votes.
      foreach($fullShortList[$catId] as $shortlistId => $name)
      {
        print($voterBallot[$shortlistId].',');
      }
    }
	// Print the voter number (PIN).
    print($chiconNo."\n");
    flush();
  }


?>
