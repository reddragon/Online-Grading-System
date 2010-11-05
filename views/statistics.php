<?php
require_once ('HTML/Table.php');
require_once ('HTML/QuickForm.php');

#-------------------------------------------------------------------------------

function statistics_init() {
    global $db, $cfg;
    nav_add('Rankings', 'Members', 'statistics', 'overall');

	$current = false;
    $res =& db_query('running_contests_list');
    while ($res->fetchInto($row)) {
        nav_add('Rankings', '<b>'.$row['name'].'</b>', 'statistics', 'contest', '&amp;id='.$row['contest_id']);
        $current = true;
    }

    $res =& db_query('past_contests_list');
    # Add menu links for all upcoming & current contests
    while ($res->fetchInto($row)) {
    	if ($current == false) {
	        nav_add('Rankings', '<b>'.$row['name'].'</b>', 'statistics', 'contest', '&amp;id='.$row['contest_id']);
	        $current = true;
        } else {    	
    	    nav_add('Rankings', $row['name'], 'statistics', 'contest', '&amp;id='.$row['contest_id']);
        }
    }
}

#-------------------------------------------------------------------------------
# Show the controls for contest team registration

function statistics_display($task) {
    global $db, $cfg;
    
    switch ($task) {
        case 'overall':
            ?>
            <p><b>Overall member ratings.</b><br />Below are the present standings of all registered members,
            based on ratings calculated over their performance in various contests.</p>
            <?php
          
          
          //for($i = 0; $i < 3; $i++)
          //{  
          if(!isset($_GET['div'])) {
			$_GET['div'] = '0';
		}
          
          
          	echo '<p><b>';
          	
          	$i = 0;
          	
          	
          	for($i = '0'; $i < $cfg["tcl"]["divcount"]; $i++)
          	{
          		if($i == $_GET['div'])
          		echo $cfg["tcl"]["divisions"][$_GET['div']];
          		else
          	   		echo '<a href="index.php?view=statistics&amp;task=overall&amp;div='.$i.'">'.$cfg["tcl"]["divisions"][$i].'</a>';
          	    
          	    if($i <> $cfg["tcl"]["divcount"] - '1')
          	    	echo '&nbsp;|&nbsp;';
          	}
          	/*
          	while(true)
          	{
          		echo 'here';
          		if($i != $_GET['div'])
          		echo $cfg["tcl"]["divisions"][$_GET['div']];
          	   else
          	   	echo '<a href="index.php?view=statistics&amp;task=overall&amp;div='.$i;
          	   	
          	   	if($i != 2)
          	   	{ echo '&nbsp;|&nbsp;'; break; }
          	   		$i++;
          	};*/
          	echo '</b></p>';
            $table = new HTML_Table;
            $table->addRow(array('<a href="index.php?view=statistics&amp;task=overall">Rank</a>', 
            '<a href="index.php?view=statistics&amp;task=overall&amp;sort=handle">User Handle</a>', 
            'Name',
            '<a href="index.php?view=statistics&amp;task=overall">Rating</a>', 
            '<a href="index.php?view=statistics&amp;task=overall&amp;sort=volatility">Volatility</a>', 
            'Participated contests'), null, 'TH');
            
            if (!isset($_GET['sort'])) {
                $_GET['sort'] = 'rating DESC';
            }
                    
            $res =& $db->query('SELECT user_id, first_name, last_name, handle, rating, volatility FROM users WHERE division = '.$_GET['div'].' ORDER BY '.$_GET['sort']);
            if (PEAR::isError($res)) error($res->toString());
            
            $prev_rat = -1;
            $rank = 0;
            $carried = 0;
            
            while ($res->fetchInto($user)) {
                if ($user['handle'] == 'tester') continue;
                if (abs($prev_rat-$user['rating']) < 1e-4) {
                    ++$carried;
                } else {
                    $rank = $rank + $carried + 1;
                    $prev_rat = $user['rating'];
                    $carried = 0;
                }
                
                $res2 =& $db->query('SELECT COUNT(*) AS count FROM members WHERE user_id = '.$user['user_id']);
                if (PEAR::isError($res)) error($res->toString());
                $res2->fetchInto($count);
                $res2->free();
                
                $table->addRow(array($rank, user_handle($user['handle']), 
                    ucwords(strtolower($user['first_name'].' '.$user['last_name'])),
                    $user['rating'], $user['volatility'], $count['count']));
            }
            $res->free();
            
            $table->altRowAttributes(1, null, array ("class" => "altrow"));
            echo '<div class="overflow">'.$table->toHtml().'</div>';
            
            
		//}
            break;
        
        case 'contest':
            $res =& db_query('contest_by_id', $_GET['id']);
            $res->fetchInto($contest);
            $res->free();
            
            /*
            if ($contest['tested'] != 1 && $contest['end_future'] != 1 && !auth_user_in_group('Administrators')) {
                ?>
                <p><b>System tests for this contest have not finished yet.</b><br /> Contest statistics containing the scores and standings of each team will be put up here, once the testing phase is over. Please check back in a few minutes.</p>
                <?php
                return;
            }
            */
            $table = new HTML_Table;
		
            if ($contest['show_future'] == 1) {
                $status = 'Hidden (not activated yet)';
            } else if ($contest['begin_future'] == 1) {
                $status = 'Not started';
            } else if ($contest['end_future'] == 1) {
                $status = 'Running';
            } else {
                $status = 'Ended';
            }
            $table->addRow(array('Contest status: ', $status), null, 'TH');
            $table->addRow(array('Name: ', $contest['name']));
            $table->addRow(array('Description: ', $contest['description']));

            $table->addRow(array('Begin time: ', $contest['begin_time']));
            $table->addRow(array('End time: ', $contest['end_time']));

            if ($contest['team_size'] != 1) {
                $table->addRow(array('Max size of team: ', $contest['team_size']));
                $prefix = 'Teams';
            } else {
                $table->addRow(array('Individual event: ', 'Yes'));
                $prefix = 'Participants';
            }

            // No. of registered teams
            $res =& db_query('count_teams_by_contest_id', $_GET['id']);
            $res->fetchInto($count);
            $res->free();
            $table->addRow(array($prefix.' registered: ', $count['count']));

            $table->altRowAttributes(1, null, array ("class" => "altrow"));
            echo '<div class="overflow">'.$table->toHtml().'</div>';
            echo "<hr />\n";
            
            if ($contest['end_future'] == 1) {
                ?>  
                <p><b>Contest Standings.</b><br/></p>
                <?php
            } else {
                ?>  
                <p><b>System testing results.</b><br /> For details about particular solutions, please click on the underlined scores.</p>
                <?php
            }
            
            $header = array('Rank');
                array_push($header, 'Coder');
                array_push($header, 'College');
                
            $probs = array();
            
            $res =& db_query('problems_by_contest_id', $_GET['id']);
            while ($res->fetchInto($problem)) {
                array_push($header, '<i>'.$problem['prob_id'].'</i>');
                array_push($probs, $problem['prob_id']);
            }
            $res->free();
            array_push($header, 'Score');
            
            $table = new HTML_Table;
            $table->addRow($header, null, 'TH');
            
            if ($contest['end_future'] != 1)
                $res =& db_query('teams_by_contest_id', $_GET['id']);
            else
                $res =& db_query('standings_by_contest_id', $_GET['id']);
                
            if ($res->numRows() == 0) {
                ?>
                <p><b>No Rankings!</b><br />No teams participated in this contest.</p>
                <?php
                return;
            }
            
            $prev_score = -1;
            $rank = 0;
            $carried = 0;
            while ($res->fetchInto($team)) {
                // calculate rank
                if (abs($prev_score-$team['score']) < 1e-4) {
                    ++$carried;
                } else {
                    $rank = $rank + $carried + 1;
                    $prev_score = $team['score'];
                    $carried = 0;
                }
                
                $row = array($rank, $team['name'], $team['college']);
                
                // add users
                if ($contest['team_size'] > 1) {
                    $res2 =& db_query('users_by_team_id', array($_GET['id'], $team['team_id']));
                    if ($res2->fetchInto($user)) {
                        $users = user_handle($user['handle']);
                        while ($res2->fetchInto($user)) {
                            $users .= ', '.user_handle($user['handle']);
                        }
                    }
                    $res2->free();
                    array_push($row, $users);
                }
                
                // add prob scores
                foreach ($probs as $prob_id) {
                    $res2 =& db_query('score_by_id', array($_GET['id'], $team['team_id'], $prob_id));
                    if ($res2->fetchInto($solution) && isset($solution['language'])) {
                        if ($contest['end_future'] == 1)
                            array_push($row, sprintf("%.3f", $solution['score']));
                        else
                            array_push($row, '<a target="_blank" href="solution.php?id='.$_GET['id'].'&amp;prob_id='.$prob_id.'&amp;team_id='.$team['team_id'].'">'.sprintf("%.3f", $solution['score']).'</a>');
                    } else {
                        array_push($row, '-');
                    }
                    $res2->free();
                }
                
                // add total score
                array_push($row, sprintf("%.3f", $team['score']));
                $table->addRow($row);
            }
            
            $table->altRowAttributes(1, null, array ("class" => "altrow"));
            echo '<div class="overflow">'.$table->toHtml().'</div>';
            break;
            
        case 'profile':
            $table = new HTML_Table;
            
            $res =& db_query('user_by_handle', $_GET['handle']);
            if (!$res->fetchInto($user)) {
                ?>
                <p><b>No such handle!</b><br/>Maybe you mistyped the user handle.</p>
                <?php;
                return;
            }
            
            $table->addRow(array('User handle: ', $_GET['handle']), null, 'TH');
            $table->addRow(array('Division: ', $cfg["tcl"]["divisions"][$user['division']]));
            $table->addRow(array('Rating: ', $user['rating']));
            $table->addRow(array('Volatility: ', $user['volatility']));
            $table->addRow(array('Name: ', $user['first_name'].' '.$user['last_name']));
            $table->addRow(array('Date of Birth: ', $user['birth_date']));
            $table->addRow(array('City: ', $user['city']));
            $table->addRow(array('Country: ', $user['country']));
            $table->addRow(array('Quote: ', $user['quote']));
            
            $table->altRowAttributes(1, null, array ("class" => "altrow"));
            echo '<div class="overflow">'.$table->toHtml().'</div>';
            break;
    }
}
