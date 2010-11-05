<?php

require_once 'HTML/QuickForm.php';
require_once 'Text/Password.php';

function login_init() {
	view_set('login'); // attempt to make this the default view
	if (auth_logged_in()) {
		nav_add('Account', 'Profile', 'login', 'profile');
		nav_add('Account', 'Logout', 'login', 'logout');
	} else {
		nav_add('Account', 'Login', 'login', 'login');
		nav_add('Account', 'Register', 'login', 'register');
		nav_add('Account', 'Forgot Password', 'login', 'forgot');
	}
}

function login_display($task) {
	global $db, $cfg;
	if ($task == NULL) {
		if (auth_logged_in()) {
			$task = 'profile';
		} else {
			$task = 'login';
		}
	}
	
	switch ($task) {
	case "register":
		$form = new HTML_QuickForm('regForm', 'post', 'index.php?view=login&task=register');
		$form->addElement('header', null, 'Register');
		$form->addElement('text', 'handle', 'Handle:');
		$form->addElement('password', 'password', 'Password:');
		$form->addElement('password', 'password2', 'Retype Password:');
		$form->addElement('text', 'email', 'Email:');
		$form->addElement('header', null, 'Personal Information');
		$form->addElement('text', 'first_name', 'First Name:');
		$form->addElement('text', 'last_name', 'Last Name:');
		$date = getdate();
		$form->addElement('date', 'birth_date', 'Date of Birth:', array('minYear' => $date['year']-100, 'maxYear' => $date['year']));
		$form->addElement('text', 'address', 'Street Address:');
		$form->addElement('text', 'city', 'City:');
		$form->addElement('text', 'state', 'State:');
		$form->addElement('text', 'zip', 'Zip:');
		$form->addElement('select', 'division', 'Division:', $cfg["tcl"]["divisions"]);
		$form->addElement('text', 'phone', 'Phone:');
		$form->addElement('textarea', 'quote', 'Quote:', array('rows' => 3));
		$form->addElement('header', null, 'For Password Recovery');
		$form->addElement('text', 'question', 'Secret Question:');
		$form->addElement('text', 'secret', 'Secret Answer:');
		$form->addElement('submit', null, 'Submit');
		
		$form->applyFilter('handle', 'trim');
		$form->applyFilter('handle', 'strtolower');
		$form->applyFilter('email', 'trim');
		$form->applyFilter('first_name', 'trim');
		$form->applyFilter('last_name', 'trim');
		$form->applyFilter('address', 'trim');
		$form->applyFilter('state', 'trim');
		$form->applyFilter('city', 'trim');
		$form->applyFilter('zip', 'trim');
		$form->applyFilter('phone', 'trim');
		$form->applyFilter('question', 'trim');
		$form->applyFilter('secret', 'trim');
					
		$form->addRule('handle', 'Handle is required.', 'required', null, 'client');
		$form->addRule('handle', 'Handle can only contain alphabets, numbers. and/or undescores.', 'alphanumericscore', null, 'client');
		$form->addRule('password', 'Password is required.', 'required', null, 'client');
		$form->addRule('password2', 'Retyped password is required.', 'required', null, 'client');
		$form->addRule('email', 'Email is required.', 'required', null, 'client');
		$form->addRule('division', 'Division is required.', 'required', null, 'client');
		$form->addRule('first_name', 'First name is required.', 'required', null, 'client');
		$form->addRule('last_name', 'Last name is required.', 'required', null, 'client');
		$form->addRule('question', 'Secret question is required.', 'required', null, 'client');
		$form->addRule('secret', 'Secret answer is required.', 'required', null, 'client');
		
		$form->addRule('handle', 'Login handle must be between 4 and 15 characters.', 'rangelength', array(4,15), 'client');
		$form->addRule('password', 'Password must be between 6 and 15 characters.', 'rangelength', array(4,15), 'client');
		$form->addRule('email', 'Email is invalid.', 'email', null, 'client');
		$form->addRule(array('password', 'password2'), 'Passwords much match.', 'compare', null, 'client');
		
		$show_form = true;
		if ($form->validate()) {
			$data = $form->getSubmitValues();
			unset($data['password2']);
			
			// Verify that email is unique
			$res =& db_query('user_by_email', $data['email']);
			if ($res->numRows() != 0) {
				$res->fetchInto($user);
				$res->free();
				?>
				<p><b>Email already registered to an existing user!</b><br />
				User <?php echo '<b>'.$user['handle'].'</b>' ?> owns that email address. Maybe you've already registered and forgotten about it?
				Try <a href="index.php?view=login&amp;task=login">logging in</a> if that is the case.</p>
				<?php
			} else {
				// Format the birth date correctly
				$data['birth_date'] = form2sql_date($data['birth_date']);
				
				$user = auth_register($data);
				if ($user == null) {
					$show_form = false;
	?>
	<p><strong>Thanks for registering!</strong><br /> Please proceed to <a href="index.php?view=login&amp;task=login">login</a> into your new account.</p>
	<?php
				} else {
	?>
	<p><b>That user-handle has already been taken!</b><br/> It belongs to an user registered with the name <?php echo $user['first_name'].' '.$user['last_name'] ?>. Please try again with another handle.</p>
	<?php
				}
			}
		}
		
		if ($show_form) {
?>
<p><strong>Please fill in your details below.</strong><br /> 
Please choose your <strong>handle</strong> and <strong>division</strong> carefully. Once chosen, they cannot be changed. Moreover, choosing an inappropriate division will lead to disqualification.
<br> 
<br>Any doubts and problems should find their way to the <? echo '<a href="mailto:'.$cfg["site"]["email"].'">admins</a>'; ?>.
</p>
<?php
			$form->display();
		}
		
		break;
		
	case 'logout':
		auth_logout();
		redirect('index.php');
		break;
	
	case 'login':
		$form = new HTML_QuickForm('loginForm', 'post', 'index.php?view=login&task=login');
		$form->addElement('header', null, 'Login');
		$form->addElement('text', 'handle', 'Handle:');
		$form->addElement('password', 'password', 'Password:');
		$form->addElement('submit', null, 'Submit');
		
		$form->applyFilter('handle', 'trim');
		$form->applyFilter('handle', 'strtolower');
		
		if ($form->validate()) {
			if (auth_login($form->getSubmitValue('handle'), $form->getSubmitValue('password'))) {
				redirect('index.php');
			} else {
				echo "<p>Invalid handle or password! Please try again.</p>\n";
			}
        } else {
            $signature = '<i>'.$_SERVER['SERVER_SOFTWARE'].' Server at '.$_SERVER['SERVER_NAME'].
                            ', port '.$_SERVER['SERVER_PORT'].'</i>';
?>
<p><strong>Welcome!</strong><br />
Please login to proceed, or <a href="index.php?view=login&amp;task=register">register</a>
 with us if you're new here.</p>
<?php
		}
		
        $form->display();
        
        ?>
<p class="system_info">This is <b>OGS 2</b> running on <? echo $signature ?>.<br />
<b>Server System:</b> <?php system("uname -srmp"); ?></p>
<hr />
<div id="javascript_warn"><p><strong>Warning!</strong> Javascript is not enabled on your browser. Many features will not work without it.</p></div>
<script type="text/javascript">
getObj('javascript_warn').style.display = "none";
</script>
<p><strong>Before you login.</strong> This website makes heavy use of modern web technologies such as CSS
and Javascript, to enjoy which, you'll need a modern browser. Below is a list of browsers along with their
earliest versions which are guaranteed to work with this website. For best results, we recommend a resolution higher than 800x600 with True Color (32-bit).</p>
<table class="browsers">
<tr>
    <td><img width="32" height="32" src="images/firefox-icon.png" /></td>
    <td><img width="32" height="32" src="images/opera_icon.gif" /></td>
    <td><img width="32" height="32" src="images/internet-explorer-icon.png" /></td>
    <td><img width="32" height="32" src="images/mozilla-icon.png" /></td>
    <td><img width="32" height="32" src="images/safari-icon.png" /></td>
    <td><img width="32" height="32" src="images/icon-konqueror.jpg" /></td>
    <td><img width="32" height="32" src="images/netscape-icon.png" /></td>
</tr>
<tr>
    <td><a href="http://www.getfirefox.com/">Firefox</a><br />1.0+</td>
    <td><a href="http://www.opera.com/">Opera</a><br />7+</td>
    <td><a href="http://www.microsoft.com/windows/ie/">Internet<br />Explorer</a> 6.0+<a></a></td>
    <td><a href="http://www.mozilla.org/products/mozilla1.x/">Mozilla</a><br />1.3+</td>
    <td><a href="http://www.apple.com/safari/">Safari</a><br />1.2+</td>
    <td><a href="http://www.konqueror.org/">Konqueror</a><br />3+</td>
    <td><a href="http://browser.netscape.com">Netscape</a><br />6+</td>
</tr>
</table>
<p>If you experience any problems while browsing this website using one of the above browsers,
then you're welcome to <a href="mailto:de.arijit@gmail.com">email the webmaster</a>. We hope you'll
enjoy your stay here.</p>
        <?php
        
		break;
		
	case 'forgot':
?>
<p><strong>Lost your password?</strong><br />Follow these steps to generate a new password for your account.
You will be mailed the new password once you're done.</p>
<?php
		$form1 = new HTML_QuickForm('forgotForm1', 'post', 'index.php?view=login&task=forgot');
		$form1->addElement('header', null, 'Password Recovery: Step 1');
		$form1->addElement('text', 'handle', 'Enter your login handle:');
		$form1->addElement('submit', null, 'Next');
		$form1->applyFilter('handle', 'trim');
		$form1->applyFilter('handle', 'strtolower');
		$form1->addRule('handle', 'Your login handle is required.', 'required', null, 'client');
		
		if ($form1->validate()) {
			redirect('index.php?view=login&task=forgot2&handle='.$form1->getSubmitValue('handle'));
		} else {
			$form1->display();
?>
<p><strong>Please note:</strong> Due to the lack of emailing support on our server (Yes! We require better servers!), you'll have to wait a few
hours before we can mail you your new password manually.</p> 
<?php			
		}
		break;
		
	case 'forgot2':
		$res =& db_query('user_by_handle', $_GET['handle']);
		
		if ($res->numRows() == 0) {
			$res->free();
?>
<p>The given login handle does not exist!</p>
<?php			
		} else {
			$res->fetchInto($row);
			$res->free();
			if ($row['question']{strlen($row['question'])-1} != '?') {
				$row['question'] .= '?';
			}
	
			$form2 = new HTML_QuickForm('forgotForm2', 'post', 'index.php?view=login&task=forgot2&handle='.$_GET['handle']);
			$form2->addElement('header', null, 'Password Recovery: Step 2');
			$form2->addElement('static', null, 'Secret Question:', $row['question']);
			$form2->addElement('text', 'secret', 'Secret Answer:');
			$form2->addElement('submit', null, 'Next');
			$form2->applyFilter('secret', 'trim');
			$form2->addRule('secret', 'Answer is required for verification.', 'required', null, 'client');
			
			if ($form2->validate()) {
				if ($form2->getSubmitValue('secret') == $row['secret']) {
					$res =& db_query('clean_forgot', $row['user_id']);
					
					$new_pass = Text_Password::create(10);
					$res =& $db->autoExecute('users', array('password' => crypt($new_pass)), DB_AUTOQUERY_UPDATE,
						'user_id='.$row['user_id']);
					if (PEAR::isError($res)) error($res->toString());
						
					$res =& $db->autoExecute('forgot', array('user_id' => $row['user_id'], 'password' => $new_pass), DB_AUTOQUERY_INSERT);
					if (PEAR::isError($res)) error($res->toString());
?>
<p>Due to lack of emailing support on our server (Yes! We require better servers!), your password will
have to be emailed to you manually. You should receive your newly generated password within 12 hours.</p>
<?php
				} else {
?>
<p><strong>Incorrect answer!</strong><br /> We need to verify your identity before we can proceed. Please try again.</p> 
<?php
					$form2->display();
				}
			} else {
				$form2->display();
			}
		}
		break;
		
	case  'profile':
?>
<p>You can view or edit your personal information here. 
Any fields that you leave blank will <i>remain unchanged</i>.</p>
<?php
		
		
		$form = new HTML_QuickForm('profileForm', 'post', 'index.php?view=login&task=profile');
			
		$res =& db_query('user_by_id', $_SESSION['user_id']);
		$res->fetchInto($row);
		$res->free();
		$form->addElement('header', null, 'Edit Your Profile');
		$form->addElement('static', 'handle', 'Handle:');
		$form->addElement('password', 'password', 'Change Password:');
		$form->addElement('password', 'password2', 'Retype Password:');
		$form->addElement('text', 'email', 'Email:');
		$form->addElement('header', null, 'Personal Information');
		$form->addElement('text', 'first_name', 'First Name:');
		$form->addElement('text', 'last_name', 'Last Name:');
		$date = getdate();
	
		$form->addElement('date', 'birth_date', 'Date of Birth:', array('minYear' => $date['year']-100, 'maxYear' => $date['year']));
		$form->addElement('text', 'address', 'Street Address:');
		$form->addElement('text', 'city', 'City:');
		$form->addElement('text', 'state', 'State:');
		$form->addElement('text', 'zip', 'Zip:');
		$form->addElement('static', null, 'Division:', $cfg['tcl']['divisions'][$row['division']]);
		$form->addElement('text', 'phone', 'Phone:');
		$form->addElement('textarea', 'quote', 'Quote:');
		$form->addElement('submit', null, 'Save Changes');
		
		unset($row['password']);
		// Format the birth date
		$row['birth_date'] = sql2form_date($row['birth_date']);
		$form->setDefaults($row);

		$form->applyFilter('email', 'trim');
		$form->applyFilter('first_name', 'trim');
		$form->applyFilter('last_name', 'trim');
		$form->applyFilter('address', 'trim');
		$form->applyFilter('state', 'trim');
		$form->applyFilter('city', 'trim');
		$form->applyFilter('zip', 'trim');
		$form->applyFilter('phone', 'trim');
		
		$form->addRule('password', 'Password must be between 6 and 15 characters.', 'rangelength', array(4,15), 'client');
		$form->addRule('email', 'Email is invalid.', 'email', null, 'client');
		$form->addRule(array('password', 'password2'), 'Passwords much match.', 'compare', null, 'client');
		
		if ($form->validate()) {
			$data = $form->getSubmitValues();
			unset($data['password2']);
			// Format the birth date correctly
			$data['birth_date'] = form2sql_date($data['birth_date']);
			
			foreach ($data as $key => $value) {
				if ($value == $row['value'] || strlen($value) == 0) unset($data[$key]);
			}
			
			//print_r($data);
			auth_update($data);
			
			redirect('index.php?view=login&task=profile&updated=1');
		} else {
			$form->display();
		}
		
        if ($_GET['updated'] == '1') {
            ?>
                <p><b>Note:</b> Your profile has been updated.</p>
            <?php
        }
		
		break;	
	}
}

?>
