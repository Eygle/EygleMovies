<?php 

require_once 'db/DBMovies.class.php';
require_once('utils/AllocineConnector.class.php');
if ($argc < 2) {
	echo "Usage: php ".$argv[0]." listFilms.csv [fileName]\n";
	exit(0);
}

$db = new DBMovies();
$allocine = new AllocineConnector();

$fd = fopen($argv[1], 'r');

$skipUntil = isset($argv[2]) && $argv[2] != "-n" ? $argv[2] : null;
$skipUntilNbr = isset($argv[2]) && isset($argv[3]) && $argv[2] == "-n" ? intval($argv[3]) : null;
$nbr = 0;

echo $skipUntilNbr;

while ($film = fgetcsv($fd)) {
	$nbr++;
	$title = formatTitle($film[0]);
	$file =  utf8_encode($film[1]);
	$path = utf8_encode($film[2]);

	if (($skipUntilNbr && $skipUntilNbr != $nbr) || ($skipUntil && $file != $skipUntil)) {
		echo "Skip: $file\n";
		continue;
	}
	else if ($skipUntil || $skipUntilNbr) {
		$skipUntil = null;
		$skipUntilNbr = null;
	}
	
	file_put_contents("./nbrs", $nbr);

	echo "Treatment ($nbr) of $file\n";
	echo "Title: $title\n";
	//error_log("Treatment ($nbr) of $file\n");
	
	if (!($tmp = $db->issetFile($file)) || !$tmp['allocineId']) {
		$res = $allocine->searchMovie($title);
		if ($res && !is_array($res)) {
			echo "One result !\n";
			$res->file = $file;
			$res->path = $path;
			if ($tmp) {
				$movieId = $db->updateMovie($tmp['id'], $res->toArray());
			} else {
				$movieId = $db->insertMovie($res->toArray());
			}
			if ($res->directors)
				$db->insertPeople($movieId, $res->directors, 'director');
			if ($res->actors)
				$db->insertPeople($movieId, $res->actors, 'actor');
			if ($res->genres)
				$db->insertGenres($movieId, $res->genres);
		} else if (is_array($res) && !$tmp['multipleChoices']) {
			if (!$tmp) // If the movie don't exist in db
				$mId = $db->insertSimpleMovie($file, $path, true);
			else {
				$mId = $db->updateSimpleMovie($tmp['id'], $file, $path, true);
			}
			echo "Multiple results !\n";
			foreach ($res as $v) {
				$v->file = $file;
				$v->path = $path;
				// var_dump($v);
				echo "Insert choice ".$v->title."\n";
				$db->insertChoice($mId, $v->toSmallArray());
			}
		} else if (!$tmp) {
			$db->insertSimpleMovie($file, $path, false);
			echo "No results founded for file: $file\n";
		}
	}
	echo "\n";
}
fclose($fd);

function formatTitle($title) {
	$title =  trim(preg_replace("/ (\- )?\[(.*)/","",$title));
	$title =  trim(preg_replace("/ (\- )?\((.*)/","",$title));
	$title =  trim(preg_replace("/ (\- )?\{(.*)/","",$title));
	$title =  str_replace(" - ", " : ", $title);
	return utf8_encode($title);
}

?>