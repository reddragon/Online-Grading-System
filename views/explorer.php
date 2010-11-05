<?php
require_once ('HTML/Table.php');
require_once ('HTML/QuickForm.php');

#-------------------------------------------------------------------------------

function explorer_init() {
	global $db, $cfg;
	nav_add('Contests', 'Overview', 'explorer', 'overview');
	nav_add('Contests', 'Archives', 'explorer', 'archives');

	$rep =& db_query('user_by_handle', $_SESSION['handle']);
	$rep->fetchInto($rox);
					
	$res2 =& db_query('groups_by_user_id', $rox['user_id']);
	$isadmin = false;
			
			
	$res2->fetchInto($row2);
	while ($row2) {
		if($row2['name'] == 'Administrators') { $isadmin = true; }
		$res2->fetchInto($row2);
	}

	$res =& db_query('relevant_contests_list');

	# Add menu links for all upcoming & current contests
	while ($res->fetchInto($row)) {
		# Check if user has registered for contest
		$res2 =& db_query('user_and_contest', array($_SESSION['user_id'], $row['contest_id']));
		if ($res2->numRows() == 0) {
			$row['name'] = '<b><i>'.$row['name'].'</i></b>';
		} else {
			$row['name'] = '<b>'.$row['name'].'</b>';
		}
		
		if($isadmin == true || ($row['division'] == $rox['division']))
			nav_add('Contests', $row['name'], 'explorer', 'contest', '&amp;id='.$row['contest_id']);
	}
	
	// Attempt to set explorer as default view
	view_set('explorer');
}

#-------------------------------------------------------------------------------
# Show the controls for contest team registration

function explorer_contest_register($contest_id) {
	global $cfg, $db;
	$res =& db_query('contest_by_id', $contest_id);
	
	assert ($res->fetchInto($row));
	$res->free();
	
	$form = new HTML_QuickForm('regForm', 'post', selflink());
	$form->addElement('header', null, 'Contest Registration');
	
    if ($row['team_size'] == '1') {
		// Deathmatch :)
		$form->addElement('text', 'college', 'College Name: ');
		$form->addRule('college', 'College name is required', 'required', null, 'client');
	} else {
		// Team deathmatch :D
        $form->addElement('text', 'name', 'Team name: ');
        $form->addElement('text', 'college', 'College Name: ');
        
        for ($i=2; $i<=$row['team_size']; ++$i) {
            $suffix = 'th';
            if ($i==2) $suffix = 'nd';
            else if ($i==2) $suffix = 'rd';
            $form->addElement('text', 'member'.($i-1), "User-handle of {$i}<super>{$suffix}</super> team member: ");
        }
		
		$form->addRule('name', 'Team name is required', 'required', null, 'client');
		$form->addRule('college', 'College name is required', 'required', null, 'client');
	}
	
	$form->addElement('checkbox', 'agree', 'I agree to the <a target="_blank" href="'.$row['rules'].'">Terms and Conditions</a> for participating in this contest.');
	$form->addRule('agree', 'You need to agree to our Terms and Conditions in order to participate.', 
		'required', null, 'client');
	
	$form->addElement('submit', 'submit', 'Register');
	if ($form->validate()) {
		$data = $form->getSubmitValues();
		$uids = array();
		
		// Check if team is already taken
		$proceed = true;
		if ($row['team_size'] > 1) {
			$res =& db_query('team_by_name', array($data['name'], $contest_id));
			if ($res->numRows() != 0) {
				echo "<p><b>That team name has already been taken! Please try again.</b></p>";
				$form->display();
				$proceed = false;
			}
		}
		
		if ($proceed) {
			if ($row['team_size'] == '1') {
				// Use user handle as team name for 1-man contests
				$data['name'] = $_SESSION['handle'];
			} else {
                // Verify member list
                
                //foreach (preg_split("/[\s,]+/", $data['members']) as $handle) {
                for ($i=1; $i<$row['team_size']; ++$i) {
                    $handle = $data['member'.$i];
                    if (strlen($handle) == 0) continue;
					$res =& db_query('user_by_handle', $handle);
					
					if (!$res->fetchInto($row1)) {
						?>
	<p><strong>Error:</strong> User <b><?php echo $handle ?></b> does not exist! Please 
    <a href="<?php echo htmlspecialchars(selflink()) ?>">try again</a>.</p>
						<?php
						
						$res->free();
						return;
                    } else {
                        $res->free();
                        $res =& db_query('user_and_contest', array($row1['user_id'], $contest_id));
                        if ($res->fetchInto($row2)) {
                            ?>
    <p><strong>Error:</strong> User <b><?php echo $handle ?></b> has already been registered as a member of team <b><?php echo $row2['name'] ?></b>
    for this contest! Please <a href="<?php echo htmlspecialchars(selflink()) ?>">choose other members</a> for your team.</p>
                            <?php
                            $res->free();
                            return;
                        }
                        
                        array_push($uids, $row1['user_id']);
					}
                }
                
                if (count($uids) > $row['team_size']-1) {
                    ?>
                    <p><strong>Error:</strong> Maximum number of team members allowed for this contest is <?php echo $row['team_size'] ?>.</p>
                    <?php
                    return;
                }
			}
			
			// Add the team
			$res =& $db->autoExecute('teams', array('name' => $data['name'], 
				'contest_id' => $contest_id, 'college' => $data['college']));
			if (PEAR::isError($res)) error($res->toString());
			
			$res =& db_query('team_by_name', array($data['name'], $contest_id));
			$res->fetchInto($row);
			$res->free();
			
			// Add the team members (including current user)
            array_push($uids, $_SESSION['user_id']);
            $uids = array_unique($uids);
            
			foreach ($uids as $uid) {
				$res =& $db->autoExecute('members', array('team_id' => $row['team_id'],
					'user_id' => $uid, 'contest_id' => $_GET['id']));
				if (PEAR::isError($res)) error($res->toString());
			}
			
			?>
            <p><strong>Thanks for registering!</strong> Please <a href="<?php echo htmlspecialchars(selflink()) ?>">
	click here</a> to proceed.</p>
			<?php
		}
    } else {
        ?>
<p><strong>Tip:</strong> You can register your team only
once through any one of your member logins. You can leave the member fields 
blank to indicate that you are the sole team member. Also, please verify your choice of team members before
registering. Currently editing
team membership is not supported. In case of problems, don't hesitate to <a href="mailto:de.arijit@gmail.com">contact</a> us.</p>
        <?php
        
		$form->display();
	}
}

#-------------------------------------------------------------------------------

function explorer_display($task) {
    global $cfg, $db;
    html_include_js($cfg['dir']['scripts'].'/timer.js');
	if ($task == '') $task = 'overview';

	switch ($task) {
		case 'overview' :
			$res =& db_query('relevant_contests_list');
			$rep =& db_query('user_by_handle', $_SESSION['handle']);
			$rep->fetchInto($rox);
			
			$res2 =& db_query('groups_by_user_id', $rox['user_id']);
			$isadmin = false;
			
			
			$res2->fetchInto($row2);
			while ($row2) {
				if($row2['name'] == 'Administrators') { $isadmin = true; }
				 $res2->fetchInto($row2);
			}
			if ($res->numRows() == 0) {
?>
	<p><b>No contests are running or open for registrations at this time.</b><br />
		To view and practice problems from past contests, please see the
	 <a href="index.php?view=explorer&task=archives">archives</a>.</p>
<?php

			} else {
?>
	 <p>Contests listed here are either open for registrations, or are currently running. For past contests, please see the
	 <a href="index.php?view=explorer&task=archives">archives</a>.</p>
<?php

				$table = new HTML_Table;
				$table->addRow(array ('Name', 'Division', 'Status', 'Begins', 'Ends', 'Description'), null, 'TH');
				
				while ($res->fetchInto($row) == '1') {
					if ($row['running'])
						$status = "Running";
					else {
                        if ($row['closed'] == '1')
                            $status = "Registrations<br />Closed";
                        else
                            $status = "Registrations<br />Open";
                    }
                    
                    if($row['division'] == $rox['division'] || $isadmin == true)
                    {
                    	$link = "<a href=\"index.php?view=explorer&amp;task=contest&amp;id={$row['contest_id']}\">$status</a>";
                    }
                    else
                    {
                    	$link = $status;
					}
					$table->addRow(array ($row['name'], $cfg["tcl"]["divisions"][$row['division']], $link, $row['begin_time'], $row['end_time'], $row['description']));
				}

				$table->altRowAttributes(1, null, array ("class" => "altrow"));
                echo '<div class="overflow">'.$table->toHtml().'</div>';
			}
			break;

		//----------------------------------------------------------------------
		case 'archives' :
			$res =& db_query('past_contests_list');

			if ($res->numRows() == 0) {
?>
	<p><b>Nothing in our archives yet!</b><br />Please come back later.</p>
<?php

			} else {
?>
	<p><b>Practice Mode:</b><br />You can practice problems from past contests below.</p>
<?php

				$table = new HTML_Table;
				$table->addRow(array ('Problems', 'Contest', 'Manager'), 
										null, 'TH');

				while ($res->fetchInto($row)) {
					$res2 =& db_query('problems_by_contest_id', $row['contest_id']);
					$problems = '<div style="padding:5px">';
					
					while ($res2->fetchInto($row2)) {
						$problems .= '<a href="index.php?view=explorer&amp;task=archives&amp;id='.
							$row['contest_id'].'&amp;prob_id='.$row2['prob_id'].'">'.
							$row2['prob_id'].'</a> ('.$row2['weight'].')<br />';
					}
					$problems .= '</div>';
					
					$res3 =& db_query('user_by_id', $row['manager']);
					$res3->fetchInto($row3);
					
					$table->addRow(array($problems, '<b>'.$row['name'].'</b><br />'.$row['description'], 
						user_handle($row3['handle'])));
				}

				$table->altRowAttributes(1, null, array ("class" => "altrow"));
                echo '<div class="overflow">'.$table->toHtml().'</div>';
                
                html_javascript_check();
				
				if (isset($_GET['id']) && isset($_GET['prob_id'])) {
					require_once ('submit.php');
					
					echo "\n<hr />\n";
                    echo '<p><b>Tip:</b> Scroll <a href="#solution">down</a> for practice solution form.</p>';
                    
                    $res =& db_query('problem_by_id', array($_GET['prob_id'], $_GET['id']));
                    $res->fetchInto($problem);
                    $res->free();
                    problem_display($problem);
					echo "<hr />";
					
					echo '<a name="solution"></a>';	
					submit_field($_GET['id'], null, $problem, true);
				}
			}
			break;

		//----------------------------------------------------------------------
		case 'contest' :
			$rep =& db_query('user_by_handle', $_SESSION['handle']);
			$rep->fetchInto($rox);
			
			
			$res =& db_query('contest_by_id_safe', $_GET['id']);
			
			if (!$res->fetchInto($row)) {
				error('No such contest with ID='.$_GET['id'].
					'. Contest doesn\'t exist, or registrations not open yet.');
			}
			
			$res2 =& db_query('groups_by_user_id', $rox['user_id']);
			$isadmin = false;
			
			
			$res2->fetchInto($row2);
			while ($row2) {
				if($row2['name'] == 'Administrators') { $isadmin = true; }
				 $res2->fetchInto($row2);
			}
			
			
			
			if (($rox['division'] != $row['division']) && ($isadmin == false)) {
				 ?>
                <p><b>Contest blocked.</b><br/>
                Sorry, you're not allowed to open or register for this contest. This contest is meant for <?php echo $cfg['tcl']['divisions'][$row['division']] ?>.</p>
                <?php
                return;
			}
			
			$user_registered = false;
			$contest_started = false;
			$contest_ended = false;
			
			if ($row['begin_future'] != '1') {
				$contest_started = true;
				if ($row['end_future'] != '1') {
					$contest_ended = true;
				}
			}
			
			if (!$contest_ended) {
				$res =& db_query('user_and_contest', array($_SESSION['user_id'], $_GET['id']));

				assert ($res->numRows() <= 1);
				if ($res->numRows() == 1) {
					$user_registered = true;
					$res->fetchInto($team);
				}
			}
			
			if (!$user_registered && $row['closed'] == 1) {
                ?>
                <p><b>Contest blocked.</b><br/>
                Sorry, you're not allowed to open or register for this contest. You can see the results after the
                contest ends at <?php echo $row['end_time'] ?>.</p>
                <?php
                return;
			}
			
			// Here we take steps based upon various states the contest is in
			if (!$contest_started) {
				if ($user_registered) {
					?>
<p>This contest is scheduled to begin at <?php echo $row['begin_time'] ?>. You
have already registered for it.</p>
					<?php
				} else {
					?>
<p>This contest is scheduled to begin at <?php echo $row['begin_time'] ?>. 
Registrations are open now, and will remain open until the contest ends.</p>
                    <?php
                    explorer_contest_register($_GET['id']);
				}
			} else if (!$contest_ended) {
				if (!$user_registered) {
					if ($row['team_size'] == 1) {
						$msg = 'enroll yourself';
					} else {
						$msg = 'register your team';
					}
					
					?>
<p><strong>Contest has started!</strong><br /> You need to <?php echo $msg ?> before you can compete in it.</p> 
					<?php
					explorer_contest_register($_GET['id']);
                } else {
                    db_query('team_seen', array($_GET['id'], $team['team_id']));
                    
					// Display a timer
					$res =& db_query('contest_time', $_GET['id']);
					$res->fetchInto($time);
					$res->free();
                    $field_size = strlen(floor($time['remain']/3600)) + 7;
                    
					?>
<div id="timer">
<form name="frmClock" action="index.php">
Contest Timer: <input type="text" name="fieldTimer" size="<?php echo $field_size; ?>"/>
</form>
</div>
<script type="text/javascript">
// <!--
startClock(<?php printf("%d,%d,%d", $time['remain']/3600, ($time['remain']/60)%60, $time['remain']%60); ?>);
// -->
</script>
					<?php
				
					// Display the problems table
					$probs =& db_query('problems_by_contest_id', $_GET['id']);
					
					$table = new HTML_Table;
					$table->addRow(array('Problem ID', 'Weight', 'Summary', 'Submission count'), null, 'TH');
					
					$prob_id_ok = false;
                    while ($probs->fetchInto($prob)) {
                        // Get submission count
                        $res =& db_query('solution_by_id', array($_GET['id'], $team['team_id'], $prob['prob_id']));
                        $submit = '0';
                        if ($res->fetchInto($soln) && $soln['submits'] > 0) {
                            $url = 'solution.php?id='.$_GET['id'].'&amp;prob_id='.$prob['prob_id'];
                            $submit = '<a onclick="newWindow(\''.$url.'\')"  href="#">'.$soln['submits'].'</a>';
                        }
                        
						$link = "index.php?view=explorer&amp;task=contest&amp;id={$_GET['id']}&amp;prob_id={$prob['prob_id']}";
						$table->addRow(array("<a href=\"$link\">{$prob['prob_id']}</a>",$prob['weight'], $prob['summary'], $submit));
						
						if ($prob['prob_id'] == $_GET['prob_id']) {
							$prob_id_ok = true;
						}
					}
					$table->altRowAttributes(1, null, array ("class" => "altrow"));
                    echo '<div class="overflow">'.$table->toHtml().'</div>';
                    
                    echo "<p><b>Note: </b>Opening a problem starts its individual timer, and you are scored according to the time required to sumit a solution. Hence, open a problem only when you are ready to code it.</p>";
                    html_javascript_check();
                    
					echo "<hr />";
					
					// Display the selected (if any) problem's contents
					if (isset($_GET['prob_id']) && $prob_id_ok == true) {
						require_once ('submit.php');
						submit_opened($_GET['id'], $team['team_id'], $_GET['prob_id']);
					
                        echo '<p><b>Tip:</b> Scroll <a href="#solution">down</a> for solution form.</p>';
                        
                        $res =& db_query('problem_by_id', array($_GET['prob_id'], $_GET['id']));
                        $res->fetchInto($problem);
                        $res->free();
                        problem_display($problem);
                        
						echo '<hr />';
						echo '<a name="solution"></a>';
						
						submit_field($_GET['id'], $team['team_id'], $problem);
					} else {
						echo "<p><strong>No problem selected.</strong><br /> Please click on a problem ID to select one.</p>";
					}
				}
			} else {
				?>
<p><strong>This contest has ended!</strong><br /> Problem are open in practice mode. You can find them in our
<a href="index.php?view=explorer&amp;task=archives">archives</a>.</p>
				<?php
			}
			
			break;
	}
}

?>
