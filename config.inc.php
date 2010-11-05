<?php
#-------------------------------------------------------------------------------
# CONFIGURATION FILE
# Careful while editing these options.
#-------------------------------------------------------------------------------

# For profiling, comment out in release environment
# apd_set_pprof_trace();

#-------------------------------------------------------------------------------
# Config variables here

$cfg["site"]["name"] = "TSEC Coding League(TCL)";
$cfg["site"]["logo"] = "TCL :: Online Grading System";
$cfg["site"]["theme"] = "default";
$cfg["site"]["email"] = "de.arijit@gmail.com";
$cfg["site"]["debug"] = true;           // Displays backtrace on error

#TCL specific variables
$cfg["tcl"]["divisions"] = array(
		'Division A (TEs/BEs/Others)',
		'Division B (SEs)'			 , 
		'Division C (FEs)');
$cfg["tcl"]["divcount"] = '3';

# Database connection variables
$cfg["db"]["phptype"] = "mysql";        // Don't change this.
$cfg["db"]["database"] = "Contest";
$cfg["db"]["username"] = "root";
$cfg["db"]["password"] = "";
$cfg["db"]["hostspec"] = "localhost";

# Directory paths
$cfg["dir"]["themes"] = "themes";
$cfg["dir"]["images"] = "images";
$cfg["dir"]["views"] = "views";
$cfg["dir"]["languages"] = "languages";
$cfg["dir"]["bin"] = "bin";
$cfg["dir"]["data"] = "data";
$cfg["dir"]["scripts"] = "scripts";
$cfg["dir"]["cache"] = "cache";

# Executables
$cfg["bin"]["diff"] = "/usr/bin/diff";
$cfg["bin"]["cc"] = "/usr/bin/gcc-4.1";
$cfg["bin"]["c++"] = "/usr/bin/g++-4.1";
$cfg["bin"]["gcj"] = "/usr/bin/gcj-4.1.0";
$cfg["bin"]["gauge"] = $cfg["dir"]["bin"]."/gauge";

$cfg["src"]["gauge"] = $cfg["dir"]["bin"]."/gauge.c";
$cfg["src"]["block"] = $cfg["dir"]["bin"]."/block.c";

# The group to which all new users should be added by default on registration
$cfg['auth']['autogroup'] = 'Competitors';

# Score calculation constants
$cfg['score']['resubmit_penalty'] = 5;		// in percentage
$cfg['score']['base'] = 0.5;                // 0 < x < 1
$cfg['score']['all_correct_bonus'] = 0.7;   // 0 < x < 1
$cfg['score']['show_rating'] = false;

# The maximum concurrent no. of running tests
$cfg['submit']['max_concurrent'] = 10;
$cfg['submit']['expire'] = 60*5;			// no. seconds to hold pending submits

# For <float> enclosed fields in reference outputs
$cfg['tester']['float_precision'] = 8;     // no. of digits after decimal pt
$cfg['tester']['java_mem_overhead'] = 32;

# Session handling parameters
$cfg['session']['timeout'] = 180 * 60;
$cfg['session']['save_path'] = ini_get('session.save_path')."/ogs_sessions";

# Autosaved draft expiry time
$cfg['draft']['expiry_time'] = 180 * 60;

# File-based cache params
$cfg['cache']['lifeTime'] = null; // never expire
$cfg['cache']['writeControl'] = true;
$cfg['cache']['readControl'] = true;
$cfg['cache']['fileLocking'] = true;
$cfg['cache']['fileNameProtection'] = false;
$cfg['cache']['cacheDir'] = $cfg['dir']['cache'].'/';

#-------------------------------------------------------------------------------
# Common include files

ini_set("include_path", realpath('./includes/').PATH_SEPARATOR.ini_get("include_path"));
require_once 'DB.php';
require_once 'lib.php';
require_once 'html.php';
require_once 'auth.php';
require_once 'view.php';
require_once 'nav.php';
require_once 'sql.php';
require_once('Cache/Lite.php');

#-------------------------------------------------------------------------------
# Session/cookie configuration here

if (!is_dir($cfg['session']['save_path'])) mkdir($cfg['session']['save_path'], 0777);
ini_set('session.save_path', $cfg['session']['save_path']);
ini_set('session.gc_maxlifetime', $cfg['session']['timeout'] + 600);
#ini_set('session.gc_divisor', 1000);
ini_set("session.use_only_cookies", "1");
session_set_cookie_params(0, '/');

#-------------------------------------------------------------------------------
# Initialize file-based cache
$cache = new Cache_Lite($cfg['cache']);
					
?>
