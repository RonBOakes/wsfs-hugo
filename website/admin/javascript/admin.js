/* Written by Ronald B. Oakes, copyright  2015, 2016
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/

function refreshMenu()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "index.php";

    topMenu.submit();
}

function manualNominate()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "manualNominate.php";

    topMenu.submit();
}

function manualVote()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "manualVote.php";

    topMenu.submit();
}


function gotReport()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "GameOfThronesReport.php";

    topMenu.submit();
}


function ballotCount()
{
    var topMenu = document.topMenu;

    // change the target URL

    topMenu.action = "ballotCount.php";

    topMenu.submit();
}

function crossCategory()
{
    var topMenu = document.topMenu;

    // change the target URL

    topMenu.action = "crossCategoryReport.php";

    topMenu.submit();
}

function viewEditCategories()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "viewEditCategories.php";

    topMenu.submit();
}

function viewEditMemberships()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "viewEditMemberships.php";

    topMenu.submit();
}

function viewEditChiconMemberships()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "viewEditChiconMemberships.php";

    topMenu.submit();
}

function uploadMemberships()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "uploadMemberships.php";

    topMenu.submit();
}

function uploadChiconMemberships()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "uploadVotingMemberships.php";

    topMenu.submit();
}

function generatePin()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "generateMissingPins.php";

    var result = confirm("Once Generated, PINs cannot be changed for newly uploaded data");

    if(result==true)
    {
      topMenu.submit();
    }
}

function recoverBallots(which)
{
    var topMenu = document.topMenu;

    if(which == "verified")
	{
	    topMenu.action = "recoverVerifiedPartials.php";
	}
    else if(which == "unverified")
	{
	    topMenu.action = "recoverUnverifiedPartials.php";
	}

    var result = confirm("This will submit ballots that were not approved by the submittor!");

    if(result==true)
	{
	    topMenu.submit();
	}
}

function emailScalziPin()
{
    var topMenu = document.topMenu;
    topMenu.action = "emailScaziLetter.php";
    topMenu.submit();
}

function emailPin(version)
{
    var topMenu = document.topMenu;

    // Change the target URL
    if(version == "initial")
	{
	    topMenu.action = "emailNewPins.php";
	}
    else if(version == "correction")
	{
	    topMenu.action = "emailCorrection.php";
	}
    else if(version == "firstReminder")
	{
	    topMenu.action = "email1stReminder.php";
	}
    else if(version == "secondReminder")
	{
	    topMenu.action = "email2ndReminder.php";
	}

    var result = confirm("This will send e-mail to EVERY e-mail address not previously contacted");

    if(result==true)
    {
      topMenu.submit();
    }
}

function viewEditNominee()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "viewEditNominee.php";

    topMenu.submit();
}

function nomineeReport()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "nomineeReport.php";

    topMenu.submit();
}

function reviewBallots()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "reviewBallots.php";

    topMenu.submit();
}

function verifyBallots()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "unverifiedBallots.php";

    topMenu.submit();
}

function verifyVoteBallots()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "unverifiedVotes.php";

    topMenu.submit();
}

function regenerateNominees()
{
    var topMenu = document.topMenu;

    var result = confirm("This will remove any changes made to the nominees through the 'View and Edit Nominee Informaiton' Form");

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "regenerateNominees.php";

    topMenu.submit();
}

function updatePassword()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "updatePassword.php";

    topMenu.submit();
}

function logout()
{
    var topMenu = document.topMenu;

    // Change the target URL                                                                                                                                                                                            
    topMenu.action = "login.php";
    topMenu.button_pressed.value = "logout";

    topMenu.submit();
}

function manageShortlist(id)
{
    var topMenu = document.topMenu;
    
    // Change the target URL
    topMenu.action = "manageShortlist.php";
    topMenu.button_pressed.value="manageShortlist";

    topMenu.submit();
}

function ballotReport()
{
    var topMenu = document.topMenu;
    
    // Change the target URL
    topMenu.action = "ballotReport.php";
    topMenu.button_pressed.value="ballotReport";

    topMenu.submit();
}

function packetDownloadReport()
{
    var topMenu = document.topMenu;
    
    // Change the target URL
    topMenu.action = "packetDownloadReport.php";
    topMenu.button_pressed.value="packetDownloadReport";

    topMenu.submit();
}

function voterCountReport()
{
    var topMenu = document.topMenu;
    
    // Change the target URL
    topMenu.action = "voterCountReport.php";
    topMenu.button_pressed.value="voterCountReport";

    topMenu.submit();
}


function votingReport()
{
    var topMenu = document.topMenu;
    
    // Change the target URL
    topMenu.action = "voteReport.php";
    topMenu.button_pressed.value="voteReport";

    topMenu.submit();
}

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

function nomineeDetail(nomineeId)
{
    var url = "nomineeDetail.php?id=" + nomineeId;
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
