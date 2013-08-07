#!/usr/bin/php
<?php

// the parser
// transforms a folder of txt files into information

if (php_sapi_name() != 'cli') {
	die('Sorry, this can only be run via the command line.');
}

date_default_timezone_set('US/Eastern');

echo "Captain's Log Parser! \n";

if (!isset($argv) || count($argv) < 2) {
	die('You need to specify a directory to parse as the argument to this script.'."\n");
}

if (in_array($argv[1], array('-h', '-help', '--help'))) {
	echo 'Usage:   just call this script and have the first argument'."\n".'         be the folder full of txt files you\'d like parsed.'."\n";
	echo 'Example: ./parser.php directory_of_txts/'."\n";
	die();
}

$all_the_logs = array();

$dir = trim($argv[1]);

if (is_dir($dir)) {
    if ($dh = opendir($dir)) {
        while (($file = readdir($dh)) !== false) {
			if ($file == '.' || $file == '..' || substr($file, 0, 1) == '.') {
				continue; // do not parse hidden files
			}
			if (substr($file, -4) != '.txt') {
				continue; // only parse .txt files
			}
            echo 'file to parse: '.$file."\n";
			$raw = file_get_contents($dir.'/'.$file); // get those file contents
			$raw = trim($raw); // trim out any white space now
			if (strpos("\r\n", $raw) !== false) { $raw = str_replace("\r", "", $raw); } // if the file line endings are WINDOWS based, get rid of em
			$raw_lines = explode("\n", $raw); // break the file into lines
			if (preg_match('/\d{4}-\d{2}-\d{2}/', trim($raw_lines[0])) !== 1) {
				// if the first line of the file isn't the DATE as outlined in the format spec, skip this file
				echo 'This file is in the wrong format, skipping!'."\n";
				continue;
			}
			
			// get the date this file belongs to
			$thedate = strtotime(trim($raw_lines[0]));
			if (!$thedate) {
				die('Error parsing the date on the first line of this file: '.$file."\n");
			}
			$thedate_string = date('Y-m-d', $thedate);
			if (array_key_exists($thedate_string, $all_the_logs)) {
				die('Oops, it looks like this date has already been parsed in a different file: '.$thedate_string."\n".'Crashing now.'."\n");
			}
			
			// let's set up a temporary array to store the info
			$day = array();
			$day['did'] = array(); // array of stuff that was done
			$day['notes'] = ''; // place to put notes
			$day['total_time'] = 0; // time spent DOING THINGS!
			$day['total_meetings'] = 0; // how many meetings did you go to?
			
			// okay, let's go through the lines
			$at_the_list = false; // take note of when we get past the list and enter the notes section
			for ($ln = 0; $ln < count($raw_lines); $ln++) {
				$line = trim($raw_lines[$ln]);
				//echo $line."\n";
				if ($ln > 3 && !$at_the_list) {
					// if we are past the third line, this is where the list should be!
					if (in_array(strtolower($line), array('notes', 'notes...', 'notes:'))) {
						// if the line is blank, that means we've reached the end of the list!
						$at_the_list = true;
					} else {
						// parse the list item
						if (substr($line, 0, 2) == '- ') { $line = substr($line, 2); }
						if (trim($line) == '') { continue; }
						$activity = array();
						$activity['meeting'] = false;
						$activity['time'] = null;
						$activity['tickets'] = array();
						//echo 'List item: '. $line . "\n";
						// let's parse that line for any time info info
						if (preg_match_all('/\[([^\]]+)\]/', $line, $time_matches) > 0) {
							foreach ($time_matches[1] as $time_match) {
								// deal with a potential amount of time spent doing something!
								$time_match = trim($time_match); // get rid of white space
								if (strpos($time_match, 'to') !== false || strpos($time_match, '-') !== false) { // check to see if it's a range
									// okay, split it up
									if (preg_match('/(.+)(-|to)(.+)/', $time_match, $time_range_matches) !== false) {
										//print_r($time_range_matches);
										$start_time_string = trim($time_range_matches[1]);
										$end_time_string = trim($time_range_matches[3]);
										$start_time_ts = strtotime('2013-01-01 '.$start_time_string);
										$end_time_ts = strtotime('2013-01-01 '.$end_time_string);
										$seconds_worked = $end_time_ts - $start_time_ts;
										//echo $time_range_matches[0].' = '.$seconds_worked.' seconds'."\n";
										$day['total_time'] += $seconds_worked;
										$activity['time'] += $seconds_worked;
									}
								} else {
									// it should just be a relative time amount, like 1 hour, or 1h30m
									// change lone "h"s into "hours" /h(?![a-z]+)/ig, lone "m"s into "mins" /m(?![a-z]+)/gi
									$relative_time_string = $time_match;
									$relative_time_string = preg_replace('/h(?![a-z]+)/i', ' hours ', $relative_time_string);
									$relative_time_string = preg_replace('/m(?![a-z]+)/i', ' mins ', $relative_time_string);
									$start_time_ts = time();
									$end_time_ts = strtotime('+'.$relative_time_string);
									$seconds_worked = $end_time_ts - $start_time_ts;
									//echo $time_match.' = '.$seconds_worked.' seconds'."\n";
									$day['total_time'] += $seconds_worked;
									$activity['time'] += $seconds_worked;
								}
							}
							// now that we are done with the time, get rid of that stuff
							$line = preg_replace('/\[([^\]]+)\]/', '', $line);
						}
						// okay, now see if there's any ticket info
						if (preg_match_all('/\{(\d+)#(\d+)\}/', $line, $ticket_matches, PREG_SET_ORDER) > 0) {
							foreach ($ticket_matches as $ticket_match) {
								$activity['tickets'][] = array('ticket_id' => $ticket_match[2] * 1, 'workspace_id' => $ticket_match[1] * 1);
							}
							$line = preg_replace('/\{(\d+)#(\d+)\}/', '', $line);
						}
						if (stripos($line, 'meeting') !== false) {
							$activity['meeting'] = true;
							$day['total_meetings']++;
						}
						$activity['info'] = trim($line);
						$day['did'][] = $activity;
					}
				} else if ($at_the_list) {
					// the only thing that should be left are the notes
					if (trim($line) == '') { continue; }
					if (strtolower(trim($line)) == 'welp' || strtolower(trim($line)) == 'welp.') { continue; }
					//echo 'After list: '. $line . "\n";
					$day['notes'] .= $line;
					if ($ln != count($raw_lines) - 1) {
						$day['notes'] .= "\n\n"; // there's more coming, put in some whitespace
					}
				}
			}
			
			// save the day to the big array of logs!
			$all_the_logs[$thedate_string] = $day;
			
			echo 'Successfully parsed '.$file."\n";
        }
        closedir($dh); // close the directory, we are DONE
		
		// go through all of the log entries and find some things
		$all_the_logs['total_time'] = 0;
		$all_the_logs['total_meetings'] = 0;
		$all_the_logs['total_days'] = 0;
		foreach ($all_the_logs as $log_entry_key => $log_entry) {
			// only do this for the log entries
			if (preg_match('/\d{4}-\d{2}-\d{2}/', $log_entry_key) === 1) {
				$all_the_logs['total_time'] += $log_entry['total_time'];
				$all_the_logs['total_days']++;
				foreach ($log_entry['did'] as $list_entry) {
					if ($list_entry['meeting'] == true) { $all_the_logs['total_meetings']++; }
				}
			}
		}
				
		// okay, all parsed
		print_r($all_the_logs); // show it off
				
    } else {
		die('Error opening the directory!'."\n");
	}
} else {
	die('The directory you provided does not seem to exist, sorry.'."\n");
}

?>
