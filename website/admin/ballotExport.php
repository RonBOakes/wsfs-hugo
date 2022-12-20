<?PHP
/* Written by Ronald B. Oakes, copyright 2014, Updated 2015
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
  session_start();
  require_once('library.php');

  $fullShortList = $db->getFullShortList();

  $categoryInfo = $db->getCategoryInfo();

  foreach ($categoryInfo as $id => $info)
  {
    $categoryOrder[$categoryInfo[$id]['ballot_position']] = $id;
  }

  // Headers
    header('Content-Description: File Transfer');
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename=\'hugo_ballot.csv\'');
    header('Content-Transfer-Encoding: binary');
    header('Expires: 0');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');


  foreach($categoryOrder as $pos => $catId)
  {
    print($categoryInfo[$catId]['name'].',');

    foreach($fullShortList[$catId] as $name)
    {
      $name = preg_replace('/,/','',$name);
      print($name.',');
    }
  }
  print("chicon member #\n");

  $voters = $db->getAllVoters();

  foreach($voters as $memberId => $chiconNo)
  {
    $voterBallot = $db->voterBallot($memberId);
    foreach($categoryOrder as $pos => $catId)
    {
      print (',');

      foreach($fullShortList[$catId] as $shortlistId => $name)
      {
        print($voterBallot[$shortlistId].',');
      }
    }
    print($chiconNo."\n");
    flush();
  }


?>
