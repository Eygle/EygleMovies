<?php

function formatFileNameFromTitle($name) {
	$name = mb_strtolower($name, 'utf-8');
	$name = preg_replace('/\'/u', '-', $name);
	$name = preg_replace('/:/u', '-', $name);
	$name = preg_replace('/,/u', '-', $name);
	$name = preg_replace('/\./u', '-', $name);
	$name = preg_replace('/\?/u', '-', $name);
	$name = preg_replace('/!/u', '-', $name);
	$name = preg_replace('/&/u', ' and ', $name);
	$name = preg_replace('/\(/u', '-', $name);
	$name = preg_replace('/\)/u', '-', $name);
	$name = preg_replace('/\//u', '-', $name);
	$name = preg_replace('/\\\/u', '-', $name);
	$name = preg_replace('/ /u', '-', $name);
	$name = preg_replace('/à/u', 'a', $name);
	$name = preg_replace('/â/u', 'a', $name);
	$name = preg_replace('/é/u', 'e', $name);
	$name = preg_replace('/è/u', 'e', $name);
	$name = preg_replace('/ë/u', 'e', $name);
	$name = preg_replace('/ê/u', 'e', $name);
	$name = preg_replace('/ï/u', 'i', $name);
	$name = preg_replace('/î/u', 'i', $name);
	$name = preg_replace('/ö/u', 'o', $name);
	$name = preg_replace('/ô/u', 'o', $name);
	$name = preg_replace('/ù/u', 'u', $name);
	while (preg_match('/--/', $name)) {
		$name = preg_replace('/--/u', '', $name);
	}
	$name = preg_replace('/-$/u', '', $name);
	return $name;
}

?>