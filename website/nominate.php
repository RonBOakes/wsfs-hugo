<?php
/*
 * Sample PHP page for a Hugo Award Nomination page.
 * Copyright (C) 2016-2024 Ronald B. Oakes
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
 * Sample PHP page for a Hugo Award Nomination page.
 * This shows how to include and set up a basic, bare-bones, web page containing the Hugo Awards nomination form.
 */
define ( 'WSFS_HUGO_VOTE_URL', 'http://mac2-hugo01.midamericon2.org/nominate.php' );

// Comment out the following once debuging has been completed.
// error_reporting(E_ALL);
// ini_set('display_errors', '1');
// End comment out section.

include "./hugonom.php";
print ("<!-- This is a line after the include -->\n") ;
print getHugoNomForm ( $_SERVER ['PHP_SELF'], false, false );
?>

