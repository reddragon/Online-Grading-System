<?php

# Start output buffering. Won't display a thing until buffer is flushed.
ob_start();
require_once 'config.inc.php';
$time_start = getmicrotime();

# Setup the database connection for every page load. The connection is global.
$db =& DB::connect($cfg["db"]);
if (PEAR::isError($db)) error($db->toString());
$db->setFetchMode(DB_FETCHMODE_ASSOC);

# Make sure all database tables are present; if not create them.
$tables = $db->getListOf('tables');
if (PEAR::isError($tables)) error($tables->getMessage());
foreach (array_keys($cfg['sql']['create']) as $table) {
	if (!in_array($table, $tables)) {
		$res = $db->query($cfg['sql']['create'][$table]);
		if (PEAR::isError($res)) error($res->toString());
	}
}

# Start session handling, check for cookie-support
session_start();
cookie_check();

# Setup user perspective
auth_check();

# Set the selected view for displaying (if any)
if (isset($_GET['view'])) {
	view_set($_GET['view']);
}

# Force the default theme, if no theme selected by user
if (!isset($_SESSION['theme'])) {
	$_SESSION['theme'] = $cfg["site"]["theme"];
}


#-------------------------------------------------------------------------------

# Start HTML
html_header($cfg['site']['name'], 
			$cfg["dir"]["themes"].'/'.$_SESSION["theme"].'.css',	// stylesheet
			$cfg["dir"]["themes"].'/'.$_SESSION["theme"].'-ie.css',	// IE-specific overrides
            $cfg['dir']['scripts'].'/global.js', 'index');
        
# Display welcome message if user is logged in
if (auth_logged_in()) {
    echo "<div id=\"welcome\">\n";
    echo "Welcome, <a href=\"index.php?view=login&amp;task=profile\">{$_SESSION['handle']}</a>";
    
    if ($cfg['score']['show_rating'])
        echo " ({$_SESSION['rating']})";
    echo "</div>\n";
}

echo "<div id=\"main\">\n";

echo "<div id=\"logo\">\n";
echo $cfg['site']['logo'];
echo "</div>\n";

# Top marquee
//echo "<div id=\"ticker\" onmouseover=\"rollNews()\" onmouseout=\"stopNews()\"><div>Loading...</div></div>";

# Top bar (containing the currect location)
echo "<div id=\"header\">\n";
if (isset($_GET['id']))
	$suffix = '&amp;id='.$_GET['id'];
else
	$suffix = null;
echo nav_get_location(view_get(), $_GET['task'], $suffix);
echo "</div>\n";

if(isset($_GET['credits']))
{
	echo "<div id=\"content\">\n";
	echo '<p align = "center">
			<strong>Credits</strong><br><br><br><br>
			<strong>Grader Design & Coding</strong><br>
			Arijit De<br><br>
			<strong>Problem Sets</strong><br>Aniruddha Laud<br>
						 Pritam Damania<br>
						  (Team c0mplexity)<br><br>
				
			<strong>Grader Tweaking for TCL</strong>
			<br>Gaurav Menghani<br><br>
			
	</p>';
	echo "</div>\n";
	
}
else
{
	# Selected View
	echo "<div id=\"content\">\n";
	view_display($_GET['task']);
	echo "</div>\n";
}

echo "<br /><div id=\"footer\">\n";
?>
<table align="right"><TR><TD align="right">
Copyright &copy; 2009, <a href="mailto:de.arijit@gmail.com">Arijit De</a>&nbsp;|&nbsp;<a href = "index.php?credits=1">Credits</a>&nbsp;|&nbsp;
Works best on <a href="http://www.getfirefox.com">Firefox 2.0+</a>.<br />
<br />(Page rendered in <?php printf("%.3f", getmicrotime()-$time_start) ?> seconds at <?php echo date('Y-m-d H:i:s') ?>. All times are in IST.)
</TD></TR></table>
<?php
echo "</div>\n";

echo "</div>";

# Navigation Menus
nav_display();

# End HTML
html_footer();

#-------------------------------------------------------------------------------

# Flush output to browser
ob_end_flush();
?>
