<?php
require_once('HTML/Table.php');
require_once('HTML/QuickForm.php');

function admin_init() {
	view_set('admin');
	nav_add('Administration', 'Contests', 'admin', 'contests');
	nav_add('Administration', 'Users', 'admin', 'users');
	nav_add('Administration', 'Groups', 'admin', 'groups');
	nav_add('Administration', 'Views', 'admin', 'views');
	nav_add('Administration', 'Shell', 'admin', 'shell');
	nav_add('Administration', 'Uploader', 'admin', 'uploader');
}

function admin_display($task) {
	global $db, $cfg;
	if ($task == NULL) $task = 'contests';
	
	switch($task) {
	case 'users':
		$table = new HTML_Table;
	
		$res =& db_query('users_list');
		$res->fetchInto($row);
	
		// add users table headers
		$headers = array_keys($row);
		array_push($headers, 'groups');
		array_push($headers, 'actions');
		$table->addRow($headers, null, 'TH');
		
		// add user records
		while ($row) {
			$res2 =& db_query('groups_by_user_id', $row['user_id']);
			
			// get list of gourps for this user
			$groups = '';
			$res2->fetchInto($row2);
			while ($row2) {
				$groups .= $row2['name'];
				if ($res2->fetchInto($row2))
					$groups .= ', ';
			}
			$res2->free();
			
			array_push($row, $groups);
			// actions
			array_push($row, "<a href=\"index.php?view=admin&amp;task=edit_user&amp;id={$row['user_id']}\">edit</a>".
							", <a href=\"index.php?view=admin&amp;task=del_user&amp;id={$row['user_id']}\">delete</a>");
			$table->addRow(array_values($row));
			$res->fetchInto($row);
		}
		$res->free();
		
		$table->altRowAttributes(1, null, array("class" => "altrow"));
        echo '<div class="overflow">'.$table->toHtml().'</div>';
		
		break;
		
	case 'del_user':
		db_query('del_user_by_id', $_GET['id']);
		db_query('del_user_perms_by_id', $_GET['id']);		
		redirect('index.php?view=admin&task=users');
		break;
		
	case 'edit_user':
		// user id to edit given as arg
		$res =& db_query('groups_by_user_id', $_GET['id']);
		
		// get list of all groups for this user 
		$user_groups = array();
		while ($res->fetchInto($row)) {
			array_push($user_groups, $row['group_id']);
		}
		$res->free();
		
		// get hanndle of user
		$res =& db_query('user_by_id', $_GET['id']);
		$res->fetchInto($row);
		$handle = $row['handle'];
		$res->free();
	
		$form = new HTML_QuickForm('userForm', 'post', 'index.php?view=admin&task=edit_user&id='.$_GET['id']);
		$form->addElement('header', null, 'Groups for user '.$handle.' (id: '.$_GET['id'].')');
		
		// get list of all available groups
		$res =& db_query('groups_list');
		
		// add checkbox for each group
		$groups = array();
		while ($res->fetchInto($row)) {
			$elem =& $form->addElement('checkbox', $row['group_id'], $row['name']);
			if (in_array($row['group_id'], $user_groups))
				$elem->setChecked(true);
			$groups[$row['group_id']] = $row['name'];
		}
		$res->free();
		$form->addElement('submit', 'submit', 'Apply Changes');
		
		if ($form->validate()) {
			$data = $form->getSubmitValues();
			foreach ($groups as $gid => $name) {
				$elem =& $form->getElement($gid);
				if ($data[$gid] == 1) {
					auth_set_perm($_GET['id'], $gid);
					$elem->setChecked(true);
				} else {
					auth_clear_perm($_GET['id'], $gid);
					$elem->setChecked(false);
				}
			}
		}
		$form->display();
		break;
	
	case 'groups':
		$table = new HTML_Table;
	
		$res =& db_query('groups_list');
		$res->fetchInto($row);
		
		// add groups table header
		$headers = array_keys($row);
		array_push($headers, 'views');
		array_push($headers, 'actions');
		$table->addRow($headers, null, 'TH');
		
		// add group records
		while ($row) {
			$res2 =& db_query('views_by_group_id', $row['group_id']);
			
			// get list of views allowed for this group
			$views = '';
			$res2->fetchInto($row2);
			while ($row2) {
				$views .= $row2['view'];
				if ($res2->fetchInto($row2))
					$views .= ', ';
			}
			$res2->free();
			
			array_push($row, $views);
			array_push($row, "<a href=\"index.php?view=admin&amp;task=edit_group&amp;id={$row['group_id']}\">edit</a>".
							 ", <a href=\"index.php?view=admin&amp;task=del_group&amp;id={$row['group_id']}\">delete</a>");
			$table->addRow(array_values($row));
			$res->fetchInto($row);
		}
		$res->free();
		
		// decor
		$table->altRowAttributes(1, null, array("class" => "altrow"));
        echo '<div class="overflow">'.$table->toHtml().'</div>';
		echo "<hr />";
		
		// form for adding a group
		$form = new HTML_QuickForm('addGroupForm', 'post', 'index.php?view=admin&task=groups');
		$form->addElement('header', null, 'Add a group');
		$form->addElement('text', 'name', 'Name: ');
		$form->addElement('submit', null, 'Submit');
		$form->applyFilter('name', 'trim');
		$form->addRule('name', 'Group name is required.', 'required', null, 'client');
		
		if ($form->validate()) {
			$res =& $db->autoExecute('groups', $form->getSubmitValues(), DB_AUTOQUERY_INSERT);
			if (PEAR::isError($res)) error($db->toString());
			redirect('index.php?view=admin&task=groups');
		}
		$form->display();
		break;
		
	case 'del_group':
		db_query('del_group_by_id', $_GET['id']);
		redirect('index.php?view=admin&task=groups');
		break;
		
	case 'edit_group':		
		// get list of views allowed for this group
		$group_views = array();
		$res =& db_query('views_by_group_id', $_GET['id']);
		while ($res->fetchInto($row)) {
			array_push($group_views, $row['view']);
		}
		$res->free();
		
		// get name of group
		$res =& db_query('group_by_id', $_GET['id']);
		$res->fetchInto($row);
		$name = $row['name'];
		$res->free();
	
		$form = new HTML_QuickForm('groupForm', 'post', 'index.php?view=admin&task=edit_group&id='.$_GET['id']);
		$form->addElement('header', null, 'Views for group '.$name.' (id: '.$_GET['id'].')');

		// get list of all available views
		$view_paths = glob($cfg['dir']['views'].'/*.php');
		$views = array();
		
		// create the checkboxes, add each view to $views for later checking
		foreach ($view_paths as $path) {
			$tmp = explode('.', basename($path));
			
			$elem =& $form->addElement('checkbox', $tmp[0], $tmp[0]);
			if (in_array($tmp[0], $group_views))
				$elem->setChecked(true);
			array_push($views, $tmp[0]);
		}
		
		$form->addElement('submit', 'submit', 'Apply Changes');
		
		if ($form->validate()) {
			$data = $form->getSubmitValues();
			foreach ($views as $view) {
				$elem =& $form->getElement($view);
				if ($data[$view] == 1) {
					auth_set_view($_GET['id'], $view);
					$elem->setChecked(true);
				} else {
					auth_clear_view($_GET['id'], $view);
					$elem->setChecked(false);
				}
			}
		}
		$form->display();
		break;
		
	case 'views':
		$table = new HTML_Table;
		$table->addRow(array('name', 'path'), null, 'TH');
		
		// display list of views
		$view_paths = glob($cfg['dir']['views'].'/*.php');
		foreach ($view_paths as $path) {
			$tmp = explode('.', basename($path));
			$table->addRow(array($tmp[0], $path));
		}
		$table->altRowAttributes(1, null, array("class" => "altrow"));
        echo '<div class="overflow">'.$table->toHtml().'</div>';
		
		?>
<p>To add a view, just drop a .php view-module file inside the views directory.
You can remove a view by deleting or renaming the corresponding file
inside the views directory.
</p> 
		<?php
		break;
		
	case 'contests':
		$table = new HTML_Table;
	
		$res =& db_query('contests_list');
		$res->fetchInto($row);
		
		if ($row) {
			// add contests table headers
			$headers = array_keys($row);
			array_push($headers, 'actions');
			$table->addRow($headers, null, 'TH');
			
			// add contests table records
			while ($row) {
				// Get the handle of the manager for displaying
				$manager_name = '[none]';
				$res2 =& $db->query($cfg['sql']['user_by_id'], $row['manager']);
				if (!PEAR::isError($res2)) {
					$res2->fetchInto($row2);
					$manager_name = $row2['handle'];
					$res2->free();
				}
				$row['manager'] = $manager_name;
				
				// add edit,delete actions
				$row['actions'] = "<a href=\"index.php?view=admin&amp;task=edit_contest&amp;id={$row['contest_id']}\">edit</a>, ".
					"<a href=\"index.php?view=admin&amp;task=del_contest&amp;id={$row['contest_id']}\">delete</a>";
				
				$table->addRow(array_values($row));
				$res->fetchInto($row);
			}
			$res->free();
			
			// decoration
            $table->altRowAttributes(1, null, array("class" => "altrow"));
			echo '<div class="overflow">'.$table->toHtml().'</div>';
		} else {
			?>
<p>No contests added yet.</p>			
			<?php
		}
		echo "<hr />";
		
		// get list of all available managers
		$res =& db_query('users_by_group_name', 'Managers');
		while ($res->fetchInto($row)) {
			$managers[$row['user_id']] = $row['handle'];
		}
		
		// form for adding a contest
		$form = new HTML_QuickForm('contestAddForm', 'post', selflink());
		$form->addElement('header', null, 'Add a contest');
		$form->addElement('text', 'name', 'Name:');
		$form->addElement('text', 'description', 'Description:');
		$elem =& $form->addElement('text', 'team_size', 'Size of team:');
		$form->addElement('select', 'division', 'Division:', $cfg['tcl']['divisions']);
		$elem->setValue('1');
		$date = getdate();
		$form->addElement('date', 'show_time', 'Activation time:',
			array('format' => 'dMY H:i', 'minYear' => $date['year'], 'maxYear' => $date['year']+5));
		$form->addElement('date', 'begin_time', 'Begin time:',
			array('format' => 'dMY H:i', 'minYear' => $date['year'], 'maxYear' => $date['year']+5));
		$form->addElement('date', 'end_time', 'End time:',
			array('format' => 'dMY H:i', 'minYear' => $date['year'], 'maxYear' => $date['year']+5));
		$form->addElement('select', 'manager', 'Contest manager:', $managers);
		$form->addElement('submit', null, 'Submit');
		
		$form->applyFilter('name', 'trim');
		$form->applyFilter('description', 'trim');
		$form->applyFilter('team_size', 'trim');
		
		$form->addRule('name', 'Contest name is required.', 'required', null, 'client');
		$form->addRule('manager', 'Contest manager is required.', 'required', null, 'client');
		$form->addRule('team_size', 'Team size is required.', 'required', null, 'client');
		
		// validate or display form
		if ($form->validate()) {
			$data = $form->getSubmitValues();
			$data['show_time'] = form2sql_datetime($data['show_time']);
			$data['begin_time'] = form2sql_datetime($data['begin_time']);
			$data['end_time'] = form2sql_datetime($data['end_time']);
			$db->autoExecute('contests', $data, DB_AUTOQUERY_INSERT);
			
			if (PEAR::isError($res)) error($db->toString());
			redirect('index.php?view=admin&task=contests');
		} else {
			$form->display();
		}
		
		break;
		
	case 'del_contest':
		$res =& db_query('del_contest_by_id', $_GET['id']);
		redirect('index.php?view=admin&task=contests');
		break;
		
	case 'edit_contest':
		// contest to edit given as arg
		$res =& db_query('contest_by_id', $_GET['id']);
		$res->fetchInto($row);
		$res->free();
		
		// get list of all available managers
		$res =& db_query('users_by_group_name', 'Managers');
		while ($res->fetchInto($row2)) {
			$managers[$row2['user_id']] = $row2['handle'];
		}
		
		// form for editing the contest
		$form = new HTML_QuickForm('contestEditForm', 'post', selflink());
		$form->addElement('header', null, "Edit contest {$row['name']} (id: {$row['contest_id']})");
		$form->addElement('text', 'name', 'Name:');
		$form->addElement('text', 'description', 'Description:');
		$elem =& $form->addElement('text', 'team_size', 'Size of team:');
		$elem->setValue('1');
		$form->addElement('select', 'division', 'Division:', $cfg['tcl']['divisions']);
		$date = getdate();
		$form->addElement('date', 'show_time', 'Activation time:',
			array('format' => 'dMY H:i', 'minYear' => $date['year'], 'maxYear' => $date['year']+5));
		$form->addElement('date', 'begin_time', 'Begin time:',
			array('format' => 'dMY H:i', 'minYear' => $date['year'], 'maxYear' => $date['year']+5));
		$form->addElement('date', 'end_time', 'End time:',
			array('format' => 'dMY H:i', 'minYear' => $date['year'], 'maxYear' => $date['year']+5));
		$form->addElement('select', 'manager', 'Contest manager:', $managers);
		$form->addElement('text', 'rules', 'Rules URL:');
		$form->addElement('submit', null, 'Submit');
		
		// convert date format and dtore default values
		$row['show_time'] = sql2form_datetime($row['show_time']);
		$row['begin_time'] = sql2form_datetime($row['begin_time']);
		$row['end_time'] = sql2form_datetime($row['end_time']);
		$form->setDefaults($row);
		
		$form->applyFilter('name', 'trim');
		$form->applyFilter('description', 'trim');
		$form->applyFilter('team_size', 'trim');
		
		$form->addRule('name', 'Contest name is required.', 'required', null, 'client');
		$form->addRule('manager', 'Contest manager is required.', 'required', null, 'client');
		$form->addRule('team_size', 'Team size is required.', 'required', null, 'client');
		
		// validate or display form
		if ($form->validate()) {
			$data = $form->getSubmitValues();
			$data['show_time'] = form2sql_datetime($data['show_time']);
			$data['begin_time'] = form2sql_datetime($data['begin_time']);
			$data['end_time'] = form2sql_datetime($data['end_time']);
			$db->autoExecute('contests', $data, DB_AUTOQUERY_UPDATE, 'contest_id='.$_GET['id']);
			
			if (PEAR::isError($res)) error($db->toString());
			redirect('index.php?view=admin&task=contests');
		} else {
			$form->display();
		}
		break;
		
	case 'shell':
		$form = new HTML_QuickForm('shellForm', 'post', selflink());
		$field =& $form->addElement('text', 'command', 'Command:');
		$field->setSize(100);
		$ifield =& $form->addElement('textarea', 'input', 'Standard Input:');
		$ifield->setRows(10);
		$ifield->SetCols(80);
		$form->addElement('submit', null, 'Submit');
		$form->display();
		
		if ($form->validate()) {
			// Write std input file
			$iname = tempnam("/tmp", "in");
			$ifile = fopen($iname, 'w');
			fwrite($ifile, $form->getSubmitValue('input'));
			fclose($ifile);
			
			$cmd = $form->getSubmitValue('command');
			echo "<pre class=\"shell_output\">";
            echo "<b>\$ ".html_escape($cmd)."</b>\n";
            
            exec("$cmd 2>&1 < $iname", $out, $ret);
            foreach ($out as $line) {
                echo html_escape($line)."\n";
            }
            
			echo "</pre>\n";
			echo "<p>Command returned: $ret</p>\n";
		}
		break;
		
	case 'uploader':
		// Get list of directories to which files can be uploaded
		$dirs = subdir_list('.');
		array_unshift($dirs, './');
	
        $form = new HTML_QuickForm('uploaderForm', 'post', selflink());
        $form->addElement('header', null, 'Upload a File:');
		$file =& $form->addElement('file', 'file', 'File:');
		$form->addElement('select', 'dir', 'Destination:', $dirs);
		$form->addElement('submit', 'upload', 'Upload');
		$form->addRule('file', 'Please select file to upload.', 'required', null, 'client');
		$form->setMaxFileSize(10485760);	// try 10 MB max file size
		
		if ($form->validate()) {
			if ($file->isUploadedFile()) {
				$dir = $dirs[$form->getSubmitValue('dir')];
				if ($file->moveUploadedFile($dir)) {
					echo "<p>File uploaded successfully to $dir.</p>";
				} else {
					echo "<p>Failed to save uploaded file to $dir (insufficient permissions?).</p>";	
				}
			} else {
				echo "<p>File upload did not finish successfully</p>";
			}
		}
        $form->display();
        echo "<p><b>Note:</b> Any previous file with the same name will be replaced.</p>";
        
        echo "<hr />";
        $form = new HTML_QuickForm('mkdirForm', 'post', selflink());
        $form->addElement('header', null, 'Create a Directory:');
        $form->addElement('text', 'name', 'Name:');
        $form->addElement('select', 'dir', 'Destination:', $dirs);
        $form->addElement('submit', 'mkdir', 'Mkdir');
        $form->addRule('name', 'Please enter directory name.', 'required', null, 'client');
		
        if ($form->validate()) {
            $path = $dirs[$form->getSubmitValue('dir')].'/'.$form->getSubmitValue('name');
            
            if (file_exists($path)) {
                echo("<p><b>Warning:</b> File or directory $path already exists.</p>");
            } else {
                if (mkdir($path)) {
                    echo("<p>Directory $path created.</p>");
                } else {
                    echo("<p>Failed to create directory $path. Make sure parent directory permissions allow it.</p>");
                }
            }
        }
        $form->display();
        break;
        
    case 'phpinfo':
        phpinfo();
        break;
	}
}

?>
