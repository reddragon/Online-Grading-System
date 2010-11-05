<?php
// Get component file name
$fname = (array_key_exists("file", $_GET)) ? $_GET["file"] : "";
// basename() also strips \x00, we don't need to worry about ? and # in path:
// Must be real files anyway, fopen() does not support wildcards
$ext = array_pop(explode('.', basename($fname)));

if (strcasecmp($ext, "htc") != 0 || !file_exists($fname))
	exit ("No file specified, file not found or illegal file.");

$flen = filesize($fname);

header("Content-type: text/x-component");
header("Content-Length: ".$flen);
header("Content-Disposition: inline; filename=$fname");

$fp = fopen($fname, "r");
echo fread($fp, $flen);
fclose($fp)
?>
