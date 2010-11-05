<?php

function lang_cpp_extensions() {
	return array("cpp", "cxx", "cc", "c++");
}

function lang_cpp_description() {
    return "C++ (GCC 3.x)";
}

function lang_cpp_header() {
    global $cfg;
    return '#include "'.realpath($cfg['src']['block']).'"';
}

function lang_cpp_compile($source_file, $exec_file) {
	global $cfg;
	
    exec("{$cfg['bin']['c++']} -O2 -DCOMPILER_GPP $source_file -lm -o $exec_file 2>&1", $output, $ret);
	if ($ret == 0) {
		// Compilation OK
		return true;
	} else {
		// On error, dump error messages into output executable file
		$handle = fopen($exec_file, 'w');
		foreach ($output as $line) {
			fwrite($handle, preg_replace("/\/tmp[^\.]+\.(cpp|c++|cxx|cc)/i", 'test.$1', $line));
			fwrite($handle, "\n");
		}
		fclose($handle);
		return false;
	}
}

?>
