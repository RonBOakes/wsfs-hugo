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
/*
 * Portions Copyright (C) 2014-2024 Ronald B. Oakes
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

     require_once('./database.php');
     require_once('./memberValidator.php');

     session_start();

    // Lookup the Nominees for a given category and the member's vote
    // and return them in a JSON package with the Noms randomized by PIN.

    $result = array();
    $nominees = array();
    $row_array = array();

    // Need these to be global in context
    $pin = '';
    $category = '';

    $wsfs_retro = false;

    if ($_SERVER['REQUEST_METHOD'] == 'GET')
    {
        if (isset($_GET['wsfs_retro']))
        {
          $wsfs_retro = $_GET['wsfs_retro'] == 1;
        }
    }
    else if ($_SERVER['REQUEST_METHOD'] == 'POST')
    {
        {
          $wsfs_retro = $_POST['wsfs_retro'] == 1;
        }
    }


    header('Content-Type: application/json');

    $logname = $_SERVER['SCRIPT_NAME'];
    if ($logname) {
        $logname = basename($logname, '.php');
    }

    // SQL Queries

    $db = new database($wsfs_retro);

    // Get data out of Ron's database and put into Steve's internal structure:
    $fullShortlist = $db->getFullShortlist();
    $categoryInfo = $db->getCategoryInfo();

    foreach($fullShortlist as $categoryId => $categoryData)
    {
      if($categoryInfo[$categoryId]['shortlist_count'] <= 1)
      {
        continue;
      }
      foreach($categoryData as $shortlistId => $sortName)
      {
          $shortListInfo = $db->getShortlistInfo($shortlistId);

          $html = $shortListInfo['datum_1'];

          if(strcmp($html,'No Award') != 0)
          {
            if($categoryInfo[$categoryId]['datum_2_description'] != '')
            {
              $html .= ' ' . $shortListInfo['datum_2'];
            }
            if($categoryInfo[$categoryId]['datum_3_description'] != '')
            {
              $html .= ' (' . $shortListInfo['datum_3'] .')';
            }
          }

          if (!isset($nominees[$categoryId])) {
              $nominees[$categoryId] = array();
          }

          array_push($nominees[$categoryId], $html);
      }
    }

    $result['nominees'] = $nominees;
    $result['valid'] = true;
    echo json_encode($result);
    return;
?>
