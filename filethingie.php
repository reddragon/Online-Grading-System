<?php
/* 
   File Thingie version 1.41 - Andreas Haugstrup Pedersen <andreas@solitude.dk> October 1st, 2003
   The newest version of File Thingie can be found at <http://www.solitude.dk/filethingie/>
   Comments, suggestions etc. are welcome and encouraged at the above e-mail.
   
   LICENSE INFORMATION:
   This work is licensed under the Creative Commons Attribution-NoDerivs-NonCommercial.
   To view a copy of this license, visit <http://creativecommons.org/licenses/by-nd-nc/1.0/>
   If you want to use File Thingie for a commercial work please contact me at <andreas@solitude.dk>
   
   KNOWN ISSUES IN THIS VERSION:
   - You cannot rename file which has a single prime (') in the name.

   Changelog for version 1.41:
   - Option for allowing all file types for upload added.
   - Fixed two typos that made some error messages not display.
   - Fixed rare bug where users could delete files they weren't supposed to.

*/
error_reporting (E_ALL ^ E_NOTICE);
session_start();

/* Setup information. Change as appropriate */

header ("Content-Type: text/html; charset=ISO-8859-1");	/* Your character set. You probably don't have to change this. */
$username = "admin";										/* The username. Case sensitive. */
$password = "nimda";										/* The password. Case sensitive */
$dir = ".";												/* The subdir name. This file has to be located 
															   one step above this. Don't start with a slash */
$maxsize = 100000;											/* Maximum upload size in bytes */
$showphp = TRUE;											/* Decides whether it should be allowed to show 
															   source on PHP files. Can have the values TRUE 
															   or FALSE. Set to FALSE by default for security 
															   reasons. */
$edithtml = TRUE;											/* Decides if HTML/CSS files can be edited. TRUE
															   on deafult. */
$edittxt = TRUE;											/* Decides if text files can be edited. TRUE on deafult. */
$editphp = TRUE;											/* Decides if PHP files can be edited. FALSE on deafult. */
$converttabs = TRUE;										/* Decides if tabs should be converted into spaces 
															   when a file is opened for edit. TRUE on deafult. */
$phpendings = array("php", "php3", "php4", "phtml");		/* List of PHP file endings. Used for security when 
															   editing and showing source. */
$htmlendings = array("htm", "html", "css");					/* List of HTML file endings. Used for security when
															   editing, showing source and making W3C links. */
$useimage = "none";											/* Path to the image you wish shown in File Thingie.
															   Default value is "none" for no image. */
$dateformat = "Y/m/d G:i:s";								/* The date format used for displaying last file change.
															   See <http://www.php.net/date> for other options. */
$showdatecolumn = TRUE;										/* FALSE disables the date column for last file change.
															   TRUE enables it. Value is TRUE by default. */
$showW3link = TRUE;											/* TRUE enables direct links to validate HTML/CSS at 
															   the W3C validator. FALSE disables it. Value is 
															   TRUE by default. */
$disableMimeCheck = FALSE;									/* TRUE disables check on MIME types. Only file endings are
															   checked. Set to FALSE by default for security reasons. */
$defaultSort = "name";										/* This is the default way files are sorted when you first 
															   log in. It can have three values: "name" sorts on file 
															   name; "size" sorts on file size; "date" sorts on the 
															   last modification time. */
$defaultLang = "en";										/* The language code for the default language. */
$allowAllFiles = FALSE;										/* If set to TRUE there are no restrictions as to what 
															   kind of files are allowed for uploading. This is not 
															   encouraged and the default is FALSE for security 
															   reasons. */
/* List of allowed file endings and their MIME-types. Both file ending and MIME-type has to match before the file is allowed for upload. */
$allowedfile["jpg"] = "image/jpeg";							/* JPEG image file (.jpg) */
$allowedfile["jpeg"] = "image/jpeg";						/* JPEG image file (.jpeg) */
$allowedfile["jpe"] = "image/jpeg";							/* JPEG image file (.jpe) */
$allowedfile["gif"] = "image/gif";							/* GIF image file */
$allowedfile["png"] = "image/png";							/* PNG image file */
$allowedfile["tif"] = "image/tif";							/* TIFF image file (.tif) */
$allowedfile["tiff"] = "image/tiff";						/* TIFF image file (.tiff) */
$allowedfile["html"] = "text/html";							/* HTML file (.html) */
$allowedfile["htm"] = "text/html";							/* HTML file (.htm) */
$allowedfile["css"] = "text/css";							/* CSS file (.css) */
$allowedfile["xml"] = "text/xml";							/* XML file (.xml) */
$allowedfile["txt"] = "text/plain";							/* Regular text file */
$allowedfile["doc"] = "application/msword";					/* MS Word document */
$allowedfile["rtf"] = "application/rtf";					/* RTF document */
$allowedfile["pdf"] = "application/pdf";					/* PDF document */
$allowedfile["pot"] = "application/mspowerpoint";			/* MS PowerPoint document (.pot) */
$allowedfile["pps"] = "application/mspowerpoint";			/* MS PowerPoint document (.pps) */
$allowedfile["ppt"] = "application/mspowerpoint";			/* MS PowerPoint document (.ppt) */
$allowedfile["ppz"] = "application/mspowerpoint";			/* MS PowerPoint document (.ppz) */
$allowedfile["xls"] = "application/x-excel";				/* MS Excel document */
//$allowedfile["php"] = "application/octet-stream";			/* PHP file. Turned off per default for security reasons */
//$allowedfile["php3"] = "application/octet-stream";		/* PHP3 file. Turned off per default for security reasons */

$allowedAlternate["jpg"] = "image/pjpeg";					/* JPEG image file alternate (.jpg) */
$allowedAlternate["jpeg"] = "image/pjpeg";					/* JPEG image file alternate (.jpeg) */
$allowedAlternate["jpe"] = "image/pjpeg";					/* JPEG image file alternate (.jpe) */
$allowedAlternate["png"] = "image/x-png";					/* PNG image file alternate. */

/* End setup information */

/* Start colours and fonts */

$CSS_fontSize = "12px";										/* The normal font size */
$CSS_fontFamily = "Verdana, \"Arial Narrow\", sans-serif";	/* Normal font */
$CSS_bg = "#fff";											/* Page background */
$CSS_fontColour = "#000";									/* Normal font colour */
$CSS_smallText = "10px";									/* Font size for small text */
$CSS_linkColour = "#039";									/* Links for filenames */
$CSS_linkHover = "#9cc";									/* Background colour on link hover */
$CSS_errorColour = "#f00";									/* The colour of error messages */
$CSS_okayColour = "#393";									/* The colour of okay messages */
$CSS_tableHeaderBg = "#039";								/* Background colour for the table header */
$CSS_tableHeaderColour = "#fff";							/* Font colour for the table header */
$CSS_tableHeaderSize = "14px";								/* Font size for the table header */
$CSS_tableHeaderSizeSmall = "12px";							/* Font size for a small table header */
$CSS_tableBg = "#fff";										/* Normal background for the file list */
$CSS_tableBgAlt = "#cff";									/* Alternative background for the file list */
$CSS_menuColour = "#000";									/* Font colour for the menu */
$CSS_menuBg = "#9cc";										/* Background colour for the menu */
$CSS_menuHoverBg = "#cff";									/* Background colour for the menu on hover */
$CSS_menuWidth = "60px";									/* The width of the menu */

/* End colours and fonts */

/* Start language information */

$msg["en"]["language"] = "en";									/* The language these messages are written in. */
$msg["en"]["title"] = "File Thingie";							/* The title in the browser window. */
$msg["en"]["headline"] = "Files in: %VAR1%/ ";					/* The headline above the dashed line. */
$msg["en"]["headlineLogin"] = "Please Login";					/* Headline for the login screen. */
$msg["en"]["headlineLoginError"] = "Login Error!";				/* Headline for the login screen on error. */
$msg["en"]["headlineShowSource"] = "Showing source: %VAR1%";	/* Headline for Show Source. */
$msg["en"]["headlineEdit"] = "Edit: %VAR1%";					/* Headline for the Edit page. */
$msg["en"]["menuHome"] = "Home";								/* Menu item for 'Home' link. */
$msg["en"]["menuReload"] = "Reload";							/* Menu item for 'Reload' link. */
$msg["en"]["menuHelp"] = "Help";								/* Menu item for 'Help' link. */
$msg["en"]["menuLogOut"] = "Log out";							/* Menu item for 'Log out' link. */
$msg["en"]["menuUp"] = "Up &uArr;";								/* Menu item for 'Up' link. */
$msg["en"]["tableFile"] = "File";								/* Table header for the 'File' column. */
$msg["en"]["tableOptions"] = "Options";							/* Table header for the 'Options column. */
$msg["en"]["tableDate"] = "Last change";						/* Table header for the 'Last change' column. */
$msg["en"]["tableFooter"] = "%VAR1% file(s), %VAR2%kb";			/* Table footer. */
$msg["en"]["buttonUpload"] = "Upload";							/* Button for upload. */
$msg["en"]["buttonMkdir"] = "Create";							/* Button for creating a directory. */
$msg["en"]["buttonOk"] = "ok";									/* 'Ok' button for renaming files and directories. */
$msg["en"]["buttonLogin"] = "login";							/* Login button. */
$msg["en"]["buttonSaveFile"] = "Save file";						/* 'Save' button on edit page. */
$msg["en"]["buttonCancel"] = "Cancel";							/* 'Cancel' button. */
$msg["en"]["textUser"] = "User: ";								/* Text on login page. */
$msg["en"]["textPass"] = "Pass: ";								/* Text on login page. */
$msg["en"]["textConvertToTabs"] = "Convert spaces to tabs";		/* 'Convert..' text on edit page. */
$msg["en"]["textUpload"] = "Upload file: ";						/* Text for upload box. */
$msg["en"]["textMkdir"] = "Create directory: ";					/* Text for Mkdir box. */
$msg["en"]["textFileDel"] = "File %VAR1% deleted.";				/* Message when file is deleted. */
$msg["en"]["textUp"] = "File %VAR1% uploaded.";					/* Message when a file has been uploaded. */
$msg["en"]["textDirDel"] = "Directory %VAR1% removed.";			/* Message when directory is deleted. */
$msg["en"]["textNewDir"] = "Directory %VAR1% created.";			/* Message when directory is created. */
$msg["en"]["textNewFile"] = "File %VAR1% created.";				/* Message when file is created. */
$msg["en"]["textRen"] = "File %VAR1% was renamed to %VAR2%";	/* Message when file is renamed. */
$msg["en"]["textEdit"] = "File %VAR1% edited.";					/* Message when file is edited. */
$msg["en"]["textFile"] = "file";								/* Text after directory name. */
$msg["en"]["textFiles"] = "files";								/* Text after directory name. */
$msg["en"]["textDirectory"] = "directory";						/* Text for directory. */
$msg["en"]["textNew"] = "New";									/* Text for new. */
$msg["en"]["textDirEmpty"] = "Directory is empty.";				/* Text when directory is empty. */
$msg["en"]["textConfirm"] = "Do you really want to delete this file?";
															/* Message for confirmation box when clicking 'delete'. */
$msg["en"]["titleListFiles"] = "List files in %VAR1%.";			/* Tooltip when hovering directory name. */
$msg["en"]["titleOpenFile"] = "Open %VAR1% in the browser.";	/* Tooltip when hovering file name. */
$msg["en"]["titleEdit"] = "Edit %VAR1%";						/* Tooltip for edit link. */
$msg["en"]["titleShowSource"] = "View the source of %VAR1%";	/* Tooltip for show source link. */
$msg["en"]["titleDel"] = "Delete %VAR1%";						/* Tooltip for delete link. */
$msg["en"]["titleRen"] = "Rename %VAR1%";						/* Tooltip for rename link. */
$msg["en"]["linkRename"] = "rename";							/* 'Rename' link for files and directories. */
$msg["en"]["linkDelete"] = "delete";							/* 'Delete' link for files and directories. */
$msg["en"]["linkLicense"] = "License Information";				/* Link to license information. */
$msg["en"]["linkAbout"] = "About the Author";					/* Link to solitude.dk. */
$msg["en"]["linkBack"] = "[go back]";							/* Go back links on show source, help etc. */
$msg["en"]["linkSrc"] = "[src]";								/* Link text for show source link. */
$msg["en"]["linkEdit"] = "[edit]";								/* Link text for edit link. */
$msg["en"]["linkW3"] = "[&radic;]";								/* Link for the W3C validator. */
$msg["en"]["err"] = "Error: ";									/* Standard error. */
$msg["en"]["errNoShow"] = "You are not allowed to view the source of this file.";
																/* Error when showing source isn't allowed. */
$msg["en"]["errNoFile"] = "This file does not exists.";			/* Error when file doesn't exists. */
$msg["en"]["errDirNotDel"] = "Directory %VAR1% not deleted. Possible error: Directory not empty.";
																/* Error if directory can't be deleted. */
$msg["en"]["errFileNotDel"] = "File %VAR1% not deleted.";		/* Error if file can't be deleted. */
$msg["en"]["errUp0"] = "Files of the type %VAR1% and the ending .%VAR2% are not allowed for upload.";
																/* Error when uploading illegal file. */
$msg["en"]["errUp1"] = "The file was too large.";				/* Error when file is larger than allowed. */
$msg["en"]["errUp2"] = "Only a part of the file was uploaded. Please try again.";
																/* Error when upload is only partial. */
$msg["en"]["errUp3"] = "No file was uploaded. Please try again.";	/* Error when no file is uploaded. */
$msg["en"]["errUnknown"] = "Unknown error.";					/* Unknown error. */
$msg["en"]["errNoDir"] = "Directory %VAR1% not created.";		/* Error when directory can't be created. */
$msg["en"]["errNoFile0"] = "File %VAR1% not created.";			/* Error when file can't be created. */
$msg["en"]["errNoFile1"] = "File %VAR1% not created. File ending .%VAR2% not allowed.";
																/* Error when new file ending isn't allowed. */
$msg["en"]["errNoMove"] = "File not moved. You cannot move files outside the designated starting directory.";
																/* Error when trying to move files outside starting directory. */
$msg["en"]["errPHP"] = "Your PHP version is <strong>%VAR1%</strong>. You need at least version 4.2.0 for the Show Source function to work.";
																/* Error when PHP version is too low. */
$msg["en"]["errNoRen0"] = "File %VAR1% could not be renamed. Possible reason: New file name already exists.";
																/* Error when file can't be renamed. */
$msg["en"]["errNoRen1"] = "You do not have permissions to rename %VAR1%";
															/* Error when you don't have permission to rename file. */
$msg["en"]["errNoRen2"] = "File %VAR1% could not be renamed. New file ending (.%VAR2%) not allowed.";
																/* Error when renaming and new file ending is illegal. */
$msg["en"]["errNoEdit0"] = "You cannot edit this file. Either is doesn't exists or it is write protected.";
																/* Error when editing write protected file. */
$msg["en"]["errNoEdit1"] = "File %VAR1% cannot be edited. File ending (.%VAR2%) not allowed for edit.";
																/* Error when file ending is illegal for edit. */
$msg["en"]["textUrl"] = "from Url";								/* Not in use yet. */
$msg["en"]["buttonReset"] = "Reset";							/* Text for 'Reset' button. */
$msg["en"]["tableSize"] = "size";								/* Text for link to sort by size. */
$msg["en"]["languageLong"] = "English";							/* Long description of the language. */
$msg["en"]["chooseLanguage"] = "Lang:";							/* Long description of the language. */

/* End language information */

function getExt ($name) {
	// This function returns the file ending without the "."
	if (strstr($name, ".")) {
		$ext = str_replace(".", "", strrchr($name, "."));
	} else {
		$ext = "";
	}
	return $ext;
}
function checkFileType ($type, $ext) {
	// This function checks whether the type and file ending is in the list of allowed files.
	global $allowedfile, $allowedAlternate, $disableMimeCheck, $allowAllFiles;
	if ($allowAllFiles == TRUE) {
		return TRUE;
	} else {
		$ext = strtolower($ext);
		if ($disableMimeCheck == FALSE) {
			foreach ($allowedfile as $currentext => $currenttype) {
				if ($ext == strtolower($currentext) && $type == strtolower($currenttype)) {
					return TRUE;
					break;
				}
			}
			foreach ($allowedAlternate as $currentext => $currenttype) {
				if ($ext == strtolower($currentext) && $type == strtolower($currenttype)) {
					return TRUE;
					break;
				}
			}
		} else {
			if (array_key_exists($ext, $allowedfile) || array_key_exists($ext, $allowedAlternate)) {
				return TRUE;
			}
		}
	}
}
function outputAcceptedFiles($allowedfile) {
	// This function returns a comma-seperated list of allowed file types for use in the HTML form.
	$allowedfile = array_unique($allowedfile);
	foreach ($allowedfile as $mimetype) {
		$formaccept = "{$formaccept}, {$mimetype}";
	}
	$formaccept = substr($formaccept, 2);
	return $formaccept;
}
function xhtml_highlight($file) {
	// This function changes the highlight_file() to be xhtml compliant.
	global $msg;
	if (version_compare(phpversion(), "4.2.0") == "-1") {
		$string = "<p class=\"error\">".printMsg("err").printMsg("errPHP2", phpversion())."</p>";
	} else {
		$string = highlight_file($file, TRUE);
	}
	// Fix lines
	$string = str_replace("<br />", "\n", $string);
	// Fix spaces and tabs
	$string = str_replace("&nbsp;&nbsp;&nbsp;&nbsp;", "\t", $string);
	$string = str_replace("&nbsp;", " ", $string);
	// Fix <font>-tags
	$string = str_replace("</font>", "</span>", $string);
	$string = str_replace("<font color=\"", "<span style=\"color:", $string);
	return $string;
}
function checkForEdit($ext) {
	// This functions checks a file ending when editing files.
	global $edittxt, $edithtml, $editphp, $phpendings, $htmlendings;
	if ($edittxt == TRUE && $ext == "txt") {
		return 1;
	} elseif ($edithtml == TRUE && in_array($ext, $htmlendings)) {
		return 1;
	} elseif ($editphp == TRUE && in_array($ext, $phpendings)) {
		return 1;
	} else {
		return 0;
	}
}
function checkForSource($ext) {
	// This functions checks a file ending when showing the source of files.
	global $showphp, $phpendings, $htmlendings;
	if ($showphp == TRUE && in_array($ext, $phpendings)) {
		return 1;
	} elseif (in_array($ext, $htmlendings)) {
		return 1;
	}
}
function checkForW3link($ext) {
	// This functions checks if a link to the W3c validator should be showed.
	global $showW3link, $phpendings, $htmlendings;
	if ($showW3link == TRUE) {
		if (in_array($ext, $phpendings)) {
			return 1;
		} elseif (in_array($ext, $htmlendings)) {
			return 1;
		}
	}
}
function buildMenu($self, $uplink, $reloadlink, $helplink) {
	// This functions outputs the menu.
	global $subdir, $msg;
	if (IsSet($subdir)) {
		$uplink = "<li><a href=\"{$self}{$uplink}\">".printMsg("menuUp")."</a></li>";
	} else {
		$uplink = "";
	}
	echo "<ul id=\"menu\">
		{$uplink}
		<li><a href=\"{$self}\">".printMsg("menuHome")."</a></li>
		<li><a href=\"{$self}{$reloadlink}\">".printMsg("menuReload")."</a></li>
		<li><a href=\"{$self}{$helplink}\">".printMsg("menuHelp")."</a></li>
		<li><a href=\"{$self}?action=logout\">".printMsg("menuLogOut")."</a></li>
	</ul>";
}
function renameFile ($name, $phpendings, $editphp, $showphp, $allowedfile, $dir) {
	// This function handles renaming of files.
	global $_POST, $msg, $allowAllFiles;
	$oldfile = stripslashes($_POST["oldfile"]);
	$newfile = stripslashes($_POST["newfile"]);
	if ((!in_array(getExt($oldfile), $phpendings) && !in_array(getExt($newfile), $phpendings)) || ($editphp == TRUE && $showphp == TRUE) || (getExt($oldfile) == getExt($newfile)) || $allowAllFiles == TRUE) {
		if (array_key_exists(getExt($name), $allowedfile) || is_dir("{$dir}/{$oldfile}") || $allowAllFiles == TRUE) {
			if (is_writeable("{$dir}/{$oldfile}")) {
				if (@rename("{$dir}/{$oldfile}", "{$dir}/{$newfile}")) {
					echo "<p class=\"okay\">".printMsg("textRen", $oldfile, $newfile)."</p>";
				} else {
					echo "<p class=\"error\">".printMsg("err").printMsg("errNoRen0", $oldfile)."</p>";
				}
			} else {
				echo "<p class=\"error\">".printMsg("err").printMsg("errNoRen1", $oldfile)."</p>"; 
			}
		} else {
			echo "<p class=\"error\">".printMsg("err").printMsg("errRenNo2", $oldfile, getExt($newfile))."</p>";
		}
	} else {
		echo "<p class=\"error\">".printMsg("err").printMsg("errNoRen1", $oldfile)."</p>";
	}
}
function printCssValue ($value, $isColour = TRUE) {
	// This function outputs values used in the stylesheet.
	$value = rtrim($value, ";");
	if ($isColour == TRUE && $value[0] != "#") {
		$value = "#{$value}";
	}
	echo $value;
}
function printMsg ($msgType) {
	// This function prints a message.
	global $msg, $currentLang;
	if (IsSet($msg[$currentLang][$msgType])) {
		$currentmsg = $msg[$currentLang][$msgType];
	} else {
		$currentmsg = "Message \"{$msgType}\" not found.";
	}
	if (func_num_args() != 1) {
		for ($i=1;$i<func_num_args();$i++) {
			$replace = func_get_arg($i);
			$currentmsg = str_replace ("%VAR{$i}%", $replace, $currentmsg);
		}
	}
	return $currentmsg;
}
function printTableHeaderLink($text, $link, $sort) {
	if ($sort == $_SESSION["sort"]) {
		return "<span class=\"current\">{$text}</span>";
	} else {
		return "<a href=\"{$link}&amp;sort={$sort}\">{$text}</a>";
	}
}
function printTableHeader() {
	global $showdatecolumn, $self, $reloadlink, $msg;
	echo "<table cellspacing=\"0\">
	<tr>
		<th>".printTableHeaderLink(printMsg("tableFile"), "{$self}{$reloadlink}", "name")." <span class=\"size\">(".printTableHeaderLink(printMsg("tableSize"), "{$self}{$reloadlink}", "size").")</span></th>
		<th class=\"center\">".printMsg("tableOptions")."</th>";
	if ($showdatecolumn == 3) {
		echo "
		<th class=\"center\">".printTableHeaderLink(printMsg("tableDate"), "{$self}{$reloadlink}", "date")."</th>";
	}
	echo "
	</tr>";
}

if ($showdatecolumn == TRUE) {
	$showdatecolumn = 3;
} else {
	$showdatecolumn = 2;
}

if (IsSet($_SESSION["lang"])) {
	$currentLang = $_SESSION["lang"];
} else {
	$currentLang = "en";
}

$self = basename($_SERVER["PHP_SELF"]);
if (stristr($_SERVER["REQUEST_URI"], "?")) {
	$requesturi = substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], "?"));
	$location = "Location: http://{$_SERVER["HTTP_HOST"]}{$requesturi}";
} else {
	$requesturi = $_SERVER["REQUEST_URI"];
	$location = "Location: http://{$_SERVER["HTTP_HOST"]}{$requesturi}";
}
$log_user = $_POST["log_user"];
$log_pass = $_POST["log_pass"];
$ses_user = $_SESSION["user"];
$ses_pass = $_SESSION["pass"];

// If the user request a directory above the location of File Thingie we deny it to him.
if (IsSet($_GET["subdir"])) {
	$subdir = $_GET["subdir"];
	if (strstr($subdir, "..")) {
		UnSet($subdir);
	}
}
// We make sure the user can't delete/rename files outside File Thingie.
if (IsSet($_GET["file"])) {
	if (strstr($_GET["file"], "..")) {
		$_GET["file"] = str_replace("..", "", $_GET["file"]);
	}
}
if (IsSet($_POST["action"])) {
	$action = $_POST["action"];
} else {
	$action = $_GET["action"];
}
if (IsSet($_GET["sort"])) {
	$_SESSION["sort"] = $_GET["sort"];
}
if ($action == "logout") {
	session_unset();
	session_destroy();
	header($location);
	exit;
}
if (IsSet($subdir)) {
// If we are in a subdirectory the action value for forms are changed and the link to move one directory up is defined.
	$originaldir = $dir;
	$dir = "{$dir}/{$subdir}";
	$formaction = "{$self}?subdir={$subdir}";
	$reloadlink = "?action=list&amp;subdir={$subdir}";
	$helplink = "?action=help&amp;subdir={$subdir}";
	$subdirlink = "&amp;subdir={$subdir}";
	if (strstr($subdir, "/")) {
		$uplink = substr($subdir, 0, strrpos($subdir, "/"));
		$uplink = "?action=list&amp;subdir={$uplink}";
	} else {
		$uplink = "?action=list";
	}
} else {
	$formaction = $self;
	$uplink = "";
	$helplink = "?action=help";
	$reloadlink = "?action=list";
	$subdirlink = "";
}
if($ses_user != $username || $ses_pass != $password) {
	if($action == "login") {
		if ($log_user != $username || $log_pass != $password) {
			$location = "$location?x=error";
			header($location);
			exit;			
		} else {
			$user = $log_user;
			$pass = $log_pass;
			$sort = $defaultSort;
			$lang = $_POST["log_lang"];
			// Sets a cookie to remember which language was used.
			if ($lang != $defaultLang || $_COOKIE["languagepreference"] != $lang) {
				setcookie("languagepreference", $lang, time()+60*60*24*365);
			}
			session_register("lang");
			session_register("sort");
			session_register("user");
			session_register("pass");
			header($location);
			exit;
		}
	}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo printMsg("language");?>">
	<head>
		<title><?php echo printMsg("title");?></title>
		<link rel="copyright" title="<?php echo printMsg("linkLicense");?>" href="http://creativecommons.org/licenses/by-nd-nc/1.0/" />
		<link rel="author" title="<?php echo printMsg("linkAbout");?>" href="http://www.solitude.dk/" />
		<style type="text/css">
			body {
				padding:20px;
				margin:0;
				font-family:"Courier New", monospace;
				font-size:<?php printCssValue($CSS_fontSize, FALSE);?>;
				background:<?php printCssValue($CSS_bg);?>;
				color:<?php printCssValue($CSS_fontColour);?>;
			}
			h1 {
				font-size:24px;
				font-weight:normal;
				border-bottom:1px dashed <?php printCssValue($CSS_fontColour);?>;
			}
			div input, div select {
				vertical-align:middle;
			}
			select, input {
				font-family:"Courier New", monospace;
			}
		</style>
	</head>
	<body>
		<form action="<?php echo $self;?>" method="post">
		<?php
			if ($_GET["x"] == "error") {
				echo "<h1>".printMsg("headlineLoginError")."</h1>";
			} else {
				echo "<h1>".printMsg("headlineLogin")."</h1>";
			}
		?>
		<?php
			$installedLangs = array_keys($msg);
			if (sizeof($installedLangs) != 1) {
		?>
			<div>
				<label for="log_lang"><?php echo printMsg("chooseLanguage");?></label>
				<select name="log_lang" id="log_lang">
					<?php
						foreach (array_keys ($msg) as $langCode) {
							echo "<option value=\"{$langCode}\"";
							if (IsSet($_COOKIE["languagepreference"])) {
								if (IsSet($msg[$_COOKIE["languagepreference"]])) {
									if ($_COOKIE["languagepreference"] == $langCode) {
										echo " selected=\"selected\"";
									}
								} else {
									if ($defaultLang == $langCode) {
										echo " selected=\"selected\"";
									}
								}
							} elseif ($defaultLang == $langCode) {
								echo " selected=\"selected\"";
							}
							echo ">{$msg[$langCode]["languageLong"]} ({$langCode})</option>";
						}
					?>
				</select>
			</div>
		<?php
			} else {
				echo "<input type=\"hidden\" name=\"log_lang\" value=\"{$installedLangs[0]}\" />";
			}
		?>
			<div>
				<label for="log_user"><?php echo printMsg("textUser");?></label><input type="text" size="15" name="log_user" id="log_user" />
			</div>
			<div>
				<label for="log_pass"><?php echo printMsg("textPass");?></label><input type="password" size="15" name="log_pass" id="log_pass" />
				<input type="hidden" name="action" value="login" />
				<input type="submit" value="<?php echo printMsg("buttonLogin");?>" />
			</div>
		</form>
	</body>
</html>
<?php
exit;
} else {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo printMsg("language");?>">
<head>
<title><?php echo printMsg("title");?></title>
<link rel="home" title="<?php echo printMsg("menuHome");?>" href="<?php echo $self;?>" />
<link rel="help" title="<?php echo printMsg("menuHelp");?>" href="<?php echo "{$self}{$helplink}";?>" />
<?php
	if(IsSet($subdir)) {
?>
<link rel="up" title="<?php echo printMsg("menuUp");?>" href="<?php echo "{$self}{$uplink}";?>" />
<?php 
	}
?>
<link rel="copyright" title="<?php echo printMsg("linkLicense");?>" href="http://creativecommons.org/licenses/by-nd-nc/1.0/" />
<link rel="author" title="<?php echo printMsg("linkAbout");?>" href="http://www.solitude.dk/" />
<style type="text/css">
form, form div, ul, h1, h2, input, table, td, th, p, body {
	margin:0;
	padding:0;
}
body, td {
	padding:20px;;
	font-family:<?php printCssValue($CSS_fontFamily, FALSE);?>;
	font-size:<?php printCssValue($CSS_fontSize, FALSE);?>;
/*	background:<?php printCssValue($CSS_bg);?> url(<?php echo $useimage;?>) no-repeat scroll top right;*/
	background:<?php printCssValue($CSS_bg);?>;
	color:<?php printCssValue($CSS_fontColour);?>;
}
.small {
	font-size:<?php printCssValue($CSS_smallText, FALSE);?>;
}
a { /* Normal links and file names. */
	color:<?php printCssValue($CSS_linkColour);?>;
	text-decoration:none;
}
a.dir { /* Links that points to a directory. */
	font-weight:bold;
}
a:hover { /* Hover effect for links and filenames. */
	text-decoration:underline;
	background:<?php printCssValue($CSS_linkHover);?>;
}
p { /* Normal paragraphs. Used for error and okay messages. */
	margin:1em 0 0 1em;
}
.error { /* Error messages */
	color:<?php printCssValue($CSS_errorColour);?>;
}
.okay { /* Okay messages */
	color:<?php printCssValue($CSS_okayColour);?>;
}
h1 { /* The header. "Files in: ..." */
	font-size:24px;
	font-weight:normal;
	border-bottom:1px dashed <?php printCssValue($CSS_fontColour);?>;
}
h2 { /* Headers on the Help page. */
	font-size:16px;
	font-weight:normal;
	margin:1em 0 0 0;
}
input { /* All form fields. */
	font-family:Verdana, sans-serif;
	height:20px;
	vertical-align:middle;
}
input[disabled][type="text"] { /* Rename fields for write protected files. */
	background:gray;
	color:black;
}
label { /* "Upload file:" and "Create dicrectory" NOT USED! */
	font-weight:bold;
}
ul#menu { /* Overall for the menu */
	list-style:none;
	margin:20px 20px 0 0;
	float:left;
}
ul#menu li { /* Each menu item. */
	padding:0 4px 0 0;
	margin:5px 0;
	text-align:center;
}
ul#menu li a { /* Menu links. */
	display:block;
	color:<?php printCssValue($CSS_menuColour);?>;
	background:<?php printCssValue($CSS_menuBg);?>;
	text-decoration:none;
	width:<?php printCssValue($CSS_menuWidth, FALSE);?>;
	padding:2px;
	margin:0;
	border:1px solid <?php printCssValue($CSS_fontColour);?>;
}
ul#menu li a:hover { /* Hover effect for menu links. */
	background:<?php printCssValue($CSS_menuHoverBg);?>;
	color:<?php printCssValue($CSS_menuColour);?>;
	text-decoration:none;
	border:1px dashed <?php printCssValue($CSS_fontColour);?>;
}
p.empty { /* "Directory is empty" message. */
	margin:20px 0 20px 0;
}
table { /* Table for the listing of files. */
	margin:20px 0 20px 0;
	border-left:1px solid <?php printCssValue($CSS_fontColour);?>;
	empty-cells:show;
}
td, th { /* Cells and cell headers for listing of files. */
	padding:4px 10px 4px 10px;
}
th { /* Cell headers for lsiting of files. */
	font-size:<?php printCssValue($CSS_tableHeaderSize, FALSE);?>;
	font-weight:normal;
	background:<?php printCssValue($CSS_tableHeaderBg);?>;
	color:<?php printCssValue($CSS_tableHeaderColour);?>;
	text-align:left;
	border-top:1px solid <?php printCssValue($CSS_fontColour);?>;
	border-bottom:1px solid <?php printCssValue($CSS_fontColour);?>;
	border-right:1px solid <?php printCssValue($CSS_fontColour);?>;
}
th a {
	font-weight:normal;
	background:<?php printCssValue($CSS_tableHeaderBg);?>;
	color:<?php printCssValue($CSS_tableHeaderColour);?>;
}
th a:hover {
	font-weight:normal;
	background:<?php printCssValue($CSS_tableHeaderBg);?>;
	color:<?php printCssValue($CSS_tableHeaderColour);?>;
	text-decoration:underline;
}
th span.current {
	font-weight:bold;
}
th span.size {
	font-size:<?php printCssValue($CSS_tableHeaderSizeSmall, FALSE);?>;
}
td { /* Individual cells for listing of files. */
	border-bottom:1px solid <?php printCssValue($CSS_fontColour);?>;
	border-right:1px solid <?php printCssValue($CSS_fontColour);?>;
}
td.bottom { /* The last cell: "NN file(s), N.NNkb" */
	background:<?php printCssValue($CSS_tableHeaderBg);?>;
	color:<?php printCssValue($CSS_tableHeaderColour);?>;
	text-align:right;
}
.center { /* Used to center "rename / delete" */
	text-align:center;
}
div {
	margin:0em 1em;
}
span.size { /* Span for showing the individual file sizes. */
	font-weight:normal;
}
div#forms {
	margin:10px 0 0 0;
}
div#forms form {
	display:block;
	margin:10px 0;
}
div#forms fieldset { /* Border around upload and create new forms. */
/*	float:left; */
	border:1px hidden white;
}
legend { /* Upload file: and New Directory File. */
	font-weight:bold;
}
fieldset {
	border:0px hidden white;
	padding:5px;
}
textarea { /* Textarea when editing files. */
	display:block;
	margin:20px 0 0 0;
}
code { /* Overall when viewing source. */
	font-family:monospace;
	display:block;
	white-space:pre;
}
.klikket form, .ikkeklikket span {
	display:inline;
}
.ikkeklikket form, .klikket span {
	display:none;
}
.white {
/* Initial colour for cell backgrounds. */
	background:<?php printCssValue($CSS_tableBg);?>;
}
.grey {
/* Alternate colour for cell backgrounds. */
	background:<?php printCssValue($CSS_tableBgAlt);?>;
}
.klikket form input[type="text"] {
/* Input field when renaming a file. */
	border:1px dotted <?php printCssValue($CSS_linkColour);?>;
}
.klikket form input { /* Both input field and button when renaming file. */
	height:20px;
}
a.renamelink { /* The "rename" link. */
	cursor:pointer;
}
strong.button { /* Mimics the menu buttons for the help page. */
	color:<?php printCssValue($CSS_menuColour);?>;
	background:<?php printCssValue($CSS_menuBg);?>;
	text-decoration:none;
	padding:2px 10px;
	margin:0;
	border:1px solid <?php printCssValue($CSS_fontColour);?>;
	font-weight:normal;
}
ul#help {
	list-style:none;
	float:left;
	border:1px dashed <?php printCssValue($CSS_fontColour);?>;
	padding:10px;
	margin:20px 20px 20px 0;
	width:150px;
	line-height:150%;
}
ul#help li:before {
	content:"\00BB \0020";
}
ul#setupinfo {
	margin:1em 4em;
	list-style:circle;
}
ul#setupinfo, p {
	line-height:150%;
}
ul#setupinfo code, p code {
	color:<?php printCssValue($CSS_okayColour);?>;
	display:inline;
}
img#logo {
/*	border:1px solid black;*/
	position:absolute;
	top:5px;
	right:5px;
}
</style>
<script type="text/javascript">
function skift(obj) {
	if (obj.className=='ikkeklikket') {
		obj.className = 'klikket';
		document.getElementById(obj.id + '.ipt').focus();
	}
	else if (obj.className=='klikket') {
		obj.className = 'ikkeklikket';
	}
}
</script>
<script type="text/javascript">
function checkDelete() {
 var value = confirm("<?php echo printMsg("textConfirm");?>");
	if (value == true) {
		return true;
	} else {
		return false;
	}
}
</script>
</head>
<body>
<?php
// Logo image is handled
if (IsSet($useimage) && $useimage != "none" && $useimage != "") {
	if ($logo = @getimagesize($useimage)) {
		echo "<img id=\"logo\" src=\"{$useimage}\" {$logo[3]} alt=\"\" />";
	}
}
if ($action == "showsource") {
	echo "<h1>".printMsg("headlineShowSource", "{$dir}/{$_GET["file"]}")." <a href=\"{$self}{$reloadlink}\" class=\"small\">".printMsg("linkBack")."</a></h1>";
	if (file_exists("{$dir}/{$_GET["file"]}")) {
		if (checkForSource(getExt($_GET["file"])) == 1) {
			echo xhtml_highlight("{$dir}/{$_GET["file"]}");
		} else {
			echo "<p class=\"error\">".printMsg("err").printMsg("errNoShow")."</p>";
		}
	} else {
		echo "<p class=\"error\">".printMsg("err").printMsg("errNoFile")."</p>";
	}
} elseif ($action == "edit") {
	$editfile = "{$dir}/{$_GET["file"]}";
	echo "<h1>".printMsg("headlineEdit", $editfile)." <a href=\"{$self}{$reloadlink}\" class=\"small\">".printMsg("linkBack")."</a></h1>";
	if (checkForEdit(getExt($editfile)) == 1) {
		if (file_exists($editfile) && is_writeable($editfile)) {
			$filecontent = implode ("", file("{$dir}/{$_GET["file"]}"));
			$filecontent = htmlentities($filecontent);
			if ($converttabs == TRUE) {
				$filecontent = str_replace("\t", "    ", $filecontent);
			}
?>
<form action="<?php echo $formaction;?>" method="post">
	<fieldset>
	<textarea cols="76" rows="20" name="filecontent"><?php echo $filecontent;?></textarea>
	<input type="hidden" name="editfile" value="<?php echo $editfile;?>" />
	<input type="hidden" name="action" value="savefile" />
	<input type="submit" value="<?php echo printMsg("buttonSaveFile");?>" name="submittype" />
	<input type="reset" name="reset" value="<?php echo printMsg("buttonReset");?>" />
	<input type="submit" value="<?php echo printMsg("buttonCancel");?>" name="submittypecancel" />
	<input type="checkbox" name="convertspaces" id="convertspaces" /><label for="convertspaces"><?php echo printMsg("textConvertToTabs");?></label>
	</fieldset>
</form>
<?php
		} else {
			echo "<p class=\"error\">".printMsg("err").printMsg("errNoEdit0")."</p>";
		}
	} else {
		echo "<p class=\"error\">".printMsg("err").printMsg("errNoEdit1", $editfile, getExt($editfile))."</p>";
	}
} elseif ($action == "help") {
	echo "<h1>Help Me! <a href=\"{$self}{$reloadlink}\" class=\"small\">".printMsg("linkBack")."</a></h1>";
?>
<ul id="help" class="small">
<li><a href="#license">License</a></li>
<li><a href="#navigation">Navigation</a></li>
<li><a href="#upload">Uploading Files and Creating Directories</a></li>
<li><a href="#rename">Renaming, Moving and Deleting Files</a></li>
<li><a href="#showsource">The Show Source function</a></li>
<li><a href="#edit">Editing Files</a></li>
<li><a href="#setup">Setting up File Thingie</a></li>
<li><a href="#customize">Customizing File Thingie</a></li>
</ul>
<h2 id="license">License</h2>
<p>This work is licensed under the Creative Commons <a href="http://creativecommons.org/licenses/by-nd-nc/1.0/">Attribution-NoDerivs-NonCommercial</a>. Please read the license information before distributing this work. If you want to use File Thingie for a commercial project please <a href="mailto:files@solitude.dk">contact me</a>.</p>
<h2 id="navigation">Navigation</h2>
<p>Navigating is done with the links to the left of the file listing. The <strong class="button">Home</strong> link will always take you to the same position you started at when you logged in. If you are in a subdirectory an <strong class="button">Up</strong> link will appear. This will take you one level up in the directory structure. <strong class="button">Reload</strong> will reload the current page. This is more useful than the reload button of the browser if you have just performed an action (eg. renamed a file). <strong class="button">Help</strong> will take you to this page and <strong class="button">Log out</strong> will log you out out. Be sure to always log out if you are using File Thingie from a shared or public computer!</p>
<p>If your browser supports navigation with <code>&lt;link&gt;</code> elements you will see additional navigation. The "Home", "Help" and "Up" buttons are identical to those in the menu. "Copyright" will take you to the page with license information for File Thingie and "Author" will take you to the author's website.</p>
<h2 id="upload">Uploading Files and Creating Directories</h2>
<p>To upload a file you simply click on the button next to the "upload" button (in my browser it's called "Choose", yours might be "Browse", "..." or something else). This allows you to choose a file from your harddrive you want to upload. When you have selected a file the path should be inserted into the text field. Click "Upload" and wait. If the file you are uploading is large it can take a while. You will get a <span class="okay">green</span> confirmation if the file was uploaded or a <span class="error">red</span> error message if the file was not uploaded correctly. The file be uploaded to the directory you are currently in.</p>
<p>To create a directory simply type the name you you want in text field and click "Mkdir". The directory will be dreated as a subdirectory to the directory you are currently in. You will get a <span class="okay">green</span> confirmation if the directory was created or a <span class="error">red</span> error message if it wasn't.</p>
<h2 id="rename">Renaming, Moving and Deleting Files</h2>
<p>To rename a file or directory click the "rename" link next to the name of the file or directory you wish to rename. The name will change to a text field and an "ok" button. Type the new name in the field and click the "ok" button. Even if you click multiple rename links and change multiple names only the name next to the "ok" button will be renamed.</p>
<p>It is also possible to move a file with the rename field. To move a file or directory to a subdirectory in the current directory simply write the path in the text field. For example: If you want to move the file.txt to the subdirectory subdirectory simply write "subdirectory/file.txt" in the text field. If you want to move a file or directory <em>up</em> in the directory structure use "../". For example: If you have file.txt in a subdirectory and you wish to move it to the home directory simply write "../file.txt" in the text field. Use multiple (eg. "../../") if you want to move the file up more than one step.</p>
<p>To delete a file or directory simply click the "delete" link. <strong>Warning:</strong> the file will be deleted without confirmation!</p>
<p>You will get a <span class="okay">green</span> confirmation if the file was moved/renamed/deleted or a <span class="error">red</span> error message if it wasn't.</p>
<h2 id="showsource">The Show Source function</h2>
<p>If you enable it in your <a href="#setup">configuration</a> File Thingie will allow you to view the source of <acronym title="HyperText Markup Language">HTML</acronym>/<acronym title="Cascading Style Sheets">CSS</acronym>/PHP documents. By default it is enabled for <acronym title="HyperText Markup Language">HTML</acronym> and <acronym title="Cascading Style Sheets">CSS</acronym> and thus you can avoid having to finding the "View Source" functionality in your browser. If you enable view source for PHP files you will get colour coded source code to help you. The view source link will show up as a link titled <strong>[src]</strong> after the file name and size.</p>
<p>Due to problems with earlier versions of PHP the view source function is only available for PHP versions of 4.2.0 or later. Your PHP version is <strong><?php echo phpversion();?></strong></p>
<h2 id="edit">Editing Files</h2>
<p>If it's enabled in your <a href="#setup">configuration</a> you can edit <acronym title="HyperText Markup Language">HTML</acronym>/<acronym title="Cascading Style Sheets">CSS</acronym>/PHP documents. It is enabled by default only for text, <acronym title="HyperText Markup Language">HTML</acronym> and <acronym title="Cascading Style Sheets">CSS</acronym>. If you can edit a file there will be a <strong>[edit]</strong> after the file name. Clicking this will take you to the edit screen. After you are done click "Save file", or click "Cancel" if you don't want to edit the file anyway. Checking the "Convert spaces to tabs" box will convert every <strong>four</strong> spaces to a tab when the file is saved.</p>
<p>You will get a <span class="okay">green</span> confirmation if the file was saved successfully or a <span class="error">red</span> error message if it wasn't.</p>
<h2 id="setup">Setting up File Thingie</h2>
	<p>The configurations start after the line that says: <code>/* Setup information. Change as appropriate */</code></p>
	<ul id="setupinfo">
		<li><code>$username = "USERNAME";</code> Change "USERNAME" to whatever username you want to use. If you want to use <code>BartSimpson</code> as your username the lines should be changed to <code>$username = "BartSimpson";</code>. The username is case sensitive!</li>
		<li><code>$password = "PASSWORD";</code> Change "PASSWORD" to whatever you want your password to be. The password is case sensitive!</li>
		<li><code>$dir = "test";</code> Change "test" to the subdirectory you want to File Thingie to work in. The File Thingie file has to be located one step above this directory. Eg. if you want File Thingie to work in /public_html/subdirectory/ the file should be placed in /public_html/ and the line should be changed to <code>$dir = "subdirectory";</code></li>
		<li><code>$maxsize = 100000;</code> Change this to the maximum file size you want to allow for upload. The size is given in bytes.</li>
		<li><code>$showphp = FALSE;</code> Change FALSE to TRUE if you want to allow the viewing of source for PHP files.</li>
		<li><code>$edithtml = TRUE;</code> Change TRUE to FALSE if you want to turn off editing of HTML and CSS files.</li>
		<li><code>$edittxt = TRUE;</code> Change TRUE to FALSE if you want to turn off editing of text files.</li>
		<li><code>$editphp = FALSE;</code> Change FALSE to TRUE if you want to turn on editing of PHP files.</li>
		<li><code>$converttabs = TRUE;</code> Change TRUE to FALSE if you don't want to convert tabs to spaces when editing files. In a browser it's easier to work with spaces than tabs (since the tab-key doesn't work in a text field).</li>
		<li><code>$phpendings = array("php", "php3", "php4", "phtml");</code> A list of files percieved by File Thingie as PHP files. You probably don't have to add anything.</li>
		<li><code>$allowedfile["jpg"] = "image/jpeg";</code> There is a long list of lines that looks like this. They represent all the different types of files allowed for upload. If you want to disallow a file type place two slashes (//) in front of the line (like it has been done with the two last lines). If you want to add more file types just add your own lines in this format: <code>$allowedfile["FileEnding"] = "MIMEType";</code></li>
	</ul>
	<p>Remember that you should CHMOD the <em>subdirectory</em> you want to work with to 777. Otherwise File Thingie will not have permissions to upload files.. or permissions to do anything other than state "Directory is empty" often.</p>
<h2 id="customize">Customizing File Thingie</h2>
<p>It is possible to quickly choose your own colours and fonts to use with File Thingie. The settings are immediately after the setup information. Each option is explained there.</p>
<p>When writing a font-family it is important the seperate each font with a comma. Futhermore it is vital that double quotes (") are written with a backslash infront (ie. \" and not just "). You are encouraged to always end your list with a default font-family - usually either <code>sans-serif</code> or <code>serif</code>.</p>
<p>The colour part of the colours and fonts <strong>only</strong> accepts <strong>hex-values</strong>. It is not possible to use decimal values or any of the keywords. Only hex-values!</p>
<p>If you want to modify more of the design than just the colours and fonts the stylesheet for File Thingie begins around line 350.</p>
<?php
} else {
	echo "<h1>".printMsg("headline", $dir)."</h1>";
?>
<div id="forms">
	<form action="<?php echo $formaction;?>" method="post" enctype="multipart/form-data" accept="<?php echo outputAcceptedFiles($allowedfile);?>">
		<fieldset>
			<legend><?php echo printMsg("textUpload");?></legend>
			<input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $maxsize;?>" />
			<input type="file" name="localfile" id="localfile" />
			<input type="hidden" name="action" value="upload" />
			<input type="submit" value="<?php echo printMsg("buttonUpload");?>" />
		</fieldset>
	</form>
	<form action="<?php echo $formaction;?>" method="post">
		<fieldset>
			<legend><?php echo printMsg("textNew");?> 
				<input type="radio" id="newtypedir" name="newtype" checked="checked" value="dir" />
				<label for="newtypedir"><?php echo printMsg("textDirectory");?></label> 
				<input type="radio" id="newtypefile" name="newtype" value="file" />
				<label for="newtypefile"><?php echo printMsg("textFile");?></label> 
<!--				<input type="radio" id="newtypeurl" name="newtype" value="url" />
				<label for="newtypeurl"><?php echo printMsg("textUrl");?></label>-->
			</legend>
			<input size="30" name="newdir" id="newdir" />
			<input type="hidden" name="action" value="mkdir" />
			<input type="submit" value="<?php echo printMsg("buttonMkdir");?>" />
		</fieldset>
	</form>
</div>
<?php
	if ($action == "delete") {
	// If we are to delete a file or directory we bring forward the flaming sword.
		$file = stripslashes("{$dir}/{$_GET["file"]}");
		if (is_dir($file)) {
			if (!@rmdir($file)) {
				echo "<p class=\"error\">".printMsg("err").printMsg("errDirNotDel", $file)."</p>";
			} else {
				echo "<p class=\"okay\">".printMsg("textDirDel", $file)."</p>";
			}
		} else {
			if (!@unlink($file)) {
				echo "<p class=\"error\">".printMsg("err").printMsg("errFileNotDel", $file)."</p>";
			} else {
				echo "<p class=\"okay\">".printMsg("textFileDel", $file)."</p>";
			}
		}
	} elseif ($action == "upload") {
	// If we are to upload a file we will do so.
		$tmp_name = $_FILES['localfile']['tmp_name'];
		$name = stripslashes("{$dir}/{$_FILES['localfile']['name']}");
		$ext = getExt($name);
		$type = $_FILES["localfile"]["type"];
		if ($_FILES["localfile"]["error"] == 0) {
			if (checkFileType($type, getExt($name)) == TRUE) {
				if (@move_uploaded_file($tmp_name, $name)) {
					@chmod($name, 0777);
					echo "<p class=\"okay\">".printMsg("textUp", $name)."</p>";
				}
			} else {
				echo "<p class=\"error\">".printMsg("err").printMsg("errUp0", $_FILES["localfile"]["type"], getExt($name))."</p>";
			}
		} else {
			switch($_FILES["localfile"]["error"]) {
				case 1:
					$currenterror = printMsg("errUp1");
					break;
				case 2:
					$currenterror = printMsg("errUp1");
					break;
				case 3:
					$currenterror = printMsg("errUp2");
					break;
				case 4:
					$currenterror = printMsg("errUp3");
					break;
				default:
					$currenterror = printMsg("errUnknown");
					break;
			}
			echo "<p class=\"error\">".printMsg("err")."{$currenterror}</p>";
		}
	} elseif ($action == "mkdir") {
	// If we are to create a dictory we will give it our best shot.
		$newdir = str_replace("../", "", stripslashes($_POST["newdir"]));
		$newdir = "{$dir}/{$newdir}";
		if ($_POST["newtype"] == "dir") {
			$oldumask = umask(0);
			if (@mkdir($newdir, 0777)) {
				echo "<p class=\"okay\">".printMsg("textNewDir", $newdir)."</p>";
			} else {
				echo "<p class=\"error\">".printMsg("err").printMsg("errNoDir", $newdir)."</p>";
			}
			umask($oldumask);
		} else {
			if (array_key_exists(getExt($newdir), $allowedfile)) {
				if (@touch($newdir)) {
					@chmod($newdir, 0777);
					echo "<p class=\"okay\">".printMsg("textNewFile", $newdir)."</p>";
				} else {
					echo "<p class=\"error\">".printMsg("err").printMsg("errNoFile0", $newdir)."</p>";
				}
			} else {
				echo "<p class=\"error\">".printMsg("err").printMsg("errNoFile1", $newdir, getExt($newdir))."</p>";
			}
		}
	} elseif ($action == "rename") {
		if (stristr($_POST["newfile"], "/")/* && !is_dir("{$dir}/{$_POST["oldfile"]}")*/) {
			// If the new file name contains a / we try to move the file.
			if (stristr($_POST["newfile"], "../")) {
				if (IsSet($subdir)) {
					// Okay, check for level.
					$level = substr_count($_POST["newfile"], "../");
					if ($level <= substr_count($dir, "/")) {
						$name = "{$dir}/{$_POST["newfile"]}";
						renameFile ($name, $phpendings, $editphp, $showphp, $allowedfile, $dir);
					} else {
						echo "<p class=\"error\">".printMsg("err").printMsg("errNoMove")."</p>";
					}
				} else {
					echo "<p class=\"error\">".printMsg("err").printMsg("errNoMove")."</p>";
				}
			} else {
				$name = "{$dir}/{$_POST["newfile"]}";
				renameFile ($name, $phpendings, $editphp, $showphp, $allowedfile, $dir);
			}
		} else {
		// Else we rename the file in question.
			$name = "{$dir}/{$_POST["newfile"]}";
			renameFile ($name, $phpendings, $editphp, $showphp, $allowedfile, $dir);
		}
	} elseif ($action == "savefile") {
		// Save a file that has been edited.
		$editfile = stripslashes($_POST["editfile"]);
		if ($_POST["submittype"] != "") {
			if (checkForEdit(getExt($editfile)) == 1) {
				$filecontent = stripslashes($_POST["filecontent"]);
				if ($_POST["convertspaces"] != "") {
					$filecontent = str_replace("    ", "\t", $filecontent);
				}
				if (is_writeable("{$editfile}")) {
					$fp = fopen("{$editfile}", "wb");
					fputs ($fp, $filecontent);
					fclose($fp);
					echo "<p class=\"okay\">".printMsg("textEdit", $editfile)."</p>";
				} else {
					echo "<p class=\"error\">".printMsg("err").printMsg("errNoEdit0")."</p>";
				}
			} else {
				echo "<p class=\"error\">".printMsg("err").printMsg("errNoEdit1", $editfile, getExt($editfile))."</p>";
			}
		}
	}
	$filelist = array();
	$sizelist = array();
	$datelist = array();
	if ($dirlink = @opendir($dir)) {
		// Creates an array with all file names in current directory.
		while (($file = readdir($dirlink)) !== false) {
			if ($file != "." && $file != "..") {
				$currentFileTime = filemtime("{$dir}/{$file}");
				$currentFileSize = filesize("{$dir}/{$file}");
				if (is_dir("{$dir}/{$file}")) {
					$subdirs[] = $file;
					$subdirsdatelist[$file] = $currentFileTime;
					UnSet($currentSubdirSize);
					if ($sublink = @opendir("{$dir}/{$file}")) {
						while (($current = readdir($sublink)) !== false) {
							if ($current != "." && $current != "..") {
								$currentSubdirSize++;
							}
						}
						closedir($sublink);
					} else {
						$currentSubdirSize = "XXX";
					}
					$subdirssizelist[$file] = $currentSubdirSize;
				} else {
					$filelist[] = $file;
					$datelist[$file] = $currentFileTime;
					$sizelist[$file] = $currentFileSize;
				}
			}
		}
	closedir($dirlink);
	}
	$filenum = sizeof($filelist)+sizeof($subdirs);
	buildMenu($self, $uplink, $reloadlink, $helplink);
	if (count($filelist) != 0 || is_array($subdirs)) {
		printTableHeader();
		if ($_SESSION["sort"] == "date") {
			$filelist = array();
			arsort($datelist);
			foreach ($datelist as $file => $currentFileTime) {
				$filelist[] = $file;
			}
			if (is_array($subdirs)) {
				asort($subdirsdatelist);
				foreach ($subdirsdatelist as $currentSubdir => $currentFileTime) {
					array_unshift($filelist, $currentSubdir);
				}
			}
		} elseif ($_SESSION["sort"] == "size") {
			$filelist = array();
			asort($sizelist);
			foreach ($sizelist as $file => $currentFileSize) {
				$filelist[] = $file;
			}
			if (is_array($subdirs)) {
				arsort($subdirssizelist);
				foreach ($subdirssizelist as $currentSubdir => $currentFileSize) {
					array_unshift($filelist, $currentSubdir);
				}
			}
		} else {
			sort($filelist);
			if (is_array($subdirs)) {
				rsort($subdirs);
				for ($i = 0; $i < sizeof($subdirs); $i++) {
					array_unshift($filelist, $subdirs[$i]);
				}
			}
		}
		$i = 0;
		foreach ($filelist as $file) {
			if (is_dir("{$dir}/{$file}")) {
				if (IsSet($subdirssizelist[$file])) {
					$size = $subdirssizelist[$file];
				}
				if (!IsSet($size)) {
					$size = "0";
				}
				if ($size == 1) {
					$size = "{$size} ".printMsg("textFile");
				} else {
					$size = "{$size} ".printMsg("textFiles");
				}
				if (!IsSet($subdir)) {
					$filelink = "<a href=\"{$self}?action=list&amp;subdir={$file}\" title=\"".printMsg("titleListFiles", $file)."\" class=\"dir\">{$file}</a> <span class=\"size small\">({$size})</span>";
				} else {
					$filelink = "<a href=\"{$self}?action=list&amp;subdir={$subdir}/{$file}\" title=\"".printMsg("titleListFiles", $file)."\" class=\"dir\">{$file}</a> <span class=\"size small\">({$size})</span>";
				}
			} else {
				$size = filesize("{$dir}/{$file}");
				$totalsize = $totalsize + $size;
				$size = substr($size/1024, 0, 4)."kb";
				if (is_readable("{$dir}/{$file}")) {
					$filelink = "<a href=\"{$dir}/{$file}\" title=\"".printMsg("titleOpenFile", $file)."\">{$file}</a> <span class=\"size small\">({$size})</span>";
					if (checkForSource(getExt($file)) == 1) {
						$filelink = "{$filelink} <a href=\"{$self}?action=showsource&amp;file={$file}{$subdirlink}\" class=\"small\" title=\"".printMsg("titleShowSource", $file)."\">".printMsg("linkSrc")."</a>";
					}
					if (checkForEdit(getExt($file)) == 1 && is_writeable("{$dir}/{$file}")) {
							$filelink = "{$filelink} <a href=\"{$self}?action=edit&amp;file={$file}{$subdirlink}\" class=\"small\" title=\"".printMsg("titleEdit", $file)."\">".printMsg("linkEdit")."</a>";
					}
					if (checkForW3link(getExt($file)) == 1) {
						if ($originaldir == ".") {
							$w3url = substr("{$dir}/{$file}", 1);
						} else {
							$w3url = "{$dir}/{$file}";
						}
						$w3url = str_replace($self, "", $requesturi).$w3url;
						$w3url = urlencode("http://{$_SERVER["HTTP_HOST"]}{$w3url}");
						if (getExt($file) == "css") {
							$w3url = "http://jigsaw.w3.org/css-validator/validator?uri={$w3url}";
						} else {
							$w3url = "http://validator.w3.org/check?uri={$w3url}";
						}
						$filelink = "{$filelink} <a href=\"{$w3url}\" class=\"small\">".printMsg("linkW3")."</a>";
					}
				} else {
					$filelink = "{$file}";
				}
			}
			if (is_writeable("{$dir}/{$file}")) {
				$delete = "<a onclick=\"skift(document.getElementById('{$file}'))\" class=\"renamelink\" title=\"".printMsg("titleRen", $file)."\">".printMsg("linkRename")."</a> / <a onclick=\"if(checkDelete() == true) {return true;} else {return false;}\" href=\"{$self}?action=delete&amp;file={$file}{$subdirlink}\" title=\"".printMsg("titleDel", $file)."\">".printMsg("linkDelete")."</a>";
				$rename = "<input type=\"text\" name=\"newfile\" value=\"{$file}\" size=\"".strlen($file)."\" />
							<input type=\"submit\" value=\"".printMsg("buttonOk")."\" />";
				if (IsSet($subdir)) {
					$rename = "<input type=\"hidden\" name=\"subdir\" value=\"{$subdir}\" />
					{$rename}";
				}
			} else {
				$delete = "";
				$rename = "<input type=\"text\" name=\"newfile\" disabled=\"disabled\" />
					<input type=\"submit\" value=\"rename\" disabled=\"disabled\" />";
			}
			if ($i % 2 == 0) {
				$style = "white";
			} else {
				$style = "grey";
			}
			$i++;
			echo "<tr class=\"ikkeklikket\" id=\"{$file}\">
					<td class=\"{$style}\"><span>{$filelink}</span>
						<form action=\"{$formaction}\" method=\"post\" id=\"{$file}.ipt\">
							<input type=\"hidden\" name=\"action\" value=\"rename\" />
							<input type=\"hidden\" name=\"oldfile\" value=\"{$file}\" />
							{$rename}
						</form>
					</td>
					<td class=\"center small {$style}\">{$delete}</td>";
				if ($showdatecolumn == 3) {
					echo "
					<td class=\"center {$style}\">".date($dateformat, filemtime("{$dir}/{$file}"))."</td>";
				}
				echo "
				</tr>";
			Unset($size);
		}
		$totalsize = substr($totalsize/1024, 0, 4);
		echo "<tr><td colspan=\"{$showdatecolumn}\" class=\"bottom small\">".printMsg("tableFooter", $filenum, $totalsize)."</td></tr>";
		echo "</table>";
	} else {
		echo "<p class=\"empty\">".printMsg("textDirEmpty")."</p>";
	}
	echo "<p><a href=\"http://www.solitude.dk/filethingie/\">File Thingie</a> &copy; <!-- Copyright --> 2003 <a href=\"http://www.solitude.dk\">Andreas Haugstrup</a>. Some rights <a href=\"http://creativecommons.org/licenses/by-nd-nc/1.0/\">reserved.</a></p>";
}
?>
</body>
</html>
<?php
	clearstatcache();
}
?>
