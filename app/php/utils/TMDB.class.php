<?php

error_reporting(E_ALL);
ini_set("display_errors", "On");

require_once dirname(__FILE__) . "/HTTPClient.class.php";

class MovieConfiguration {
	public $imagesBackdropSizes;
	public $imagesProfileSizes;
	public $imagesPosterSizes;
	public $imagesBaseUrl;
}

class MovieCastCharacter{
	public $character;
	public $name;
	public $photoUrl;
	public $icon;
	public $id=null;
}

class MovieInfo{
	public $movieId;
	public $title;
	public $originalTitle;
	public $genres;
	public $budget;
	public $revenue;
	public $releaseDate;
	public $voteAvg;
	public $countries;
	public $overview;
	public $posterUrl;
	public $movieCast;
	public $backdrops;
}

class TMDB{
	private $httpClient;

	private $imagesBackdropSizes;
	private $imagesProfileSizes;
	private $imagesPosterSizes;
	private $imagesBaseUrl;

	CONST API_KEY = "ddbaa67496698ed0919a0200379bea30";
	const API_URL = "http://api.themoviedb.org/3/";

	function __construct($config = null){
		$this->httpClient = new HTTPClient();
		$this->httpClient->addHeader("Accept: application/json");
		$this->httpClient->setUserAgent(null);
		$this->httpClient->setReferer(null);
		$this->getConfiguration($config);
	}

	public function getConfigObject() {
		$conf = new MovieConfiguration();
		$config->imagesBackdropSizes = $this->imagesBackdropSizes;
		$config->imagesProfileSizes = $this->imagesProfileSizes;
		$config->imagesPosterSizes = $this->imagesPosterSizes;
		$config->imagesBaseUrl = $this->imagesBaseUrl;
		return $config;
	}

	private function requestApi($url){
		echo("request API $url ...");
		$json = $this->httpClient->request($url, HTTPClient::TTL_24_HOURS);
		//	$json = $this->httpClient->request($url, 0);
		if(!$json){
			return false;
		}
		$results = json_decode($json, true);
		echo("OK\n");
		return $results;
	}

	private function getconfiguration($config){
		if ($config) {
			$this->imagesBackdropSizes = $config->imagesBackdropSizes;
			$this->imagesProfileSizes = $config->imagesProfileSizes;
			$this->imagesPosterSizes = $config->imagesPosterSizes;
			$this->imagesBaseUrl = $config->imagesBaseUrl;
		} else {
			$url = self::API_URL."configuration?api_key=".self::API_KEY;
			$result = $this->requestApi($url);
			if($result && isset($result["images"])){
				$this->imagesBackdropSizes = $result["images"]["backdrop_sizes"];
				$this->imagesProfileSizes = $result["images"]["profile_sizes"];
				$this->imagesPosterSizes = $result["images"]["poster_sizes"];
				$this->imagesBaseUrl = $result["images"]["base_url"];
			}
		}
	}

	public function searchMovie($title){
		$url = self::API_URL."search/movie?api_key=".self::API_KEY."&language=fr&query=".urlencode($title);
		$result = $this->requestApi($url);
		if($result && isset($result["results"]) && count($result["results"])>0){
			foreach ($result["results"] as $r){
				$movieId = $r["id"];
				$movie = $this->getMovieInfo($movieId);
				$movieCast = $movie->movieCast;
				return $movie;
			}
		}
		return null;

	}

	public function searchMovieById($id){
		return $this->getMovieInfo($id);
	}

	private function getMovieInfo($movieId){
		$url = self::API_URL."movie/".$movieId."?api_key=".self::API_KEY."&language=fr";
		$result = $this->requestApi($url);
		if($result){
			$movieInfo = new MovieInfo();
			$movieInfo->movieId = $movieId;
			$movieInfo->title = $result["title"];
			$movieInfo->releaseDate = $result["release_date"];
			$movieInfo->originalTitle = $result["original_title"];
			$movieInfo->budget = $result["budget"];
			$movieInfo->revenue = $result["revenue"];
			$movieInfo->voteAvg = $result["vote_average"];
			if($result["poster_path"]){
				$movieInfo->posterUrl = $this->imagesBaseUrl.$this->imagesPosterSizes[0].$result["poster_path"];
			}
			$movieInfo->genres = array();
			if($result["genres"]){
				foreach ($result["genres"] as $genre){
					if($genre["name"] != "Foreign"){
						$movieInfo->genres[] = $genre["name"];
					}
				}
			}
			$movieInfo->countries = array();
			if($result["production_countries"]){
				foreach ($result["production_countries"] as $country){
					$movieInfo->countries[] = $country["iso_3166_1"];
				}
			}
			$movieInfo->overview = $result["overview"];

			$movieInfo->movieCast = $this->getMovieCast($movieId);

			$movieInfo->backdrops = $this->getMovieBackdrops($movieId);
			return $movieInfo;
		}
		return null;
	}

	private function getMovieCastNames($movieId){
		$url = self::API_URL."movie/".$movieId."/casts?api_key=".self::API_KEY."&language=fr";
		$result = $this->requestApi($url);
		if($result && isset($result["cast"])){
			$cast = $result["cast"];
			$movieCast = array();
			foreach($cast as $char){
				$movieChar = new MovieCastCharacter();
				$movieChar->character = $char["character"];
				$movieChar->name = $char["name"];
				$movieCast[] = $movieChar;
			}
			return $movieCast;
		}
		return null;
	}

	private function getMovieCast($movieId){
		$url = self::API_URL."movie/".$movieId."/casts?api_key=".self::API_KEY."&language=fr";
		$result = $this->requestApi($url);
		if($result && isset($result["cast"])){
			$cast = $result["cast"];
			$movieCast = array();
			foreach($cast as $char){
				$movieChar = new MovieCastCharacter();
				$movieChar->character = $char["character"];
				$movieChar->name = $char["name"];
				if($char["profile_path"]){
					$tbUrl = $this->imagesBaseUrl.$this->imagesProfileSizes[0].$char["profile_path"];
					list($tbWidth, $tbHeight) = getimagesize($tbUrl);
					$originalUrl = $this->imagesBaseUrl.$this->imagesProfileSizes[count($this->imagesProfileSizes)-1].$char["profile_path"];
					list($originalWidth, $originalHeight) = getimagesize($originalUrl);
					$movieChar->icon = array(
							"tbUrl"=>$tbUrl,
							"tbWidth"=>$tbWidth,
							"tbHeight"=>$tbHeight,
							"originalUrl"=>$originalUrl,
							"originalWidth"=>$originalWidth,
							"originalHeight"=>$originalHeight);
					$movieChar->photoUrl = $originalUrl;
				}
				$movieCast[] = $movieChar;
			}
			return $movieCast;
		}
		return null;
	}

	private function getMovieBackdrops($movieId){
		$url = self::API_URL."movie/".$movieId."/images?api_key=".self::API_KEY;
		$result = $this->requestApi($url);
		if($result && isset($result["backdrops"])){
			$backdrops = $result["backdrops"];
			$backdropsUrls = array();
			foreach ($backdrops as $bd){
				if($bd["file_path"]){
					$backdropsUrls[] = $this->imagesBaseUrl.$this->imagesBackdropSizes[0].$bd["file_path"];
				}
			}
			return $backdropsUrls;
		}
		return null;
	}

	public static function generateMovieData($movieInfos, $db) {
		//make sure there is not already a tmdb entry with the same originalId
		$movieData = array('poster' => $movieInfos->posterUrl, 'title' => $movieInfos->originalTitle, 'votes' => $movieInfos->voteAvg,
				'genres' => implode(",", $movieInfos->genres), 'countries' => implode(",", $movieInfos->countries), 'date' => strtotime($movieInfos->releaseDate) * 1000,
				'budget' => number_format($movieInfos->budget, 0, ',', ' '), 'revenue' => number_format($movieInfos->revenue, 0, ',', ' '),
				'overview' => $movieInfos->overview, 'casting' => json_encode($movieInfos->movieCast), 'images' => json_encode($movieInfos->backdrops), "originalId"=>$movieInfos->movieId);
		return $movieData;
	}
}

?>
