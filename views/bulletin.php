<?php
require_once ('HTML/Table.php');
require_once ('HTML/QuickForm.php');

#-------------------------------------------------------------------------------

function bulletin_init()
{
    nav_add('Bulletin', 'Announcements', 'bulletin', 'announce');
    nav_add('Bulletin', 'Shoutbox', 'bulletin', 'public');
    nav_add('Bulletin', 'Analysis', 'bulletin', 'analysis');
    
    if (auth_user_in_group('Administrators'))
        nav_add('Bulletin', 'Admin Chat', 'bulletin', 'admin');
}

#-------------------------------------------------------------------------------

function bulletin_navbar($start, $count)
{
	echo '<div class="bulletin_nav">';
    echo '<a href="index.php?view=bulletin&amp;task='.$_GET['task'].'&amp;start='.max($start-$count, 0).'">&lt;&lt;</a>';
    echo ' [Posts '.$start.' to '.($start+$count-1).'] ';
    echo '<a href="index.php?view=bulletin&amp;task='.$_GET['task'].'&amp;start='.max($start+$count, 0).'">&gt;&gt;</a>';
    echo '</div>';
}

function bulletin_tabulate($level)
{
    global $db, $cfg;
    $start_limit = 0;
    if (array_key_exists('start', $_GET)) $start_limit = $_GET['start'];
    $count_limit = 15;
    if (array_key_exists('count', $_GET)) $start_limit = $_GET['count'];
    
    $is_admin = auth_user_in_group('Administrators');
    if (!$is_admin && $level==2) {
        error("Admin Chat not authorised for non-admins.");
    }
    
    if ($level > 2 || $is_admin) {
        $form = new HTML_QuickForm('shoutForm', 'post', selflink());
        $form->addElement('header', null, 'Post your message here:');
        $form->addElement('text', 'subject', 'Subject: ');
        $elem =& $form->addElement('checkbox', 'addbreaks', null);
		$elem->setChecked(false);
		$elem->setText('Allow HTML formatting tags. Makes line-break tags necessary.');
		$elem =& $form->addElement('textarea', 'message', 'Shout Message: ');
        $elem->setRows(6);
        $elem->setCols(60);
        $form->addElement('submit', 'submit', 'Post');
        $form->addRule('subject', 'Subject must be maximum 100 characters.', 'maxlength', 100, 'client');
        
        if ($form->validate()) {
            $data = $form->getSubmitValues();
            if (!isset($data['addbreaks'])) {
				$data['addbreaks'] = 1;
				$data['message'] = htmlentities($data['message']);
			} else
				$data['addbreaks'] = 0;
            
            db_query('bulletin_post', array($_SESSION['user_id'], $data['subject'], $data['message'], $level, $data['addbreaks']));
            redirect('index.php?view=bulletin&task='.$_GET['task']);
        }
    }
    
    if ($level > 2 || $is_admin) {
        $form->display();
        echo '<hr />';
    }
    
    bulletin_navbar($start_limit, $count_limit);
    
    $res =& $db->query($cfg['sql']['bulletin_by_level']." LIMIT $start_limit, $count_limit", array($level));
    if (PEAR::isError($res)) error($res->toString());
    $table = new HTML_Table;
    
    if ($res->numRows() == 0) {
        $table->addRow(array('Sorry, no more messages on this bulletin page.'));
    }
    
    while ($res->fetchInto($row)) {        
        $action = "";
        if ($row['poster_id'] == $_SESSION['user_id'])
        		$action = ' [<a href="index.php?view=bulletin&task=edit&prev='.$_GET['task'].'&id='.$row['post_id'].'">edit</a>, <a href="index.php?view=bulletin&task=delete&prev='.$_GET['task'].'&id='.$row['post_id'].'">delete</a>]';
        	else if ($is_admin)
        		$action = ' [<a href="index.php?view=bulletin&task=delete&prev='.$_GET['task'].'&id='.$row['post_id'].'">delete</a>]';
        $table->addRow(array('Subject: <b>'.$row['subject'].'</b>', 'Posted by: <b>'.user_handle($row['handle']).'</b>'.$action.' on <i>'.$row['posted'].'</i>'));
        
        if ($row['addbreaks'] == 1)
	        $table->addRow(array('<div class="message">'.preg_replace('/\n/', '<br />', $row['message']).'</div>'), array(colspan => "2"));
		else
			$table->addRow(array('<div class="message">'.$row['message'].'</div>'), array(colspan => "2"));
    }
    $table->altRowAttributes(0, array ("class" => "altrow"), null);
    echo $table->toHtml();
    
    bulletin_navbar($start_limit, $count_limit);
}

#-------------------------------------------------------------------------------

function bulletin_display($task)
{
    global $db;
    
    switch ($task) {
        case 'announce':
            bulletin_tabulate(0);
            break;
            
        case 'show':
       		$res =& db_query('bulletin_by_id', array($_GET['id']));
			$res->fetchInto($row);
			
			$res =& db_query('user_by_id', array($row['poster_id']));
			$res->fetchInto($user);
			
			echo "<h1>{$row['subject']}</h1>";
//        $table->addRow(array('Subject: <b>'.$row['subject'].'</b>', 'Posted by: <b>'.user_handle($row['handle']).'</b>'.$action));
        
	        if ($row['addbreaks'] == 1)
		        echo('<div class="message">'.preg_replace('/\n/', '<br />', $row['message']).'</div>'.'<i>The above message was posted by <b>'.user_handle($user['handle']).'</b> on '.$row['posted'].'.</i>');
			else
				echo('<div class="message">'.$row['message'].'</div>'.'<i>The above message was posted by <b>'.user_handle($user['handle']).'</b> on '.$row['posted'].'.</i>');
			break;
            
        case 'analysis':
            bulletin_tabulate(1);
            break;
            
        case 'admin':
            bulletin_tabulate(2);
            break;
        
        case 'public':
            bulletin_tabulate(3);
            break;
            
        case 'edit':
			$form = new HTML_QuickForm('shoutForm', 'post', selflink());
			$form->addElement('header', null, 'Post your message here:');
			$form->addElement('text', 'subject', 'Subject: ');
			$elem =& $form->addElement('checkbox', 'addbreaks', null);
			$elem->setChecked(false);
			$elem->setText('Allow HTML formatting tags. Makes line-break tags necessary.');
			$elem =& $form->addElement('textarea', 'message', 'Shout Message: ');
			$elem->setRows(20);
			$elem->setCols(60);
			$form->addElement('submit', null, 'Post');
			$form->addRule('subject', 'Subject must be maximum 100 characters.', 'maxlength', 100, 'client');
			
			$res =& db_query('bulletin_by_id', array($_GET['id']));
			$res->fetchInto($row);
			if ($row['addbreaks'] == 1) {
				unset($row['addbreaks']);
			} else {
				$row['addbreaks'] = 1;
			}
				
			$form->setDefaults($row);
			$res->free();
     	   
			if ($form->validate()) {
				$data = $form->getSubmitValues();
				if (!isset($data['addbreaks'])) {
					$data['addbreaks'] = 1;
					$data['message'] = htmlentities($data['message']);
				} else
					$data['addbreaks'] = 0;
				
				$res =& $db->autoExecute('bulletin', $data, DB_AUTOQUERY_UPDATE, 'post_id='.$_GET['id']);
				if (PEAR::isError($res)) error($res->toString());
				
				redirect('index.php?view=bulletin&task='.$_GET['prev']);
			} else {
				$form->display();
			}
			break;
		
		case 'delete':
			db_query('delete_bulletin_by_id', array($_GET['id']));
			redirect('index.php?view=bulletin&task='.$_GET['prev']);
			break;
    }
}
