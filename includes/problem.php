<?php

#-------------------------------------------------------------------------------
# Parses a XML problem file and echoes it in HTML formatting

define ("TESTCASE_NORMAL", "0");
define ("TESTCASE_FILE", "1");
class TestCase
{
    var $input;
    var $output;
    var $imode;
    var $omode;
    var $time_limit; // null means use problem's defaults
    var $mem_limit;  // ditto
    var $points;
    
    function TestCase($points, $time, $mem) {
        $this->time_limit = $time;
        $this->mem_limit = $mem;
        $this->points = $points;
        $this->imode = TESTCASE_NORMAL;
        $this->omode = TESTCASE_NORMAL;
    }
    
    function getInput() {
        global $cfg;
        if ($this->imode == TESTCASE_FILE)
            return trim(file_get_contents($cfg['dir']['data'].'/'.$this->input));
        else
            return trim($this->input);
    }
    
    function getOutput() {
        global $cfg;
        if ($this->omode == TESTCASE_FILE)
            return file_get_contents($cfg['dir']['data'].'/'.$this->output);
        else
            return $this->output;
    }
}

#-------------------------------------------------------------------------------
# Class for parsing a problem XML document

class Problem
{
    var $tests = array();
    var $body = "";
    var $examples = array();
    var $constraints = array("INPUT" => array(), "OUTPUT" => array());
    
    var $parser;
    var $inside = array(null);
    var $depth = 0;
    var $id;
	var $error_func;	// error function to call
	
	function error($msg) {
		$errf = $this->error_func;
		$line = xml_get_current_line_number($this->parser);
    	$col = xml_get_current_column_number($this->parser);
        $errf("$msg at line:$line, col:$col.");
	}

    function Problem($err_func = "error") {
        $this->parser = xml_parser_create();
        xml_set_object($this->parser, $this);
        xml_set_element_handler($this->parser, "_startElement", "_endElement");
        xml_set_character_data_handler($this->parser, "_charData");            
        $this->error_func = $err_func;
    }

    function load($text) {
        xml_parse($this->parser, $text, true) 
            or $this->error(xml_error_string(xml_get_error_code($this->parser)));
    
        if ($this->depth != 0)
            $this->error("Element depth didnt return to 0, possible unmatched tags");
        if (count($this->tests) == 0)
            $this->error("No tests provided");
        if (count($this->examples) == 0)
            $this->error("No examples provided");
        if (strlen($this->body) == 0)
            $this->error("No body provided");
    }

    function grabStart(&$dest, $tag, &$attrib) {
        if ($tag == "BR") {
            $dest .= "<BR />";
        } else {
            $dest .= "<$tag";
            foreach ($attrib as $name => $value) {
                $dest .= " $name=\"$value\"";
            }
            $dest .= ">";
        }
    }

    function grabEnd(&$dest, $tag) {
        if ($tag != "BR") $dest .= "</$tag>";
    }

    function _charData($parser, $cdata) {
        switch ($this->inside[$this->depth]) {
        case "TITLE":
            $this->summary = trim($cdata);
            break;
        case "BODY":
            $this->body .= $cdata;
            break;
        case "EXAMPLE":
            $this->examples[$this->id] .= $cdata;
            break;
        case "INPUT":
            if ($this->inside[$this->depth-1] == "TEST" &&
                    $this->tests[$this->id]->imode == TESTCASE_NORMAL) {
                $this->tests[$this->id]->input .= $cdata;
            }
            break;
        case "OUTPUT":
            if ($this->inside[$this->depth-1] == "TEST" &&
                    $this->tests[$this->id]->omode == TESTCASE_NORMAL) {
                $this->tests[$this->id]->output .= $cdata;
            }
            break;
        case "CONSTRAINT":
            $arr =& $this->constraints[$this->inside[$this->depth-1]];
            $arr[count($arr)-1] .= $cdata;
            break;
        }
    }

    function _endElement($parser, $tag) {
        if ($this->inside[$this->depth] == "BODY" && $tag != "BODY") {
            $this->grabEnd($this->body, $tag);
        } else if ($this->inside[$this->depth] == "EXAMPLE" && $tag != "EXAMPLE") {
            $this->grabEnd($this->examples[$this->id], $tag);
        } else if ($this->inside[$this->depth] == "CONSTRAINT" && $tag != "CONSTRAINT") {
            $arr =& $this->constraints[$this->inside[$this->depth-1]];
            $this->grabEnd($arr[count($arr)-1], $tag);
        } else if ($this->inside[$this->depth] == "INPUT" && $tag != "INPUT" &&
                $this->tests[$this->id]->imode == TESTCASE_NORMAL) {
            $this->grabEnd($this->tests[$this->id]->input, $tag);
        } else if ($this->inside[$this->depth] == "OUTPUT" && $tag != "OUTPUT" &&
                $this->tests[$this->id]->omode == TESTCASE_NORMAL) {
            $this->grabEnd($this->tests[$this->id]->output, $tag);
        } else {
            if ($this->inside[$this->depth] != $tag) {
                $this->error("Ending tag $tag does not match starting tag {$this->inside[$this->depth]}, ");
            } else {
                --$this->depth;
            }
        }
    }

    function _startElement($parser, $tag, $attrib) {
        $old_depth = $this->depth;
        
        switch ($this->inside[$this->depth]) {
        case null:
            if ($tag != "PROBLEM") {
                $this->error("Invalid root element. Root element must be PROBLEM");
            } else {
                $this->inside[++$this->depth] = $tag;
            }
            break;
        case "PROBLEM":
            if ($tag == "TITLE" || $tag == "BODY" || $tag == "EXAMPLES" || 
                $tag == "TESTS" || $tag == "CONSTRAINTS") {
                $this->inside[++$this->depth] = $tag;
            }
            break;
        case "BODY":
            $this->grabStart($this->body, $tag, $attrib);
            return;
        case "EXAMPLES":
            if ($tag == "EXAMPLE") {
                if (!isset($attrib['ID'])) $this->error("ID attribute required for EXAMPLE element");
                $this->id = $attrib['ID'];
                $this->inside[++$this->depth] = $tag;
        
                $this->examples[$this->id] = "";
            }
            break;
        case "EXAMPLE":
            $this->grabStart($this->examples[$this->id], $tag, $attrib);
            return;
        case "TESTS":
            if ($tag == "TEST") {
                if (!isset($attrib['ID'])) $this->error("ID attribute required for TEST element");
                $this->id = $attrib['ID'];
                $this->inside[++$this->depth] = $tag;
        
                $this->tests[$this->id] = new TestCase($attrib['POINTS'], $attrib['TIME'], $attrib['MEMORY']);
            } else if ($tag == "FILES") {
                if (!isset($attrib['INPUTBASE']) || !isset($attrib['OUTPUTBASE']) ||
                        !isset($attrib['IDSTART']) || !isset($attrib['COUNT'])) {
                    $this->error("INPUTBASE, OUTPUTBASE, IDSTART, COUNT attributes required for FILES element");
                }
                        
                $id = intval($attrib['IDSTART']);
                $end = $attrib['COUNT'];
                $i = $id;
                
                while ($i < $end+$id) {
                    $this->tests[$i] = new TestCase($attrib['POINTS'], $attrib['TIME'],
                        $attrib['MEMORY']);
                    $this->tests[$i]->imode = TESTCASE_FILE;
                    $this->tests[$i]->omode = TESTCASE_FILE;
                    $this->tests[$i]->input = $attrib['INPUTBASE'].'.'.$i;
                    $this->tests[$i]->output = $attrib['OUTPUTBASE'].'.'.$i;
                    ++$i;
                }
                $this->inside[++$this->depth] = $tag;
            }
            break;
        case "TEST":
            if ($tag == "INPUT" || $tag == "OUTPUT") {
                if (isset($attrib['FILE'])) {
                    if ($tag == "INPUT") {
                        $this->tests[$this->id]->imode = TESTCASE_FILE;
                        $this->tests[$this->id]->input = $attrib['FILE'];
                    } else if ($tag == "OUTPUT") {
                        $this->tests[$this->id]->omode = TESTCASE_FILE;
                        $this->tests[$this->id]->output = $attrib['FILE'];
                    }
                }
                $this->inside[++$this->depth] = $tag;
            }
            break;
        case "CONSTRAINTS":
            if ($tag == "INPUT" || $tag == "OUTPUT") {
                $this->inside[++$this->depth] = $tag;
            }
            break;
        case "CONSTRAINT":
            $arr =& $this->constraints[$this->inside[$this->depth-1]];
            $this->grabStart($arr[count($arr)-1], $tag, $attrib);
            return;
        case "INPUT":
        case "OUTPUT":
            if ($this->inside[$this->depth-1] == "CONSTRAINTS" && $tag == "CONSTRAINT") {
                array_push($this->constraints[$this->inside[$this->depth]], '');
                $this->inside[++$this->depth] = $tag;
            } else if ($this->inside[$this->depth-1] == "TEST") {
                if ($this->inside[$this->depth] == "INPUT" &&
                    $this->tests[$this->id]->imode == TESTCASE_NORMAL) {
                    $this->grabStart($this->tests[$this->id]->input, $tag, $attrib);
                    return;
                } else if ($this->tests[$this->id]->omode == TESTCASE_NORMAL) {
                    $this->grabStart($this->tests[$this->id]->output, $tag, $attrib);
                    return;
                }
            }
            break;
        }
    
        if ($old_depth == $this->depth) { // means some error occured
            $this->error("Invalid element $tag found");
        }
    }
}

#-------------------------------------------------------------------------------
# Returns a unique ID representing a problem, for storing in a cache

function problem_cache_id($contest_id, $prob_id) {
    return $contest_id.'.'.$prob_id;
}

#-------------------------------------------------------------------------------
# Loads a problem: either loads from a cached serialized data, or parses the
# XML. Also uses an in-memory cache.

$prob_cache = array();
function problem_load($problem) {
    global $prob_cache, $cache;
    $id = problem_cache_id($problem['contest_id'], $problem['prob_id']).'.prob';
    
	// Check in memory
    if (isset($prob_cache[$id])) {
		return $prob_cache[$id];
	}
	
    // Check in file
    if ($data = $cache->get($id)) {
        $prob_cache[$id] =& unserialize($data);
        return $prob_cache[$id];
    }
    
    $res =& db_query('problem_content_by_id', array($problem['prob_id'], $problem['contest_id']));
    $res->fetchInto($prob_content);
    $res->free();
    
    $prob =& new Problem;
    $prob->load($prob_content['content']);
    
    // store in both caches
    $cache->save(serialize($prob), $id);
	
	return $prob;
}

#-------------------------------------------------------------------------------
# Parses a XML problem file and echoes it in HTML formatting
# Expects a row from problems table.
# Uses file-based caching of generated content; any changes to problem definition
# must remove this cache.

function problem_display($row) {
    global $cache;
    
    $cache_id = problem_cache_id($row['contest_id'], $row['prob_id']).'.htm';
    if ($data = $cache->get($cache_id)) {
        echo $data;
    } else {
        $prob =& problem_load($row);
        $data = "<script type=\"text/javascript\">var problemVersion = {$row['version']};</script>";
        
        // Display heading
        $data .= '<div id="problem_title">';
        $data .= 'Problem: <b>'.$row['prob_id'].'</b>';
        $data .= '</div>';
        
        $data .= '<div id="problem">';
        $data .= '<div id="problem_header">';
        $data .= '<b>Summary:</b> '.$row['summary'];
        $data .= '<br /><b>Weight:</b> '.$row['weight'];
        $data .= '<br /><b>Time limit:</b> '.$row['time_limit'].' second(s)';
        $data .= '<br /><b>Memory limit:</b> '.$row['mem_limit'].' MB';
        $data .= '<br /><b>Test cases:</b> '.count($prob->tests);
        $data .= '</div>';
    
        // Display problem description (body)
        $data .= "<div id=\"problem_body\">\n";
        $data .= "<b>Description:</b><br /><div id=\"problem_body_text\">\n";
        $data .= $prob->body;
        $data .= "</div></div>\n";
        
        // Display the formatting constraints
        $data .= "<div id=\"problem_constraints\">\n";
        $data .= "<b>Formatting/Constraints:</b>\n";
        $data .= "<ul>\n";
        foreach ($prob->constraints as $type => $points) {
            $data .= '<li><i>'.ucfirst(strtolower($type)).":</i>\n<ol>\n";
            foreach ($points as $point) {
                $data .= "<li>$point</li>\n";
            }
            $data .= "</ol>\n</li>\n";
        }
        $data .= "</ul>\n</div>\n";
        
        // Display the examples
        $data .= '<div id="problem_examples">';
        $data .= "<b>Examples:</b>\n";
        $data .= "<ol>\n";
        foreach ($prob->examples as $id => $text) {
            $data .= "<li>\n";
            
            $data .= "<b>Input:</b> <pre>";
            $data .= html_strip($prob->tests[$id]->getInput());
            $data .= "</pre>\n";
            
            $data .= "<b>Output:</b> <pre>";
            $data .= html_strip($prob->tests[$id]->getOutput());
            $data .= "</pre>\n";
            
            $data .= "<b>Analysis:</b> <div class=\"analysis\">\n";
            $data .= $text;
            $data .= "</div>\n";
        
            $data .= "</li>\n";
        }
        
        if (count($prob->examples) == 0) {
            $data .= "None.\n";
        }
        $data .= "</ol></div>\n";
        $data .= "</div>\n";
        
        $cache->save($data, $cache_id);
        echo $data;
    }
}

#-------------------------------------------------------------------------------
# Parses an XML problem file and returns null on success, or an array of strings
# on failure containing list of all errors.

function _problem_test_error($txt) {
	global $problem_test_error_strings, $problem_test_error;
	if ($txt{strlen($txt)-1} != '.') {
		$txt .= '.';
	}
	
	array_push($problem_test_error_strings, $txt);
	$problem_test_error = true;
}

function problem_check(&$content) {
	global $problem_test_error_strings, $problem_test_error;
	$problem_test_error_strings = array();
	$problem_test_error = false;
	
	$prob = new Problem("_problem_test_error");
	$prob->load($content);
	unset($prob);
	
	if ($problem_test_error) {
		return $problem_test_error_strings;
	} else {
		return null;
	}
}

?>