<?php

require_once 'HTML/QuickForm.php';
require_once 'score.php';
require_once 'problem.php';

#-------------------------------------------------------------------------------
# Records the opening of a problem by a team

function submit_opened($contest_id, $team_id, $prob_id) {
	global $cfg;
	
	$res = db_query('solution_by_id', array($contest_id, $team_id, $prob_id));
	if ($res->numRows() == 0) {
		$res = db_query('open_solution', array($contest_id, $team_id, $prob_id));
	}
}

#-------------------------------------------------------------------------------
# Records a submission for further processing in submits table

function submit_record($contest_id, $problem_id, &$solution, $mode = '') {
    global $cfg, $db;
    
    // Expire old submits
    db_query('expire_submits', $cfg['submit']['expire']);
    
	// Some sanity checks
	$res =& db_query('pending_user_submits', $_SESSION['user_id']);
	if ($row['count'] != 0) {
?>
<p class="lower"><b>You already have one test in progress.</b> Please wait for that to end first.</p>
<?php
	} else {
		$res = db_query('pending_submits');
		$res->fetchInto($row);
		if ($row['count'] >= $cfg['submit']['max_concurrent']) {
?>
<p class="lower"><b>The tester has reached its maximum concurrent capacity.
Please retry in a minute.</b></p>
<?php
		} else {
			// All clear, we can proceed with testing/submission
			
			db_query('insert_submit', array($contest_id, $problem_id,
				$_SESSION['user_id'], $solution['source'], $solution['language'], $mode));
			
			$res =& db_query('submit_by_user', $_SESSION['user_id']);
			$res->fetchInto($row);
			$res->free();
			
			if ($mode != 'submit' && strlen($solution['custom']) > 0) {
				$res =& $db->autoExecute('submits', array('custom' => $solution['custom']), DB_AUTOQUERY_UPDATE,
					'submit_id = '.$row['submit_id']);
				if (PEAR::isError($res)) error($res->toString());
			}
			
			return $row['submit_id'];
		}
	}
	
	return 0; // indicate error
}

#-------------------------------------------------------------------------------
# Shows a solution submission field (text-area)

function submit_field($contest_id, $team_id, &$problem, $practiceMode = false) {
	global $cfg;
    
    if ($practiceMode == true) {
        // Check for running contests in practice mode
        $res =& db_query('count_running_contests');
        $res->fetchInto($count);
        if ($count['count'] > 0) {
            ?>
            <p class="system_info"><b>Sorry, solution form is disabled in practice mode.</b><br />
            This is to preserve server resources for the running contest. Practice submissions will be re-enabled when that contest is over.</p>
            <?php
            return;
        }
    }
	
    html_include_js($cfg['dir']['scripts'].'/editor.js');
	$langs = language_list();
	$languages = array();
    foreach ($langs as $lang) {
        require_once($cfg['dir']['languages'].'/'.$lang.'.php');
        
        $func = 'lang_'.$lang.'_description';
        $languages[$lang] = $func();
    }

    $lang = $langs[0];
    $source = '';
    $res =& db_query('draft_by_user', array($_SESSION['user_id']));
    if ($res->fetchInto($draft)) {
        if ($draft['contest_id'] == $contest_id && $draft['prob_id'] == $problem['prob_id']) {
            $lang = $draft['language'];
            $source = $draft['source'];
        }
    }
    
	// Code editing form
	$form = new HTML_QuickForm('submitForm', 'post', selflink().'#results');
    $e =& $form->addElement('select', 'language', 'Language: ', $languages);
    if (!isset($_POST['language'])) $e->setValue($lang);
        
    $e =& $form->addElement('textarea', 'source', 'Code: ', array('rows' => 12, /*'cols' => 70, */'class' => 'editor'));
    if (!isset($_POST['source'])) $e->setValue($source);
    
    $form->addElement('html', "\n".'<tr><td align="right" valign="top"><div id="custom_input1" style="display:none"><b>Custom<br/>Input: </b></div></td>
    		<td><div id="custom_input2" style="display:none"><textarea rows="4" class="editor" name="custom">'.$_POST['custom'].'</textarea></div></td></tr>'."\n");
    
    $form->addElement('html', "\n".'<tr><td align="right" valign="top"></td><td valign="top" align="left"><input name="test" value="Compile and Test" type="submit"/>
    		<input onclick="handleTestButton()" id="custom_button" name="customb" value="Test with custom input" type="button" />'."\n");
	if ($practiceMode == false) {
        $form->addElement('html', ' <input name="submitit" value="Submit" type="submit" /></td></tr>');
    } else {
        $form->addElement('html', '</td></tr>');   
    }
	
	$form->applyFilter('source', 'trim');
	//$form->addRule('source', 'Source code area is blank! Refusing to accept.', 'required', null, 'client');
	
	// Display some text & the form
?>
<div class="mimic_para">
<a id="shortcuts_link" onclick="toggleShowShortcuts()" href="#solution">[+] Useful Editor Shortcuts:</a>
<div id="shortcuts"></div>
</div>
<?php

    html_javascript_check();
    html_rounded_box_open();
    $form->display();
    echo '<div id="edit_status"></div>';
    html_rounded_box_close();
	
	if ($form->validate()) {
		echo "<a name=\"results\"></a>";
?>
<p class="lower"><b>Tester:</b><br /> Please be patient while your code is being compiled and tested.
Results will be displayed in the frame below.</p>
<?php
        $solution =& $form->getSubmitValues();
		
		$mode = "";
		if ($practiceMode) {
			$mode = "practice";
		} else if (isset($solution['submitit'])) {
			$mode = "submit";
		}
		
        if ($id = submit_record($contest_id, $problem['prob_id'], $solution, $mode)) {
            html_rounded_box_open();
            ?>
<iframe width="90%" height="300" scrolling="yes" src="<?php echo "progress.php?id=$id" ?>">
<!-- Following gets displayed if IFRAME is not supported -->
<b>Your browser is not supported!</b><br />
Please upgrade your browser, as it lacks basic support for inline-frames,
which is necessary for this feature. Recommended browsers are 
<a href="http://www.getfirefox.com">Mozilla/Firefox</a>,
Internet Explorer 5.0+ and Opera 7.0+.
</iframe>
            <?php
            html_rounded_box_close();
		}
	}
}

#-------------------------------------------------------------------------------
# Performs testing and (optionally) submission

function submit_perform($contest_id, $team_id, &$problem, &$solution, $mode = null, $custom = null) {
	global $cfg;
    require_once('tester.php');
	
	$prob = problem_load($problem);
	
	// Compile and Test first
	if ($custom != null) {
		$ret = test_cases($problem, $solution, $custom);
    } else if ($mode == 'practice') {
        $ret = test_cases($problem, $solution);
	} else {
		// only test examples
		$ret = test_cases($problem, $solution, array_keys($prob->examples));
    }
    $result = $ret[0];
	
	if ($mode == 'submit') {
		// Submit after testing
		if ($result == 1) {
			echo "<pre class=\"testing\">\n";
			echo "Checking for previous submission(s)...";
			
			$res = db_query('solution_by_id', array($contest_id, $team_id, $problem['prob_id']));
			$res->fetchInto($row);
			$res->free();
			
			$initial = $problem['weight'];
			if ($row['submits'] == '0') {
				echo "none\n";
			} else {
				echo "found\n";
                echo "Resubmission count...{$row['submits']}\n";
                
                $initial1 = score_resubmit_penalty($initial, $row['submits']);
				$percent = (1-$initial1/$initial) * 100;
                echo "Cumulative penalty...{$percent}%\n";
                
                $initial = $initial1;
			}
			
			$res = db_query('contest_time', $contest_id);
			$res->fetchInto($row2);
			$score = score_calc($initial, $row['elapsed2'], $row2['time']);
			
			db_query('submit_solution', array($solution['language'], 
                $solution['source'], $score, $contest_id, $team_id, $problem['prob_id']));
			// Now that user has submitted, we clear his draft
			db_query('delete_draft_by_user', $_SESSION['user_id']);
			
			echo "</pre>\n<pre class=\"test_success\">";
			echo "<b>Solution to problem </b><i>{$problem['prob_id']}</i><b> submitted for ";
			printf("%.3f points!</b>\n", $score);
			echo "Note: The above score is not final. The actual score will be determined by the system tests.";
			echo "</pre>\n";
		} else {
			echo "<pre class=\"test_error\"><b>Solution failed to pass all tests. Refusing to submit!</b></pre>";
		}
	}
}

?>
