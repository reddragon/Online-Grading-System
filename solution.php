<?php

require_once('config.inc.php');

#------------------------------------------------------------------------------
# This is a script to display a team's own submissions.
#------------------------------------------------------------------------------

ob_start();

session_start();
cookie_check();
if (!auth_logged_in()) error("No user logged in.");

html_header(null, $cfg["dir"]["themes"].'/'.$_SESSION["theme"].'.css',	// stylesheet
    $cfg["dir"]["themes"].'/'.$_SESSION["theme"].'-ie.css',	// IE-specific overrides
null, "solution");

# Setup the database connection
$db =& DB::connect($cfg["db"]);
if (PEAR::isError($db)) error($db->toString());
$db->setFetchMode(DB_FETCHMODE_ASSOC);

$res =& db_query('contest_by_id', $_GET['id']);
if (!$res->fetchInto($contest)) error("No such contest.");

if ($contest['end_future'] == '1') {
    $res =& db_query('user_and_contest', array($_SESSION['user_id'], $_GET['id']));
    if (!$res->fetchInto($team)) error("You are not registered for this contest.");
} else {
    $team['team_id'] = $_GET['team_id'];
}

$res =& db_query('solution_by_id', array($_GET['id'], $team['team_id'], $_GET['prob_id']));
if (!$res->fetchInto($soln)) error("No submitted solution for this problem yet.");

if (!isset($soln['language'])) error("No submitted solution for this problem.");
require_once($cfg['dir']['languages'].'/'.$soln['language'].'.php');
$func = 'lang_'.$soln['language'].'_description';
echo '<b>Language:</b> '.$func();

echo '<pre class="solution_code">';
echo html_escape($soln['source']);
echo '</pre>';
ob_end_flush();

if ($contest['end_future'] != '1' && ($contest['tested'] == 1 || auth_user_in_group('Administrators'))) {
    // Show test-case passing summary
    require_once('problem.php');
    require_once('HTML/Table.php');
    
    $table = new HTML_Table;
    $table->addRow(array('Test Case', 'Passed', 'Input'), null, 'TH');
    
    $res =& db_query('problem_by_id', array($_GET['prob_id'], $_GET['id']));
    $res->fetchInto($problem);
    $prob = problem_load($problem);

    $show_eg = (strlen($soln['passed']) == count($prob->tests));
    $i = '0';
    foreach ($prob->tests as $id => $case) {
        if (!$show_eg && array_key_exists($id, $prob->examples))
            continue;
        
        $passed = "no";
        if ($soln['passed'][$i] == '1') $passed = "yes";
        
        $table->addRow(array($i, $passed, '<pre>'.$case->getInput().'</pre>'));
        ++$i;
    }
    
    $table->altRowAttributes(1, null, array ("class" => "altrow"));
    echo '<div class="overflow">'.$table->toHtml().'</div>';
}

html_footer();

?>
