<?php

require_once dirname(__FILE__) . '/../db/DBMovies.class.php';

$db = new DBMovies();

function editMovie($id, $request) {
    global $db;
    
    $allocineId = isset($request['allocineId']) ? $request['allocineId'] : null;
    $title = isset($request['title']) ? $request['title'] : null;
    $originalTitle = isset($request['originalTitle']) ? $request['originalTitle'] : null;
    $releaseDate = isset($request['releaseDate']) ? $request['releaseDate'] : null;
    $pressRating = isset($request['pressRating']) ? $request['pressRating'] : null;
    $userRating = isset($request['userRating']) ? $request['userRating'] : null;
    $poster = isset($request['poster']) ? $request['poster'] : null;
    $synopsis = isset($request['synopsis']) ? $request['synopsis'] : null;
    $actors = isset($request['actors']) ? explode(',', $request['actors']) : null;
    $directors = isset($request['directors']) ? explode(',', $request['directors']) : null;
    $genres = isset($request['genres']) ? explode(',', $request['genres']) : null;

    $db->editMovie($id, $allocineId,
            $title, $originalTitle,
            $releaseDate, $pressRating,
            $userRating, $poster,
            $synopsis);

    $db->removeMoviePeopleLinks($id);
    $db->removeMovieGenreLinks($id);
    
    if ($directors) $db->insertPeople($id, $directors, 'director');
    if ($actors) $db->insertPeople($id, $actors, 'actor');
    if ($genres) $db->insertGenres($id, $genres);
}

try {
    switch ($_SERVER["REQUEST_METHOD"]) {
        case 'GET':
            switch ($_GET['action']) {
                case 'list-movies':
                    $genre = isset($_GET['genre']) ? $_GET['genre'] : null;
                    $search = isset($_GET['search']) ? $_GET['search'] : null;
                    echo json_encode($db->getMovies($_GET['from'], $_GET['nbr'], $genre, $search));
                    break;
                case 'list-genres':
                    echo json_encode($db->getGenres());
                    break;
                case 'get-total-movies':
                    $genre = isset($_GET['genre']) ? $_GET['genre'] : null;
                    $search = isset($_GET['search']) ? $_GET['search'] : null;
                    echo $db->getTotalMovies($genre, $search);
                    break;
                case 'get-movie':
                    echo json_encode($db->getMovieInfos($_GET['id']));
                    break;
                case 'get-doubles':
                    echo json_encode($db->getDoubleMovies());
                    break;
                case 'get-doubles-id':
                    echo json_encode($db->getDoubleForId($_GET['id']));
                    break;
                case 'get-multi':
                    echo json_encode($db->getMoviesMult());
                    break;
                case 'get-to-validate':
                    echo json_encode($db->getMoviesToValidate());
                    break;
                case 'get-uncomplete':
                    echo json_encode($db->getUncompleteMovies());
                    break;
                case 'get-movie-from-allocine':
                    $allocine = new AllocineConnector();
                    $res = array();
                    $alloRes = $allocine->getCompleteMovieInfosFromId($_GET['allocineId']);
                    if (!$alloRes) {
                        echo '';
                        return;
                    }
                    $list = array('title', 'originalTitle', 'releaseDate', 'directors', 'actors', 'genres', 'pressRating', 'userRating', 'poster', 'synopsis');
                    foreach ($list as $v) {
                        if ($alloRes->$v) {
                            $res[$v] = $alloRes->$v;
                        }
                    }
                    
                    echo json_encode($res);
                    break;
                case 'get-trash':
                    echo json_encode($db->getTrash($_GET['from'], $_GET['nbr']));
                    break;
                case 'get-total-trash':
                    echo json_encode($db->getTotalTrash());
                    break;
                case 'get-admin-total':
                    $res = array(
                        "validate" => $db->getTotalMoviesToValidate(),
                        "doubles" => $db->getTotalDoubleMovies(),
                        "multi" => $db->getTotalMoviesMult(),
                        "uncomplete" => $db->getTotalUncompleteMovies(),
                        "trash" => $db->getTotalTrash()
                    );
                    echo json_encode($res);
                    break;
            }
            break;
        case 'POST':
            $postdata = file_get_contents("php://input");
            $request = json_decode($postdata, true);
            switch ($request['action']) {
                case 'delete-movie':
                    $db->deleteMovie($request['id']);
                    break;
                case 'choose-multi-item':
                    $db->chooseMovie($request['movie-id'], $request['choice-id']);
                    break;
                case 'validate-movie':
                    $db->validateMovie($request["movie-id"]);
                    break;
                case 'edit-movie':
                    $id = $request['id'];
                    editMovie($i, $request);
                    break;
                case 'edit-movie-reload-image':
                    $id = $request['id'];
                    editMovie($id, $request);
                    
                    $logs = array();
                    exec("php ".dirname(__FILE__)."/../scriptGetPosters.php " . $id, $logs);
                    break;
                case 'delete-mult':
                    if (isset($request['remove-movie'])) {
                        $db->removeMovie($request['movie-id']);
                    } else {
                        $db->setMovieNotMultiple($request['movie-id']);
                    }
                    $db->removeMultiplesMovies($request['movie-id']);
                    break;
            }
            break;
    }
} catch (Exception $ex) {

}

?>