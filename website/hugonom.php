<?PHP
/*
 * Written by Ronald B. Oakes, copyright 2015, 2016, 2018, 2022
 * Rights assigned to Worldcon Intellectual Property, A California Nonprofit Corporation
 * For the exclusive of the World Science Fiction convention for purposes of administering the Hugo Awards
 * All other uses are forbidden without explicit permission from the author and Worldcon Intellection Property.
 */

/**
 * Code to build and display the Hugo Award nomination form.
 * This form will be built and displayed as an HTML form,
 * with a POST method. It will be updated when Posted via the HTTP or, preferably, HTTPS protocol. The function uses
 * information from $_POST and $_SERVER to determine if this is an initial load, or after the user has posted data.
 */
require_once ('database.php');
require_once ('memberValidator.php');

/**
 * Load the nominator structure from $_POST
 *
 * @param $wsfs_hugo_db object
 *          Hugo Award database object
 * @return array Hugo Award nomination structure.
 */
function loadNomination($wsfs_hugo_db)
{
  // Get the category information.
  $wsfs_hugo_categoryInfo = $wsfs_hugo_db->getCategoryInfo ();

  $wsfs_hugo_nominationStruct = array ();

  // Transfer the data from $_POST into $wsfs_hugo_nominationStruct, setting values were they exist to the value, to null if they do not.
  if (array_key_exists ( 'pin', $_POST ))
  {
    $wsfs_hugo_nominationStruct ['pin'] = is_null ( $_POST ['pin'] ) ? '' : $_POST ['pin'];
  }
  else
  {
    $wsfs_hugo_nominationStruct ['pin'] = null;
  }

  if (array_key_exists ( 'member_id', $_POST ))
  {
    $wsfs_hugo_nominationStruct ['member_id'] = is_null ( $_POST ['member_id'] ) ? '' : $_POST ['member_id'];
  }
  else
  {
    $wsfs_hugo_nominationStruct ['member_id'] = null;
  }

  if (array_key_exists ( 'last_name', $_POST ))
  {
    $wsfs_hugo_nominationStruct ['last_name'] = is_null ( $_POST ['last_name'] ) ? '' : $_POST ['last_name'];
  }
  else
  {
    $wsfs_hugo_nominationStruct ['last_name'] = null;
  }

  $wsfs_hugo_nominationStruct ['email_ballot'] = true;

  if ((array_key_exists ( 'email_ballot', $_POST )) && ($_POST ['email_ballot'] == 'NO'))
  {
    $wsfs_hugo_nominationStruct ['email_ballot'] = false;
  }

  foreach ( $wsfs_hugo_categoryInfo as $wsfs_hugo_id => $wsfs_hugo_info )
  {
    $wsfs_hugo_nominationStruct [$wsfs_hugo_id] = array ();

    for($wsfs_hugo_index = 1; $wsfs_hugo_index <= 5; $wsfs_hugo_index ++)
    {
      $wsfs_hugo_datumIndex = 'cat_' . $wsfs_hugo_id . '_nom_' . $wsfs_hugo_index . '_datum_1';
      if (array_key_exists ( $wsfs_hugo_datumIndex, $_POST ))
      {
        $wsfs_hugo_nominationStruct [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_1'] = preg_replace ( '/"/', '&quot;;', $_POST [$wsfs_hugo_datumIndex] );
        if ((! is_null ( $wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_2_description'] )) && ($wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_2_description'] != ''))
        {
          $wsfs_hugo_datumIndex = 'cat_' . $wsfs_hugo_id . '_nom_' . $wsfs_hugo_index . '_datum_2';
          $wsfs_hugo_nominationStruct [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_2'] = preg_replace ( '/"/', '&quot;;', $_POST [$wsfs_hugo_datumIndex] );
        }
        if ((! is_null ( $wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_3_description'] )) && ($wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_3_description'] != ''))
        {
          $wsfs_hugo_datumIndex = 'cat_' . $wsfs_hugo_id . '_nom_' . $wsfs_hugo_index . '_datum_3';
          $wsfs_hugo_nominationStruct [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_3'] = preg_replace ( '/"/', '&quot;', $_POST [$wsfs_hugo_datumIndex] );
        }
      }
      else
      {
        $wsfs_hugo_nominationStruct [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_1'] = null;
        $wsfs_hugo_nominationStruct [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_2'] = null;
        $wsfs_hugo_nominationStruct [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_3'] = null;
      }
    }
  }

  return $wsfs_hugo_nominationStruct;
}

/**
 * Update a Hugo Award nomination with the current data in the database.
 *
 * @param $wsfs_hugo_nominationStruct array
 *          Hugo Award Nomination data.
 * @param $wsfs_hugo_db object
 *          Hugo Award database object.
 * @return array The updated Hugo Award Nomination data.
 */
function updateNomination($wsfs_hugo_nominationStruct, $wsfs_hugo_db)
{
  // Get the category information
  $wsfs_hugo_categoryInfo = $wsfs_hugo_db->getCategoryInfo ();

  // Loop over each category
  foreach ( $wsfs_hugo_categoryInfo as $wsfs_hugo_id => $wsfs_hugo_info )
  {
    $wsfs_hugo_nominatorId = $wsfs_hugo_nominationStruct ['pin'];

    if (preg_match ( '(\\d+)', $_POST ['pin'], $wsfs_hugo_matches ))
    {
      $wsfs_hugo_nominatorId = $wsfs_hugo_matches [0];
    }

    // Get the current nominations.
    $wsfs_hugo_currentNoms = $wsfs_hugo_db->getNominationsForNominatior ( $_POST ['pin'], $wsfs_hugo_id );

    // If there are too many nominations currently, remove the ones at the bottom of the list.
    if (count ( $wsfs_hugo_currentNoms ) > 5)
    {
      $tempArray = array_reverse ( $wsfs_hugo_currentNoms, true );
      while ( count ( $tempArray ) > 5 )
      {
        array_pop ( $tempArray );
      }

      $wsfs_hugo_currentNoms = array_reverse ( $tempArray, true );
    }

    // Populate the Hugo Award nomination structure from the current nominations.
    $wsfs_hugo_index = 0;
    foreach ( $wsfs_hugo_currentNoms as $wsfs_hugo_nomRecord )
    {
      $wsfs_hugo_index += 1;

      $wsfs_hugo_nominationStruct [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_1'] = preg_replace ( '/"/', '&quot;', $wsfs_hugo_nomRecord ['datum1'] );
      $wsfs_hugo_nominationStruct [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_2'] = preg_replace ( '/"/', '&quot;', $wsfs_hugo_nomRecord ['datum2'] );
      $wsfs_hugo_nominationStruct [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_3'] = preg_replace ( '/"/', '&quot;', $wsfs_hugo_nomRecord ['datum3'] );
    }
  }

  return $wsfs_hugo_nominationStruct;
}

/**
 * Build the "Log in" form that gets the membership data and validates it
 *
 * @param $wsfs_hugo_db object
 *          Hugo Award database object
 * @param $wsfs_hugo_formAction string
 *          formAction for the form. That is where this form will be submitted
 * @param $wsfs_retro boolean
 *          true for Retro Hugo Awards, false otherwise
 * @return string The Hugo Award nomination page login form.
 */
function buildLoginForm($wsfs_hugo_db, $wsfs_hugo_formAction, $wsfs_retro)
{
  // Set the page description. NOTE: the text needs to be updated to reflect the text for the convention and year.
  $wsfs_hugo_descriptor = "2018 Hugo Awards, Award for Best Young Adult Book and John W. Campbell Award for Best New Writer";
  $wsfs_hugo_closing = "Hugo Award and John W. Campbell Award nominations will close at 11:59pm Pacific Daylight Time on March 16, 2018.";
  if ($wsfs_retro)
  {
    $wsfs_hugo_descriptor = "1943 Retrospective Hugo Awards";
    $wsfs_hugo_closing = "Retrospective Hugo Award nominations will close at 11:59pm Pacific Daylight Time on Friday March 16, 2018.";
  }

  // Build the login form and page.
  // NOTE: there is text in this page that needs to be updated to reflect the text for the convention and year.
  $wsfs_hugo_pageData = <<<EOT
    <P>
      <H1>
        <B>$wsfs_hugo_descriptor Login</B>
      </H1>
    </P>
    <P>
        <B>
           $wsfs_hugo_closing
        </B>
    </P>
    <P>
      <H2>
        <B>Eligibility to Nominate</B>
      </H2>
    </P>
    <P>
      <B>
        You may nominate for the $wsfs_hugo_descriptor if, on or before December 31, 2017, 11:59 pm PST (7:59 am GMT on January 1, 2018):
      </B>
      <UL>
        <LI>you are an attending or supporting member of Worldcon 76 (the 2018 World Science Fiction Convention), and/or</LI>
        <LI>you are an attending or supporting member of Dublin 2018, An Irish Worldcon (the 2019 World Science Fiction Convention), and/or</LI>
        <LI>you were an attending or supporting member of Worldcon 75 (the 2017 World Science Fiction Convention).</LI>
      </UL>
    </P>
    <P>
        Please read the following information in order to log in successfully.
        <UL>
          <LI>Eligible nominators received both a membership Number and a PIN.  The memberships number was assigned to you by Worldcon 76, Dublin 2019, or Worldcon 75.                                                                                     </LI>
          <LI>PINs will be sent primarily by email when the process opens in January.                                                                                                                                                                      </LI>
          <LI>Reminder e-mails will be sent throughout the nomination process.                                                                                                                                                                             </LI>
          <LI>If you have not received your PIN by February 5, you can request it be re-sent to you by sending an e-mail to hugopin@worldcon76.org.</LI>
        </UL>
    </P>
    <P>

EOT;

  // Optionally include links to the mail-in forms here.
  if (! $wsfs_retro)
  {
    # $wsfs_hugo_pageData .= 'If you would prefer, you can download the <a href="http://worldcon76.org/wp-content/uploads/2016/01/2016-hugo-nominating-ballotv2.pdf">paper ballot</a>, and then mail it in following the directions on the ballot.'."\n";
  }
  else
  {
    # $wsfs_hugo_pageData .= 'If you would prefer, you can download the <a href="http://worldcon76.org/wp-content/uploads/2015/12/PDF_1941RetroHugoBallot.pdf">paper ballot</a>, and then mail it in following the directions on the ballot.'."\n";
  }

  $wsfs_hugo_pageData .= <<<EOT
    </P>
    <P>
      Please fill in the eligibility section below.<BR/>
      <EM>Do not forget to provide both your Voting Number and PIN provided by Worldcon 76.</EM>
    </P>
    <P>
      PINs will be sent primarily by email starting in January. <!-- You can look up your PIN <A HREF="http://worldcon76.org/hugo-awards/pin-lookup/">here</a>, or you can also request your PIN by sending
      an e-mail to <A HREF="mailto:hugopin@worldcon76.org">hugopin@worldcon76.org</A> -->.
    </P>
    <HR/>
    <FORM ACTION="$wsfs_hugo_formAction" METHOD="post">
      <INPUT TYPE="HIDDEN" NAME="phase" VALUE="login"/>

EOT;
  // NOTE: The form here is Eurocentric regarding the names.
  $wsfs_hugo_pageData .= '      <TABLE>' . "\n";
  $wsfs_hugo_pageData .= '          <TR>' . "\n";
  $wsfs_hugo_pageData .= '            <TD>' . "\n";
  $wsfs_hugo_pageData .= '              <TABLE>' . "\n";
  $wsfs_hugo_pageData .= '                <TR>' . "\n";
  $wsfs_hugo_pageData .= '                  <TD>First Name:&nbsp;<INPUT TYPE="TEXT" NAME="first_name"';
  if (! empty ( $_POST ))
  {
    $wsfs_hugo_pageData .= ' VALUE="' . $_POST ['first_name'] . '"';
  }
  $wsfs_hugo_pageData .= ' SIZE=40 MAXLENGTH=64/></TD>' . "\n";
  $wsfs_hugo_pageData .= '                  <TD>Last Name:&nbsp;<INPUT TYPE="TEXT" NAME="last_name"';
  if (! empty ( $_POST ))
  {
    $wsfs_hugo_pageData .= ' VALUE="' . $_POST ['last_name'] . '"';
  }
  $wsfs_hugo_pageData .= ' SIZE=40 MAXLENGTH=64/></TD>' . "\n";
  $wsfs_hugo_pageData .= '                </TR>' . "\n";
  $wsfs_hugo_pageData .= '              </TABLE>' . "\n";
  $wsfs_hugo_pageData .= '            </TD>' . "\n";
  $wsfs_hugo_pageData .= '          </TR>' . "\n";
  $wsfs_hugo_pageData .= '          <TR>' . "\n";
  $wsfs_hugo_pageData .= '            <TD VALIGN="TOP">' . "\n";
  $wsfs_hugo_pageData .= '              <TABLE>' . "\n";
  $wsfs_hugo_pageData .= '                <TR>' . "\n";
  $wsfs_hugo_pageData .= '                  <TD>Membership Number:&nbsp;<INPUT TYPE="TEXT" NAME="member_id"';
  if (! empty ( $_POST ))
  {
    $wsfs_hugo_pageData .= ' VALUE="' . $_POST ['member_id'] . '"';
  }
  $wsfs_hugo_pageData .= ' SIZE=10 MAXLENGTH=10/></TD>' . "\n";
  $wsfs_hugo_pageData .= '                </TR>' . "\n";
  $wsfs_hugo_pageData .= '                <TR>' . "\n";
  $wsfs_hugo_pageData .= '                  <TD>PIN:&nbsp;<INPUT TYPE="PASSWORD" NAME="pin"   SIZE=15 MAXLENGTH=15/></TD>' . "\n";
  $wsfs_hugo_pageData .= '                </TR>' . "\n";
  $wsfs_hugo_pageData .= '              </TABLE>' . "\n";
  $wsfs_hugo_pageData .= '            </TD>' . "\n";
  $wsfs_hugo_pageData .= '          </TR>' . "\n";
  $wsfs_hugo_pageData .= '      </TABLE>' . "\n";
  $wsfs_hugo_pageData .= <<<EOT
      <TABLE>
        <TR>
          <TD><INPUT TYPE="SUBMIT" NAME="phase_1" VALUE="Log on"/></TD>
          <TD><INPUT TYPE="RESET" VALUE="Clear"/></TD>
        </TR>
      </TABLE>
    </FORM>
EOT;

  return $wsfs_hugo_pageData;
}

/**
 * Build the nomination form
 *
 * @param $wsfs_hugo_nomination int
 *          Hugo Award nomination data for this nominator
 * @param $wsfs_hugo_db object
 *          Hugo Award database object.
 * @param $wsfs_hugo_formAction string
 *          Action for the form submittal. That is the URL where the form will be submitted to.
 * @param $wsfs_retro boolean
 *          true for Retro Hugo Awards, false otherwise
 * @param $wsfs_hugo_userSubmission string
 *          TODO
 * @return string The HTML for the Hugo Nomination Form.
 */
function buildNominationForm($wsfs_hugo_nomination, $wsfs_hugo_db, $wsfs_hugo_formAction, $wsfs_retro, $wsfs_hugo_userSubmission)
{
  global $_POST;

  $wsfs_hugo_pageData = '';

  $wsfs_hugo_pageData .= "<!-- \$wsfs_hugo_userSubmission = $wsfs_hugo_userSubmission -->\n";

  $wsfs_hugo_pageData .= "<!-- \$wsfs_hugo_nomination \n";
  ob_start ();
  var_dump ( $wsfs_hugo_nomination );
  $wsfs_hugo_pageData .= ob_get_contents ();
  ob_end_clean ();
  $wsfs_hugo_pageData .= "-->\n";

  // Set the description and nomination closing time. These need to be updated to match the current information.
  $wsfs_hugo_descriptor = "2018 Hugo Awards, Award for Best Young Adult Book, and John W. Campbell Award for Best New Writer";
  $wsfs_hugo_closing = "Hugo Award and John W. Campbell Award nominations will close 11:59pm Pacific Daylight Time on Friday March 16, 2016.";
  if ($wsfs_retro)
  {
    $wsfs_hugo_descriptor = "1943 Retrospective Hugo Awards";
    $wsfs_hugo_closing = "Retrospective Hugo Award nominations will be close at 11:59pm Pacific Daylight Time on Friday March 16, 2018.";
  }

  // Get the category information
  $wsfs_hugo_categoryInfo = $wsfs_hugo_db->getCategoryInfo ();

  // Get the ballot for the current nominator.
  $wsfs_hugo_ballot = $wsfs_hugo_db->getBallot ( $wsfs_hugo_nomination ['pin'] );

  $wsfs_hugo_pageData .= "<!-- \$wsfs_hugo_ballot \n";
  ob_start ();
  var_dump ( $wsfs_hugo_ballot );
  $wsfs_hugo_pageData .= ob_get_contents ();
  ob_end_clean ();
  $wsfs_hugo_pageData .= "-->\n";

  // Populate the category order array with from the category info.
  foreach ( $wsfs_hugo_categoryInfo as $wsfs_hugo_id => $wsfs_hugo_info )
  {
    $wsfs_hugo_categoryOrder [$wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['ballot_position']] = $wsfs_hugo_id;
  }

  // Start building the page. Note that there is embedded date and convention information that needs to be updated.
  // Also note that this is here text, so comments must be HTML/XML encoded.
  $wsfs_hugo_pageData .= <<<EOT
    <P>
      <H1>
        <B>$wsfs_hugo_descriptor Nomination</B>
      </H1>
    </P>
    <P>
      <B>
        $wsfs_hugo_closing
      </B>
    </P>
    <P>
      <H2>
        <B>Eligibility to Nominate</B>
      </H2>
    </P>
    <P>
      You may nominate for the $wsfs_hugo_descriptor if
      on or before December 31, 2017 11:59 p.m. PST, you:
      <UL>
        <LI>are an attending or supporting member of Worldcon 76 (the 2018 World Science Fiction Convention); or </LI>
        <LI>are an attending or supporting member of Dublin 2019, an Irish Worldcon (the 2019 World Science Fiction Convention); or </LI>
        <LI>were an attending or supporting member of Worldcon 75 (the 2017 World Science Fiction Convention)</LI>
      </UL>
    </P>
    <P>You may only cast one nomination ballot even if you are/were a member of more than one of these conventions</P>
    <P>
      Details on the eligibility of works and individuals for nomination, and other information can be

EOT;
  $wsfs_hugo_pageData .= '      found <A HREF="' . $wsfs_hugo_formAction . '#rules">below</A> the ballot on this page.' . "\n";
  $wsfs_hugo_pageData .= <<<EOT
      </P>
      <P>
        Notice: Each time you press any of the "Submit Ballot" buttons, you are updating your ballot.  Your ballot will be saved
        and can be updated at any time during the nominating period.
      </P>
      <P>
        If you are having any problems using the nomination form, please email <A HREF="mailto:hugoadmin@worldcon76.org">hugoadmin@worldcon76.org</A>.
      </P>
      <FORM ACTION="$wsfs_hugo_formAction" METHOD="post">
        <INPUT TYPE="HIDDEN" NAME="phase" VALUE="ballot"/>

EOT;
  $wsfs_hugo_pageData .= '      <INPUT TYPE="hidden" NAME="pin" VALUE="' . $wsfs_hugo_nomination ['pin'] . '"/>' . "\n";
  $wsfs_hugo_pageData .= '      <INPUT TYPE="hidden" NAME="member_id" VALUE="' . $wsfs_hugo_nomination ['member_id'] . '"/>' . "\n";
  $wsfs_hugo_pageData .= '      <INPUT TYPE="hidden" NAME="last_name" VALUE="' . $wsfs_hugo_nomination ['last_name'] . '"/>' . "\n";
  $wsfs_hugo_pageData .= "<!--\n";
  $wsfs_hugo_pageData .= '      <P>Do you want your ballot emailed each time you submit?&nbsp;' . "\n";
  $wsfs_hugo_pageData .= '      <INPUT TYPE="radio"  NAME="email_ballot" VALUE="YES"';
  if ($wsfs_hugo_nomination ['email_ballot'])
  {
    $wsfs_hugo_pageData .= ' CHECKED ';
  }
  $wsfs_hugo_pageData .= '/>Yes&nbsp;' . "\n";
  $wsfs_hugo_pageData .= '      <INPUT TYPE="radio"  NAME="email_ballot" VALUE="NO"';
  if (! $wsfs_hugo_nomination ['email_ballot'])
  {
    $wsfs_hugo_pageData .= ' CHECKED ';
  }
  $wsfs_hugo_pageData .= '/>No' . "\n";
  $wsfs_hugo_pageData .= "-->\n";
  $wsfs_hugo_pageData .= '<INPUT TYPE="hidden" NAME="email_ballot" VALUE="YES"/>' . "\n";

  foreach ( $wsfs_hugo_categoryOrder as $wsfs_hugo_ballotOrder => $wsfs_hugo_id )
  {
    $wsfs_hugo_pageData .= "<!-- Category ID $wsfs_hugo_id -->\n";
    // TODO: Replace <B> with style appropriate tags
    $wsfs_hugo_pageData .= "<P><B>" . $wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['name'] . "</B></P>\n";
    $wsfs_hugo_pageData .= "<P>" . $wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['description'] . "</P>\n";
    if ($wsfs_hugo_userSubmission)
    {
      $wsfs_hugo_pageData .= "<P><FONT COLOR='RED'><EM>Your nominations have been updated.  You may continue to make updates until they close at 11:59PM Pacific Daylight Time on March 16, 2018</EM></FONT></P>\n";
    }
    else
    {
      $wsfs_hugo_pageData .= "<!-- \$wsfs_hugo_userSubmission is apparently false -->\n";
    }
    $wsfs_hugo_pageData .= "<TABLE>\n";
    $wsfs_hugo_pageData .= "  <TR>\n";
    $wsfs_hugo_pageData .= "    <TH>" . $wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['primary_datum_description'] . "</TH>\n";
    if ((! is_null ( $wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_2_description'] )) && ($wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_2_description'] != ''))
    {
      $wsfs_hugo_pageData .= "    <TH>" . $wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_2_description'] . "</TH>\n";
    }
    if ((! is_null ( $wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_3_description'] )) && ($wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_3_description'] != ''))
    {
      $wsfs_hugo_pageData .= "    <TH>" . $wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_3_description'] . "</TH>\n";
    }
    $wsfs_hugo_pageData .= "  </TR>\n";
    // The general format for nomination POST variables is cat_id_nom_#_datum_#
    for($wsfs_hugo_index = 1; $wsfs_hugo_index <= 5; $wsfs_hugo_index ++)
    {
      $wsfs_hugo_pageData .= "  <!-- \$wsfs_hugo_nomination[\$wsfs_hugo_id][\$wsfs_hugo_index]:\n";
      ob_start ();
      var_dump ( $wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] );
      $wsfs_hugo_pagedata .= ob_get_contents ();
      ob_end_clean ();
      $wsfs_hugo_pageData .= "  \$wsfs_hugo_ballot['nominations'][\$wsfs_hugo_id][\$wsfs_hugo_index-1]:\n";
      ob_start ();
      var_dump ( $wsfs_hugo_ballot ['nominations'] [$wsfs_hugo_id] [$wsfs_hugo_index - 1] );
      $wsfs_hugo_pageData .= ob_get_contents ();
      ob_end_clean ();
      $wsfs_hugo_pageData .= "  \$wsfs_hugo_id = $wsfs_hugo_id\n  \$wsfs_hugo_index = $wsfs_hugo_index\n  -->\n";

      $wsfs_hugo_pageData .= "  <TR>\n";
      $wsfs_hugo_datumIndex = 'cat_' . $wsfs_hugo_id . '_nom_' . $wsfs_hugo_index . '_datum_1';

      $wsfs_hugo_field_value = '';
      if (($wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_1'] != NULL) && ($wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_1'] != ''))
      {
        $wsfs_hugo_field_value = $wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_1'];
      }
      elseif (! empty ( $wsfs_hugo_ballot ['nominations'] [$wsfs_hugo_id] [$wsfs_hugo_index - 1] ))
      {
        $wsfs_hugo_field_value = $wsfs_hugo_ballot ['nominations'] [$wsfs_hugo_id] [$wsfs_hugo_index - 1] [1];
      }
      else
      {
        $wsfs_hugo_field_value = '';
      }

      $wsfs_hugo_pageData .= "    <!-- \$wsfs_hugo_datumIndex = $wsfs_hugo_datumIndex, \$wsfs_hugo_field_value = $wsfs_hugo_field_value -->\n";
      $wsfs_hugo_pageData .= '    <TD><INPUT TYPE="TEXT" NAME="' . $wsfs_hugo_datumIndex . '" SIZE="40" MAXLENGTH="256" VALUE="' . $wsfs_hugo_field_value . '"/></TD>' . "\n";

      if ((! is_null ( $wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_2_description'] )) && ($wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_2_description'] != ''))
      {
        $wsfs_hugo_datumIndex = 'cat_' . $wsfs_hugo_id . '_nom_' . $wsfs_hugo_index . '_datum_2';

        $wsfs_hugo_field_value = '';
        if (($wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_2'] != NULL) && ($wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_2'] != ''))
        {
          $wsfs_hugo_field_value = $wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_2'];
        }
        elseif (! empty ( $wsfs_hugo_ballot ['nominations'] [$wsfs_hugo_id] [$wsfs_hugo_index - 1] ))
        {
          $wsfs_hugo_field_value = $wsfs_hugo_ballot ['nominations'] [$wsfs_hugo_id] [$wsfs_hugo_index - 1] [2];
        }
        else
        {
          $wsfs_hugo_field_value = '';
        }

        $wsfs_hugo_pageData .= "    <!-- \$wsfs_hugo_datumIndex = $wsfs_hugo_datumIndex, \$wsfs_hugo_field_value = $wsfs_hugo_field_value -->\n";
        $wsfs_hugo_pageData .= '    <TD><INPUT TYPE="TEXT" NAME="' . $wsfs_hugo_datumIndex . '" SIZE="40" MAXLENGTH="256" VALUE="' . $wsfs_hugo_field_value . '"/></TD>' . "\n";
      }

      if ((! is_null ( $wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_3_description'] )) && ($wsfs_hugo_categoryInfo [$wsfs_hugo_id] ['datum_3_description'] != ''))
      {
        $wsfs_hugo_datumIndex = 'cat_' . $wsfs_hugo_id . '_nom_' . $wsfs_hugo_index . '_datum_3';

        $wsfs_hugo_field_value = '';
        if (($wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_3'] != NULL) && ($wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_3'] != ''))
        {
          $wsfs_hugo_field_value = $wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_3'];
        }
        elseif (! empty ( $wsfs_hugo_ballot ['nominations'] [$wsfs_hugo_id] [$wsfs_hugo_index - 1] ))
        {
          $wsfs_hugo_field_value = $wsfs_hugo_ballot ['nominations'] [$wsfs_hugo_id] [$wsfs_hugo_index - 1] [3];
        }
        else
        {
          $wsfs_hugo_field_value = '';
        }

        $wsfs_hugo_pageData .= "    <!-- \$wsfs_hugo_datumIndex = $wsfs_hugo_datumIndex, \$wsfs_hugo_field_value = $wsfs_hugo_field_value -->\n";
        $wsfs_hugo_pageData .= '    <TD><INPUT TYPE="TEXT" NAME="' . $wsfs_hugo_datumIndex . '" SIZE="40" MAXLENGTH="256" VALUE="' . $wsfs_hugo_field_value . '"/></TD>' . "\n";
      }
      $wsfs_hugo_pageData .= '  </TR>' . "\n";
    }
    $wsfs_hugo_pageData .= '  <TR><TD><INPUT TYPE="SUBMIT" VALUE="Submit Ballot"/></TD></TR>' . "\n";
    $wsfs_hugo_pageData .= "</TABLE><BR/>\n";
  }
  $wsfs_hugo_pageData .= "</FORM>\n";

  $wsfs_hugo_pageData .= <<<EOT
    <HR/>
    <A NAME="rules"/>

EOT;
  $wsfs_hugo_nomConfig = $wsfs_hugo_db->getCurrentNominationConfig ();

  $wsfs_hugo_pageData .= "<!-- \$wsfs_hugo_nomination Config \n";
  ob_start ();
  var_dump ( $wsfs_hugo_nomConfig );
  $wsfs_hugo_pageData .= ob_get_contents ();
  ob_end_clean ();
  $wsfs_hugo_pageData .= "-->\n";

  $wsfs_hugo_pageData .= $wsfs_hugo_nomConfig ['eligibility_txt'] . "\n";

  return $wsfs_hugo_pageData;
}

/**
 * Submit the completed or partially completed nominating ballot.
 *
 * @param $wsfs_hugo_nomination array
 *          Nomination data.
 * @param $wsfs_hugo_db object
 *          Database object
 * @param $wsfs_retro boolean
 *          true for Retro Hugo Awards, false otherwise
 * @return string HTML/XML comment code documenting the activites carried out.
 */
function submitBallot($wsfs_hugo_nomination, $wsfs_hugo_db, $wsfs_retro)
{
  global $_POST;

  $wsfs_hugo_pageData = '';

  // $_POST['member_id'],$_POST['pin'],$_POST['last_name']
  // $revalidation = validateMember($wsfs_hugo_nomination['member_id'],$wsfs_hugo_nomination['pin'],$wsfs_hugo_nomination['last_name']);

  // if($revalidation <= 0)
  {
    // return '';
  }

  // $wsfs_hugo_nominatorId = $wsfs_hugo_nomination['pin'];

  // Get the categories and information.
  $wsfs_hugo_categoryInfo = $wsfs_hugo_db->getCategoryInfo ();

  // Loop over all of the categories
  foreach ( $wsfs_hugo_categoryInfo as $wsfs_hugo_id => $wsfs_hugo_info )
  {
    // If there are any nominations in the current category in this ballot - as indicated by having any content in datum_1...
    if (($wsfs_hugo_nomination [$wsfs_hugo_id] [1] ['datum_1'] != '') or ($wsfs_hugo_nomination [$wsfs_hugo_id] [2] ['datum_1'] != '') or ($wsfs_hugo_nomination [$wsfs_hugo_id] [3] ['datum_1'] != '') or ($wsfs_hugo_nomination [$wsfs_hugo_id] [4] ['datum_1'] != '') or ($wsfs_hugo_nomination [$wsfs_hugo_id] [5] ['datum_1'] != ''))
    {
      // Clear the existing nominations so that they can be replaced.
      $wsfs_hugo_pageData .= $wsfs_hugo_db->clearNominations ( $_POST ['pin'], $wsfs_hugo_id );

      // Loop over the 5 nominations.
      for($wsfs_hugo_index = 1; $wsfs_hugo_index <= 5; $wsfs_hugo_index ++)
      {
        // If the datum_1 for the nomination is not blank, add the nomination to the database for this nominator.
        if (($wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_1'] != NULL) && ($wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_1'] != ''))
        {
          $wsfs_hugo_pageData .= $wsfs_hugo_db->addNomination ( $_POST ['pin'], $wsfs_hugo_id, $wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_1'], $wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_2'], $wsfs_hugo_nomination [$wsfs_hugo_id] [$wsfs_hugo_index] ['datum_3'] );
        }
      }
    }
    else
    {
      // Remove any previous nominations.
      $wsfs_hugo_pageData .= $wsfs_hugo_db->clearNominations ( $_POST ['pin'], $wsfs_hugo_id );
    }
  }

  // if($wsfs_hugo_nomination['email_ballot'])
  // Email the ballot to the nominator.
  {
    emailBallot ( $wsfs_hugo_nomination, $wsfs_hugo_db, $wsfs_retro );
    // Sometimes we need to reset to retro
    $wsfs_hugo_db = new Database ( $wsfs_retro );
  }
  return $wsfs_hugo_pageData;
}

/**
 * Emails the ballot to the Hugo Award nominator after they submit the form.
 *
 * @param $wsfs_hugo_nomination array
 *          Hugo Award nominatioin information
 * @param $db object
 *          Hugo Award database object
 * @param $wsfs_retro boolean
 *          true if this is for Retro Hugo awards, false otherwise.
 */
function emailBallot($wsfs_hugo_nomination, $db, $wsfs_retro)
{
  $ballot = $db->getBallot ( $wsfs_hugo_nomination ['pin'] );
  $wsfs_hugo_categoryInfo = $db->getCategoryInfo ();

  $PIN = $wsfs_hugo_nomination ['pin'];
  $email = getMemberEmailFromPin ( $PIN );

  $emailText = "";

  if (! $wsfs_retro)
  {
    $emailText = <<<EOT
Thank you for your nomination for the 2018 Hugo Awards, John W. Campbell Award, and Award for Best Young Adult Novel.

Below is a summary of your current nominating ballot:

EOT;
  }
  else
  {
    $emailText = <<<EOT
Thank you for your nomination for the 1942 Retrospective Hugo Awards.

Below is a summary of your current nominating ballot:

EOT;
  }
  foreach ( $ballot ['nominations'] as $categoryId => $ballotEntry )
  {
    if (count ( $ballot ) > 0)
    {
      $emailText .= "Your nominations for " . $wsfs_hugo_categoryInfo [$categoryId] ['name'] . ":\n\n";
      foreach ( $ballotEntry as $key => $nomination )
      {
        foreach ( $nomination as $datum )
        {
          $emailText .= $datum . " ";
        }
        $emailText .= "\n";
      }
      $emailText .= "\n";
    }
  }

  // Update the signature as needed.
  $emailText .= <<<EOT
Thank You
    Worldcon 76 Hugo Administrators

EOT;

  $subject = "";
  // Update the subject lines as needed
  if (! $wsfs_retro)
  {
    $subject = 'Your 2018 Hugo Award, John W. Campbell Award, and Award for Best Young Adult Novel Nominating Ballot';
  }
  else
  {
    $subject = 'Your 1942 Retrospective Hugo Award Ballot';
  }
  // Update the From: field as needed, as well as any other additional headers needed.
  if ($email != '')
  {
    $result = mail ( $email, $subject, $emailText, 'From: hugoadmin@worldcon76.org' );
  }

  $db->logEmail ( $PIN, $emailText, $result, $email );
}

/**
 * Build the Hugo Award Nomination form.
 *
 * @param $wsfs_hugo_formAction string
 *          The filename of the currently executing script, relative to the document root. That is the relative path of the web page. Most likely comes from $_SERVER['PHP_SELF'] (Optional)
 * @param $wsfs_retro boolean
 *          Set to true for true for Retro Hugos, false otherwise (Optional - defaults to true)
 * @param $wsfs_hugo_privlidge boolean
 *          Set to true to execute in the privlidged mode that allows for use outside of normal nominating dates.
 * @return string The HTML for the Hugo Award Nomination Form.
 */
function getHugoNomForm($wsfs_hugo_formAction = '', $wsfs_retro = true, $wsfs_hugo_privlidge = false)
{
  global $_POST;

  $wsfs_hugo_pageData = '';

  if ($wsfs_hugo_formAction == '')
  {
    $wsfs_hugo_formAction = $_SERVER ['PHP_SELF'];
  }

  $wsfs_hugo_db = new Database ( $wsfs_retro );

  // Log the $_POST and $_SERVER for debugging and recovery
  $wsfs_hugo_db->logNominationPost ();

  $wsfs_hugo_pageData .= "<!-- \$_POST: \n";
  ob_start ();
  var_dump ( $_POST );
  $wsfs_hugo_pageData .= ob_get_contents ();
  ob_end_clean ();
  $wsfs_hugo_pageData .= "-->\n";

  // Main section - choose page based on $_POST data
  $wsfs_hugo_nomination = loadNomination ( $wsfs_hugo_db );

  // If we're in preview add a notification
  // NOTE: The date in this text is hard coded and will need to be updated.
  if ($wsfs_hugo_db->inPreview ())
  {
    $wsfs_hugo_pageData .= <<<EOT
    <FONT COLOR="RED">
      <H1><STRONG>Notice</STRONG>: Hugo Nominations Are Not Open.</H1>
      <P>Any nominations submitted before January 26, 2018 at Noon PST will be deleted and not count</P>
      <P>Nomination web interface is in preview mode and may not reflect the final version</P>
    </FONT>
EOT;
  }

  // Determine what kind of form needs to be loaded based on the nomination status, and the contents of $_POST
  if ((($wsfs_hugo_nominationStatus = $wsfs_hugo_db->areNominationsOpen ()) != 'Hugo Award nominations are open') && (! $wsfs_hugo_db->inPreview ()) && (! $wsfs_hugo_privlidge))
  {
    $wsfs_hugo_pageData = '    <H1>' . $wsfs_hugo_nominationStatus . '</H1>' . "\n";
  }
  elseif (empty ( $_POST )) // Fresh Load
  {
    $wsfs_hugo_pageData .= buildLoginForm ( $wsfs_hugo_db, $wsfs_hugo_formAction, $wsfs_retro );
  }
  else // We've got something
  {
    $wsfs_hugo_submission = false;
    $wsfs_hugo_submission = false;

    $wsfs_hugo_validationCode = validateMember ( $_POST ['member_id'], $_POST ['pin'], $_POST ['last_name'] );

    // In some cases during 2016 nomination, the wrong database would get loaded during validation
    $wsfs_hugo_db = new Database ( $wsfs_retro );

    if ($wsfs_hugo_validationCode == 0) // Invalid
    {
      $wsfs_hugo_pageData .= '<P><FONT COLOR="RED">Invalid Membership Number or PIN</FONT></P>' . "\n";
      $wsfs_hugo_pageData .= buildLoginForm ( $wsfs_hugo_db, $wsfs_hugo_formAction, $wsfs_retro );
    }
    elseif ($wsfs_hugo_validationCode < 0) // Valid, but ineligible
    {
      $wsfs_hugo_pageData .= '<P><FONT COLOR="RED">Valid Membership Number and PIN, but not allowed to nominate at thist time</FONT></P>' . "\n";
    }
    else // Eligible nominator, build the form.
    {
      $wsfs_hugo_pageData .= "<!-- Found Ballot -->\n";
      $wsfs_hugo_submission = false;
      if ($_POST ['phase'] == 'ballot') // If we have received a ballot, submit it which updates the comments for the web page.
      {
        $wsfs_hugo_pageData .= submitBallot ( $wsfs_hugo_nomination, $wsfs_hugo_db, $wsfs_retro );
        $wsfs_hugo_submission = true;
      }

      $wsfs_hugo_pageData .= "<!-- before: \n";
      ob_start ();
      var_dump ( $wsfs_hugo_nomination );
      $wsfs_hugo_pageData .= ob_get_contents ();
      ob_end_clean ();
      $wsfs_hugo_pageData .= "-->\n";

      // Update the nomination data
      $wsfs_hugo_nomination = updateNomination ( $wsfs_hugo_nomination, $wsfs_hugo_db );

      $wsfs_hugo_pageData .= "<!-- after: \n";
      ob_start ();
      var_dump ( $wsfs_hugo_nomination );
      $wsfs_hugo_pageData .= ob_get_contents ();
      ob_end_clean ();
      $wsfs_hugo_pageData .= "-->\n";

      $wsfs_hugo_pageData .= "<!-- Just before buildNominationForm -->\n";

      $wsfs_hugo_pageData .= buildNominationForm ( $wsfs_hugo_nomination, $wsfs_hugo_db, $wsfs_hugo_formAction, $wsfs_retro, ($_POST ['phase'] == 'ballot') );
    }
  }

  $wsfs_hugo_pageData .= "\n";

  $wsfs_hugo_db->logNominationPage ( $wsfs_hugo_pageData );

  return $wsfs_hugo_pageData;
}

?>
