<?php

#-------------------------------------------------------------------------------
# Checks user session and enables views accordingly.

function auth_check() {
	global $db, $cfg;
	$views = array('login');
	
	view_add('login');
	if (auth_logged_in()) {
		# Add views for groups in DB
		$res =& db_query('auth_check', $_SESSION['user_id']);
		
		while ($res->fetchInto($row)) {
			if (!in_array($row['view'], $views)) {
				view_add($row['view']);
				array_push($views, $row['view']);
			}
		}
	}
}

#-------------------------------------------------------------------------------
# Returns true if user is logged in.

function auth_logged_in() {
	return isset($_SESSION['user_id']);
}

#-------------------------------------------------------------------------------
# Logs in a user after verifying the password. Returns false on failure.
# $user <- handle of user

function auth_login($user, $pass) {
	global $db, $cfg;
	$res =& db_query('user_by_handle', $user);
	$res->fetchInto($row);
	$res->free();
	
	# The following auth verification relies on the crypt() UNIX system call,
	# which on some systems uses 56-bit DES and thus relies only on the
	# first 8 characters of the password! This doesnt apply to systems
	# using the MD5 algorithm. 
	if ($row['password'] == crypt($pass, $row['password'])) {
		$_SESSION['user_id'] = $row['user_id'];
		$_SESSION['handle'] = $user;
		$_SESSION['rating'] = $row['rating'];
		
		return true;
	} else {
		return false;		
	}
}

#-------------------------------------------------------------------------------
# Updates the logged in user's profile

function auth_update($data) {
	global $db;
	if (isset($data['password'])) {
		$data['password'] = crypt($data['password']);
	}
	
	$res =& $db->autoExecute('users', $data, DB_AUTOQUERY_UPDATE, 'user_id='.$_SESSION['user_id']);
	if (PEAR::isError($res)) error($res->toString());
}

#-------------------------------------------------------------------------------
# Logs out the user

function auth_logout() {
	unset($_SESSION['user_id']);
	unset($_SESSION['handle']);
	unset($_SESSION['theme']);
}

#-------------------------------------------------------------------------------
# Adds a user to a group
# $user <- user_id
# $group <- group_id

function auth_set_perm($user, $group) {
	global $db, $cfg;
	$res =& db_query('user_and_group', array($user, $group));
	
	if ($res->numRows() == 0) {
		$res =& $db->autoExecute('perms', array('user_id' => $user, 'group_id' => $group), DB_AUTOQUERY_INSERT);	
	}
}

#-------------------------------------------------------------------------------
# Allows access to a view for a group 
# $view <- name of view
# $group <- group_id

function auth_set_view($group, $view) {
	global $db, $cfg;
	$res =& db_query('group_and_view', array($group, $view));
	
	if ($res->numRows() == 0) {
		$res =& $db->autoExecute('views', array('group_id' => $group, 'view' => $view), DB_AUTOQUERY_INSERT);
		if (PEAR::isError($res)) error($res->toString());
	}
}

#-------------------------------------------------------------------------------
# Removes a user from a group
# $user <- user_id
# $group <- group_id

function auth_clear_perm($user, $group) {
	global $db, $cfg;
	$res =& db_query('del_user_and_group', array($user, $group));
}

#-------------------------------------------------------------------------------
# Removes access to a view for a group
# $view <- name of view
# $group <- group_id

function auth_clear_view($group, $view) {
	$res =& db_query('del_group_and_view', array($group, $view));
}

#-------------------------------------------------------------------------------
# Registers a new user. All data is passed in as an associative array.
# Returns null on success, or an associative array of previous user's data.

function auth_register($data) {
	global $db, $cfg;
	
	// Verify that handle is not already taken
	$res =& db_query('user_by_handle', $data['handle']);
	if ($res->numRows() != 0) {
		$res->fetchInto($row);
		$res->free();
		return $row;
	}
	
	$data['password'] = crypt($data['password']);
	$res =& $db->autoExecute('users', $data, DB_AUTOQUERY_INSERT);
	if (PEAR::isError($res)) error($res->toString());
	
	$res =& db_query('user_by_handle', $data['handle']);
	assert($res->numRows() == 1);
	$res->fetchInto($row1);
	$res->free();
	
	$res =& db_query('group_by_name', $cfg['auth']['autogroup']);
	assert($res->numRows() == 1);
	$res->fetchInto($row2);
	$res->free();
	
	auth_set_perm($row1['user_id'], $row2['group_id']);
	return null;
}

function auth_user_in_group($group_name) {
    global $db;
    $res =& db_query('user_and_group_name', array($_SESSION['user_id'], $group_name));
    if ($res->numRows() == 0)
        return false;
    else
        return true;
}

?>