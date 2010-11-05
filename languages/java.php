<?php

function lang_java_extensions() {
    return array("java");
}

function lang_java_description() {
    return "Java 1.4 (GCJ)";
}

function lang_java_header() {
    return '';
}

function lang_java_compile($source_file, $exec_file) {
    global $cfg;
    
    // Extract public class name from source file
    $source = file_get_contents($source_file);
    if (!preg_match("/public\s+class\s+(\w+)/", $source, $matches)) {
        $handle = fopen($exec_file, 'w');
        fwrite($handle, "Error: No public class definition found in your Java source code.\n");
        fclose($handle);
        return false;
    }
    
    $class = $matches[1];
    $java_file = dirname($source_file).'/'.$class.'.java';
    copy($source_file, $java_file);
    
    exec("{$cfg['bin']['gcj']} -O3 --main=$class $java_file -o $exec_file 2>&1", $output, $ret);
    unlink($java_file);
    
    if ($ret == 0) {
        // Compilation OK
        return true;
    } else {
        // On error, dump error messages into output executable file
        $handle = fopen($exec_file, 'w');
        foreach ($output as $line) {
            fwrite($handle, preg_replace("/\/tmp[^\.]+\.java/i", $class.'.java', $line));
            fwrite($handle, "\n");
        }
        fclose($handle);
        return false;
    }
}

?>
