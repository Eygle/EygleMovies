<?php

require_once dirname(__FILE__) . '/../db/DBMovies.class.php';

$db = new DBMovies();

function editMovie($id) {
    global $db;
    
    $allocineId = isset($_POST['allocineId']) ? $_POST['allocineId'] : null;
    $title = isset($_POST['title']) ? $_POST['title'] : null;
    $originalTitle = isset($_POST['originalTitle']) ? $_POST['originalTitle'] : null;
    $releaseDate = isset($_POST['releaseDate']) ? $_POST['releaseDate'] : null;
    $pressRating = isset($_POST['pressRating']) ? $_POST['pressRating'] : null;
    $userRating = isset($_POST['userRating']) ? $_POST['userRating'] : null;
    $poster = isset($_POST['poster']) ? $_POST['poster'] : null;
    $synopsis = isset($_POST['synopsis']) ? $_POST['synopsis'] : null;
    $actors = isset($_POST['actors']) ? explode(',', $_POST['actors']) : null;
    $directors = isset($_POST['directors']) ? explode(',', $_POST['directors']) : null;
    $genres = isset($_POST['genres']) ? explode(',', $_POST['genres']) : null;

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
            switch ($_POST['action']) {
                case 'delete-movie':
                    $db->deleteMovie($_POST['id']);
                    break;
                case 'choose-multi-item':
                    $db->chooseMovie($_POST['movie-id'], $_POST['choice-id']);
                    break;
                case 'edit-movie':
                    $id = $_POST['id'];
                    editMovie($id);
                    break;
                case 'edit-movie-reload-image':
                    $id = $_POST['id'];
                    editMovie($id);
                    
                    $logs = array();
                    exec("php ".dirname(__FILE__)."/../scriptGetPosters.php " . $id, $logs);
                    break;
                case 'delete-mult':
                    if (isset($_POST['remove-movie'])) {
                        $db->removeMovie($_POST['movie-id']);
                    } else {
                        $db->setMovieNotMultiple($_POST['movie-id']);
                    }
                    $db->removeMultiplesMovies($_POST['movie-id']);
                    break;
            }
            break;
    }
} catch (Exception $ex) {

}

?>