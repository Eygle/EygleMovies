<?php

require_once dirname(__FILE__).'/db/DBMovies.class.php';
require_once dirname(__FILE__).'/utils/HTTPClient.class.php';
require_once(dirname(__FILE__).'/utils/utils.php');

$db = new DBMovies();
$posters = $db->getAllPosters();
$http = new HTTPClient();

$unique = isset($argv[1]) ? false : true;

if ($argv[1]) {
    foreach ($posters as $v) {
        if ($v['id'] != $argv[1]) {
            continue;
        }
	$name = $v['title'];
	if (!$name) $name = $v['originalTitle'];
	$name = $v['id'].'-'.formatFileNameFromTitle($name).'-'.time().'.'.end(explode('.', $v['poster']));
        $http->download($v['poster'], dirname(__FILE__).'/../posters/'.$name);
        $db->addLocalPosterForMovie($v['id'], $name);
        return;
    }
}

foreach ($posters as $v) {
	$name = $v['title'];
	if (!$name) $name = $v['originalTitle'];
	echo "Process: $name\n";
	$name = $v['id'].'-'.formatFileNameFromTitle($name).'-'.time().'.'.end(explode('.', $v['poster']));
        if (file_exists(dirname(__FILE__).'/../posters/'.$name)) {
            continue;
        }
	$http->download($v['poster'], dirname(__FILE__).'/../posters/'.$name);
	$db->addLocalPosterForMovie($v['id'], $name);
	//echo $http->getLastHttpResponseCode();
	//break;
}

?>