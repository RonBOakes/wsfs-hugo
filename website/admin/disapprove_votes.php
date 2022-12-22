<?php
/*
 * Written by Ronald B. Oakes, copyright 2015, 2022
 * Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
 * For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
 * All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
 */
/**
 * AJAX backend function for approving votes.
 *
 * Does not actually do anything.
 */
require_once ('library.php');

session_start ();
header ( 'Content-Type: application/json' );

// Comment out the following once debuging has been completed.
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
