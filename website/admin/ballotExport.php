<?PHP
/*
 * Exports the Hugo Award voting ballots as a CSV file.
 * Copyright (C) 2014-2024, Ronald B. Oakes
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
