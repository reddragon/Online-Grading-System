<?php

require_once('config.inc.php');

#-------------------------------------------------------------------------------
# Prints the problem version (used by AJAX)

session_start();
cookie_check();
if (!auth_logged_in()) error1("No user logged in.");

# Setup the database connection
$db =& DB::connect($cfg["db"]);
if (PEAR::isError($db)) error($db->toString());
$db->setFetchMode(DB_FETCHMODE_ASSOC);

$res =& db_query('problem_by_id', array($_POST['prob_id'], $_POST['contest_id']));

if ($res->fetchInto($row)) {
    echo "ver".$row['version'];
    exit;
} else {
    echo "invalid ".$_POST['contest_id']." and ".$_POST['prob_id'];
}

?>