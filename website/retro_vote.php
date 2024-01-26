<?php
/*
 * Sample PHP page for Retro Hugo Award Voting.
 * Copyright (C) 2016, 2018, 2024 Ronald B. Oakes
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
 
      define('WSFS_HUGO_VOTE_URL','https://www.worldcon76.org/hugo/retro_vote.php');
      define('WSFS_HUGO_FILE_URL','https://www.worldcon76.org/hugo');


      // Comment out the following once debuging has been completed.
//      error_reporting(E_ALL);
//      ini_set('display_errors', '1');
      // End comment out section.

      include "./hugovote.php";
      print("<!-- This is a line after the include -->\n");
?>
<HEAD>
<TITLE>Worldcon 76 Retrospective Hugo Award Voting</TITLE>
<?php
      print getHugoVoteHeader($_SERVER['PHP_SELF'],true,false);
?>
</HEAD>
<BODY>
<?php
      print getHugoVoteForm($_SERVER['PHP_SELF'],true,false);
?>
</BODY>

