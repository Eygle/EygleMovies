<?php 

require_once dirname(__FILE__) . '/db/DBMovies.class.php';
require_once(dirname(__FILE__) . '/utils/AllocineConnector.class.php');
require_once dirname(__FILE__) . '/utils/HTTPClient.class.php';
require_once(dirname(__FILE__) . '/utils/utils.php');

if ($argc < 2) {
	echo "Usage: php ".$argv[0]." listFilms.csv [fileName]\n";
	exit(0);
}

$db = new DBMovies();
$allocine = new AllocineConnector();
$files = array();

function insertMoviesFromFile() {
    global $db, $allocine, $argv, $files;

    $fd = fopen($argv[1], 'r');

    $skipUntil = isset($argv[2]) && $argv[2] != "-n" ? $argv[2] : null;
    $skipUntilNbr = isset($argv[2]) && isset($argv[3]) && $argv[2] == "-n" ? intval($argv[3]) : null;
    $nbr = 0;

    echo $skipUntilNbr;

    $processedTitles = array();

    while ($film = fgetcsv($fd)) {
            $nbr++;
            $title = formatTitle($film[0]);
            $file =  utf8_encode($film[1]);
            $path = utf8_encode($film[2]);

            $files[] = $file;

            if (($skipUntilNbr && $skipUntilNbr != $nbr) || ($skipUntil && $file != $skipUntil)) {
                    echo "Skip: $file\n";
                    continue;
            }
            else if ($skipUntil || $skipUntilNbr) {
                    $skipUntil = null;
                    $skipUntilNbr = null;
            }

            file_put_contents("./nbrs", $nbr);
            //error_log("Treatment ($nbr) of $file\n");

            if (in_array($title, $processedTitles)) {
                    continue;
            }

            $tmp = $db->issetFile($file);

            if ($tmp && ($tmp['multipleChoices'] || $tmp['allocineId'] != 0)) {
                    $processedTitles[] = $title;
                    continue;
            }
            
            

            echo "Process ($nbr): $file\n";
            echo "Title: $title\n";

            echo "New file detected with: ";
            $res = $allocine->searchMovie($title);

            if ($res) {
                    if (!is_array($res)) {
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
                    } else if (is_array($res)) {
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
                    }
            } else {
                    echo "No results\n";
                    if ($tmp) {
                            $db->setNoAllocineIdForMovie($file);
                    } else {
                            $db->insertSimpleMovie($file, $path, false);
                    }
            }
            echo "\n";
            $processedTitles[] = $title;
    }
    fclose($fd);
}

function removeMoviesNotInList() {
    global $db, $files;
    # Remove files not actually here
    $movies = $db->getAllSavedFiles();
    $i = 0;
    foreach ($movies as $movie) {
        if (!in_array($movie["file"], $files)) {
            $i++;
            echo "Put in trash ($i): ".$movie["file"]."\n";
            $db->putInTrash($movie['id']);
        }
    }
}

function removeFromTrashIfMovieStillExist() {
    global $db;
    
    $trash = $db->getTrash();
    foreach ($trash as $v) {
        if (!$v['allocineId']) continue;
        if ($db->getTotalMovieWithAllocineId($v['allocineId']) > 0) {
            echo "delete movie ".$v['file']."\n";
            $db->deleteMovie($v['id']);
        }
    }
}

function getPosters() {
    global $db;
    $posters = $db->getAllPosters();
    $http = new HTTPClient();
    
    foreach ($posters as $v) {
	$name = $v['title'];
	if (!$name) $name = $v['originalTitle'];
	$name = $v['id'].'-'.formatFileNameFromTitle($name).'.'.end(explode('.', $v['poster']));
        if (file_exists(dirname(__FILE__).'/../posters/'.$name)) {
            continue;
        }
	echo "add poster: $name\n";
	$http->download($v['poster'], dirname(__FILE__).'/../posters/'.$name);
	$db->addLocalPosterForMovie($v['id'], $name);
	//echo $http->getLastHttpResponseCode();
	//break;
}
}


function formatTitle($title) {
	$title =  trim(preg_replace("/ (\- )?\[(.*)/","",$title));
	$title =  trim(preg_replace("/ (\- )?\((.*)/","",$title));
	$title =  trim(preg_replace("/ (\- )?\{(.*)/","",$title));
	$title =  str_replace(" - ", " : ", $title);
	return utf8_encode($title);
}

insertMoviesFromFile();
removeMoviesNotInList();
getPosters();
removeFromTrashIfMovieStillExist();

?>