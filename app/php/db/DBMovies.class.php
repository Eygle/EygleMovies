<?php
require_once(dirname(__FILE__) . '/DAO.class.php');
require_once dirname(__FILE__) . '/../utils/AllocineConnector.class.php';

class DBMovies extends DAO
{


    public function __construct()
    {
        parent::__construct();
    }

    public function getGenres()
    {
        $stmt = $this->pdo->prepare('SELECT * FROM genres ORDER BY name');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMovies($from, $nbr, $genre, $search)
    {
        $arr = array();
        if ($genre != null)
            $arr["genre"] = $genre;
        if ($search != null)
            $arr["search"] = strtolower("%$search%");

        $stmt = $this->pdo->prepare("SELECT m.id, title, YEAR(releaseDate) AS year, poster, localPoster"
            . " FROM movies AS m"
            . ($genre != null ? " LEFT JOIN movies_genres AS mg ON m.id = mg.movieId WHERE mg.genreId = :genre AND" : " WHERE")
            . " allocineId > 0 AND allocineId IS NOT NULL AND trash = 0 AND multipleChoices = 0"
            . ($search != null ? " AND (LOWER(title) LIKE :search OR LOWER(originalTitle) LIKE :search)" : "")
            . " ORDER BY title LIMIT $from, $nbr");
        $stmt->execute($arr);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalMovies($genre = null, $search = null)
    {
        $arr = array();
        if ($genre != null)
            $arr["genre"] = $genre;
        if ($search != null)
            $arr["search"] = strtolower("%$search%");

        $stmt = $this->pdo->prepare("SELECT count(*) as tot" . " FROM movies AS m"
            . ($genre != null ? " LEFT JOIN movies_genres AS mg ON m.id = mg.movieId WHERE mg.genreId = :genre AND" : " WHERE")
            . " allocineId > 0 AND allocineId IS NOT NULL AND trash = 0 AND multipleChoices = 0"
            . ($search != null ? " AND (LOWER(title) LIKE :search OR LOWER(originalTitle) LIKE :search)" : ""));
        $stmt->execute($arr);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res["tot"];
    }

    public function getMovieInfos($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM movies WHERE id = :id');
        $stmt->execute(array('id' => $id));
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

// Get genres
        $stmt = $this->pdo->prepare('SELECT name FROM movies_genres AS mg LEFT JOIN genres AS g ON mg.genreId = g.id WHERE movieId = :id');
        $stmt->execute(array('id' => $id));
        $genres = array();
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) AS $v) {
            $genres[] = $v['name'];
        }
        $res['genres'] = implode(',', $genres);

// Get people
        $directors = array();
        $actors = array();
        $stmt = $this->pdo->prepare('SELECT name, role FROM movies_people AS mp LEFT JOIN people AS p ON mp.peopleId = p.id WHERE movieId = :id');
        $stmt->execute(array('id' => $id));
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) AS $v) {
            if ($v['role'] == 'actor')
                $actors[] = $v['name'];
            else
                $directors[] = $v['name'];
        }
        $res['genres'] = implode(', ', $genres);
        $res['directors'] = implode(', ', $directors);
        $res['actors'] = implode(', ', $actors);

        $res['synopsis'] = stripslashes($res['synopsis']);
        return $res;
    }


##### ADMIN #####

    public function getDoubleMovies()
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS tot, allocineId, title, localPoster'
            . ' FROM `movies`'
            . ' WHERE trash = 0'
            . ' GROUP BY allocineId'
            . ' HAVING allocineId > 0 AND tot > 1');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalDoubleMovies()
    {
        return count($this->getDoubleMovies());
    }

    public function getDoubleForId($id)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM `movies`'
            . ' WHERE allocineId = :id');
        $stmt->execute(array('id' => $id));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function deleteMovie($id)
    {
        $stmt = $this->pdo->prepare('DELETE FROM movies WHERE id = :id');
        $stmt->execute(array('id' => $id));
    }

    public function getMoviesMult()
    {
        $stmt = $this->pdo->prepare('SELECT * FROM movies WHERE multipleChoices = 1');
        $stmt->execute();
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($res as $i => $v) {
            $stmt = $this->pdo->prepare('SELECT * FROM movies_multiple_choices WHERE movieId = :id');
            $stmt->execute(array('id' => $v['id']));
            $res[$i]['choices'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        return $res;
    }

    public function getTotalMoviesMult()
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS tot FROM movies WHERE multipleChoices = 1');
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($res["tot"]);
    }

    public function chooseMovie($movieId, $choiceId)
    {
        $stmt = $this->pdo->prepare('SELECT * FROM movies_multiple_choices WHERE id = :id');
        $stmt->execute(array('id' => $choiceId));
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        $stmt = $this->pdo->prepare('UPDATE movies SET title = :title, originalTitle = :originalTitle, '
            . 'poster = :poster'
            . ' WHERE id = :id');

        $stmt->execute(array(
            "title" => $res['title'],
            "originalTitle" => $res["originalTitle"],
            "poster" => $res["poster"],
            "id" => $movieId));

        $allocine = new AllocineConnector();
        $alloRes = $allocine->getCompleteMovieInfosFromId($res['allocineId']);
        $list = array('title', 'originalTitle', 'releaseDate', 'directors', 'actors', 'genres', 'pressRating', 'userRating', 'poster', 'synopsis');
        foreach ($list as $v) {
            if ($alloRes->$v) {
                $res[$v] = $alloRes->$v;
            }
        }
        $this->editMovie($movieId, $res['allocineId'], $res['title'], $res['originalTitle'], $res['releaseDate'], $res['pressRating'], $res['userRating'], $res['poster'], $res['synopsis']);

        $directors = explode(',', $res['directors']);
        if ($directors) $this->insertPeople($movieId, $directors, 'director');

        $actors = explode(',', $res['actors']);
        if ($actors) $this->insertPeople($movieId, $actors, 'actor');

        $genres = explode(',', $res['genres']);
        if ($genres) $this->insertGenres($movieId, $genres);

        $this->removeMultiplesMovies($movieId);
    }

    public function removeMultiplesMovies($movieId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM movies_multiple_choices WHERE movieId = :movieId');
        $stmt->execute(array('movieId' => $movieId));
    }

    public function setMovieNotMultiple($movieId)
    {
        $stmt = $this->pdo->prepare('UPDATE movies SET multipleChoices = 0 WHERE id = :id');
        $stmt->execute(array('id' => $movieId));
    }

    public function removeMovie($movieId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM movies WHERE id = :id');
        $stmt->execute(array('id' => $movieId));
    }

    public function getMoviesToValidate()
    {
        $stmt = $this->pdo->prepare('SELECT * FROM movies WHERE multipleChoices = -1');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getUncompleteMovies()
    {
        $stmt = $this->pdo->prepare('SELECT * FROM movies WHERE (allocineId IS NULL OR allocineId < 0) AND multipleChoices = 0 AND trash = 0');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalUncompleteMovies()
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS tot FROM movies WHERE (allocineId IS NULL OR allocineId < 0) AND multipleChoices = 0 AND trash = 0');
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($res['tot']);
    }

    public function getAllSavedFiles()
    {
        $stmt = $this->pdo->prepare('SELECT id, file FROM movies WHERE trash = 0');
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function putInTrash($id)
    {
        $stmt = $this->pdo->prepare('UPDATE movies SET trash = 1 WHERE id = :id');
        $stmt->execute(array('id' => $id));
    }

    public function getTrash($from = null, $nbr = null)
    {
        $stmt = $this->pdo->prepare("SELECT m.id, title, YEAR(releaseDate) AS year, poster, localPoster, allocineId, file"
            . " FROM movies AS m"
            . " WHERE trash = 1"
            . " ORDER BY title"
            . ($from != null ? " LIMIT $from, $nbr" : ""));
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalTrash()
    {
        $stmt = $this->pdo->prepare("SELECT count(*) as tot"
            . " FROM movies"
            . " WHERE trash = 1");
        $stmt->execute();
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($res["tot"]);
    }

    public function getTotalMovieWithAllocineId($allocineId)
    {
        $stmt = $this->pdo->prepare("SELECT count(*) as tot FROM movies WHERE trash = 0 AND allocineId = :id");
        $stmt->execute(array('id' => $allocineId));
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return intval($res["tot"]);
    }

    public function removeMoviePeopleLinks($movieId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM movies_people WHERE movieId = :id');
        $stmt->execute(array("id" => $movieId));
    }

    public function removeMovieGenreLinks($movieId)
    {
        $stmt = $this->pdo->prepare('DELETE FROM movies_genres WHERE movieId = :id');
        $stmt->execute(array("id" => $movieId));
    }

    public function getMovieMult($id)
    {
        $stmt = $this->pdo->prepare('SELECT mmc.*, m.file, m.path
FROM movies_multiple_choices AS mmc
LEFT JOIN movies AS m ON mmc.movieId = m.id
WHERE mmc.id = :id');
        $stmt->execute(array('id' => $id));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getMovie($id)
    {
        $stmt = $this->pdo->prepare('SELECT *, id AS movieId FROM movies WHERE id = :id');
        $stmt->execute(array('id' => $id));
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function editMovie($movieId, $allocineId, $title, $originalTitle, $releaseDate, $pressRating, $userRating, $poster, $synopsis)
    {
        $stmt = $this->pdo->prepare('UPDATE movies
SET allocineId = :allocineId,
title = :title,
originalTitle = :originalTitle,
releaseDate = :releaseDate,
pressRating = :pressRating,
userRating = :userRating,
poster = :poster,
synopsis = :synopsis,
multipleChoices = 0
WHERE id = :movieId');
        $stmt->execute(array(
            'movieId' => $movieId,
            'allocineId' => $allocineId,
            'title' => $title,
            'originalTitle' => $originalTitle,
            'releaseDate' => $releaseDate,
            'pressRating' => $pressRating,
            'userRating' => $userRating,
            'poster' => $poster,
            'synopsis' => $synopsis
        ));
    }

##### INSERTIONS #####

    public function issetFile($file)
    {
        $stmt = $this->pdo->prepare('SELECT id, allocineId, multipleChoices FROM movies WHERE file LIKE :file LIMIT 1');
        $stmt->execute(array('file' => $file));
        $res = $stmt->fetch(PDO::FETCH_ASSOC);

        return $res;
    }

    public function insertMovie($arr)
    {
        $stmt = $this->pdo->prepare('INSERT INTO movies(title, originalTitle, allocineId, releaseDate, pressRating, userRating, poster, synopsis, file, path, multipleChoices)
VALUES(:title, :originalTitle, :allocineId, :releaseDate, :pressRating, :userRating, :poster, :synopsis, :file, :path, -1)');
        $stmt->execute($arr);
        return $this->pdo->lastInsertId();
    }

    public function updateMovie($mId, $arr)
    {
        $arr['mId'] = $mId;
        $stmt = $this->pdo->prepare('UPDATE movies SET title = :title, originalTitle = :originalTitle, allocineId = :allocineId,
releaseDate = :releaseDate, pressRating = :pressRating, userRating = :userRating, poster = :poster,
synopsis = :synopsis, file = :file, path = :path
WHERE id = :mId');
        $stmt->execute($arr);
        return $mId;
    }

    public function insertSimpleMovie($file, $path, $multiple)
    {
        $stmt = $this->pdo->prepare('INSERT INTO movies(allocineId, file, path, multipleChoices)
VALUES(-1, :file, :path, :multiple)');
        $stmt->execute(array('file' => $file, 'path' => $path, 'multiple' => $multiple));
        return $this->pdo->lastInsertId();
    }

    public function setNoAllocineIdForMovie($file)
    {
        $stmt = $this->pdo->prepare('UPDATE movies SET allocineId = -1
WHERE file = :file');
        $stmt->execute(array('file' => $file));
    }

    public function updateSimpleMovie($mId, $file, $path, $multiple)
    {
        $stmt = $this->pdo->prepare('UPDATE movies
SET file = :file, path = :path, multipleChoices = :multiple
WHERE id = :mId
LIMIT 1');
        $stmt->execute(array('mId' => $mId, 'file' => $file, 'path' => $path, 'multiple' => $multiple));
        return $mId;
    }

    public function insertChoice($mId, $arr)
    {
        $arr['mId'] = $mId;
        $stmt = $this->pdo->prepare('INSERT INTO movies_multiple_choices(movieId, title, originalTitle, allocineId, pressRating, userRating, poster, actors, directors)
VALUES(:mId, :title, :originalTitle, :allocineId, :pressRating, :userRating, :poster, :actors, :directors)');
        $stmt->execute($arr);
    }

    public function insertPeople($movieId, $arr, $role)
    {
        foreach ($arr as $v) {
            $v = trim($v);
            $stmt = $this->pdo->prepare('SELECT id FROM people WHERE name LIKE :name');
            $stmt->execute(array('name' => $v));
            $res = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($res && !empty($res)) {
                $id = $res['id'];
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO people(name) VALUES(:name)');
                $stmt->execute(array('name' => $v));
                $id = $this->pdo->lastInsertId();
            }

            $stmt = $this->pdo->prepare('INSERT IGNORE INTO movies_people(movieId, peopleId, role) VALUES(:mId, :pId, :role)');
            $stmt->execute(array('mId' => $movieId, 'pId' => $id, 'role' => $role));
        }
    }

    public function insertGenres($movieId, $arr)
    {
        foreach ($arr as $v) {
            $v = trim($v);
            $stmt = $this->pdo->prepare('SELECT id FROM genres WHERE name LIKE :name');
            $stmt->execute(array('name' => $v));
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($res && !empty($res)) {
                $id = $res['id'];
            } else {
                $stmt = $this->pdo->prepare('INSERT INTO genres(name) VALUES(:name)');
                $stmt->execute(array('name' => $v));
                $id = $this->pdo->lastInsertId();
            }
            $stmt = $this->pdo->prepare('INSERT IGNORE INTO movies_genres(movieId, genreId) VALUES(:mId, :gId)');
            $stmt->execute(array('mId' => $movieId, 'gId' => $id));
        }
    }

    public function getAllPosters()
    {
        $stmt = $this->pdo->prepare('SELECT id, poster, title, originalTitle FROM movies WHERE poster IS NOT NULL AND trash = 0 ORDER BY title');
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function addLocalPosterForMovie($movieId, $poster)
    {
        $stmt = $this->pdo->prepare('UPDATE movies SET localPoster = :poster WHERE id = :id');
        $stmt->execute(array('id' => $movieId, 'poster' => $poster));
    }


    public function getMovies2($search, $genres, $from, $nbr)
    {
        $stmt = $this->pdo->prepare('
SELECT m.*, YEAR(releaseDate) AS year FROM movies AS m
' . ($genres ? 'LEFT JOIN movies_genres AS mg ON m.id = mg.movieId WHERE mg.genreId IN(' . $genres . ')' : '') . '
' . ($search ? ($genres ? 'AND ' : 'WHERE ') . 'LOWER(title) LIKE :title OR LOWER(originalTitle) LIKE :title' : '') . '
ORDER BY title
LIMIT ' . $from . ', ' . $nbr . '
');
        $stmt->execute(array('title' => "%$search%"));
        $res = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($res as $i => $v) {
            $stmt = $this->pdo->prepare('SELECT g.* FROM movies_genres AS mg
LEFT JOIN genres AS g ON mg.genreId = g.id
WHERE mg.movieId = :id');
            $stmt->execute(array('id' => $v['id']));
            $res[$i]['genres'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $this->pdo->prepare('SELECT p.*, role FROM people AS p
LEFT JOIN movies_people AS mp ON p.id = mp.peopleId
WHERE mp.movieId = :id');
            $stmt->execute(array('id' => $v['id']));
            $tmp = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $res[$i]['directors'] = array();
            $res[$i]['actors'] = array();
            foreach ($tmp as $t) {
                if ($t['role'] == 'actor') {
                    $res[$i]['actors'][] = $t;
                } else {
                    $res[$i]['directors'][] = $t;
                }
            }
        }
        return $res;
    }

    public function getTotalMovies2($search, $genres, $from, $nbr)
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS tot FROM movies AS m
' . ($genres ? 'LEFT JOIN movies_genres AS mg ON m.id = mg.movieId WHERE mg.genreId IN(' . $genres . ')' : '') . '
' . ($search ? ($genres ? 'AND ' : 'WHERE ') . 'LOWER(title) LIKE :title OR LOWER(originalTitle) LIKE :title' : '')
        );
        $stmt->execute(array('title' => "%$search%"));
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res['tot'];
    }
}