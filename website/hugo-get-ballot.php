<?php
    /**
    * Copyright 2013 Steven R. Staton (http://www.deltos.com/hugo-awards)
    * This package is dedicated to LoneStarCon3 and Bill Parker.
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

/* Portions written by Ronald B. Oakes, copyright  2015, 2016
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/

    require_once('./database.php');
    /**
    * Package: Get Ballot
    *
    * Kind: PHP Web Service
    *
    * Service:
    * Lookup the Nominees for a given category and the member's vote
    * and return them in a JSON package with the Noms randomized by PIN.
    *
    * Inputs:
    * @pin - Member's PIN
    * @category - Enumerated Hugo Category
    *
    * Returns:
    * JSON hash containing columns from matching member's ballot row.
    *
    **/

    $result = array();
    $nominees = array();
    $row_array = array();


    // Need these to be global in context
    $pin = '';
    $category = '';
    $session_id = '0';
    date_default_timezone_set('America/Los_Angeles');
    $timeout = strtotime(date('Y-m-d H:i:s', time()));

    header('Content-Type: application/json');

    $logname = $_SERVER['SCRIPT_NAME'];
    if ($logname) {
        $logname = basename($logname, '.php');
    }

    $wsfs_retro = false;

    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        if (isset($_GET['pin']))        { $pin      = $_GET['pin']; }
        if (isset($_GET['category']))   { $category = $_GET['category']; }
        if (isset($_GET['wsfs_retro']))
        {
          $wsfs_retro = $_GET['wsfs_retro'] == 1;
        }
    }
    else if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        if (isset($_POST['pin']))       { $pin      = $_POST['pin']; }
        if (isset($_POST['category']))  { $category = $_POST['category']; }
        if (isset($_GET['wsfs_retro']))
        {
          $wsfs_retro = $_GET['wsfs_retro'] == 1;
        }
    }

    $db = new database($wsfs_retro);

    $votes = array();

    // Size the categories by nomination size.

    $shortlistCount = $db->getShortlistCount($category);
    $order = array();
    for($index = 0; $index < $shortlistCount; $index++)
    {
      array_push($votes,"");
      array_push($order, $index);
    }

    $userVotes = $db->getVotes($category,$pin);
    $shortlist = $db->getShortlist($category);

    $index = 0;

    foreach($shortlist as $shortlistId => $info)
    {
      if(isset($userVotes[$shortlistId]))
      {
        $votes[$index] = $userVotes[$shortlistId];
      }
      $index += 1;
    }

    // Uncomment the next line to randomize the nominee order in the e-ballot.
    // shuffle($order);    // Randomize a new ballot's order

    $result['category'] = $category;
    $result['votes']    = $votes;
    $result['order']    = $order;
    $result['session']  = $session_id;
    $result['timeout']  = $timeout;
    $result['valid']    = true;
    $result['year']     = $year;
    $result['session']  = $sessionDump;
    echo json_encode($result);
    return;
?>
