<?php
    /**
    * Copyright 2013 Steven R. Staton (http://www.deltos.com/hugo-awards)
    * This package is dedicated to LoneStarCon3 and Bill Parker.
    * Portions copyright 2015-2024 Ronald B. Oakes
    *
    * This program is free software: you can redistribute it and/or modify
    * it under the terms of the GNU General Public License as published by
    * the Free Software Foundation, either version 3 of the License, or
    * (at your option) any later version.
    *
    * This program is distributed in the hope that it will be useful,
    * but WITHOUT ANY WARRANTY; without even the implied warranty of
    * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    * GNU General Public License for more details.
    *
    * You should have received a copy of the GNU General Public License
    * along with this program.  If not, see <http://www.gnu.org/licenses/>.
    **/

     require_once('./database.php');

     date_default_timezone_set('America/Los_Angeles');

     // Store category vote ranking for given PIN.

    $result = array();
    $nominees = array();
    $row_array = array();


    // Need these to be global in context
    $pin = '';
    $votes = '';
    $category = '';
    $session_id = '';
    $wsfs_retro = 0;
    $timeout = strtotime(date('Y-m-d H:i:s', time()));

    header('Content-Type: application/json');

    $logname = $_SERVER['SCRIPT_NAME'];
    if ($logname) {
        $logname = basename($logname, '.php');
    }

    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        if (isset($_GET['pin']))        { $pin      = $_GET['pin']; }
        if (isset($_GET['category']))   { $category = $_GET['category']; }
        if (isset($_GET['votes']))      { $votes    = $_GET['votes']; }
        if (isset($_GET['wsfs_retro']))
        {
          $wsfs_retro = $_GET['wsfs_retro'];
        }

        error_log("[$logname: GET] CATEGORY: $category PIN: '$pin' VOTES: '$votes', RETRO: '$wsfs_retro'");
    }
    else if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        if (isset($_POST['pin']))       { $pin      = $_POST['pin']; }
        if (isset($_POST['category']))  { $category = $_POST['category']; }
        if (isset($_POST['votes']))     { $votes    = $_POST['votes']; }
        if (isset($_POST['wsfs_retro']))
        {
          $wsfs_retro = $_POST['wsfs_retro'];
        }

        error_log("[$logname: POST] CATEGORY: $category PIN: '$pin' VOTES: '$votes', RETRO: '$wsfs_retro'");
    }

    if (empty($pin) ||
        empty($category) ||
        empty($votes)) {
            $result['valid'] = false;
            $result['error'] = "Missing argument(s):";

            if (empty($pin))        { $result['error'] .= " pin"; }
            if (empty($category))   { $result['error'] .= " category"; }
            if (empty($votes))      { $result['error'] .= " votes"; }

            echo json_encode($result);
            return;
    }

    $db = new database($wsfs_retro);

    $votelist1 = explode(',',$votes);

    $votelist = array();

    foreach($votelist1 as $entry)
    {
      if(preg_match('/(\\d+)/',$entry,$matches))
      {
        $votelist[] = $matches[1] + 0;
      }
      else
      {
        $votelist[] = 0;
      }
    }

    // DEBUG
//    ob_start();
//    var_dump($votelist);
//    $voteText = ob_get_clean();
//    ob_end_clean();
//    $filehandle = fopen('./hugo-set-ballot_log.txt','a');
//    fwrite($filehandle,date('Y-m-d H:i:s', time()));
//    fwrite($filehandle,"\n\$category $category\n");
//    fwrite($filehandle,"\$pin $pin\n");
//    fwrite($filehandle,"\$votelist\n$voteText\n");
//    fflush($filehandle);
//    fclose($filehandle);
    // END DEBUG

    $debug_data = "";

    $shortlist = $db->getShortlist($category);

    ob_start();
    var_dump($shortlist);
    $shortlist_text = ob_get_clean();
    ob_end_clean();

    $index = 0;
    foreach($shortlist as $shortlistId => $shortlistInfo)
    {
      if($votelist[$index] != 0) // We have a rank
      {
        $debug_data .= $db->addUpdateVote($pin,$category,$shortlistId,$votelist[$index]);
      }
      else
      {
        $debug_data .= $db->deleteVote($pin,$category,$shortlistId,$votelist[$index]);
      }
      $index += 1;
    }

    $result['valid'] = true;
    $result['category'] = $category;
    $result['voteText'] = $voteText;
    $result['shortlist'] = $shortlist_text;
    $result['wsfs_retro'] = $wsfs_retro;
    $result['debug_data'] = $debug_data;

    echo json_encode($result);
    return;
?>
