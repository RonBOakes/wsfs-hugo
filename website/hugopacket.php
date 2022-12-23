<?PHP
/* Written by Ronald B. Oakes, copyright  2015-2022
   Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
   For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
   All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
*/
/**
 * Source for building the Hugo Voters Packet download page.
 */
require_once('database.php');

/**
 * Gets the header for the Hugo Voters Packet page.  This header contains the JavaScript code and other 
 * information that needs to be embedded into the HTTP/HTTPS HTML page header.
 * @param string $formAction The form action for the web page, that is the URL for the page.  Optional
 * @param boolean $wsfs_retro true if this is for Retro Hugo Awards, false otherwise.  Optional
 * @param boolean $privlidge true to ignore the open and close dates, false otherwise.  Optional
 * @return string
 */
  function getHugoPacketHeader($formAction='', $wsfs_retro = false, $privlidge=false)
  {
    if($formAction == '')
    {
      $formAction = $_SERVER['PHP_SELF'];
    }

    $db = new Database($wsfs_retro);

    $categoryInfo = $db->getCategoryInfo();

    $htmlData = <<<EOT
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
    </script>
EOT;
    $htmlData .= '    <script type="text/javaScript" src="'.WSFS_HUGO_FILE_URL.'jQuery/jquery.fileDownload.js">'."\n";
    $htmlData .= '    </script>'."\n";
    $htmlData .= '    <script type="text/JavaScript" src="'.WSFS_HUGO_FILE_URL.'jQuery/countdown/jquery.countdown.js">'."\n";
    $htmlData .= '    </script>'."\n";
    $htmlData .= <<<EOT
    <script type="text/JavaScript">

        function displayPINMessage(message)
        {
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
        function enableLogin()
        {
            $('#login').removeAttr('disabled');
            $('#email').removeAttr('disabled');
            pinFlag = false;
            lastFlag = false;
            firstFlag = false;
        }

        var \$iDownFrame;    // Hidden iFrame for downloads

        // Download the Expiry URL

        function downloadURL(url)
        {
            if (\$iDownFrame)
            {
                \$iDownFrame.attr('src', url);
            }
            else
            {
                \$iDownFrame = $('<iframe>', { id: 'iDownFrame', src: url }).hide().appendTo('body');
            }
        }

        jQuery(document).ready(function() {
            // Hide the category downlaod form.
            $('.terms').hide();
            $('.hugo').hide();
            $('.download').hide();

            $('#login').attr('disabled', 'disabled');
            $('#email').attr('disabled', 'disabled');

            // Capture the download link actions, sending the PIN and URL to an
            // AJAX PHP script that logs the download.

            $("input.Download").click(function(event){
                var \$archive = $(this).attr('id');              // File URL tail
                var \$pin     = $('input#hg_pin').val();         // Capture the PIN
                var \$member  = $('input#hg_membership').val();   // Membership Number
                var \$lname   = $('input#hg_last_name').val();    // Last name

EOT;
    $htmlData .= "                var \$url     = '".WSFS_HUGO_FILE_URL."/generate-expiry-url.php'; // Amazon URL generator\n";
    $htmlData .= "                var \$ipAddr    = '". $_SERVER['REMOTE_ADDR'] . "'; // IP Address that loaded this page\n";
    $htmlData .= "                var \$userAgent = '".$_SERVER['HTTP_USER_AGENT'] . "'; // User Agent (browser) used to load this page\n";
    $htmlData .= <<<EOT
                var \$data    = "archive="+\$archive+"&pin="+\$pin+"&member="+\$member+"&lname="+\$lname;
                var callback = function(response) {
                    if (response.valid) {
                        downloadURL(response.url);

EOT;

    $htmlData .= "                        var \$url2  = '".WSFS_HUGO_FILE_URL."/hugo-log-packet-download.php';\n";
    $htmlData .= <<<EOT
                        var \$data2 = "archive="+\$archive+"&pin="+\$pin+"&ipAddr="+\$ipAddr+"&userAgent="+\$userAgent;
                        var callback2 = function(response) { };
                        // Log the packet archive download.
                        $.post(\$url2, \$data2, callback2, 'json');
                    }
                }

                // Send the authentication request via HTTP POST.
                $.post(\$url, \$data, callback, 'json');

                // Now go download the link (default function).
            });

            // GetPIN: Send the member their PIN based on looking up the email associated with name/memberID.

            $('#getpin').click(function(event) {
                event.preventDefault();

                var \$data = $('form#authform').serialize(); // Prepare POST data

EOT;
    $htmlData .= "                var \$url  = '".WSFS_HUGO_FILE_URL."/hugo-get-pin.php';\n";
    $htmlData .= <<<EOT
                var callback = function(response) {
                    if (response.PINstatus == 'valid') {
                        displayPINMessage('Your PIN has been emailed to the address attached to your Membership.');
                    } else {
                        displayPINMessage('Your PIN was not found. ' + response.reason +
                        ' Please send an email to request your PIN.');
                    }
                }
                // Send the authentication request via HTTP POST.
                $.post(\$url, \$data, callback, 'json');
            });

            // Verify the user's credentials, and if they are good, enable the downloader (using AJAX calls).
            $('#login').click(function(event) {
                // Stop the default behavior of the button.
                event.preventDefault();

                var \$pin  = $('input#hg_pin').val();        // Capture the PIN
                var \$data = $('form#authform').serialize(); // Prepare POST data

EOT;

    $htmlData .= "                var \$url  = '".WSFS_HUGO_FILE_URL."/hugo-validate-pin.php';\n";
    $htmlData .= <<<EOT
                // Define the POST callback function.  It processes the returned JSON assoc. array.
                var callback = function(response) {

                    if (response.PINstatus == "valid") {

                        // Reveal the form after it's populated.
                        $('.terms').show();
                        $('html, body').animate({
                                scrollTop: $('form#termsform').offset().top
                            }, 2000);

                    } else {
                        alert("Your PIN is invalid.  Please try again.\\nIf this message repeats, send an email to hugopin&#64;worldcon76.org to request your PIN (please include your membership name).");
                    }
                }

                // Send the authentication request via HTTP POST.
                $.post(\$url, \$data, callback, 'json');

                // [DEBUG] Force the form to be active
                // callback({PINstatus: "valid"});
            });

            $('#eula').click(function() {
                if ($(this).is(":checked")) {
                   $('.hugo').show();
                   $('.download').show();
                   $('html, body').animate({
                       scrollTop: $('form#categories').offset().top
                   }, 2000);
               }
               else {
                   $('.hugo').hide();
                   $('.download').hide();
                       $('html, body').animate({
                            scrollTop: $('form#termsform').offset().top
                        }, 2000);
               }
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

            $('#select_all').click(function(event) {
                // Stop the default behavior of the button.
                event.preventDefault();

                $('.category').prop('checked', true);
            });
        });
    </script>

    <style type="text/css">
        @import "./jQuery/countdown/jquery.countdown.css";
        @import "./jQuery.css";
    </style>

EOT;

    return $htmlData;
  }

  function getHugoPacketForm($formAction='', $wsfs_retro = false, $privlidge=false)
  {
    global $_POST;

    $pageData = '';

    if($formAction == '')
    {
      $formAction = $_SERVER['PHP_SELF'];
    }

    if(isset($_SESSION['wsfs_retro']))
    {
      $wsfs_retro = $_SESSION['wsfs_retro'];
    }
    else
    {
      $_SESSION['wsfs_retro'] = $wsfs_retro;
    }

    $db = new Database($wsfs_retro);
    $votingStatus = $db->getVotingStatus();

    if($votingStatus == 'Preview')
    {
      $pageData .= '<p><b><em><span style="color:red">Hugo Award Voting is in preview and testing mode.  Responses entered will not be counted in the final balloting</span></em></b></p>'."\n";
    }
    else if($votingStatus == 'BeforeOpen')
    {
      $pageData .= '<p><b>Hugo Voters Packet download is not yet open.</b></p>'."\n";
      return $pageData;
    }
    else if ($votingStatus == 'Closed')
    {
      $pageData .= '<p><b>Hugo Award Voting has now closed.</b></p>'."\n";
      return $pageData;
    }

    $pageData .= <<<EOT
    <table class="template choices" id="choicesTemplate">
        <tr class="newRow templateRow">
            <td class="choices"></td>

            <td class="label">&nbsp;</td>
        </tr>
    </table>

    <h1 class="title">Packet Download Service for the<br>
EOT;
    if($wsfs_retro)
    {
      $pageData .= "Retrospective Hugo Awards</h1>\n";
    }
    else
    {
      $pageData .= "Hugo Awards, Award for Best Young Adult Novel, &amp; John W. Campbell Award</h1>\n";
    }

    $pageData .= <<<EOT
      <form id="authform" action="hugo-get-ballot-data.php" method="post">
          <fieldset class="authenticate">
              <legend><strong>Voter Authentication</strong> - Verify Your Membership and Access Your Packet</legend>

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
                                  <td><label for="hg_pin">Worldcon 76 PIN <input tabindex="3" type="text" id="hg_pin" name="hg_pin"></label></td>
                              </tr>
                          </table>
                      </td>

                      <td>
                          <table>
                              <tr>
                                  <td class="instructions">
                                    <p>Enter your name as it appears on your membership
                                       and enter your private Worldcon 76 PIN to authenticate your ballot.</p>

                                      <p><strong>You must have a valid PIN to vote</strong>.  If you have recently joined Worldcon 76 you will
                                         be sent a membership number and PIN shortly after joining. If your name or membership number and PIN
                                         fail to authenticate, you can use the the <a href="http://www.worldcon76.org/hugo/pin_lookup.php">
                                         PIN lookup page</a> to request your PIN.
                                         Your PIN will be e-mailed to you using the e-mail address you entered when you registered
                                         with Worldcon 76.</p>

                                    <p>Your membership number is a 1 to 5 digit number.</p>

                                    <p>Your PIN is a 13 character code consisting of the letters &quot;SJ&quot; followed by 11 numerical
                                       digits.  This is a private number and is only communicated directly to the member.</p>

                                    <p>You can download any content archive(s) you choose, and continue downloading at a later date by
                                       returning to this page and re-authenticating.</p>

                                      <p><strong>Note:</strong>&nbsp;This online form requires JavaScript enabled and if you are using
                                         IE, version 6 or newer.  This online form has not been tested with mobile browsers and may not work
                                         on tablets and phones.</p>
                                  </td>
                              </tr>

                              <tr>
                                  <td>
                                      <input tabindex="5" type="button" name="login" id="login" value="Login"> &nbsp;

                                      <div class="PINStatusBox"></div>
                                  </td>
                              </tr>
                          </table>
                      </td>
                  </tr>
              </table>
          </fieldset>
      </form>

    <form id="termsform" action="hugo-vote.php" method="post">
        <fieldset class="terms">
            <legend><strong>Terms and Conditions</strong> - Licensing Terms for the 2018 Hugo Packet</legend>

            <table>
                <tr>
                    <td>
                        <p><strong>You Agree to the Following Licensing Terms.</strong>The 2018 Hugo Packet is a
                           Digital Rights Management (DRM) free version of the copyright content provided as a courtesy
                           by the Publishers, Editors, Artists and Authors of the works nominated for a Hugo Award, Award 
                           for Best Young Adult Book, or John W. Campbell Award. It is made available to you under the 
                           following conditions:</p>

                        <ul>
                            <li>You are a member in good standing with the Worldcon 76 Convention</li>

                            <li>You agree to not redistribute the content you download from this site</li>

                            <li>You will not reveal your PIN to another individual or anonymous group</li>
                        </ul>

                        <p>The creators of these works are trusting you with their content in the hope that
                           you will be able to fairly evaluate it for the Hugo Award, Award for Best Young Adult Book,
                           and Campbell Awards. Please honor that trust and do not redistribute the content you have 
                           the option to download as a member of Worldcon 76.</p>
                    </td>
                </tr>

                <tr>
                    <td><input tabindex="6" id='eula' type="checkbox" value="EULA">&nbsp;I agree to the terms above (your PIN is your signature). 
                    <strong>You may not download unless you agree and check the box.</strong></td>
                </tr>
            </table>
        </fieldset>
    </form>

    <form id="categories" action="hugo-vote.php" method="post">
        <input type="hidden" name="pin" id="pin" value="">

        <fieldset class="hugo">
            <legend><strong>Categories</strong> - Select the categories and versions of the Hugo Nominees that you wish to download.</legend>

            <table class="catalog">
                <colgroup>
                    <col style="background-color: #F5F5F5">
                    <col style="background-color: #FFEFD5">
                    <col style="background-color: #F0E68C">
                    <col style="background-color: #FFEFD5">
                    <col style="background-color: #CCFFCC">
                </colgroup>

                <tr>
                    <th>Category</th>
                    <th>Size (MiB)</th>
                    <!-- <th>SHA256SUM Value</th> -->
                    <th align="center">Action</th>
                    <th>Notes</th>
                </tr>

EOT;

    $packetFileList = $db->getPacketFileList(false);

    foreach($packetFileList as $packetId => $packetInfo)
    {
      $pageData .= "                <tr>\n";
      $pageData .= "                    <td>".$packetInfo['file_short_description']."</td>\n";
      $pageData .= '                    <td align="center">&nbsp;'.$packetInfo['file_size']."</td>\n";
      // $pageData .= '                    <td>'.$packetInfo['sha256sum']."</td>\n";
      $pageData .= '                    <td align="center"><input type="button" value="Download" class="Download" id="'.$packetInfo['file_download_name'].'"></td>'."\n";
      $pageData .= '                    <td>'.$packetInfo['file_format_notes'].'</td>'."\n";
      $pageData .= "                <tr>\n";
    }



    $pageData .= <<<EOT
            </table>
        </fieldset>
    </form>

EOT;

    return $pageData;
  }

?>
