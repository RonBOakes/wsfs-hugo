<?PHP
/*
 * Log the download of a Hugo Voter Packet File
 * Copyright (C) 2015-2024 Ronald B. Oakes
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
