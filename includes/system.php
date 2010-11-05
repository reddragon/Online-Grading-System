<?php

#-------------------------------------------------------------------------------
# Filters a output case, for tasks such as float-tolerance tags.

function system_filter_output($txt) {
    return preg_replace_callback("/<float>([^>]*)</float>/i", 
    create_function('$m',
        'global $cfg; return round(floatval($m[1]), $cfg["tester"]["float_precision"]);'),
    $txt);
}

#------------------------------------------------------------------------------
# Executes the given program, piping in the given input, and returing the time
# in $exec_time optionally.
# Return value: 0 if success, 1 if execution failed, 2 if output incorrect
# In case of exec failed, $output contains a description of the error. Else
# if output incorrect, $output contains output of program.

function system_gauge($program, $input, /* in, out */ &$output, $time_limit, 
						$mem_limit, /*out */ &$exec_time) {
	global $cfg;
	// We offload part of the work to a C program here
	// Arguments to the C program: PROGRAM_NAME TIME_LIMIT MEM_LIMIT
	// Output of C program: EXECUTION_TIME\n[ERROR_MESSAGE]
	// Return code gives status of execution (0=success, 1=error)
	// Output of the program to be tested is stored in OUTPUT_FILE, while it
	// takes input from INPUT_FILE.
	
	$prog_out = tempnam("/tmp", "ogs");
	$prog_in = tempnam("/tmp", "ogs");
	//$correct_out = tempnam("/tmp", "ogs");
	
	// Put the input in a temporary file
	$handle = fopen($prog_in, 'w');
	fwrite($handle, $input);
	fwrite($handle, "\n");
	fclose($handle);
	
    /*
	// Put the correct output in a temporary file
	$handle = fopen($correct_out, 'w');
	fwrite($handle, $output);
	fwrite($handle, "\n");	// add an extra newline, helps comparison
	fclose($handle);*/
	
	// Execute the gauging program
	exec("{$cfg['bin']['gauge']} $program $time_limit $mem_limit <$prog_in 2>&1 1>$prog_out", $gauge_out, $ret);
	
	if ($ret != 0) {
		// we have an error
		$output = $gauge_out[1];
		$ret = 1;
	} else {
        // execution ok, compare outputs
        $prog_output = file_get_contents($prog_out);
        
        if (system_compare($output, $prog_output) == true) {
			$ret = 0;
		} else {
			$output = $prog_output;
			$ret = 2;
		}
	}
	
	@unlink($prog_in);
	@unlink($prog_out);
	$exec_time = $gauge_out[0];
	return $ret;
}

#------------------------------------------------------------------------------
# Returns true if the two files are same, or false if they differ.

function system_diff($a, $b) {
	global $cfg;
	
	$diff_opts = ' -B ';
	exec($cfg['bin']['diff'].$diff_opts."$a $b", $output, $ret);
	
	if ($ret == 0)
		return true;
	else
		return false;
}
    
#------------------------------------------------------------------------------
# Returns true if the two strings are same, or false if they differ.
# $a must be the correct version.

function system_compare($a, $b) {
    global $cfg;
           
    $lena = strlen($a);
    $lenb = strlen($b);
    $ai = 0;
    $bi = 0;
    
    while ($ai < $lena && $bi < $lenb) {
        if ($a[$ai] == '<' && strcasecmp(substr($a, $ai+1, 6), "float>") == 0) {
            $j = strpos($a, '</', $ai+7);
            if ($j !== false && strcasecmp(substr($a, $j+2, 6), "float>") == 0) {
                $fa = floatval(substr($a, $ai+7, $j-($ai+7)));
                
                $k = $bi;
                for (; $k < $lenb; ++$k) {
                    if ($b[$k] == ' ' || $b[$k] == '\n') break;
                }
                $fb = floatval(substr($b, $bi, $k-$bi));
                
                $err = pow(10, -1*$cfg['tester']['float_precision']);
                if (abs($fa-$fb) > $err && (abs($fa) < 1 || abs($fa-$fb)/$fa > $err)) {
                    return false;
                } else {
                    $ai = $j+8;
                    $bi = $k;
                    continue;
                }
            }
        }
        
        if ($a[$ai] != $b[$bi]) {
            if ($a[$ai] == "\n" || $a[$ai] == "\r") ++$ai;
            else if ($b[$bi] == "\n" || $b[$bi] == "\r") ++$bi;
            else return false;
        } else {
            ++$ai;
            ++$bi;
        }
    }
    
    while ($ai != $lena) {
        if ($a[$ai] == "\n" || $a[$ai] == "\r") ++$ai;
        else return false;
    }
    while ($bi != $lenb) {
        if ($b[$bi] == "\n" || $b[$bi] == "\r") ++$bi;
        else return false;
    }
    return true;
}

?>