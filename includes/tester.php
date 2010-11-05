<?php

require_once('problem.php');
require_once('system.php');

$test_system_mode = false;  // whether system testing mode

#-------------------------------------------------------------------------------
# Tests a solution and returns the number of points earned by it.

function test_solution($contest_id, $team_id, $prob_id) {
    global $db, $test_system_mode, $cfg;
    require_once('score.php');
    
	$res =& db_query('solution_by_id', array($contest_id, $team_id, $prob_id));
	$res->fetchInto($solution);
	$res->free();

	// check for actual submission
	if ($solution['submits'] == 0) return 0;
	
    $res =& db_query('problem_by_id', array($prob_id, $contest_id));
    if (!$res->fetchInto($problem)) error("Problem $prob_id for contest ID:$contest_id is missing from DB.");
    $res->free();

    $filter = null;
    
    if ($test_system_mode) {
        $prob = problem_load($problem);
        $filter = array_diff(array_keys($prob->tests), array_keys($prob->examples));
    }
    $ret = test_cases($problem, $solution, $filter, false);
    
    $res = db_query('contest_time', $contest_id);
    $res->fetchInto($row);
    
    // Apply time & resubmit penalties to score
    $score = score_resubmit_penalty($problem['weight'], $solution['submits']-1);
    $score = score_calc($score, $solution['elapsed'], $row['time']);
    
    if ($ret[0] != 1) {
        $score *= (1-$cfg['score']['all_correct_bonus']);
        $score *= $ret[0];
    }
    
    if ($test_system_mode) {
        $res =& $db->autoExecute('solutions', array('passed' => $ret[1], 'score' => $score), 
            DB_AUTOQUERY_UPDATE, 
           'contest_id = '.$contest_id.' AND team_id = '.$team_id.' AND prob_id = \''.$prob_id."'");
        if (PEAR::isError($res)) error($res->toString());
    }
    
	return $score;
}

#-------------------------------------------------------------------------------
# Tests the given solution and returns array containing ratio of passed test cases
# to total no. of cases, and a bitmask of passed cases.
# $tests - Either an array of test case IDs to use from problem,
#           or a custom input.
# needs $solution['language'] & $solution['source']

function test_cases(&$problem, &$solution, $tests = null, $verbose = true) {
    global $cfg;
    $buf = '';
    $passed = '';
	
	// Get valid extensions for language
	$lang = $solution['language'];
	require_once($cfg['dir']['languages'].'/'.$solution['language'].'.php');
	$extn_func = 'lang_'.$lang.'_extensions';
	$extns = $extn_func();
	
    $temp_dir = tempnam('/tmp', 'ogs');
    unlink($temp_dir);
    mkdir($temp_dir);
	$source_file = $temp_dir.'/test.'.$extns[0];
	$exec_file = tempnam($temp_dir, 'ogs');
	
	// Load problem
	$prob = problem_load($problem);
	
	// Make the source code file
    $handle = fopen($source_file, 'w');
    $header_func = 'lang_'.$lang.'_header';
    fwrite($handle, $header_func());
    fwrite($handle, "\n");
	fwrite($handle, $solution['source']);
	fclose($handle);
	
	// Compile in appropriate language
	dump("<pre class=\"testing\">");
	dump("Compiling [language=$lang]...\n");
	$compile_func = 'lang_'.$lang.'_compile';
	
	if (!$compile_func($source_file, $exec_file)) {
		// Error in compilation, error in $exec_file
		$error = file_get_contents($exec_file);
		
		dump("</pre>\n<pre class=\"test_error\">");
		dump("<b>Compilation Error:</b>\n<br />$error");
		dump("</pre>\n");
	} else {
		dump("Verifying binary...<b>OK</b>");
		dump("</pre>\n");
		
		dump("<pre class=\"testing\">");
		$count = 0; // test case counter
		$correct = 0; // correct test cases
		
		// Check for test case filter
		$custom = false;
		if ($tests == null)
			$tests_to_do =& $prob->tests;
		else if (is_array($tests)) {
			$tests_to_do = array();
			
			/*
			foreach ($tests as $id) {
				array_push($tests_to_do, $prob->tests[$id]);
			}*/ 	
			
			/*The following code pushes all tests into $tests, modified for TCL*/
			/* Does not modify the calling function, just makes the params obsolete */
			
			  $tests2 = array_keys($prob->tests);	
			  foreach ($tests2 as $id)
				array_push($tests_to_do, $prob->tests[$id]);
			
			
			
		} else {
			//Custom testing
            $custom = true;
            $tests_to_do = array(new TestCase(1, null, null));
            $tests_to_do[0]->input = $tests;
            $tests_to_do[0]->output = '';
		}
		
		foreach ($tests_to_do as $test_case) {
			dump("Test case $count: ");
		
			// Determine limits
			$time_limit = $problem['time_limit'];
			$mem_limit = $problem['mem_limit'];
			if ($test_case->time_limit != null) $time_limit = $test_case->time_limit;
			if ($test_case->mem_limit != null) $mem_limit = $test_case->mem_limit;	
			
			// Special case for Java
			if ($lang == 'java')
				$mem_limit += $cfg['tester']['java_mem_overhead'];
			
			// Execute and get output in $output
			$output =& $test_case->getOutput();
			$test_input =& $test_case->getInput();
			
			$ret = system_gauge($exec_file, $test_input, $output,
					$time_limit, $mem_limit, $exec_time);
			
			if ($custom == true && $ret != 1) {
                dump("<b>done</b>\n");
                dump("</pre>\n<pre class=\"test_success\">");
                dump("<b>Execution time:</b> $exec_time seconds\n");
                dump("<b>Program output:</b>\n");
                
                // Print output returned
                dump($output);
                dump("(Please verify correctness of output yourself)\n");
			} else if ($ret == 0) {
				dump("<b>OK</b> ($exec_time seconds)\n");
                ++$correct;
                $passed .= '1';
			} else if ($ret == 1) {
				// Execution failed, error msg in $output
				dump("<b>failed </b>");
                dump("\t(Reason: $output)\n");
                $passed .= '0';
			} else if ($ret == 2) {
				dump("<b>failed</b>");
				
				
				/*Remove the comments if you wish to show which cases failed*/
				/*
                if (!$verbose) {
                    dump("\t(Reason: Output Incorrect)\n");
                } else {
                    dump("\t(Reason: Output incorrect, shown BELOW)\n");
					dump("-------------- Input used --------------\n");
					dump($test_input);
					
					dump("\n-------------- Your output --------------\n");
					dump($output);
					if ($output{strlen($output)-1} != "\n") {
						dump("\n");
					}
					
					dump("-------------- Expected output --------------\n");
					$output =& $test_case->getOutput();
					dump($output);
					if ($output{strlen($output)-1} != "\n")
						dump("\n");
					dump("\n");
                }*/
                /* And comment the following line */
                dump("\t(Reason: Output Incorrect)\n");
                $passed .= '0';
			}
			
			++$count;
		}
		dump("</pre>\n");
		
		if ($custom == false) {
            if ($correct == $count)
                $status = 'test_success';
            else if ($correct > 0)
                $status = 'test_warning';
            else
                $status = 'test_error';
            
            dump("<pre class=\"$status\">");
            dump("<b>Total cases passed: $correct/$count</b>");
            dump("</pre>\n");
        }
	}
	
	// Remove temprary files
	
    @unlink($source_file);
    @unlink($exec_file);
    @rmdir($temp_dir);
	
	if ($count == 0) return 0;
	
	if ($correct == $count)
        return array(1, $passed);
    else
        return array($correct*1.0 / $count, $passed);
}

#-------------------------------------------------------------------------------
# Tests all solutions submitted by a single team in a contest

function test_team($contest_id, $team_id, $team_name = null) {
    global $db, $test_system_mode;
    
	echo "<div class=\"test_team\">\n";
    echo "<h3>Testing team <i>$team_name</i> (ID=$team_id):</h3>";
    $res =& db_query('problems_by_team_id', $team_id);
    
    $score = 0;
	while ($res->fetchInto($row)) {
        $score += test_solution($contest_id, $team_id, $row['prob_id']);
        flush();
    }
    
    if ($test_system_mode) {
        $res =& $db->autoExecute('teams', array('score' => $score), DB_AUTOQUERY_UPDATE, 
            'contest_id = '.$contest_id.' AND team_id = '.$team_id);
        if (PEAR::isError($res)) error($res->toString());
    }
    
    printf("<p>Total team score: %.3f</p>\n", $score);
    echo "</div>\n";
    flush();
}

#-------------------------------------------------------------------------------
# Tests all solutions for all teams in a contest. (System test mode)

function test_contest($updateratings, $contest_id) {
    global $test_system_mode, $cfg, $db;
    $test_system_mode = true;
    
    // Clog up the submit queue to prevent any concurrent testing
    for ($i = 0; $i < $cfg['submit']['max_concurrent']; ++$i) {
        $res =& $db->autoExecute('submits', array('contest_id' => $contest_id, 'user_id' => 0), DB_AUTOQUERY_INSERT);
        if (PEAR::isError($res)) error($res->toString());
    }
     
    echo "<pre class=\"testing\">Starting system tests for contest_id=$contest_id...\nDisabling concurrent tests...</pre>\n";
    flush();
    
	$res =& db_query('teams_by_contest_id', $contest_id);
    while ($res->fetchInto($row)) {
        test_team($contest_id, $row['team_id'], $row['name']);
    }
    
    $res =& $db->query('DELETE FROM submits WHERE user_id = ? AND contest_id = ?', array(0, $contest_id));
    if (PEAR::isError($res)) error($res->toString());
    
    //$res =& $db->autoExecute('contests', array('tested' => 1), DB_AUTOQUERY_UPDATE, 'contest_id = '.$contest_id);
    //if (PEAR::isError($res)) error($res->toString());
    
   if($updateratings == 'true')
   { 
   	score_rate($contest_id);
   	echo "<BR>Ratings Updated<BR>";
   } 
    echo "Testing Complete.";
}

?>
