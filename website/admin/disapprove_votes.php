<?php
/*
 * AJAX back end function for approving votes.
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
 * AJAX back end function for approving votes.
 * 
 */
require_once ('library.php');

session_start ();
header ( 'Content-Type: application/json' );

// Comment out the following once debugging has been completed.
error_reporting ( E_ALL );
ini_set ( 'display_errors', '1' );
// End comment out section.

$memberId = - 1;
$year = 2015;
$approval = 0;

if ($_SERVER ['REQUEST_METHOD'] == 'GET')
{
  if (isset ( $_GET ['pin'] ))
  {
    $memberId = $_GET ['pin'];
  }
  if (isset ( $_GET ['year'] ))
  {
    $year = $_GET ['year'];
  }
  if (isset ( $_GET ['approved'] ))
  {
    $approval = $_GET ['approved'];
  }
}
elseif ($_SERVER ['REQUEST_METHOD'] == 'POST')
{
  if (isset ( $_POST ['pin'] ))
  {
    $memberId = $_POST ['pin'];
  }
  if (isset ( $_POST ['year'] ))
  {
    $year = $_POST ['year'];
  }
  if (isset ( $_POST ['approved'] ))
  {
    $approval = $_POST ['approved'];
  }
}

$db = new database ( $year );

$result = array (); // Empty array to hold <input> values as they are retrieved.

$result ['summary'] = $db->approveVotes ( $memberId, $approval );

$result ['valid'] = true;
echo json_encode ( $result );
return;

?>
