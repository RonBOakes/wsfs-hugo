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

    // Get Hugo Packet File (via S3) for Category
    
    require 'aws.phar';
    
    $key = '';  # Amazon S3 key (garbled, but for example)
    $secret = ''; # Amazon S3 secret key (garbled)
    
    $s3 = new AmazonS3($key, $secret);
    
    $logname = $_SERVER['SCRIPT_NAME'];
    if ($logname) 
    {
        $logname = basename($logname, '.php');
    }
    
    if ($_SERVER['REQUEST_METHOD'] == 'GET') 
    {
        if (isset($_GET['category']))   
        { 
          $category = $_GET['category']; 
        }

        error_log("[$logname: GET] CATEGORY: $category");
    } 
    else if ($_SERVER['REQUEST_METHOD'] == 'POST') 
    {
        if (isset($_POST['category']))  
        { 
            $category = $_POST['category']; 
        }

        error_log("[$logname: POST] CATEGORY: $category");
    }
    
    // SQL Queries
    
    $select_vote_str = <<< END_OF_SELECT_PACKET
    SELECT * FROM Hugo_Packet
    WHERE `category` = ':category'
END_OF_SELECT_PACKET;

    // Set up database access over PDO.
    
    $db = new PDO('mysql:dbname=hugo_awards;host=localhost;charset=utf8', 'username', 'password');
    $db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $bucket = 'LSC3HugoPacket'; # Amazon 'bucket' where files are archived
    $filename = 'Campbell_All.zip';
    
    $file_header = $s3->get_object_headers($bucket, $filename);
    $headers = array('headers' => array('content-disposition' => $file_header['_info']['content_type']));
    $file = $s3->get_object($bucket, $filename, $headers);
    
    header('Content-type: ' . $file_header['_info']['content_type']);
    echo $file->body;
?>
