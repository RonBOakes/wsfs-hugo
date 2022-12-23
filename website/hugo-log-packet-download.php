<?PHP
/*
 * Written by Ronald B. Oakes, copyright 2015-2022
 * Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
 * For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
 * All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
 */
/**
 * Log the download of a Hugo Voter Packet file.
 */
header ( 'Content-Type: application/json' );

require_once ('database.php');

// url=03-Novelette-2014-05-13.zip&pin=LL1234567&ipAddr=::1

$db = new database ( false );

if ($_SERVER ['REQUEST_METHOD'] == 'GET')
{
  if (isset ( $_GET ['archive'] ))
  {
    $fileName = $_GET ['archive'];
  }

  if (isset ( $_GET ['pin'] ))
  {
    $pin = $_GET ['pin'];
  }

  if (isset ( $_GET ['ipAddr'] ))
  {
    $ipAddr = $_GET ['ipAddr'];
  }

  if (isset ( $_GET ['userAgent'] ))
  {
    $userAgent = $_GET ['userAgent'];
  }
}
else if ($_SERVER ['REQUEST_METHOD'] == 'POST')
{
  if (isset ( $_POST ['archive'] ))
  {
    $fileName = $_POST ['archive'];
  }

  if (isset ( $_POST ['pin'] ))
  {
    $pin = $_POST ['pin'];
  }

  if (isset ( $_POST ['ipAddr'] ))
  {
    $ipAddr = $_POST ['ipAddr'];
  }

  if (isset ( $_POST ['userAgent'] ))
  {
    $userAgent = $_POST ['userAgent'];
  }
}
else
{
  $result ['valid'] = false;
  echo json_encode ( $result );
  return;
}

if ((! isset ( $fileName ) || (! isset ( $pin )) || (! isset ( $ipAddr )) || (! isset ( $userAgent ))))
{
  $result ['valid'] = false;
  echo json_encode ( $result );
  return;
}

$packetId = $db->reversePacketLookup ( $fileName );

$db->logPacketDownload ( $pin, $ipAddr, $packetId, $userAgent );

$result ['valid'] = true;
echo json_encode ( $result );
return;
?>
