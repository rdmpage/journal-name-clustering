<?php

// Read list of entities, clean them, create sort keys
// based on first letter in cleaned names, load into clusters table.

error_reporting(E_ALL);

require_once (dirname(__FILE__) . '/clean.php');

//----------------------------------------------------------------------------------------
function clean_series($string)
{
	$string = preg_replace('/[\[|\(]?n\.\s*[f|s]\.[\]|\)]?$/i', '', $string);
	
	$string = preg_replace('/\(Nouvelle Serie\)$/ui', '', $string);

	$string = preg_replace('/[\[|\(]?new\s+series[\]|\)]?$/i', '', $string);

	$string = preg_replace('/[\[|\(]?nu[e|o]va\s+serie[\]|\)]?$/i', '', $string);

	$string = preg_replace('/\(series\s+\d+\)$/i', '', $string);
	$string = preg_replace('/\(s[e|é]r.\s+\w+\)$/ui', '', $string);
	
	$string = preg_replace('/\(n.?[f|s].?\)\.?$/ui', '', $string);
	
	$string = preg_replace('/\d/i', '', $string);
	
	$string = preg_replace('/\[[^\]]+\]/i', '', $string);
	
	$string = preg_replace('/\s*\.$/', '', $string);
	
	$string = preg_replace('/\s+$/', '', $string);
	
	$string = preg_replace('/\s\s+/', ' ', $string);
	
	return $string;
}


//----------------------------------------------------------------------------------------
// Clean string of anything which may interfere with cleaning
// For example [=? ser#80]
function before_cleaning($string)
{
	$string = preg_replace('/[\[|\(](=\?|use)\s*(ser\#)?\d+[^\]]*[\]|\)]/', '', $string);
	$string = preg_replace('/\s+$/', '', $string);
	
	$string = preg_replace('/\[eds?\]\s+(.*)/', '$1', $string);
	
	return $string;
}

//----------------------------------------------------------------------------------------

function read_data($filename)
{
	$data = array();
	
	$headings = array();

	$row_count = 0;

	$file = @fopen($filename, "r") or die("couldn't open $filename");
		
	$file_handle = fopen($filename, "r");
	while (!feof($file_handle)) 
	{
		$row = fgetcsv(
			$file_handle, 
			0, 
			"\t" 
			);
			
		$go = is_array($row);
		
		if ($go)
		{
			if ($row_count == 0)
			{
				$headings = $row;		
			}
			else
			{
				$obj = new stdclass;
			
				foreach ($row as $k => $v)
				{
					if ($v != '')
					{
						$obj->{$headings[$k]} = $v;
					}
				}
				
				// ensure we have an id
				if (!isset($obj->id))
				{
					$obj->id = $row_count;
				}
			
				$data[] = $obj;

			}
		}	
		$row_count++;
	}
	
	return $data;
}

//----------------------------------------------------------------------------------------

$filename = '';
if ($argc < 2)
{
	echo "Usage: " . basename(__FILE__) . " <filename>\n";
	exit(1);
}
else
{
	$filename = $argv[1];
}

$data = read_data($filename);

//print_r($data);

// post process and clean


foreach ($data as &$row)
{
	$row->text_strings = array($row->name);
	
	// remove things that will get in the way of next steps
	$row->text_strings[0] = before_cleaning($row->text_strings[0]);
	
	// split if we have two names (this step is error prone)
	if (preg_match('/(?<one>.*)\s+(\[|\()\s*=\s*(?<two>.*)(\]|\))/u', $row->text_strings[0], $m))
	{
		$row->text_strings = array($m['one'], $m['two']);
	}
	
	$row->sortkeys = [];
	$row->cleaned = [];
		
	foreach ($row->text_strings as $string)
	{
		// get cleaned tokenised string and use first letter of
		// each string to create a "sortkey"
		$string = str_replace('-', ' - ', $string);
		
		// any other cleaning, maybe dataset-specific
		$string = preg_replace('/^\?\s*/', '', $string);
		$string = preg_replace('/pp\.?$/', '', $string);
		$string = preg_replace('/pp\.?$/', '', $string);
		
		$string = preg_replace('/\s+LIII$/', '', $string);
		$string = preg_replace('/\s+XXII \(no.$/', '', $string);
		$string = preg_replace('/^VII.\s+/', '', $string);
		$string = preg_replace('/^Reed.\s+/', '', $string);
		$string = preg_replace('/^Sbornik,\s+/', '', $string);
		$string = preg_replace('/^Hemipteros Heteropteros del Uruguay.\s+/', '', $string); 
		
		
		$string = clean_series($string);
		
		$extras = array(
		'Synonymische Bemerkungen uber Hemipteren und eine neue Art der Gattung Prostemma. ',
		'Hemipterologische Miscellaneen. ',
		'Zur Heteropteren - Fauna Ceylon\'s. ',
		'Berichtigung. ',
		'Las tribus de Hemipteros de Espana. ',
		'Psocus bastmannianus n. sp. aus Finnland. ',
		);
		
		foreach ($extras as $extra)
		{
			$string = str_replace($extra, '', $string);
		}		
		
		/*
		if (preg_match('/[A-Za-z0-9 \.]{40,}/', $string))
		{
			echo $string . "\n";
		}
		*/
		
		$row->cleaned[] = $string;
		
		$parts = tokenise_string($string);
		
		// print_r($parts);
		
		$key = array();
		foreach ($parts as $part)
		{
			$key[] = mb_substr($part, 0, 1);
		}	
		
		$row->sortkeys[] = join("", $key);
	}


}

//print_r($data);

$keys = ['id', 'cluster_id', 'sortkey', 'cleaned', 'text', 'issn'];


$output = fopen('php://output', 'w');
fputcsv($output, $keys);
//echo join(",", $keys) . "\n";

foreach ($data as $item)
{
	if (isset($item->text_strings))
	{
	
		$n = count($item->text_strings);
		for ($i = 0; $i < $n; $i++)
		{
			$row = array();
			
			$row[] = $item->id;
			$row[] = ''; // no clusters yet
			$row[] = $item->sortkeys[$i];
			$row[] = $item->cleaned[$i];
			$row[] = $item->text_strings[$i];
			
			if (isset($item->issn))
			{
				$row[] = $item->issn;
			}
			else
			{
				$row[] = '';
			}
			
			fputcsv($output, $row);
			//echo join(",", $row) . "\n";
		}
	}
}

fclose($output);

?>
