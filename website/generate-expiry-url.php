<?php

    // Generate a Expiring URL for Amazon S3 Resource
    // Hugo Packet ZIP Archive (c) 2013 Deltos Fleet Computing

/* Portions Written by Ronald B. Oakes, copyright  2015, 2018
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/

    require_once('memberValidator.php');

    session_start();
    header('Content-Type: application/json');

    $BUCKET_NAME = 'worldcon76-hugo-packet';
    $ARCHIVE_KEY = 'AKIAJISI5TEK2QJ4FSFQ';
    $SECRET_KEY  = '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';
    $TTL         = 60;    // Time-to-live in seconds.

    // Internal database of PINs that require longer TTL value
    $extended = array(
    );

    $result = array();    // Prep the response JSON array.

    if(!function_exists('el_crypto_hmacSHA1'))
    {
        /**
        * Calculate the HMAC SHA1 hash of a string.
        *
        * @param string $key The key to hash against
        * @param string $data The data to hash
        * @param int $blocksize Optional blocksize
        * @return string HMAC SHA1
        */
        function el_crypto_hmacSHA1($key, $data, $blocksize = 64) {
            if (strlen($key) > $blocksize) $key = pack('H*', sha1($key));
            $key = str_pad($key, $blocksize, chr(0x00));
            $ipad = str_repeat(chr(0x36), $blocksize);
            $opad = str_repeat(chr(0x5c), $blocksize);
            $hmac = pack( 'H*', sha1(
            ($key ^ $opad) . pack( 'H*', sha1(
              ($key ^ $ipad) . $data
            ))
          ));
            return base64_encode($hmac);
        }
    }

    if(!function_exists('el_s3_getTemporaryLink')){
        /**
        * Create temporary URLs to your protected Amazon S3 files.
        *
        * @param string $accessKey Your Amazon S3 access key
        * @param string $secretKey Your Amazon S3 secret key
        * @param string $bucket The bucket (bucket.s3.amazonaws.com)
        * @param string $path The target file path
        * @param int $expires In minutes
        * @return string Temporary Amazon S3 URL
        * @see http://awsdocs.s3.amazonaws.com/S3/20060301/s3-dg-20060301.pdf
        */

        function el_s3_getTemporaryLink($accessKey, $secretKey, $bucket, $path, $expires = 5) {
          // Calculate expiry time
          $expires = time() + intval(floatval($expires));
          // Fix the path; encode and sanitize
          $path = str_replace('%2F', '/', rawurlencode($path = ltrim($path, '/')));
          // Path for signature starts with the bucket
          $signpath = '/'. $bucket .'/'. $path;
          // S3 friendly string to sign
          $signsz = implode("\n", $pieces = array('GET', null, null, $expires, $signpath));
          // Calculate the hash
          $signature = el_crypto_hmacSHA1($secretKey, $signsz);
          // Glue the URL ...
          $url = sprintf('https://worldcon76-hugo-packet.s3.amazonaws.com/%s', $path);
          // ... to the query string ...
          $qs = http_build_query($pieces = array(
            'AWSAccessKeyId' => $accessKey,
            'Expires' => $expires,
            'Signature' => $signature,
          ));
          // ... and return the URL!
          return $url.'?'.$qs;
        }
    }

  // Initialize logging
  $logname = $_SERVER['SCRIPT_NAME'];
  if ($logname) {
    $logname = basename($logname, '.php');
  }

  // Extract parameters
  if ($_SERVER['REQUEST_METHOD'] == 'GET')
  {
    if (isset( $_GET['archive']))
    {
      $archive =  $_GET['archive'];
    }

    if (isset( $_GET['pin']))
    {
      $pin     =  $_GET['pin'];
    }

    if (isset( $_GET['member']))
    {
      $member = $_GET['member'];
    }

    if (isset($_GET['lname']))
    {
      $lname = $_GET['lname'];
    }

    error_log("[$logname: GET] PIN: '$pin' ZIP Archive: '$archive'");
  }
  else if ($_SERVER['REQUEST_METHOD'] == 'POST')
  {
    if (isset($_POST['archive']))
    {
      $archive = $_POST['archive'];
    }

    if (isset($_POST['pin']))
    {
      $pin     = $_POST['pin'];
    }

    if (isset( $_POST['member']))
    {
      $member = $_POST['member'];
    }

    if (isset($_POST['lname']))
    {
      $lname = $_POST['lname'];
    }

    error_log("[$logname: POST] PIN: '$pin' ZIP Archive: '$archive'");
  }

  // Did the caller provide a ZIP archive to generate a URL for?
  if (!isset($archive))
  {
    error_log("[$logname] No ZIP Archive requested.  Exiting.");
    $result['reason'] = "No ZIP archive specified.";
    $result['valid'] = false;
    echo json_encode($result);
    return;
  }

  if(!isset($lname))
  {
    error_log("[$logname] No Lastname provided.  Exiting.");
    $result['reason'] = "No Lastname specified.";
    $result['valid'] = false;
    echo json_encode($result);
    return;
  }

  if(!isset($member))
  {
    error_log("[$logname] No Member Number provided.  Exiting.");
    $result['reason'] = "No Member Number specified.";
    $result['valid'] = false;
    echo json_encode($result);
    return;
  }


  // Is the PIN valid?  Compare with SESSION stored PIN.
  if (!isset($pin))
  {
    error_log("[$logname] No PIN provided.  Exiting.");
    $result['reason'] = "No PIN specified.";
    $result['valid'] = false;
    echo json_encode($result);
    return;
  }
  else
  {
    // Revalidate the PIN
    $validationData = validateMember($member,$pin,$lname);

    if(is_array($validationData))
    {
      if($validationData['valid'] == 0)
      {
        $result['valid'] = false;
        $result['reason'] = "Unable to revalidate PIN";
        echo json_encode($result);
        return;
      }
    }
    else
    {
      if($validationData == 0)
      {
        $result['valid'] = false;
        $result['reason'] = "Unable to revalidate PIN";
        echo json_encode($result);
        return;
      }
    }
  }

    // Replace the TTL if a specific PIN requires it.
    if (isset($extended[$pin])) {
        $TTL = $extended[$pin];
        error_log("[$logname] PIN: $pin Resetting TTL: $TTL.");
    }

    // Crank out the expiring URL
  $url =  el_s3_getTemporaryLink($ARCHIVE_KEY, $SECRET_KEY, $BUCKET_NAME,
                   $archive, $TTL);

  $result['url'] = $url;
  $result['valid'] = true;
  echo json_encode($result);
  return;
?>
