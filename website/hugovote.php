<?PHP
/*
 * Build the Hugo Award Voting form.
 * Copyright (C) 2014-2024 Ronald B. Oakes
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

require_once('database.php');

  function getHugoVoteHeader($formAction='', $wsfs_retro = false, $privlidge=false)
  {
    if($formAction == '')
    {
      $formAction = $_SERVER['PHP_SELF'];
    }

    print("<!-- \$wsfs_retro = $wsfs_retro -->\n");

    $db = new Database($wsfs_retro);

    $categoryInfo = $db->getCategoryInfo();

    $htmlData = <<<EOT
    <script src="//ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    <script type="text/JavaScript">

        // Nominee name map (initialized in .ready() method)
        // Maps category => Literal ballot name (e.g. "2312 by Kim Stanley Robinson")
        var nominee_map = [];

        // Category List
        var category_list = [

EOT;
        $comma = false;
        foreach($categoryInfo as $id => $data)
        {
          if($data['shortlist_count'] <= 1)  // 1 entry is No Award
          {
            continue;
          }
          if($comma)
          {
            $htmlData .= ",\n         ";
          }
          else
          {
            $htmlData .= "\n        ";
            $comma = true;
          }
          $htmlData .= "'$id'";
        }

        $htmlData .= <<<EOT
            ];

        // Category map
        // Maps category => Expanded category
        var category_map = {

EOT;
        $comma = false;
        foreach($categoryInfo as $id => $data)
        {
          if($data['shortlist_count'] <= 1)  // 1 entry is No Award
          {
            continue;
          }
          if($comma)
          {
            $htmlData .= ",\n        ";
          }
          else
          {
            $htmlData .= "\n        ";
            $comma = true;
          }

          $newName = preg_replace('/\\s+/','_',$data['name']);
          $newName = preg_replace('/\(/','_',$newName);
          $newName = preg_replace('/\./','_',$newName);
          $newName = preg_replace('/\)/','_', $newName);
          $htmlData .= "'$id' : '".$newName."'";
        }

        $htmlData .= <<<EOT
        };

        var category_responses_map = {};

        // Popup function
        function displayAlertMessage(message) {
            var timeOut = 10;
            jQuery('.messageBox').text(message).fadeIn('slow').css("display", "block");
            setTimeout(function() {
                    jQuery('.messageBox').fadeOut('slow').css("display", "none");
                }, timeOut * 1000);
        }

        function displayPINMessage(message) {
            var timeOut = 30;
            jQuery('.PINStatusBox').text(message).fadeIn('slow').css("display", "block");
            setTimeout(function() {
                    jQuery('.PINStatusBox').fadeOut('slow').css("display", "none");
                }, timeOut * 1000);
        }

        var pinFlag = false;
        var lastFlag = false;
        var firstFlag = false;

        // Enable login button
        function enableLogin() {
            $('#login').removeAttr('disabled');
            $('#email').removeAttr('disabled');
            pinFlag = false;
            lastFlag = false;
            firstFlag = false;
        }

        function buildCategorySection(category, ajaxResponse) {
            var tableElement = $("table.category." + category);
            tableElement.find("tr.templateRow").remove();
            var rowTemplate = $("table.template.choices").find("tr");
            for (var i = 0; i < ajaxResponse.nominations.length; i++) {
                tableElement.append(rowTemplate.clone());
                var j = parseInt(ajaxResponse.order[i]);
                var row = tableElement.find("tr.newRow");
                row.find("td.label").html(ajaxResponse.nominations[j]);
                var selectBox = row.find("select");
                selectBox.attr("name", category+'_'+j);
                selectBox.addClass(category);
                selectBox.append("<option value=\"\"><\/option>");
                for (var optionsCount = 1; optionsCount <= ajaxResponse.nominations.length; optionsCount++) {
                    selectBox.append("<option class=\"option" + optionsCount + "\" value=\"" + optionsCount + "\">" + optionsCount + "<\/option>");
                }
                row.removeClass("newRow");
            }
            tableElement.append("<tr><td colspan=\"2\"><input class=\"reset\" type=\"button\" value=\"Restore Rankings\" /><input class=\"clear\" type=\"button\" value=\"Clear Rankings\" /><input type=\"button\" disabled=\"disabled\" class=\"vote\" name=\"vote\" value=\"Save All Changes\"><div class=\"messageBox\"><\/div><\/td><\/tr>");

            tableElement.find("select." + category).on("change", function() {
                updateOptions(category);
            });

            tableElement.find("input.reset").on("click", function() {
                resetChoices(category, tableElement, ajaxResponse);
            });

            tableElement.find("input.clear").on("click", function() {
                clearChoices(tableElement);
            });

            tableElement.find("input.vote").on("click", function() {
                saveChoices(tableElement, ajaxResponse);
            });

            // initialization
            for (var j = 0; j < ajaxResponse.votes.length; j++) {
                $(tableElement.find("select")[j]).val(ajaxResponse.votes[j]);
            }
            updateOptions(category);
            category_responses_map[category] = ajaxResponse;
        }

        function saveChoices(tableElement, ajaxResponse) {
            var \$pin  = $('input#hg_pin').val();        // Capture the PIN
            for (var i = 0; i < category_list.length; i++) {
              var category = category_list[i];
                var votes = gatherBallotData(category);

EOT;
       $htmlData .= "                var \$url3 = '".WSFS_HUGO_FILE_URL."/hugo-set-ballot.php';\n";
       $htmlData .= <<<EOT
                var \$data3 = "votes=" + JSON.stringify(votes)
                           + "&pin=" + \$pin
                           + "&category=" + category

EOT;
        $htmlData .= "                           + \"&wsfs_retro=".($wsfs_retro?'1':'0')."\";\n";
        $htmlData .= <<<EOT
                var set_ballot_callback = function(response) {
                    if (response.valid == false) {
                        alert('Internal error: failed to update ballot.');
                    } else {
                      displayAlertMessage('Your ballot update is saved.');
                    }
                }
                category_responses_map[category_map[category]].votes = votes;

                // Post the request using AJAX call to get ballot service
                $.post(\$url3, \$data3, set_ballot_callback, 'json');
            }
             var \$pin  = $('input#hg_pin').val();        // Capture the PIN
             var \$emailaddr = $('input#email_address').val();

EOT;
    $htmlData .= "             var \$url4 = '".WSFS_HUGO_FILE_URL."/hugo-mail-ballot.php';\n";

    $htmlData .= <<<EOT
              var \$data4 = "pin=" + \$pin +
                            "&email_address=" + \$emailaddr

EOT;
        $htmlData .= "                            + \"&wsfs_retro=".$wsfs_retro."\";\n";
        $htmlData .= "                            + \"&privlidge=" . $privlidge."\";\n";
        $htmlData .= <<<EOT
              var set_email_callback = function(response) {
                  if (response.valid == false) {
                      alert('Internal error: failed to email ballot.');
                  }
              }
              // Post the request using AJAX call to get ballot service
              $.post(\$url4, \$data4, set_email_callback, 'json');
        }

        function clearChoices(tableElement) {
            tableElement.find("option").removeAttr("disabled");
            tableElement.find("select").val("");
        }

        function resetChoices(category, tableElement, ajaxResponse) {
            clearChoices(tableElement);
            for (var k = 0; k < ajaxResponse.votes.length; k++) {
                $(tableElement.find("select")[k]).val(ajaxResponse.votes[k]);
            }
            updateOptions(category);
        }

        function updateOptions(category) {
            var selectBoxes = $("select." + category);
            selectBoxes.find("option").removeAttr("disabled");
            selectBoxes.each(function(index) {
                var selectedValue = $(this).val();
                if (selectedValue === "") {
                    // do nothing?
                } else {
                    selectBoxes.find("option.option" + selectedValue).attr("disabled", "true");
                    $(this).find("option.option" + selectedValue).removeAttr("disabled");
                }
                // Togle the SaveAllChanges button as a change has occurred
                $('input.vote').removeAttr("disabled");
            });
        }

        function gatherBallotData(category) {
            var selectBoxes = $("select." + category_map[category]);
            var votes = [];
            selectBoxes.each(function(index) {
                var selectedValue = $(this).val();
                votes[votes.length] = selectedValue;
            });
            return votes;
        }

        jQuery(document).ready( function() {
            // Hide the ballot, and disable the Login button
            $('.alt_link').hide(); // Never made visible unless Javascript code fails to run - error, disabled, or mobile device
            $('.manual').hide();
            $('.hugo').hide();
            $('.vote').hide();
            $('#login').attr('disabled', 'disabled');
            $('#email').attr('disabled', 'disabled');

            // Download the Nominee database
            var nominee_callback = function (response) {
                if (response.valid == true) {
                    nominee_map = response.nominees;
                }
                else {
                    alert("Failed to download the Nominee database.  Please try again later.");
                    return false;   // TODO: Redirect to error page
                }
            }
            // Send the authentication request via HTTP POST
            var \$yearData = "wsfs_retro=$wsfs_retro";

EOT;
    $htmlData .= "          $.post('".WSFS_HUGO_FILE_URL."/hugo-get-nom-list.php', \$yearData, nominee_callback, 'json');\n";

    $htmlData .= <<<EOT
          // Print Ballot feature button handler
            $('#print').click(function() {
                displayPINMessage('You have requested a printout of your ballot.');
            });

            // E-mail Ballot feature button handler
            $('#email').click(function() {
                var \$pin  = $('input#hg_pin').val();        // Capture the PIN
                var \$emailaddr = $('input#email_address').val();

EOT;
    $htmlData .= "                var \$url4 = '".WSFS_HUGO_FILE_URL."/hugo-mail-ballot.php';\n";

    $htmlData .= <<<EOT
                var \$data4 = "pin=" + \$pin +
                              "&email_address=" + \$emailaddr

EOT;
        $htmlData .= "                           + \"&wsfs_retro=".$wsfs_retro."\";\n";
        $htmlData .= <<<EOT
                var set_email_callback = function(response) {
                    if (response.valid == false) {
                        alert('Internal error: failed to email ballot.');
                    }
                    else {
                         alert('Your ballot has been sent to the email address we have on record');
                    }
                }
                // Post the request using AJAX call to get ballot service
                $.post(\$url4, \$data4, set_email_callback, 'json');
            });

            // Recover PIN feature button.
            $('#getpin').click(function(event) {
                event.preventDefault();

                var \$data = $('form#authform').serialize(); // Prepare POST data
                var \$url  = 'https://worldcon76.org/hugo/hugo-get-pin.php';

                var callback = function(response) {
                    if (response.PINstatus == 'valid') {
                        displayPINMessage('Your PIN has been emailed to the address attached to your Membership.');
                    } else {
                        displayPINMessage('Your PIN was not found. ' + response.reason +
                        ' Please send an email to pin@detcon1.org to request your PIN.');
                    }
                }
                // Send the authentication request via HTTP POST.
                $.post(\$url, \$data, callback, 'json');
            });

            // Verify the user's credentials, and if they are good, reveal the manual section
            $('#login').click(function(event) {
                // Stop the default behavior of the button.
                event.preventDefault();

                var \$pin  = $('input#hg_pin').val();        // Capture the PIN
                var \$data = $('form#authform').serialize(); // Prepare POST data

EOT;
    $htmlData .= "                var \$url  = '".WSFS_HUGO_FILE_URL."/hugo-validate-pin.php';\n";

    $htmlData .= <<<EOT
                // Define the POST callback function.  It processes the returned JSON assoc. array
                var validate_pin_callback = function(response) {
                    if (response.PINstatus == "valid") {
                        // Save the email address if present and activate the email button
                        if(response.email != "n/a")
                        {
                          var newInput = document.createElement("input");
                          newInput.setAttribute("type","hidden");
                          newInput.setAttribute("name","email_address");
                          newInput.setAttribute("id","email_address");
                          newInput.setAttribute("value", response.email);
                          // Append to the form
                          var myForm = document.getElementById("authform");
                          myForm.appendChild(newInput);
                        }
                        else
                        {
                          $('#email').attr('disabled','disabled');
                        }
                        // Populate the ballot with the member's custom ballot order
                        for (var i = 0; i < category_list.length; i++) {


EOT;
    $htmlData .= "                            var \$url2 = '".WSFS_HUGO_FILE_URL."/hugo-get-ballot.php?wsfs_retro=".$wsfs_retro."';\n";
    $htmlData .= <<<EOT
                            var \$data2 = "category="+category_list[i]+"&pin="+\$pin+"&wsfs_retro=$wsfs_retro";
                            var get_ballot_callback = function(response) {
                                if (response.valid == true) {
                                buildCategorySection(category_map[response.category],
                                                     { votes: response.votes,
                                                       order: response.order,
                                                       nominations: nominee_map[response.category]});
                                } else {
                                    alert('Category '+response.category+' error.');
                                }
                            }

                            // Post the request using AJAX call to get ballot service
                            $.post(\$url2, \$data2, get_ballot_callback, 'json');
                        }

                        // Reveal the manual and scroll it up to the top of the page
                        $('.manual').show();
                        $('html, body').animate({
                                scrollTop: $('form#hugo_manual').offset().top
                            }, 2000);

                    } else {
                        $('#email').attr('disabled','disabled');
                        alert("Your PIN is invalid.  Please try again.\\nIf this message repeats, send an email to hugopin&#64;worldcon76.org to request your PIN (please include your membership name).");
                    }
                }

                // Send the authentication request via HTTP POST
                $.post(\$url, \$data, validate_pin_callback, 'json');
                //callback({PINstatus: "valid"});
            });

            $('#manual').click(function(event) {
                event.preventDefault();

                // Reveal the ballot
                $('.hugo').show();
                $('.vote').show();

                // Scroll to the actual ballot
                $('html, body').animate({
                        scrollTop: $('form#hugo_ballot').offset().top
                    }, 2000);

            });

            // Enable login button after the user has provided a first and last name, and PIN
            // Enable RecoverPIN button after the user has provided a first and last name

            $('#hg_first_name').change(function() {
                firstFlag = true;
                if (lastFlag && pinFlag) {
                    enableLogin();
                }
            });

            $('#hg_first_name').focus(function() {
                firstFlag = true;
                if (lastFlag && pinFlag) {
                    enableLogin();
                }
            });

            $('#hg_last_name').change(function() {
                lastFlag  = true;
                if (firstFlag && pinFlag) {
                    enableLogin();
                }
            });

            $('#hg_last_name').focus(function() {
                lastFlag  = true;
                if (firstFlag && pinFlag) {
                    enableLogin();
                }
            });

            $('#hg_pin').change(function() {
                pinFlag = true;
                if (firstFlag && lastFlag) {
                    enableLogin();
                }
            });

            $('#hg_pin').focus(function() {
                pinFlag = true;
                if (firstFlag && lastFlag) {
                    enableLogin();
                }
            });
        });
    </script>
EOT;
    $htmlData .= '    <style type="text/css">'."\n";
    $htmlData .= '        @import "'.WSFS_HUGO_FILE_URL.'/jQuery.css"'."\n";
    $htmlData .= '    </style>'."\n";

    return $htmlData;
  }

  function getAltHugoVoteForm($formAction='', $wsfs_retro, $privlidge=false)
  {
    return "<p>Alt form not available at this point.  Try later</p>";
  }

  function getHugoVoteForm($formAction='', $wsfs_retro = false, $privlidge=false)
  {
    global $_POST;

    if(isset($_POST['WSFS_Hugo_Use_Alt']))
    {
      return getAltHugoVoteForm($formAction, $wsfs_retro, $privlidge);
    }

    $pageData = '';

    if($formAction == '')
    {
      $formAction = $_SERVER['PHP_SELF'];
    }

//    if(isset($_SESSION['year']))
//    {
//      $year = $_SESSION['year'];
//    }
//    else
//    {
//      $_SESSION['year'] = $year;
//    }

    $db = new Database($wsfs_retro);

    $votingStatus = $db->getVotingStatus();

    if (!$privlidge)
    {
      if($votingStatus == 'Preview')
      {
        $pageData .= '<p><b><em><span style="color:red">Hugo Award Voting is in preview and testing mode.  Responses entered will not be counted in the final balloting</span></em></b></p>'."\n";
      }
      else if($votingStatus == 'BeforeOpen')
      {
        $pageData .= '<p><b>Hugo Award Voting is not yet open.</b></p>'."\n";
        return $pageData;
      }
      else if ($votingStatus == 'Closed')
      {
        $pageData .= '<p><b>Hugo Award Voting has now closed.</b></p>'."\n";
        return $pageData;
      }
    }

    $pageData .= <<<EOT
      <table class="template choices" id="choicesTemplate">
          <tr class="newRow templateRow">
              <td width="60px" class="choices">
                  <select>
                  </select>
              </td>

              <td class="label">
              </td>
          </tr>
      </table>
EOT;
    if(!$wsfs_retro)
    {
      $pageData .= '<h1 class="title">Ballot for the 2018 Hugo Award</h1>'."\n";
    }
    else // Assume 1939
    {
      $pageData .= '<h1 class="title">Ballot for the 1943 Retrospective Hugo Awards</h1>'."\n";
    }

    $pageData .= '      <!-- <form id="get_alt_form" action="'.WSFS_HUGO_VOTE_URL.'" method="post">'."\n";

    $pageData .= <<<EOT
        <fieldset class="alt_link">
          <input type="hidden" name="WSFS_Hugo_Use_Alt" value="1" />
          <p>If you are seeing this section, your browser is not support Javascript (or there is a temporary error in the Javascript code).</p>
          <p>To use an alternate version of the Hugo Award ballot, press this button: <input type="submit" value="Load Alternate Form" /></p>
        </fieldset>
      </form> -->

EOT;
    $pageData .= '      <form id="authform" action="'.WSFS_HUGO_FILE_URL.'/hugo-get-ballot-data.php" method="post">'."\n";

    $pageData .= <<<EOT
            <fieldset class="authenticate">
              <legend><strong>Voter Authentication</strong> - Verify Your Membership and Access Your Ballot</legend>

              <table>
                  <tr>
                      <td>
                          <table>
                              <tr>
                                  <td><label for="hg_first_name">First Name <input tabindex="1" type="text" id="hg_first_name" name="hg_first_name" width="50"></label></td>
                              </tr>
                              <tr>
                                  <td><label for="hg_last_name">Last Name <input tabindex="2" type="text" id="hg_last_name" name="hg_last_name" width="50"></label></td>
                              </tr>
                              <tr>
                                  <td><label for="hg_membership">Worldcon 76 Membership <input tabindex="3" type="text" id="hg_membership" name="hg_membership"></label></td>
                              </tr>
                              <tr>
                                  <td><label for="hg_pin">Worldcon 76 PIN <input tabindex="4" type="text" id="hg_pin" name="hg_pin"></label></td>
                              </tr>
                          </table>
                      </td>

                      <td>
                          <table>
                              <tr>
                                  <td class="instructions">
                                      <p><strong>Begin Your Balloting Here:</strong> Enter your name as it appears on your membership
                                          and enter your private Worldcon 76 PIN to authenticate your ballot.</p>

                                      <p><strong>You must have a valid PIN to vote</strong>.  If you have recently joined Worldcon 76 you will
                                         be sent a membership number and PIN shortly after joining. If your name or membership number and PIN
                                         fail to authenticate, you can use the the <a href="https://www.worldcon76.org/hugo/pin_lookup.php">
                                         PIN lookup page</a> to request your PIN.
                                         Your PIN will be e-mailed to you using the e-mail address you entered when you registered
                                         with Worldcon 76.</p>

                                    <p>Your membership number is a 1 to 5 digit number.</p>

                                    <p>Your PIN is a 13 character code consisting of the letters &quot;SJ&quot; followed by 11 numerical
                                       digits.  This is a private number and is only communicated directly to the member.</p>

                                      <p>You can submit a partial ballot and update/edit/complete the ballot at a later date by returning
                                         to this page and re-authenticating. Your previous selections will be reloaded into the form, which
                                         you can then alter, delete or leave as is.</p>

                                      <!-- <p>If you would like a copy of your ballot for your records, you can request this at any time
                                         using the 'Email Ballot' button. A copy of your ballot will then be immediately mailed to
                                         you at the email address recorded for you in our membership database.</p> -->

                                      <p>This Ballot must be received by Tuesday July 31, 2018, 11:59 PM PDT (Wednesday August 1, 2018, 2:59 AM EDT; 6:59 AM UTC/GMT; 5:59 PM AEST)</p>

                                      <p><strong>Note:</strong>&nbsp;This online form requires JavaScript enabled and if you are using
                                         IE, version 6 or newer.  This online form has not been tested with mobile browsers and may not work
                                         on tablets and phones.</p>
                                  </td>
                              </tr>

EOT;

    $pageData .= <<<EOT
                              <tr>
                                  <td>
                                      <input tabindex="5" type="button" name="login" id="login" value="Login"> &nbsp;
                                      <!-- <input tabindex="6" type="button" name="email" id="email" value="E-mail Ballot"> &nbsp; -->
                                      <!-- <input tabindex="7" type="button" name="print" id="print" value="Print Ballot"> &nbsp; -->
                                      <!-- <input tabindex="8" type="button" name="getpin" id="getpin" value="Recover PIN"> &nbsp; -->

                                      <div class="PINStatusBox"></div>
                                  </td>
                              </tr>
                          </table>
                      </td>
                  </tr>
              </table>
          </fieldset>
      </form>


EOT;
    $pageData .= '      <form id="hugo_manual" action="'.WSFS_HUGO_VOTE_URL.'/hugo-vote.php" method="post">'."\n";
    $pageData .= <<<EOT
        <fieldset class="manual">
              <legend><strong>Ballot Instructions</strong> - How to complete the ballot.</legend>

              <table>
                  <tr>
                      <td>
                          <p><b>The Final Ballot is a preference ballot</b>. Please rank the nominees in order of preference, starting
                              with a "<tt>1</tt>" for your first choice.</p>

                          <p><b>You do not have to set a rank for every nominee</b> You can submit a vote (i.e. set a rank) for as many
                            or as few of the nominees in a category as you want. If you are only ranking some of the nominees, please use
                            consecutive numbers starting from "<tt>1</tt>" (e.g., if you are voting for three nominees, please rank
                            them "<tt>1</tt>", "<tt>2</tt>" and "<tt>3</tt>"). Note that the drop-down buttons will prevent you from
                            selecting the same rank for two nominees in the same category.  If you want to reassign a rank to a different
                            nominee, set the rank of the nominee (that you want to reassign) to blank and then you can reassign that rank
                            to a different nominee.</p>
                          <p>The <b>Clear Rankings</b> button will blank out all ranks for a given category.  Use this to reset a
                             category's rankings to be the same as in a new ballot (this does not change your ballot unless you then
                             click on the <b>Save All Changes</b> button).</p>
                          <p>The <b>Restore Rankings</b> button will reset the ranks to the values they had when you <i>last saved the
                              ballot form</i> (by clicking on any of the <b>Save All Changes</b> buttons on the form).</p>

                          <p>Any or all of the <b>Save All Changes</b> buttons will save <i>your entire ballot</i> with the currently
                             assigned rankings and reset the <b>Restore Ranking</b> values to the same current ballot.  This is the
                             <em>only button that changes your recorded vote</em>.  You <em>MUST</em> save at least once to create a
                             counted ballot.</p>

                          <p><b>There is no need to explicitly submit your final ballot</b>.  Once you begin to fill out your ballot,
                             we keep track of your ballot throughout the voting period, and whatever the state of your ballot is as of the
                             voting cutoff (TBD) will be treated as your final submitted ballot.</p>

                          <input type="button" name="manual" id="manual" value="Begin Voting">
                      </td>
                  </tr>
              </table>
          </fieldset>
      </form>


EOT;
    $pageData .= '      <form id="hugo_ballot" action="'.WSFS_HUGO_FILE_URL.'/hugo-vote.php" method="post">'."\n";
    $pageData .= '          <input type="hidden" name="pin" id="pin" value=""> <input type="hidden" name="category" id="category" value="">'."\n";

    $categoryInfo = $db->getCategoryInfo();

    foreach($categoryInfo as $id => $categoryData)
    {
      if($categoryData['shortlist_count'] > 1) // If only one in shortlist it is "No Award"
      {
        $newName = preg_replace('/\\s+/','_',$categoryData['name']);
        $newName = preg_replace('/\(/','_',$newName);
        $newName = preg_replace('/\./','_',$newName);
        $newName = preg_replace('/\)/','_', $newName);
        $pageData .= "\n";
        $pageData .= '          <!-- '.$newName.' section -->'."\n";
        $pageData .= '          <fieldset class="hugo">'."\n";
        $pageData .= '            <legend><strong>'.$categoryData['name'].'</strong>'."\n";
        $pageData .= '            <p>'.$categoryData['description'].'</p></legend>'."\n";
        $pageData .= '            <table class="category '.$newName.'">'."\n";
        $pageData .= '              <tr>'."\n";
        $pageData .= '                <td></td>'."\n";
        $pageData .= '              </tr>'."\n";
        $pageData .= '            </table>'."\n";
        $pageData .= '          </fieldset>'."\n";
      }
    }

    $pageData .= <<<EOT
      </form>
EOT;

    return $pageData;
  }

?>
