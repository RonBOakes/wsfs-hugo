<?PHP
/*
 * Written by Ronald B. Oakes
 * Copyright (C) 2015-2024.
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
 * Pull up the form to allow the administrator to enter Hugo Award votes.
 */
define ( 'WSFS_HUGO_VOTE_URL', 'https://www.worldcon76.org/hugo/admin/manualVote.php' );
define ( 'WSFS_HUGO_FILE_URL', 'https://www.worldcon76.org/hugo' );

chdir ( '..' );
include ('./hugovote.php');
chdir ( './admin/' );

?>
<HTML>
<HEAD>
<TITLE>Hugo Nomination Administration</TITLE>
<!-- TODO: Rest of HEAD code -->
    <?PHP print(getHugoVoteHeader($_SERVER['PHP_SELF'],false,true)); ?>
  </HEAD>
<BODY>
	<!-- $_POST:
    <?PHP var_dump($_POST); ?>
    -->
	<BR />
    <?PHP print(getHugoVoteForm($_SERVER['PHP_SELF'],false,true)); ?>
  </BODY>
</HTML>
