<?php
#-------------------------------------------------------------------------------
# This script is only to be used for boot-strapping the system. You can also do
# the relevant steps manually, as outlined below. Once the system is up and
# running, you should delete or rename this file to ensure security.
#
# IMPORTANT: This script ensures that the first user that signs up (user_id=1)
# after system is running (i.e. this script has been run), is automatically the
# administrator. You can change permissions later through the Administration
# interface.
#
# NOTE: This script will fail if database tables have already been initialized.
# This is expected behavior and enforced for security & integrity purposes. To
# forcibly clear the whole database and re-boot-strap the system, use this
# script as (WARNING: this will delete all records):
#     install.php?force_clear=true 
#
# Things needed to be done for boot-strapping (if you're not using this script):
# 1) Create group 'Competitors', and add 'explorer' view to this group.
# 2) Create group 'Managers', and add 'manage' view to this group.
# 3) Create an Administrator group and add yourself to this group. Then add
#    the 'admin' view to this group.
#-------------------------------------------------------------------------------

require_once 'config.inc.php';
ob_start();

# For executing DB queries with error checking
function safe_query($query, $param = null) {
	global $db;
	if ($param == null) {
		$res =& $db->query($query);
	} else {
		$res =& $db->query($query, $param);
	}
	
	if (PEAR::isError($res)) error($res->toString());
}

$db =& DB::connect($cfg["db"]);
if (PEAR::isError($db)) error($db->toString());
$db->setFetchMode(DB_FETCHMODE_ASSOC);

if ($_GET['force_clear'] == 'true') {
	$tables = array_keys($cfg['sql']['create']);
	foreach ($tables as $table) {
		safe_query("DROP TABLE IF EXISTS $table");
	}
}

safe_query($cfg['sql']['create']['groups']);
safe_query($cfg['sql']['create']['views']);
safe_query($cfg['sql']['create']['perms']);

$db->autoExecute('groups', array('name' => 'Administrators'), DB_AUTOQUERY_INSERT);
$db->autoExecute('groups', array('name' => 'Competitors'), DB_AUTOQUERY_INSERT);
$db->autoExecute('groups', array('name' => 'Managers'), DB_AUTOQUERY_INSERT);
$db->autoExecute('views', array('group_id' => '1', 'view' => 'admin'), DB_AUTOQUERY_INSERT);
$db->autoExecute('views', array('group_id' => '2', 'view' => 'explorer'), DB_AUTOQUERY_INSERT);
$db->autoExecute('views', array('group_id' => '3', 'view' => 'manage'), DB_AUTOQUERY_INSERT);

// Violates the referential integrity of user_id=1, but fortunately mysql doesn't care.
$db->autoExecute('perms', array('group_id' => '1', 'user_id' => '1'), DB_AUTOQUERY_INSERT);

// Compile the gauging program
exec("{$cfg['bin']['cc']} {$cfg['src']['gauge']} -lm -o {$cfg['bin']['gauge']} 2>&1", $output, $ret);
if ($ret != 0) {
	error(print_r($output, true));
}

html_header($cfg['site']['name'].' Installation', $cfg["dir"]["themes"].'/'.$cfg['site']["theme"].'.css');
?>
	<h1>Installtion succeeded</h1>
	<p>You can access the main site <a href="index.php">here</a>.</p>
	<p>Please immediately register as a user to get administration rights. If you for some reason
	fail to register as the first user (with user_id=1) which is required for site administration
	rights, then you can forcibly <a href='install.php?force_clear=true'>clear the database and re-install</a>.
	</p>
	<p>Don't forget to remove this script (install.php) once the site is setup, for ensuring security.</p> 
<?php
html_footer();

ob_end_flush();

?>
