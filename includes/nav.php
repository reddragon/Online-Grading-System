<?php

$links = array();
$nav_cache = array();

#-------------------------------------------------------------------------------
# Class for holding navigation links

class NavItem
{
	var $name;
	var $view;
	var $task;
	var $suffix; // a suffix for the determined URL
	
	function NavItem($name, $view, $task, $suffix = '') {
		$this->name = $name;
		$this->view = $view;
		$this->task = $task;
		$this->suffix = $suffix;
	}
	
	function display() {
		echo "<a class=\"navitem\" href=\"index.php?view={$this->view}&amp;task={$this->task}{$this->suffix}\">{$this->name}</a>\n";
	}
}

#-------------------------------------------------------------------------------
# Adds a navigation menu (which is not a link, but a container of links)

function nav_add_menu($name) {
	global $links;
	if (!array_key_exists($name, $links))
		$links[$name] = array();
}

#-------------------------------------------------------------------------------
# Adds a navigation link under the given menu
# $view <- View to use
# $task <- Task under that view
# $suffix <- anything you want to add to the end of the URL link

function nav_add($menu, $item, $view, $task, $suffix = null) {
	global $links, $nav_cache;
	nav_add_menu($menu);
	
	array_push($links[$menu], new NavItem($item, $view, $task, $suffix));
	
	$nav_cache[$view][$task][$suffix] = array($menu, $item);
}

#-------------------------------------------------------------------------------
# Callback function for rendering navigation menus

function nav_display_callback($items, $menu) {
	echo "<li><div class=\"navmenu\">$menu</div><ul>\n";
	foreach ($items as $item) {
		echo "<li>\n";
		$item->display();
		echo "</li>\n";
	}
	echo "</ul></li>";
}

#-------------------------------------------------------------------------------
# Renders the HTML for the whole navigation menu

function nav_display() {
	global $links;
	echo "<div id=\"navi\"><ul class=\"nav\">\n";
	array_walk($links, "nav_display_callback");
	echo "</ul></div>\n";
}

#-------------------------------------------------------------------------------
# Gets string representing the location inside navigation for the given
# view and task.

function nav_get_location($view, $task, $suffix = null) {
	global $nav_cache;
	
	$citem =& $nav_cache[$view][$task][$suffix];
	if (!isset($citem)) {
		$citem =& current(current($nav_cache[$view]));
	}
	
	return $citem[0]." &#187; ".$citem[1];
}

?>
