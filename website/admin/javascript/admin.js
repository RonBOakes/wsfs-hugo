/* Library of JavaScript (ECMAscript) functions used for the Hugo Award system administrtive back-end.
 * Written by Ronald B. Oakes,
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
  Refresh the menu by returning to the top level index page (index.php)
*/
function refreshMenu()
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "index.php";

    topMenu.submit();
}

/**
  Go to the manual nomination page.
*/
function manualNominate()
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "manualNominate.php";

    topMenu.submit();
}

/**
  Go to the manual voting page.
*/
function manualVote()
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "manualVote.php";

    topMenu.submit();
}

/**
  Go to the ballot count page
*/
function ballotCount()
{
    var topMenu = document.topMenu;

    // change the target URL

    topMenu.action = "ballotCount.php";

    topMenu.submit();
}

/**
  Go to the cross-category report page
  @warning The nomination normalization feature has not been used since Chicon 7, if then.
*/
function crossCategory()
{
    var topMenu = document.topMenu;

    // change the target URL

    topMenu.action = "crossCategoryReport.php";

    topMenu.submit();
}

/**
  Go to the page for managing the Hugo Award categories.
*/
function viewEditCategories()
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "viewEditCategories.php";

    topMenu.submit();
}

/**
  Go to the nominee maintaince page
  @warning The nomination normalization feature has not been used since Chicon 7, if then.
  @warning The corresponding PHP file is not currently present.
*/
function viewEditNominee()
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "viewEditNominee.php";

    topMenu.submit();
}

/**
  Go to the nominee report page
  @warning The nomination normalization feature has not been used since Chicon 7, if then.
  @warning The corresponding PHP file is not currently present.
*/
function nomineeReport()
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "nomineeReport.php";

    topMenu.submit();
}

/**
  Go to the page for regeneration of nominations.
  @warning The nomination normalization feature has not been used since Chicon 7, if then.
*/
function regenerateNominees()
{
    var topMenu = document.topMenu;

    var result = confirm("This will remove any changes made to the nominees through the 'View and Edit Nominee Informaiton' Form");

    // Change the target URL
    topMenu.action = "regenerateNominees.php";

    topMenu.submit();
}

/**
  Go to the page for managing the Hugo Award Finalists.
  @param id Unused.
*/
function manageShortlist(id)
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "manageShortlist.php";
    topMenu.button_pressed.value="manageShortlist";

    topMenu.submit();
}

/**
  Go to the ballot report page
*/
function ballotReport()
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "ballotReport.php";
    topMenu.button_pressed.value="ballotReport";

    topMenu.submit();
}

/**
  Go to the Packet Download Report page.
*/
function packetDownloadReport()
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "packetDownloadReport.php";
    topMenu.button_pressed.value="packetDownloadReport";

    topMenu.submit();
}

/**
  Go to the Voter Count Repor page
*/
function voterCountReport()
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "voterCountReport.php";
    topMenu.button_pressed.value="voterCountReport";

    topMenu.submit();
}

/**
  Go to the Voting Report page
*/
function votingReport()
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "voteReport.php";
    topMenu.button_pressed.value="voteReport";

    topMenu.submit();
}

/**
  Go to the Voting (Ballot) Export page 
*/
function votingExport()
{
    var topMenu = document.topMenu;

    // Change the target URL
    topMenu.action = "ballotExport.php";
    topMenu.button_pressed.value="ballotExport";

    topMenu.submit();
}

function updateBallotOrder()
{
    var categoryMenu = document.editCategories;
    categoryMenu.button_pressed.value = "Update Ballot Order";

    categoryMenu.submit();
}

function emailReminder(nominatorId)
{
    var membershipMenu = document.membershipData;
    membershipMenu.button_pressed.value = "email_reminder";
    membershipMenu.reminder_id.value = nominatorId;

    membershipMenu.submit();
}

function emailVoteReminder(nominatorId)
{
    var membershipMenu = document.membershipData;
    membershipMenu.button_pressed.value = "email_reminder";
    membershipMenu.reminder_id.value = nominatorId;

    membershipMenu.submit();
}

function emailScalzi(nominatorId)
{
    var membershipMenu = document.membershipData;
    membershipMenu.button_pressed.value = "email_scalzi";
    membershipMenu.reminder_id.value = nominatorId;

    membershipMenu.submit();
}

function reviewBallot(id)
{
    var ballotReview = document.ballotReview;
    ballotReview.button_pressed.value = "Review Ballot";
    ballotReview.review_id.value      = id;

    ballotReview.submit();
}

function verifyBallot(id)
{
    var ballotVerify = document.ballotVerify;
    ballotVerify.button_pressed.value = "Validate Ballot";
    ballotVerify.review_id.value      = id;

    ballotVerify.submit();
}

function navigate(start)
{
    var navMenu = document.navMenu;
    navMenu.button_pressed.value = "Navigate";
    navMenu.count.value = start;

    navMenu.submit();
}

function shortlistDelete(id)
{
    var shortlistData = document.shortlistData;
    shortlistData.button_pressed.value = "Delete";
    shortlistData.shortlist_id.value = id;

    var result = confirm("Delete the selecting nominee from the shortlist?  Cannot be undone!");

    if(result==true)
    {
        shortlistData.submit();
    }
}

function editCategory(categoryId)
{
    var url = "categoryDetail.php?id=" + categoryId;
    popup(url, '', 800, 800);
}

function shortlistEdit(shortlistId,wsfsRetro)
{
    var url = "shortlistDetail.php?id=" + shortlistId + "&wsfs_retro=" + wsfsRetro;
    popup(url, '', 800, 800);
}

function shortlistAdd(categoryId,wsfsRetro)
{
    var url = "shortlistDetail.php?id=-1&categoryId=" + categoryId + "&wsfs_retro=" + wsfsRetro;
    popup(url, '', 800, 800);
}

function membershipDetail(nominatorId)
{
    var url = "membershipDetail.php?id=" + nominatorId;
    popup(url, '', 800, 800);
}

function chiconMembershipDetail(nominatorId)
{
    var url = "chiconMembershipDetails.php?id=" + nominatorId;
    popup(url, '', 800, 800);
}

function updateNomineeCategory()
{
    var categories = document.categories;
    categories.submit();
}

function updateMembershipInitial()
{
    var initials = document.initials;
    initials.submit();
}

function popup(mylink, windowname, width, height)
{
    if (!window.focus)
    {
        return true;
    }

    var href;
    if (typeof (mylink) == 'string')
    {
        href = mylink;
    }
    else
    {
        href = mylink.href;
    }

    window.open(href, windowname, 'width=' + width + ',height=' + height
                + ',scrollbars=yes');

    return false;
}
