<?php

$views = array();
$current_view = null;

#-------------------------------------------------------------------------------
# Class for encapsulating a view module

class View
{
	var $name;
	var $path;
	
	function View($name) {
		$this->name = $name;
		$this->path = view_path($name);
		require_once $this->path;
	}
	
	function init() {
		$func = $this->name.'_init';
		$func();
	}
	
	# Display the contents of the view
	function display($task = null) {
		$func = $this->name.'_display';
		$func($task);
	}
}

#-------------------------------------------------------------------------------
# Returns the path for the given view, fails if view doesnt exist

function view_path($name) {
	global $cfg;	
	$path = $cfg['dir']['views'].'/'.$name.'.php';
	if (!is_file($path)) {
		error("View $name does not exist.");
	} else {
		return $path;	
	}
}

#-------------------------------------------------------------------------------
# Adds a view to the current user's perspective

function view_add($name) {
	global $views;
	$views[$name] = new View($name);
	$views[$name]->init();
}

#-------------------------------------------------------------------------------
# Sets the view to be displayed for the current user's perspective

function view_set($name) {
	global $current_view, $views;
	if (isset($views[$name])) {
		$current_view = $name;
    } else {
        $current_view = null;
	}
}

#-------------------------------------------------------------------------------
# Returns the view that would be currently displayed (the currently set one)

function view_get() {
	global $current_view;
	return $current_view;
}

#-------------------------------------------------------------------------------
# Renders the currently selected view

function view_display($task) {
    global $current_view, $views;
    
    if ($current_view == null) {
        if (auth_logged_in()) {
            ?>
            <p><b>No such view authorized for current user.</b><br />One of the following possibilities could have lead to this error:</p>
            <ol>
            <li>Your permissions to this page were revoked by the administrator. Please try some other page from the navigation menu.</li>
            <li>You got here through a broken link on this website. Please report the circumstances to the <a href="mailto:de.arijit@gmail.com">webmaster</a>. We apologize for the trouble.</li>
            </ol>
            <?php
        } else {
            echo "<p><b>No such view authorized for visitors.</b><br />Maybe your session cookie expired. Please <a href=\"index.php\">re-login</a> to continue.</p>";
        }
    } else {
        $views[$current_view]->display($task);
    }
}

?>