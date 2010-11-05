<?php

function lang_c_extensions() {
	return array("c");
}

function lang_c_description() {
    return "C";
}

function lang_c_header() {
    global $cfg;
    return '#include "'.realpath($cfg['src']['block']).'"';
}

function lang_c_compile($source_file, $exec_file) {
	global $cfg;
    exec("{$cfg['bin']['cc']} -O2 $source_file -lm -o $exec_file 2>&1", $output, $ret);
	if ($ret == 0) {
		// Compilation OK
		return true;
	} else {
		// On error, dump error messages into output executable file
		$handle = fopen($exec_file, 'w');
		foreach ($output as $line) {
			fwrite($handle, preg_replace("/\/tmp[^\.]+\.c/i", 'test.c', $line));
			fwrite($handle, "\n");
		}
		fclose($handle);
		return false;
	}
}

?>
