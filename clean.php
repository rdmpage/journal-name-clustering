<?php

define ('WHITESPACE_CHARS', ' \f\n\r\t\x{00a0}\x{0020}\x{1680}\x{180e}\x{2028}\x{2029}\x{2000}\x{2001}\x{2002}\x{2003}\x{2004}\x{2005}\x{2006}\x{2007}\x{2008}\x{2009}\x{200a}\x{202f}\x{205f}\x{3000}');
define ('PUNCTUATION_CHARS', '\?\!\.\-—,\(\)\[\]:;«»\'\&"\`\´„”“”‘’');

//----------------------------------------------------------------------------------------
// https://stackoverflow.com/a/2759179
function unaccent($string)
{
	// $string = str_replace('ü', 'ue', $string);

    $string = preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);~i', '$1', htmlentities($string, ENT_QUOTES, 'UTF-8'));
    $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    return $string;
}

//----------------------------------------------------------------------------------------
// Based on https://gist.github.com/keithmorris/4155220
// adding more languages, not that some stop words are shared across languages
function removeCommonWords($input){
 
 	// EEEEEEK Stop words
	$commonWords = array(
	 // en
	'and', 'from', 'in', 'of', 'on', 'the',
	
    //de
    'aus', 'dem', 'der','des', 'die', 'fur', 'und', 'zu', 'zur',
    
    // fr
    'de','du', 'et', 'la',
    
    // es
    'del','y',
    
	 // it
	 'della', 'di',
    
    // nl
    'van',
    
    // pt
    'da','e',
    
	 // other
	 'v',
    );
 
	$input= preg_replace('/\b('.implode('|',$commonWords).')\b/i','',$input);
	$input = preg_replace('/\s\s+/', ' ', $input);
	
	$input = preg_replace('/^\s+/', '', $input);
	
	return $input;
}

//----------------------------------------------------------------------------------------
// Clean up text so that we have single spaces between text, 
// see https://github.com/readmill/API/wiki/Highlight-locators
function clean_text($text)
{	
	$text = strip_tags($text);
	$text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	
	// Replace any prefixes that might interfere with matching acronyms
	$text = preg_replace("/[d|l][\'|’]/iu", "", $text);
	
	// Ensure spaces between words ending in"."
	$text = preg_replace('/\.(\p{Lu}|\p{L})/u', '. $1', $text);
	
	if (!preg_match('/^[\p{Lu}\s"]+$/', $text))
	{
		
		// Split probable acronyms into individual letters (this will fail if text is all caps)
		$text = preg_replace_callback('/\b[A-Z]{2,}\b/', 
			function ($matches) {
				return implode(' ', str_split($matches[0]));}, 
			$text);
   }
    
	// All whitespace collapsed to single space
	$text = preg_replace('/[' . WHITESPACE_CHARS . ']+/u', ' ', $text);
	
	return $text;
}

//----------------------------------------------------------------------------------------
// Normalise text by cleaning it and removing punctuation
function normalise_text($text)
{
	// clean
	$text = clean_text($text);
	$text = unaccent($text);
	
	// remove punctuation
	//$text = preg_replace('/[' . PUNCTUATION_CHARS . ']+/u', '', $text);
	$text = preg_replace('/[^a-z0-9 ]/i', '', $text);
	
	// lowercase
	$text = mb_convert_case($text, MB_CASE_LOWER);
	
	return $text;
}

//----------------------------------------------------------------------------------------
function tokenise_string($string)
{
	$string = normalise_text($string);
	$string = removeCommonWords($string);
	
	$tokens = explode(' ', $string);
	
	return $tokens;
}

//----------------------------------------------------------------------------------------
function starts_with_either($a, $b) {
    // Use native PHP 8+ function if available
    if (function_exists('str_starts_with')) {
        return str_starts_with($a, $b) || str_starts_with($b, $a);
    }

    // Fallback for PHP < 8.0
    
    if (!$a || !$b)
    {
    	return 0;
    }
    
    return (strpos($a, $b) === 0) || (strpos($b, $a) === 0);
}

//----------------------------------------------------------------------------------------
// Return true if string is a possible abbreviation of another. Simplest case is
// one string is the prefix of another string, but we add special cases, e.g.
// "boln" for "boletin"
function is_abbreviation($a, $b)
{
   $is_abbrev = starts_with_either($a, $b);
   
   if (!$is_abbrev)
   {
		// sometimes abbreviates are not true substring
		if (strlen($a) > strlen($b))
		{
			[$a, $b] = [$b, $a];
		}
		
		switch ($a)
		{
			case 'boln':
				$is_abbrev = ($b == "boletin");
				break;
				
			case 'qld':
				$is_abbrev = ($b == "queensland");
				break;
		
			default:
				$is_abbrev = false;
		}
   }
   
   return $is_abbrev;
}

?>
