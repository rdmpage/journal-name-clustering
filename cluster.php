<?php

// For each sortkey in clusters table load all names and compare strings based on 
// a chosen measure (e.g., longest common subsequence). 
// Use disjoint set datastructure to find connected components of graph where nodes 
// are names and edges link names that are deemed sufficiently similar to be part of
// the same cluster.

require_once (dirname(__FILE__) . '/clean.php');
require_once (dirname(__FILE__) . '/disjoint_set.php');
require_once (dirname(__FILE__) . '/sqlite.php');

//----------------------------------------------------------------------------------------
// Get unique cleaned strings and use them to create a map between cleaned strings
// and local ids
function unique_cleaned_strings($input)
{
	$strings = [];
	
	foreach ($input as $string => $ids)
	{
		$string = normalise_text($string);
		$string = removeCommonWords($string);
		
		//echo $string . "\n";
		
		if (!isset($strings[$string]))
		{
			$strings[$string] = [];
		}
		
		// print_r($ids);
		
		foreach ($ids as $id)
		{
			$strings[$string][] = $id;
		}
	
	}
	
	return $strings;
}

//----------------------------------------------------------------------------------------
function compare_strings($string1, $string2, $parameters, $debug = false)
{
   	$tokens1 = tokenise_string($string1);
   	$tokens2 = tokenise_string($string2);
	
	$result = longestCommonSubsequence($tokens1, $tokens2, $parameters);
	
	if ($debug)
	{
		print_r($result);
	}
	
	return $result['d'];
}

//----------------------------------------------------------------------------------------
// token Longest common subsequence
function longestCommonSubsequence(array $tokens1, array $tokens2, $parameters)
{
	//$min_match_length 	= 2;
	//$extra_match_score 	= 0.1;
	//$max_character_diff = 1;

    $m = count($tokens1);
    $n = count($tokens2);

    // Initialize the DP table
    $dp = array_fill(0, $m + 1, array_fill(0, $n + 1, 0));
    $trace = array_fill(0, $m + 1, array_fill(0, $n + 1, ''));

    // Fill the DP table and trace info
    for ($i = 1; $i <= $m; $i++) {
        for ($j = 1; $j <= $n; $j++) {
        
        	$sim = 0;
        	
        	// is one string a prefix of the other?
        	if (is_abbreviation($tokens1[$i - 1], $tokens2[$j - 1]))
        	{
        		$sim = 1;
        		
        		// are they non-trivial matches? 
                if (($tokens1[$i - 1] === $tokens2[$j - 1]) && strlen($tokens1[$i - 1]) >= $parameters['min_match_length'])
                {
                	$sim += $parameters['extra_match_score'];
                }        		
        	}
        	else
        	{
        		// if not substrings, are the two strings similar, say differing by a single letter?
        		if (levenshtein($tokens1[$i - 1], $tokens2[$j - 1]) <= $parameters['max_character_diff'])
        		{
        			$sim = 1;
        		}        	
        	}
        	
            if ($sim > 0)
            {
                $dp[$i][$j] = $dp[$i - 1][$j - 1] + $sim;
                 
                $trace[$i][$j] = 'diag';  // match
            } elseif ($dp[$i - 1][$j] >= $dp[$i][$j - 1]) {
                $dp[$i][$j] = $dp[$i - 1][$j];
                $trace[$i][$j] = 'up';    // move up
            } else {
                $dp[$i][$j] = $dp[$i][$j - 1];
                $trace[$i][$j] = 'left';  // move left
            }
        }
    }

    // Reconstruct the LCS sequence via traceback
    $lcs = [];
    $i = $m;
    $j = $n;
    while ($i > 0 && $j > 0) {
        if ($trace[$i][$j] === 'diag') {
            $lcs[] = [$tokens1[$i - 1],$tokens2[$j - 1]];
            $i--;
            $j--;
        } elseif ($trace[$i][$j] === 'up') {
            $i--;
        } else {
            $j--;
        }
    }
    
    // score for longest subsequence
    $score = $dp[$m][$n];
   
    // normalised score
    $d = 2 * $dp[$m][$n] / ($m + $n + 2 * min($m,$n) * $parameters['extra_match_score']);
    $d = round($d, 2);
    
    return [
        'score' => $score,
        't1' => join(' ', $tokens1),
        't2' => join(' ', $tokens2),
        'd' => $d,
        array_reverse($lcs)
    ];

}

//----------------------------------------------------------------------------------------
	
function cluster_sortkeys($sortkeys, $parameters, $debug = false)
{
	$sql = "SELECT * FROM clusters WHERE sortkey IN ('" . join("','", $sortkeys) . "')";
	
	$data  = db_get($sql);
	
	$values = array();
	
	$input = array();
	
	foreach ($data as $row)
	{
		if (!isset($input[$row->cleaned]))
		{
			$input[$row->cleaned] = array();
		}
		$input[$row->cleaned][] = $row->id;
	}
	
	if ($debug)
	{
		print_r($input);
	}
	
	$clean_string_map = unique_cleaned_strings($input);
	
	// print_r($clean_string_map);
	
	// compare cleaned strings
	$strings = array_keys($clean_string_map);
	
	if ($debug)
	{
		print_r($strings);
	}
	
	$dj = new DisjointSet();
	foreach ($clean_string_map as $string => $ids)
	{
		foreach ($ids as $id)
		{
			$dj->makeset($id);
		}
		
		if (count($ids) > 1)
		{
			$n = count($ids);
			for ($i = 1; $i < $n; $i++)
			{
				$dj->union($ids[$i], $ids[0]);
			}
		}
	}
	
	$n = count($strings);
	
	for ($i = 0; $i < $n - 1; $i++)
	{
		for ($j = $i + 1; $j < $n; $j++)
		{
			$d = compare_strings($strings[$i], $strings[$j], $parameters, $debug);
			
			// if reasonably similar add to set
			if ($d >= $parameters['threshold'])
			{
				$dj->union($clean_string_map[$strings[$i]][0], $clean_string_map[$strings[$j]][0]);
			}
		}
	}
	
	if ($debug)
	{
		echo $dj->dot();
	}
	
	$clusters = $dj->clusters();
	
	foreach ($clusters as $index => $members)
	{
		foreach ($members as $i)
		{
			echo "UPDATE clusters SET cluster_id=$index WHERE id=$i;\n";
		}
	}

}


$debug = true;
$debug = false;


$parameters = array(
	'threshold' 		 => 0.9,
	'min_match_length' 	 => 2,
	'extra_match_score'  => 0.1,
	'max_character_diff' => 1
);

// debugging so we try some examples
if (1)
{
	$sortkeys = array('em');
	//$sortkeys = array('jbnhs');
	//$sortkeys = array('trssa');
	
	$sortkeys = array('sez');
	
	$sortkeys = array('rsz');
	
	$sortkeys = array('bmcsnv');
}
else
{
	// Build clusters for everything	
	$sql = "SELECT DISTINCT sortkey FROM clusters WHERE sortkey IS NOT NULL AND sortkey != ''";
	
	$sortkeys = array();
	
	$data  = db_get($sql);
	
	foreach ($data as $row)
	{
		$sortkeys [] = $row->sortkey;
	}
}

foreach ($sortkeys as $s)
{
	cluster_sortkeys([$s], $parameters, $debug);
}

?>

