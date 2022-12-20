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

	// Lookup Member PIN and Email to Member
	
	$result		= array();	// Empty array to hold <input> values as they are retrieved.
	$result_row = array();	// Row array used by PDO fetch.
	
	$email = "";
	$lastname = "";
	$firstname = "";
	$convention = "";
	$membership = 0;
	
	$runFlag	= false;
	$appendFlag = false;
	$firstFlag  = false;
	$lastFlag	= false;
	$emailFlag	= false;
	$memberFlag = false;
	
	$rowCount = 0;
	
	$con = array( 'detcon1' => 'Detcon 1');
	
	$result['PINstatus'] = 'invalid';	// Default response.
	
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		if (isset($_GET['hg_membership'])) 	{ $membership   = $_GET['hg_membership']; }
		if (isset($_GET['hg_last_name'])) 	{ $lastname     = $_GET['hg_last_name']; }
		if (isset($_GET['hg_first_name'])) 	{ $firstname    = $_GET['hg_first_name']; }
		error_log("[hugo-get-pin: GET] Lastname: '$lastname' Firstname: '$firstname' Membership: '$membership'");
	} else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (isset($_POST['hg_membership'])) { $membership   = $_POST['hg_membership']; }
		if (isset($_POST['hg_last_name'])) 	{ $lastname     = $_POST['hg_last_name']; }
		if (isset($_POST['hg_first_name'])) { $firstname    = $_POST['hg_first_name']; }
		error_log("[hugo-get-pin: POST] Lastname: '$lastname' Firstname: '$firstname' Membership: '$membership'");
	}

	// Normalize to Uppercase as the table is all UC.
	
	$firstname = strtoupper($firstname);
	$lastname  = strtoupper($lastname);
	
	// Design Pattern: all rows of Hugo_PIN contain the voter's row_id in the respective membership table.
	// Don't rely on the name/email stored in this table, and instead always look up the actual row.
	
	if (empty($firstname) && empty($lastname) && empty($membership)) {
		$result['reason'] = "You must provide at least a first and last name to facilitate search.";
		$result['PINstatus'] = "unknown";
		
		echo json_encode($result);
		return;
	}
	
	// Remove leading letters from membership #
	$first_letter = substr($membership, 0, 1);
	if ($first_letter == 'A' || $first_letter == 'S') {
		$membership = substr($membership, 1);
		error_log("[hugo-get-pin: DEBUG] membership: $membership");
	}

	// Build up the voter row SELECT statement based on the parameters provided by the user.
	
	$query_str = "";
	
	if (strlen($firstname) > 0) {
		$query_str .= "`first_name` = UPPER(:firstname) ";
		$appendFlag = true;
		$firstFlag = true;
	}
	
	if (strlen($lastname) > 0) {
		if ($appendFlag) {
			$query_str .= "AND ";
		}
		$query_str .= "`last_name` = UPPER(:lastname) ";
		$appendFlag = true;
		$lastFlag = true;
	}

	if (strlen($membership) > 0) {
		if ($appendFlag) {
			$query_str .= "AND ";
		}
		$query_str .= "`member` = :membership ";
		$appendFlag = true;
		$memberFlag = true;
	}
	
	foreach (array('detcon1') as $convention) {
		// Build dynamic SQL query based on the subset of parameters provided by the caller.
	
		$stmt_str = "SELECT COUNT(*), first_name, last_name, pin, email_addr FROM DT1YAAward.PINs WHERE " . $query_str;
		error_log("[hugo-get-pin: DEBUG] stmt_str: $stmt_str");
		
		// Just search each convention's PINs, favoring LSC3, until one is found.  Only return the first found.
		if (queryPIN($stmt_str, $firstFlag, $lastFlag, $memberFlag, $firstname, $lastname, $membership) == true) {
			if ($result['email']) {
			    $first = $result['firstname'];
			    $last  = $result['lastname'];
			    $conv  = $con[$convention];     // Mapped string (hash) of human-readable convention names.
			    $pin   = $result['pin'];

                    $msg = <<< EOS
                	<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">

                	<html>
                		<head>
                		    <title>Detcon1 YA Award PIN Information</title>
                		    <style type="text/css">

                		        body { 
                		            background-repeat: no-repeat;
                		            background-attachment: fixed;
                		            background-position: center center;
                		            font-family: "Gill Sans", Calibri, Helvetica, sans-serif;
                		            font-size: 12pt;
                		        }

                		        table.boarded
                		        {
                		            border-width: 0 0 1px 1px;
                		            border-spacing: 0;
                		            border-collapse: collapse;
                		            border-style: solid;
                		        }

                		        .boarded td, .boarded th
                		        {
                		            margin: 0;
                		            padding: 10px;
                		            border-width: 2px 2px 0 0;
                		            border-style: solid;
                		        }

                		        td.header
                		        {
                		            font-weight: 600;
                		        }

                		        .footer
                		        {
                		            font-size: 9pt;
                		        }

                		        .alamo
                		        {
                		            width: 800px;
                		        }

                		        #content 
                		        {
                		            background-color: #fff;
                		            padding: 20px;
                		            border: 5px solid #673527;
                		            border-top-left-radius: 10px 10px;
                		            border-top-right-radius: 10px 10px;
                		            border-bottom-left-radius: 10px 10px;
                		            border-bottom-right-radius: 10px 10px;
                		            margin-left: 0px;
                		            width: 800px;
                		        }
                		    </style>
                		</head>

                		<body>
                		    <div id="content">
							<img src="http://www.deltos.com/detcon1/DETCON1-masthead-3.ai.png" border="0" width="780">
                		    <h3>Greetings from the Detcon1 YA Award Committee</h3>
                		    <p>This is an automated email that is generated in response to a new membership or a <i>Recover PIN</i> request from any of the Detcon1 YA Awards pages.</p><br/>
                            
                    		<p>Your Detcon1 PIN is: <font face="Consolas, Courier, Sans-Serif">$pin</font>.</p>
                    		<p>Your Detcon1 login is: <br>
							<center>First Name: '<font face="Consolas, Courier, Sans-Serif">$first</font>'&nbsp;
							Last Name: '<font face="Consolas, Courier, Sans-Serif">$last</font>'</center></p>
                    		<p>The Detcon1 YA Awards are hosted by Detcon1, the 2014 NASFiC.  Members of Detcon1 who join prior to the close of voting are eligible to take part in the final ballot.</p>
                            <p>For more information on the YA Awards, please visit Detcon1's website at <a href="http://www.detcon1.org/">http://www.detcon1.org/</a>.</p>
                            
                		</div>
                		    <p></p>
                		</body>
                	</html>
EOS;

        		send_msg($msg, $result['email']);
            	
				$result['PINstatus'] = 'valid';	// Return valid status, indicating there are PINs found.
			} else {
				// No email found.
				$result['reason'] = "No e-mail address is on file with this PIN, so we cannot e-mail your PIN to you.  Please send your valid e-mail address to the following address:";
			}
			
			echo json_encode($result);
			return;
		}
	}
	
	// No PIN found.
	echo json_encode($result);
	return;
	
	function queryPIN($stmt_str, $firstFlag, $lastFlag, $memberFlag, $firstname, $lastname, $membership) {
		// Set up database access over PDO.
		
		global $result;
		global $result_row;
		
		$rowCount = -1;
	
		$db = new PDO('mysql:dbname=DT1YAAward;host=localhost;charset=utf8', 'detcon', 'detcon1admin');
		$db->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
		$stmt_select = $db->prepare($stmt_str);
	
		if ($firstFlag) {
			error_log("[hugo-get-pin: DEBUG] binding first_name: ". $firstname);
			$stmt_select->bindParam(':firstname', $firstname);		
		}

		if ($lastFlag) {
			error_log("[hugo-get-pin: DEBUG] binding last_name: ". $lastname);
			$stmt_select->bindParam(':lastname', $lastname);		
		}

		if ($memberFlag) {
			error_log("[hugo-get-pin: DEBUG] binding member: ". $membership);
			$stmt_select->bindParam(':membership', $membership);		
		}

		$runFlag  = $stmt_select->execute();
		
		if ($runFlag) {
			while($result_array = $stmt_select->fetch(PDO::FETCH_BOTH)) {
				error_log("[hugo-get-pin: DEBUG] fetch returned count: '" . $result_array[0] . "' email: '" . $result_array['email_addr'] . "'");
				$result['email'] = $result_array['email_addr'];
				$result['firstname'] = $result_array['first_name'];
				$result['lastname'] = $result_array['last_name'];
				$result['pin'] = $result_array['pin'];
				$result['count'] = $result_array[0];
			}
			
			$rowCount = $result['count'];
	
			if ($rowCount < 1) {
				$result['reason'] = 'No PIN matches the name provided.';
				$result['PINstatus'] = 'nomatch';
				return false;
			} else if ($rowCount > 1) {
				$result['reason'] = 'Multiple names match your input.';
				$result['PINstatus'] = 'multimatch';
				return false;
			} else {
				error_log("[hugo-get-pin: DEBUG] pin found.");
				
				$result['PINmatch'] = 'valid';
				return true;
			}
		} else {
			$result['reason'] = 'SQL command failed.';
			return false;
		}
	
		return true;
	}
	
	
	function send_msg($msg, $email) {
	    $header = "From: pin@detcon1.org\r\nMIME-Version: 1.0\r\nContent-type: text/html; charset=iso-8859-1\r\nX-Priority: 1\r\nX-Mailer: PHP/" . phpversion();
		mail ($email, "Detcon1 YA Award PIN Information", $msg, $header);
	}
	
?>
