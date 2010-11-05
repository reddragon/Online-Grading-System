<?php

#------------------------------------------------------------------------------
# This is the test/submit progress display script.
#------------------------------------------------------------------------------

require_once('config.inc.php');
require_once('submit.php');

# Start output buffering
ob_start();

# Force the default theme, if no theme selected by user
if (!isset($_SESSION['theme'])) {
	$_SESSION['theme'] = $cfg["site"]["theme"];
}

html_header(null, $cfg["dir"]["themes"].'/'.$_SESSION["theme"].'.css',	// stylesheet
			$cfg["dir"]["themes"].'/'.$_SESSION["theme"].'-ie.css',	// IE-specific overrides
			null, "submit_frame");

# Open a database connection
$db =& DB::connect($cfg["db"]);
if (PEAR::isError($db)) error($db->toString());
$db->setFetchMode(DB_FETCHMODE_ASSOC);

# Start session handling, check for cookie-support, needed for auth support
session_start();
cookie_check();

# Sanity checks
if (!isset($_GET['id'])) error("No submit ID given.");
if (!auth_logged_in()) error('No user logged in.');

# Get submit row
$res = db_query('submit_by_id', array($_GET['id'], $_SESSION['user_id']));
if ($res->numRows() == 0) error('No submits pending by this user.<br />Note: You may see this error due to a bug in some versions of Opera 8.0. Please upgrade, or try another browser (www.getfirefox.com).');
$res->fetchInto($row);
$res->free();

$solution['language'] =& $row['language'];
$solution['source'] =& $row['source'];
$mode = $row['mode'];

if ($_COOKIE['testMode'] == 'custom') {
	$custom = $row['custom'];
	if ($custom == null)
		$custom = "\n";
} else
	$custom = null;

if ($mode == 'practice') {
	$rowTeam['team_id'] = null;
} else {
	$resTeam = db_query('user_and_contest', array($_SESSION['user_id'], $row['contest_id']));
	$resTeam->fetchInto($rowTeam);
}

session_write_close();
$resProb = db_query('problem_by_id', array($row['prob_id'], $row['contest_id']));
$resProb->fetchInto($rowProb);

# We dont want to buffer test progress
ob_end_flush();

# Delete submit after use
db_query('delete_submit', array($_GET['id'], $_SESSION['user_id']));

# Here we go!
submit_perform($row['contest_id'], $rowTeam['team_id'], $rowProb, $solution, $mode, $custom);

html_footer();
exit(0);

?>