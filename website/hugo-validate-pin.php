<?php
    /**
    * Copyright 2013 Steven R. Staton (http://www.deltos.com/hugo-awards)
    * This package is dedicated to LoneStarCon3 and Bill Parker.
    * Portions Copyright 2015-2024 Ronald B. Oakes
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

    require_once('./memberValidator.php');

    session_start();
    header('Content-Type: application/json');

    // Comment out the following once debuging has been completed.
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
    // End comment out section.

     // DEBUG
//    $result = array('PINstatus' => 'valid');
//    echo json_encode($result);
//    return;
     // END DEBUG

     // Validate PIN

    $result = array();      // Empty array to hold <input> values as they are retrieved.

    $pin = "";
    $lastname = "";
    $firstname = "";
    $membership = "";

    $runFlag = false;
    $rowCount = 0;

    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        if (isset($_GET['hg_pin']))         { $pin = $_GET['hg_pin']; }
        if (isset($_GET['hg_membership']))  { $membership = $_GET['hg_membership']; }
        if (isset($_GET['hg_last_name']))   { $lastname = $_GET['hg_last_name']; }
        if (isset($_GET['hg_first_name']))  { $firstname = $_GET['hg_first_name']; }
        error_log("[hugo-validate-pin: GET] Lastname: '$lastname' Firstname: '$firstname' Membership: '$membership' PIN: '$pin'");
    } else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        if (isset($_POST['hg_pin']))        { $pin        = $_POST['hg_pin']; }
        if (isset($_POST['hg_membership'])) { $membership = $_POST['hg_membership']; }
        if (isset($_POST['hg_last_name']))  { $lastname   = $_POST['hg_last_name']; }
        if (isset($_POST['hg_first_name'])) { $firstname  = $_POST['hg_first_name']; }
        error_log("[hugo-validate-pin: POST] Lastname: '$lastname' Firstname: '$firstname' Membership: '$membership' PIN: '$pin'");
    }

    if(empty($pin))
    {
      $result['reason']    = 'You must provide a PIN to login';
      $result['PINstatus'] = 'missing values';
      echo json_encode($result);
      return;
    }

    if (empty($membership) && empty($lastname))
    {
        $result['reason'] = "You must provide either your last name, or membership number to login.";
        $result['PINstatus'] = "missing values";
        echo json_encode($result);
        return;
    }


    //   $validationCode = validateMember($_POST[member_id],$_POST['pin'],$_POST['last_name']);

    $validationData = validateMember($membership,$pin,$lastname,$firstname);

    if(is_array($validationData))
    {
      if($validationData['valid'] != 0)
      {
        $result['PINstatus'] = 'valid';
        $result['email']     = $validationData['email'];
        $_SESSION['email']   = $validationData['email'];
        echo json_encode($result);
        return;
      }
    }
    else
    {
      if($validationData != 0)
      {
        $result['PINstatus'] = 'valid';
        $result['email']     = getMemberEmailFromPin($pin);
        echo json_encode($result);
        return;
      }
    }

    // Return failed status as no PIN matched.
    $result['PINstatus'] = 'invalid';
    echo json_encode($result);
    return;

?>
