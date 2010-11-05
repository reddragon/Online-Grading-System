<?php

require_once('config.inc.php');

#------------------------------------------------------------------------------
# This is a script to autosave drafts of code, called by AJAX.
#------------------------------------------------------------------------------

function error1($msg)
{
    echo $msg;
    exit;
}

session_start();
cookie_check();
if (!auth_logged_in()) error1("No user logged in.");

# Setup the database connection
$db =& DB::connect($cfg["db"]);
if (PEAR::isError($db)) error($db->toString());
$db->setFetchMode(DB_FETCHMODE_ASSOC);

db_query('clean_drafts');
if ($_POST['discard']=='1') {
    db_query('delete_draft_by_user', array($_SESSION['user_id']));
    exit;
}

# Chk for previous drafts
$res =& db_query('draft_by_user', array($_SESSION['user_id']));
if ($res->numRows() != 0) {
    $res->fetchInto($draft);
    
    if ($draft['contest_id'] == $_POST['contest_id'] && $draft['prob_id'] == $_POST['prob_id']) {
        db_query('delete_draft_by_user', array($_SESSION['user_id']));
    } else {
        $res =& db_query('contest_by_id', array($draft['contest_id']));
        $res->fetchInto($contest);
        
        $task = 'contest';
        if ($contest['end_future'] != '1')
            $task = 'archives';
        ?>
        Are your sure you want to replace previously saved draft for <i>[Contest: <?php echo $contest['name'] ?>,
        Problem: <?php echo $draft['prob_id'] ?>]</i> ?<br />
        <b><a onclick="discardDraft()" href="#solution">Yes, discard that draft</a> | <a href="index.php?view=explorer&amp;task=<?php echo $task ?>&amp;id=<?php echo $draft['contest_id'] ?>&amp;prob_id=<?php echo $draft['prob_id'] ?>#solution">
        No, resume editing that solution</a></b>
        <?php
        
        exit;
    }
}

$draft['user_id'] = $_SESSION['user_id'];
$draft['source'] = stripslashes($_POST['source']);
$draft['language'] = $_POST['language'];
$draft['contest_id'] = $_POST['contest_id'];
$draft['prob_id'] = $_POST['prob_id'];

$res =& $db->autoExecute('drafts', $draft, DB_AUTOQUERY_INSERT);
if (PEAR::isError($res)) error1($res->toString());
db_query('update_draft', $_SESSION['user_id']);

echo "saved";
exit;
?>