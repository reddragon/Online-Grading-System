<?php

function dump($msg) {
	echo $msg;
	flush();
}

function getmicrotime() { 
    list($usec, $sec) = explode(" ", microtime()); 
    return ((float)$usec + (float)$sec); 
}

#-------------------------------------------------------------------------------
# Discards current output buffer, displays the given message in a new page,
# and terminates execution. Use on fatal errors.

function error($msg) {
	global $cfg;
	ob_end_clean();
	html_reset();
	
	html_header("Error");
?>
<h1>Error Detected</h1>
<p>Sorry for the inconvenience.
Please <a href="mailto:<?php echo $cfg['site']['email'] ?>">email</a> the webmaster if the
problem persists.</p>
<p><a href="index.php">Click here</a> to return to the home-page.</p>
<hr />
<p><strong>Description:</strong> <?php echo $msg ?></p>
<hr />
<p><b>Backtrace:</b></p>
<?php
    if ($cfg['site']['debug']) {
        echo "<pre>";
        print_r(debug_backtrace());
        echo "</pre>";
    } else {
        echo "Display of debugging information disabled by admin.";
    }

	html_footer();
	exit;
}

#-------------------------------------------------------------------------------
# Redirects to a relative URL (directly using header() might not work)

function redirect($loc) {
	header("Location: http://".$_SERVER['HTTP_HOST'].
            dirname($_SERVER['PHP_SELF']).'/'.$loc);
	exit;
}

#-------------------------------------------------------------------------------
# Creates a relative link to current page with the (optional) added query

function selflink($query = '') {
	$add_query = '?';
	$add_url = '';
	
	// preserve queries
	if (count($_GET) > 0) {
		$add_url = '?'.$_SERVER['QUERY_STRING'];
		$add_query = '&';
	}
	
	$url = basename($_SERVER['PHP_SELF']);
	if ($add_url != '') {
		$url .= $add_url;
	}
	if ($query != '') {
		$url .= $add_query.$query;
    }
    
    return $url;
}

#-------------------------------------------------------------------------------
# Checks for availibility of cookie support.
# TODO: Make sure this functions currectly on all browsers. REQUIRES TESTING!!!

function cookie_check() {
	global $cfg;
	
	if (!isset($_SESSION['cookie_check'])) {
		if (isset($_GET['cookie_check'])) {
			html_header("Cookie Support Required");
			echo "<p>Cookies are not supported by your browser. " .
                "Please enable cookies and <a href=\"".selflink()."\">try again</a>.</p>";
			html_footer();
			exit;
		} else {
			$_SESSION['cookie_check'] = 1;
			redirect(selflink('cookie_check=1'));
		}
	}
}

#-------------------------------------------------------------------------------
# Converts date field content from an HTML_QuickForm to a sql date format

function form2sql_date($array) {
	return date('Y-m-d', mktime(0, 0, 0, $array['M'], $array['d'], $array['Y']));
}

#-------------------------------------------------------------------------------
# Converts datetime field content from an HTML_QuickForm to a sql date format
# Seconds are ignored (forced to 0).

function form2sql_datetime($array) {
	return date('Y-m-d H:i:s', mktime($array['H'], $array['i'], 0, $array['M'], $array['d'], $array['Y']));
}

#-------------------------------------------------------------------------------
# Reverse of form2sql_date()

function sql2form_date($str) {
	$date = getdate(strtotime($str));
	return array('d' => $date['mday'], 'M' => $date['mon'], 'Y' => $date['year']);
}

#-------------------------------------------------------------------------------
# Reverse of form2sql_datetime()

function sql2form_datetime($str) {
	$date = getdate(strtotime($str));
	return array('d' => $date['mday'], 'M' => $date['mon'], 'Y' => $date['year'],
		'H' => $date['hours'], 'i' => $date['minutes']);
}

#-------------------------------------------------------------------------------
# To simplify query

function db_query($qid, $args = null) {
	global $db, $cfg;
	
	if (args != null) {
		$res =& $db->query($cfg['sql'][$qid], $args);
	} else {
		$res =& $db->query($cfg['sql'][$qid]);
	}

	if (PEAR::isError($res)) error($res->toString());
	return $res;
}

#-------------------------------------------------------------------------------
# Returns a list of all descendant directories of the given path

function subdir_list($path) {
	if (!is_dir($path)) return null;

	$dirs = glob("$path/*", GLOB_ONLYDIR);
	$subdirs = $dirs;
	
	if ($dirs == null) return array();
	
	foreach ($dirs as $dir) {
		$subdirs = array_merge($subdirs, subdir_list($dir));
	}
	return $subdirs;
}

#-------------------------------------------------------------------------------
# Returns a list of available programming languages

function language_list() {
	global $cfg;
	$files = glob($cfg['dir']['languages'].'/*.php');
	
	for ($i=0; $i<count($files); ++$i) {
		preg_match("/.*\/([^\/\.]+)\.php/", $files[$i], $match);
		$files[$i] = $match[1];
	}
	return $files;
}

function user_handle($handle) {
    return '<a href="index.php?view=statistics&amp;task=profile&amp;handle='.$handle.'">'.$handle.'</a>';
}


?>