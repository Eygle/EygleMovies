<?php

require_once "HTTPClient.class.php";
require_once "simple_html_dom.php";

class AllocineResult {
	public $title = null;
	public $originalTitle = null;
	public $allocineId = null;
	public $releaseDate = null;
	public $directors = null;
	public $actors = null;
	public $pressRating = null;
	public $userRating = null;
	public $poster = null;
	public $synopsis = null;
	public $genres = null;
	
	public $file;
	public $path;
	
	public function toArray() {
		return array(
				'title' => $this->title,
				'originalTitle' => $this->originalTitle,
				'allocineId' => $this->allocineId,
				'releaseDate' => $this->releaseDate,
				'pressRating' => $this->pressRating,
				'userRating' => $this->userRating,
				'poster' => $this->poster,
				'file' => $this->file,
				'path' => $this->path,
				'synopsis' => $this->synopsis
				);
	}
	
	public function toSmallArray() {
		return array(
				'title' => $this->title,
				'originalTitle' => $this->originalTitle,
				'allocineId' => $this->allocineId,
				'pressRating' => $this->pressRating,
				'userRating' => $this->userRating,
				'poster' => $this->poster,
				'actors' => $this->actors ? implode(',', $this->actors) : null,
				'directors' => $this->directors ? implode(',', $this->directors) : null
		);
	}
}

class AllocineConnector {
	
	private $httpClient;

	const ENDPOINT = "http://api.allocine.fr/rest/v3/";
	const PARTNER = "YW5kcm9pZC12Mg";

	function __construct(){
		$this->httpClient = new HTTPClient();
	}

	private function performRequest($url){
		$url = self::ENDPOINT.$url."&format=json&partner=".self::PARTNER;
		echo "Performing allocine request $url\n";
		return $this->httpClient->request($url,HTTPClient::TTL_24_HOURS);
	}

	public function getCompleteMovieInfosFromId($id) {
		$json = $this->performRequest("movie?code=$id&profile=medium");
		if ($json) $json = json_decode($json, true);
		if (!$json) return NULL;
		
		$movie = isset($json['movie']) ? $json['movie'] : null;
		if (!$movie) return null;

		$res = new AllocineResult();
		
		//var_dump($movie);

		if (isset($movie['poster']['href']) && $movie['poster']['href'] != 'http://images.allocine.fr/commons/event/android/GPLAY2.png')
			$res->poster = $movie['poster']['href'];
		$res->allocineId = $movie['code'];
		if (isset($movie['originalTitle']) && mb_strstr($movie['originalTitle'], 'Mise à jour sur Google play', 'UTF-8') === false)
		$res->originalTitle = $movie['originalTitle'];
		$res->title = isset($movie['title']) && mb_strstr($movie['title'], 'Mise à jour sur Google play', 'UTF-8') === false ? $movie['title'] : $movie['originalTitle'];
		if (isset($movie['castingShort']['directors']))
			$res->directors = $movie['castingShort']['directors'];
		if (isset($movie['castingShort']['actors']))
			$res->actors = $movie['castingShort']['actors'];
		if (isset($movie['statistics']['pressRating']))
			$res->pressRating = $movie['statistics']['pressRating'];
		if (isset($movie['statistics']['userRating']))
			$res->userRating = $movie['statistics']['userRating'];
		if (isset($movie['synopsis']))
			$res->synopsis = $movie['synopsis'];
		if (isset($movie['release']['releaseDate']))
			$res->releaseDate = $movie['release']['releaseDate'];
		else if (isset($movie['productionYear']))
			$res->releaseDate = $movie['productionYear'].'-01-01';
		else if (isset($movie['dvdReleaseDate']))
			$res->releaseDate = $movie['dvdReleaseDate'];
		$genres = array();
		foreach ($movie['genre'] as $v) {
			$genres[] = $v['$'];
		}
		$res->genres = implode(',', $genres);
		return $this->searchMovieFromAllocinePage($res, $id);
	}
	
	public function getMovieInfosFromId($res) {
		$json = $this->performRequest("movie?code=$res->allocineId&profile=medium");
		if ($json) $json = json_decode($json, true);
		if (!$json) return NULL;
		
		$movie = isset($json['movie']) ? $json['movie'] : null;
		if (!$movie) return null;
		
		// var_dump($movie);
		
		if (isset($movie['synopsis']))
			$res->synopsis = $movie['synopsis'];
		if (isset($movie['release']['releaseDate']))
			$res->releaseDate = $movie['release']['releaseDate'];
		else if (isset($movie['productionYear']))
			$res->releaseDate = $movie['productionYear'].'-01-01';
		else if (isset($movie['dvdReleaseDate']))
			$res->releaseDate = $movie['dvdReleaseDate'];
		$res->genres = array();
		foreach ($movie['genre'] as $v) {
			$res->genres[] = $v['$'];
		}
		return $res;
	}
	
	public function searchMovie($title)
	{
		$json = $this->performRequest("search?q=".urlencode($title)."&filter=movie");
		if ($json) $json = json_decode($json, true);
		if (!$json) {
			echo "Allociné error\n";
			exit(0);
		}
		
		$feed = isset($json['feed']) ? $json['feed'] : null;
		if (!$feed) {
			echo "Allociné feed error\n";
			rexit(0);
		}
		
		$movie = isset($feed['movie']) ? $feed['movie'] : null;
		if (!$movie) return null;
		
		$title = strtolower($title);
		foreach ($feed['movie'] as $v) {
			//var_dump($v)."\n";
			if ((isset($v['title']) && $title == strtolower($v['title']))
				|| (isset($v['originalTitle']) && $title == strtolower($v['originalTitle']))) {
				return $this->getMovieInfosFromId($this->createAllocineResultFromSearch($v));
			}
		}
		return $this->insertMultipleChoicesMovies($feed['movie']);
	}

	public function searchMovieFromAllocinePage($res, $id) {
		$html = file_get_html("http://www.allocine.fr/film/fichefilm_gen_cfilm=$id.html");
		$res->title = $html->find('meta[itemprop=name]', 0)->content;
		$res->poster = $html->find('div.poster', 0)->find('img', 0)->src;
		// $table = $html->find('div.expendTable', 0);
		// if ($table) {
		// 	foreach ($table->find('tr') as $v) {
		// 		$tmp = $v->find('div');
		// 		if (isset($tmp[0]) && $tmp[0]->innertext == "Titre original") {
		// 			$res->originalTitle = $v->find('td', 0);
		// 			break;
		// 		}
		// 	}
		// }
		return $res;
	}

	private function createAllocineResultFromSearch($movie) {
		$res = new AllocineResult();
		if (isset($movie['poster']['href']))
		$res->poster = $movie['poster']['href'];
		$res->allocineId = $movie['code'];
		if (isset($movie['originalTitle']))
		$res->originalTitle = $movie['originalTitle'];
		$res->title = isset($movie['title']) ? $movie['title'] : $movie['originalTitle'];
		if (isset($v['castingShort']['directors']))
			$res->directors = explode(',', $movie['castingShort']['directors']);
		if (isset($v['castingShort']['actors']))
			$res->actors = explode(',', $movie['castingShort']['actors']);
		if (isset($v['statistics']['pressRating']))
			$res->pressRating = $movie['statistics']['pressRating'];
		if (isset($movie['statistics']['userRating']))
			$res->userRating = $movie['statistics']['userRating'];
		return $res;
	}
	
	private function insertMultipleChoicesMovies($movies) {
		$ret = array();
		foreach ($movies as $v) {
			$ret[] = $this->createAllocineResultFromSearch($v);
		}
		return $ret;
	}

	private function formatDate($str) {
		$date = explode('-', $str);
		$y = intval($date[0]);
		$m = intval($date[1]);
		$d = intval($date[2]);
		return @mktime(0,0,0,$m,$d,$y);
	}
}