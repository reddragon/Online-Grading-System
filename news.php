<?php

require_once('config.inc.php');

#-------------------------------------------------------------------------------
# Returns latest list of annoucements (used by AJAX)

session_start();
cookie_check();

# Setup the database connection
$db =& DB::connect($cfg["db"]);
if (PEAR::isError($db)) error($db->toString());
$db->setFetchMode(DB_FETCHMODE_ASSOC);

$ret = "";
if (auth_logged_in()) {
	$res =& $db->query($cfg['sql']['bulletin_by_level'].' LIMIT 0, 10', 0);
	if (PEAR::isError($res)) error($res->toString());
	while ($res->fetchInto($row)) {
		$row['message'] = preg_replace("/<br\s*\/?>/", '', $row['message']);
		$row['message'] = substr($row['message'], 0, min(strlen($row['message']), 80));
		$ret .= "<div><a href=\"index.php?view=bulletin&amp;task=announce\">{$row['subject']}:</a> {$row['message']}</div>";
	}
	$res->free();
} else {
	$res =& db_query('relevant_contests_list');
	if ($res->numRows() == 0) {
		$ret .= "No contests scheduled.";
	}
	
	while ($res->fetchInto($row)) {
		$row['description'] = preg_replace("/<br\s*\/?>/", '', $row['description']);
		$row['description'] = substr($row['description'], 0, min(strlen($row['description']), 80));
		if ($row['running'])
			$ret .= "<div><i>Running Contest: </i>";
		else
			$ret .= "<div><i>Future Contest: </i>";
		$ret .= "<b>{$row['name']}:</b> {$row['description']}</div>";
	}
	$res->free();
}
echo $ret;

?>