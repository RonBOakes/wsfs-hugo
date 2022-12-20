<?PHP
/* Written by Ronald B. Oakes, copyright  2015, 2018
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/

    require_once('./database.php');
    header('Content-Type: application/json');

    $pin = '';
    $email = '';
    $wsfs_retro = 0;
    $privlidge = 0;

    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        if (isset($_GET['pin']))
        {
          $pin = $_GET['pin'];
        }
        if (isset($_GET['email_address']))
        {
          $email = $_GET['email_address'];
        }
        if (isset($_GET['wsfs_retro']))
        {
          $wsfs_retro = $_GET['wsfs_retro'];
        }
        if (isset($_GET['privlidge'])) 
        {
            $privlidge = $_GET['privlidge'];
        }
    }
    else if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        if (isset($_POST['pin']))
        {
          $pin = $_POST['pin'];
        }
        if (isset($_POST['email_address']))
        {
          $email = $_POST['email_address'];
        }
        if (isset($_POST['wsfs_retro']))
        {
          $wsfs_retro = $_POST['wsfs_retro'];
        }
        if (isset($_POST['privlidge'])) 
        {
            $privlidge = $_POST['privlidge'];
        }
    }

    $db = new database($wsfs_retro);

    $body = '';
    $subject = '';

    if (!$wsfs_retro)
    {
      $body .= 'Your current 2018 Hugo Award Ballot as recorded by Worldcon 76:' . "\n";
      $subject = 'Your 2018 Hugo Award Ballot as Requested';
    }
    else
    {
      $body .= 'Your current 2018 Retrospective Hugo Award Ballot (for works from 1943) as recorded by Worldcon 76:' . "\n";
      $subject = 'Your 2018 Retrospective Hugo Award Ballot as Requested';
    }

    $body .= "Note: this email is formatted as plain-text and is best viewed with a fixed width font\n\n";
    $body .= "If you are receiving this email, and have not made a recent change to your Hugo Awards ballot,\n";
    $body .= "or requested the ballot be emailed to you, please inform the Hugo Award Administrators at\n";
    $body .= "hugoadmin@worldcon76.org.org\n\n";

    $fullShortlist = $db->getFullShortlist();
    $categoryInfo = $db->getCategoryInfo();

    foreach($fullShortlist as $categoryId => $categoryData)
    {
      if($categoryInfo[$categoryId]['shortlist_count'] > 1) // If only one in shortlist it is "No Award"
      {
        $votes = $db->getVotes($categoryId,$pin);

        $body .= "----------------------------------------\n"
               .  $categoryInfo[$categoryId]['name']."\n\n"
               .  "\tRank\t\t----------------------------\n";
        foreach($categoryData as $shortlistId => $sortName)
        {
          $shortListInfo = $db->getShortlistInfo($shortlistId);

          $name = $shortListInfo['datum_1'];

          $name = preg_replace('/\\<em\\>/','',$name);
          $name = preg_replace('/\\<\\/em\\>/','',$name);
          $name = preg_replace('/\&quot;/','',$name);

          if(!isset($votes[$shortlistId]))
          {
            $body .= "\tNo Vote\t\t".$name;
          }
          else
          {
            $body .= "\t".$votes[$shortlistId]."\t\t".$name;
          }
          if((trim($categoryInfo[$categoryId]['datum_2_description']) == 'Author') && ($name != 'No Award'))
          {
            $body .= ' by '.$shortListInfo['datum_2'];
          }
          elseif ((trim($categoryInfo[$categoryId]['datum_2_description']) != '') && ($name != 'No Award'))
          {
            $body .= ' ' . $shortListInfo['datum_2'];
          }
          if(($categoryInfo[$categoryId]['datum_3_description'] != '') && ($name != 'No Award'))
          {
            $datum3 = $shortListInfo['datum_3'];
            $datum3 = preg_replace('/\\<em\\>/','',$datum3);
            $datum3 = preg_replace('/\\<\\/em\\>/','',$datum3);
            $datum3 = preg_replace('/\&quot;/','',$datum3);

            $body .= " ($datum3)";
          }
          $body .= "\n";
        }
      }
    }

    $success = false;

    if(!$privlidge != 1)
    {
      // Send email
      $sendername = 'hugoadmin@worldcon76.org';
      $fromemail  = 'Worldcon 76 Hugo Award Administrators';
      $senderemail = 'hugoadmin@worldcon76.org';
      DEFINE('MAIL_DOMAIN','@worldcon76.org');

      $headers = "From: \"".$fromemail."\" <".trim($sendername).">\n";

      $success = mail($email,$subject,$body,$headers);
      $db->logEmail($pin,$body,$success,$email);
    }
    else
    {
        $success = true;
    }

    $result = array('valid' => $success, 'email' => $email, 'pin' => $pin, 'year' => $year);

    echo json_encode($result);
    return;
?>
