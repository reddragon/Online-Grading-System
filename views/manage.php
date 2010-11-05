<?php
require_once 'HTML/Table.php';
require_once 'HTML/QuickForm.php';
require_once 'problem.php';

function manage_init() {
	global $db, $cfg;
	$res =& db_query('contests_by_manager', $_SESSION['user_id']);
	
	// Add menu links for all contests managed by current user
	while ($res->fetchInto($row)) {
		$name = 'Manage <i>'.$row['name'].'</i> contest';
		nav_add($name, 'Status','manage', 'status', '&amp;id='.$row['contest_id']);
		nav_add($name, 'Problems','manage', 'problems', '&amp;id='.$row['contest_id']);
		nav_add($name, 'Settings','manage', 'settings', '&amp;id='.$row['contest_id']);
		nav_add($name, 'Submissions','manage', 'submissions', '&amp;id='.$row['contest_id']);
	}
}

function manage_display($task) {
	global $cfg, $db, $cache;
	if ($task == '') $task = 'status';
	
	// Verify that the user has management perms for the selected contest
	$res =& db_query('contest_by_id', $_GET['id']);
	$res->fetchInto($row);
	$res->free();
	if ($_SESSION['user_id'] != $row['manager'] && !auth_user_in_group('Administrators')) {
		error("Access denied. You are not the contest-manager for this contest.");
	}
	
	switch ($task) {
	case 'status':
		$table = new HTML_Table;
		
		// Re-use $row from above	
		if ($row['show_future'] == 1) {
			$status = 'Hidden (not activated yet)';
		} else if ($row['begin_future'] == 1) {
			$status = 'Not started';
		} else if ($row['end_future'] == 1) {
			$status = 'Running';
		} else {
			$status = 'Ended';
        }
		$table->addRow(array('Contest status: ', $status), null, 'TH');
		$table->addRow(array('Name: ', $row['name']));
        $table->addRow(array('Description: ', $row['description']));
		
		$table->addRow(array('Activation time: ', $row['show_time']));
		$table->addRow(array('Begin time: ', $row['begin_time']));
        $table->addRow(array('End time: ', $row['end_time']));
		
		if ($row['team_size'] != 1) {
			$table->addRow(array('Max size of team: ', $row['team_size']));
			$prefix = 'Teams';
		} else {
			$table->addRow(array('Individual event: ', 'Yes'));
			$prefix = 'Participants';
		}
		
		// No. of registered teams
		$res =& db_query('count_teams_by_contest_id', $_GET['id']);
		$res->fetchInto($row);
		$res->free();
		$table->addRow(array($prefix.' registered: ', $row['count']));
		
		// No. of teams logged in
		$res =& db_query('count_last_teams_by_contest_id', $_GET['id']);
		$res->fetchInto($row);
		$res->free();
        $table->addRow(array($prefix.' seen in last 30 minutes: ', $row['count']));
		
		$table->altRowAttributes(1, null, array ("class" => "altrow"));
        echo '<div class="overflow">'.$table->toHtml().'</div>';
		break;
		
	case 'problems':
		// display problem info as table
		$table = new HTML_Table;
		$res =& db_query('problems_by_contest_id', $_GET['id']);
		if (!$res->fetchInto($row)) {
			?>
<p>No problems added yet.</p>			
			<?php
		} else {
			// extra attributes
			$row['content'] = null;
			$row['actions'] = null;
			$table->addRow(array_keys($row), null, 'TH');
		
			while ($row) {
				$row['content'] = "<a href=\"index.php?view=manage&amp;task=show_problem&amp;id={$_GET['id']}&amp;prob_id={$row['prob_id']}\">show</a>";
				$row['actions'] = "<a href=\"index.php?view=manage&amp;task=edit_problem&amp;id={$_GET['id']}&amp;prob_id={$row['prob_id']}\">edit</a>, ".
					"<a href=\"index.php?view=manage&amp;task=del_problem&amp;id={$_GET['id']}&amp;prob_id={$row['prob_id']}\">delete</a>";
				$table->addRow(array_values($row));
				$res->fetchInto($row);
			}
			$res->free();
			
			// display tables
			$table->altRowAttributes(1, null, array ("class" => "altrow"));
            echo '<div class="overflow">'.$table->toHtml().'</div>';
		}
		echo "<hr />";
		
		// form for adding a problem
		$form = new HTML_QuickForm('problemAddForm', 'post', selflink());
		$form->addElement('header', null, 'Add a problem');
		$form->addElement('text', 'prob_id', 'Name (one word ID): ');
		$form->addElement('text', 'summary', 'Summary: ');
		$form->addElement('text', 'weight', 'Points weightage: ');
		$form->addElement('text', 'time_limit', 'Time limit: ');
		$form->addElement('text', 'mem_limit', 'Memory limit: ');
		$elem =& $form->addElement('textarea', 'content', 'Problem content (XML): ');
		$elem->setRows(10);
		$elem->setCols(80);
		$form->addElement('submit', null, 'Submit');
		
		$form->applyFilter('prob_id', 'trim');
		$form->applyFilter('summary', 'trim');
		$form->applyFilter('weight', 'trim');
		$form->applyFilter('time_limit', 'trim');
		$form->applyFilter('mem_limit', 'trim');
		
		$form->addRule('prob_id', 'Problem ID is required', 'required', null, 'client');
		$form->addRule('summary', 'Problem summary is required', 'required', null, 'client');
		$form->addRule('weight', 'Points weightage is required', 'required', null, 'client');
		$form->addRule('time_limit', 'Time limit is required', 'required', null, 'client');
		$form->addRule('mem_limit', 'Memory limit is required', 'required', null, 'client');
		$form->addRule('content', 'Problem content in XML is required', 'required', null, 'client');
		
		if ($form->validate()) {
			$data = $form->getSubmitValues();
			
			$errs = problem_check($data['content']);
			if ($errs == null) {
				$data['contest_id'] = $_GET['id'];
				$res =& $db->autoExecute('problems', $data, DB_AUTOQUERY_INSERT);
				if (PEAR::isError($res)) error($res->toString());
				
                $cache->remove(problem_cache_id($_GET['id'], $data['prob_id']).'.htm');
                $cache->remove(problem_cache_id($_GET['id'], $data['prob_id']).'.prob');
				redirect('index.php?view=manage&task=problems&id='.$_GET['id']);
			} else {
				?>
<p><b>Error:</b> The problem could not be added due to the following errors encountered while
parsing the problem XML file. Please fix them and try submitting again.</p>
				<?php
				echo "<ol class=\"errors\">\n";
				foreach ($errs as $line) {
					echo "<li>$line</li>\n";
				}
				echo "</ol>\n<hr />\n";
			}
		}
		$form->display();
		
		break;
		
	case 'del_problem':
		db_query('del_problem_by_id', array($_GET['prob_id'], $_GET['id']));
		redirect('index.php?view=manage&task=problems&id='.$_GET['id']);
		break;
		
	case 'edit_problem':
		$res =& db_query('problem_by_id', array($_GET['prob_id'], $_GET['id']));
		$res->fetchInto($row);
        $res->free();
        
        // Get XML content too
        $res =& db_query('problem_content_by_id', array($_GET['prob_id'], $_GET['id']));
        $res->fetchInto($row2);
        $res->free();
        $row['content'] =& $row2['content'];
		
		// form for editing a problem
		$form = new HTML_QuickForm('problemAddForm', 'post', selflink());
		$form->addElement('header', null, 'Edit a problem');
		$form->addElement('text', 'prob_id', 'Name (one word ID): ');
		$form->addElement('text', 'summary', 'Summary: ');
		$form->addElement('text', 'weight', 'Points weightage: ');
		$form->addElement('text', 'time_limit', 'Time limit: ');
		$form->addElement('text', 'mem_limit', 'Memory limit: ');
		$elem =& $form->addElement('textarea', 'content', 'Problem content (XML): ');
		$elem->setRows(10);
        $elem->setCols(80);
		$form->addElement('submit', null, 'Submit');
		
		$form->applyFilter('prob_id', 'trim');
		$form->applyFilter('summary', 'trim');
		$form->applyFilter('weight', 'trim');
		$form->applyFilter('time_limit', 'trim');
		$form->applyFilter('mem_limit', 'trim');
		
		$form->addRule('prob_id', 'Problem ID is required', 'required', null, 'client');
		$form->addRule('summary', 'Problem summary is required', 'required', null, 'client');
		$form->addRule('weight', 'Points weightage is required', 'required', null, 'client');
		$form->addRule('time_limit', 'Time limit is required', 'required', null, 'client');
		$form->addRule('mem_limit', 'Memory limit is required', 'required', null, 'client');
		$form->addRule('content', 'Problem content in XML is required', 'required', null, 'client');
		
		$form->setDefaults($row);
		if ($form->validate()) {
            $data = $form->getSubmitValues();
			
			$errs = problem_check($data['content']);
			if ($errs == null) {
                //$data['contest_id'] = $_GET['id'];
                $data['version'] = $row['version'] + 1; // increment version
                
				$res =& $db->autoExecute('problems', $data, DB_AUTOQUERY_UPDATE, 'contest_id='.
					$_GET['id']." AND prob_id='".$data['prob_id']."'");
                if (PEAR::isError($res)) error($res->toString());
                                    
                $cache->remove(problem_cache_id($_GET['id'], $data['prob_id']).'.htm');
                $cache->remove(problem_cache_id($_GET['id'], $data['prob_id']).'.prob');
				redirect('index.php?view=manage&task=problems&id='.$_GET['id']);
			} else {
				?>
<p><b>Error:</b> The changes could not be saved due to the following errors encountered while
parsing the problem XML file. Please fix them and try submitting again.</p>
				<?php
				echo "<ol class=\"errors\">\n";
				foreach ($errs as $line) {
					echo "<li>$line</li>\n";
				}
				echo "</ol>\n<hr />\n";
			}
		}
		$form->display();
		break;
		
	case 'show_problem':
        $res =& db_query('problem_by_id', array($_GET['prob_id'], $_GET['id']));
        $res->fetchInto($problem);
        $res->free();
        problem_display($problem);
		break;

	case 'settings':
		// Re-using $row from above
	
		// form for editing the contest
		$form = new HTML_QuickForm('contestEditForm', 'post', selflink());
		$form->addElement('header', null, "Edit contest {$row['name']} (id: {$row['contest_id']})");
		$form->addElement('text', 'name', 'Name: ');
		$form->addElement('text', 'description', 'Description: ');
		$elem =& $form->addElement('text', 'team_size', 'Size of team: ');
		$elem->setValue('1');
		$date = getdate();
		$form->addElement('date', 'show_time', 'Activation time: ',
			array('format' => 'dMY H:i', 'minYear' => $date['year'], 'maxYear' => $date['year']+5));
		$form->addElement('date', 'begin_time', 'Begin time: ',
			array('format' => 'dMY H:i', 'minYear' => $date['year'], 'maxYear' => $date['year']+5));
		$form->addElement('date', 'end_time', 'End time: ',
			array('format' => 'dMY H:i', 'minYear' => $date['year'], 'maxYear' => $date['year']+5));
		$form->addElement('text', 'rules', 'Rules URL: ');
		$form->addElement('submit', null, 'Submit');
		
		// convert date format and store default values
        $row['show_time'] = sql2form_datetime($row['show_time']);
		$row['begin_time'] = sql2form_datetime($row['begin_time']);
		$row['end_time'] = sql2form_datetime($row['end_time']);
		$form->setDefaults($row);
		
		$form->applyFilter('name', 'trim');
		$form->applyFilter('description', 'trim');
		$form->applyFilter('team_size', 'trim');
		
		$form->addRule('name', 'Contest name is required.', 'required', null, 'client');
		$form->addRule('team_size', 'Team size is required.', 'required', null, 'client');
		
		// validate or display form
		if ($form->validate()) {
			$data = $form->getSubmitValues();
			$data['show_time'] = form2sql_datetime($data['show_time']);
			$data['begin_time'] = form2sql_datetime($data['begin_time']);
			$data['end_time'] = form2sql_datetime($data['end_time']);
			$db->autoExecute('contests', $data, DB_AUTOQUERY_UPDATE, 'contest_id='.$_GET['id']);
			
			if (PEAR::isError($res)) error($db->toString());
			redirect('index.php?view=manage&id='.$_GET['id']);
		} else {
			$form->display();
		}
		break;
		
	case 'submissions':
		// Re-use $row from above
        if ($row['end_future'] != '1') {
            // Contest has ended, show system test button
            if ($row['tested'] != 1) {
                ?>
    <p>Contest has ended. 
    <a class="button" href="index.php?view=manage&amp;&amp;task=test&amp;updateratings=false&amp;id=<?php echo $_GET['id'] ?>">Test and grade all submissions.</a>
    <a class="button" href="index.php?view=manage&amp;task=test&amp;updateratings=true&amp;id=<?php echo $_GET['id'] ?>">Update Ratings</a>

    </p>
                <?php
            } else {
                ?>
    <p>Contest has ended and system tests are over.
    <a class="button" href="index.php?view=manage&amp;task=test&amp;id=<?php echo $_GET['id'] ?>">Re-run system tests.</a>
    </p>
                <?php
            }
		}
		
		// Show table of all solutions in the contest
		$table = new HTML_Table;
		$res =& db_query('solutions_by_contest_id', $_GET['id']);
		
		if (!$res->fetchInto($row)) {
			// If no solutions in yet
			?>
<p>Sorry, no solutions have been submitted yet.</p>
			<?php
		} else {
            $table->addRow(array_keys($row), null, 'TH');
            
            if ($row['score'] == '') $row['score'] = 'n/a';
            if ($row['passed'] == '') $row['passed'] = 'n/a' ;
			$table->addRow(array_values($row));
		
            while ($res->fetchInto($row)) {
                if ($row['score'] == '') $row['score'] = 'n/a';
                if ($row['passed'] == '') $row['passed'] = 'n/a' ;
                
				$table->addRow(array_values($row));
			}
			$table->altRowAttributes(1, null, array ("class" => "altrow"));
            echo '<div class="overflow">'.$table->toHtml().'</div>';
		}
		break;
		
	case 'test':
        require_once 'tester.php';
        ob_end_clean();
        html_reset();
        
        html_header(null, $cfg["dir"]["themes"].'/'.$_SESSION["theme"].'.css',	// stylesheet
            $cfg["dir"]["themes"].'/'.$_SESSION["theme"].'-ie.css',	// IE-specific overrides
        null, "submit_frame");
        
        $contest_id = $_GET['id'];
        $update_ratings = $_GET['updateratings'];
        session_write_close(); 
        test_contest($update_ratings, $contest_id);
        
        echo ' <a class="white" href="index.php?view=statistics&amp;task=contest&amp;id='.$_GET['id'].'">See the results.</a>';
        html_footer();
        exit;
	}
}

?>
