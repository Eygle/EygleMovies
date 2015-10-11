<?php
if (!defined('DBCONFIG_FILE')) {
	define('DBCONFIG_FILE', dirname(__FILE__).'/dbconfig.php');
}
require_once DBCONFIG_FILE;

class DAO{
	protected $pdo;
	protected $modelClass;
	public static $CACHE_ENABLED = true;
	private $memcache =null;

	const TTL_1_MINUTE = 60;
	const TTL_1_HOUR = 3600;
	const TTL_24_HOURS = 86400;
	const TTL_1_WEEK = 604800;

	const LATEST_PEOPLE_LEANK_TIMESTAMP = "latest-people-leank-timestamp";
	const LATEST_PLACE_LEANK_TIMESTAMP = "latest-places-leank-timestamp";
	const LATEST_NEWS_LEANK_TIMESTAMP = "latest-news-leank-timestamp";
	const LATEST_PRODUCT_LEANK_TIMESTAMP = "latest-products-leank-timestamp";
	const LATEST_DEFINITION_LEANK_TIMESTAMP = "latest-definition-leank-timestamp";

	function __construct($modelClass=NULL){
		$pdo_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
		$pdo_options[PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES utf8";
		try{
			$this->pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_DATABASE, DB_USER, DB_PASSWORD, $pdo_options);
		} catch (PDOException $e) {
			print "Erreur de connexion<br/>";
		}
		$this->modelClass = $modelClass;

		if(self::$CACHE_ENABLED && class_exists('Memcache')){
			set_error_handler(array($this, 'memcacheConnectErrorHandler'));
			try{
				$this->memcache = new Memcache;
				$this->memcache->connect('localhost', 11211);
			}catch (Exception $e){
				$this->memcache = null;
				self::$CACHE_ENABLED = false;
			}
			restore_error_handler();
		}
	}

	function memcacheConnectErrorHandler($errno, $errstr, $errfile, $errline)
	{
		$this->memcache = null;
		self::$CACHE_ENABLED = false;
		return true;
	}

	protected function createModelObject($dbResult){
		if(!$dbResult){
			return null;
		}
		$mo = new $this->modelClass();
		if($mo instanceof AbstractEPGObject){
			$mo->hydrate($dbResult);
		}
		return $mo;
	}

	protected function createModelObjectArray($dbResultArray){
		if(!$dbResultArray){
			return null;
		}
		$moa = array();
		foreach ($dbResultArray as $dbResult){
			$moa[] = $this->createModelObject($dbResult);
		}
		return $moa;
	}

	protected function getFromCache($cacheKey){
		if($this->memcache){
			try {
				$result = $this->memcache->get($cacheKey);
				return $result;
			} catch (Exception $e) {
			}
		}
		return FALSE;
	}

	protected function putInCache($cacheKey, $ttl, $data){
		if($this->memcache){
			try {
				$this->memcache->set($cacheKey,$data,0, $ttl);
			} catch (Exception $e) {
			}
		}
	}

	protected function deleteFromCache($cacheKey){
		if($this->memcache){
			$this->memcache->delete($cacheKey);
		}
	}

	protected function getLatestPeopleLeankTimestamp($channelId=1){
		$latest = $this->getFromCache(self::LATEST_PEOPLE_LEANK_TIMESTAMP."-$channelId");
		if($latest === FALSE){
			$latest = $this->getMaxPeopleTimestamp($channelId);
		}
		return $latest;
	}

	protected function getMaxPeopleTimestamp($channelId){
		$stmt = $this->pdo -> prepare("SELECT timestamp as max
										FROM people_appearances WHERE channelId=:channelId
										ORDER BY timestamp DESC LIMIT 1");
		$stmt -> execute(array("channelId"=>$channelId));
		$res = $stmt -> fetch(PDO::FETCH_ASSOC);
		return $res["max"];
	}

	protected function getLatestPlaceLeankTimestamp($channelId=1){
		$latest = $this->getFromCache(self::LATEST_PLACE_LEANK_TIMESTAMP."-$channelId");
		if($latest === FALSE){
			$latest = $this->getMaxPlaceTimestamp($channelId);
		}
		return $latest;
	}

	protected function getMaxPlaceTimestamp($channelId){
		$stmt = $this->pdo -> prepare("SELECT timestamp as max
												FROM places_appearances WHERE channelId=:channelId
												ORDER BY timestamp DESC LIMIT 1");
		$stmt -> execute(array("channelId"=>$channelId));
		$res = $stmt -> fetch(PDO::FETCH_ASSOC);
		return $res["max"];
	}

	protected function getLatestDefinitionLeankTimestamp($channelId=1){
		$latest = $this->getFromCache(self::LATEST_DEFINITION_LEANK_TIMESTAMP."-$channelId");
		if($latest === FALSE){
			$latest = $this->getMaxDefinitionTimestamp($channelId);
		}
		return $latest;
	}

	protected function getMaxDefinitionTimestamp($channelId){
		$stmt = $this->pdo -> prepare("SELECT timestamp as max
												FROM definitions_appearances WHERE channelId=:channelId
												ORDER BY timestamp DESC LIMIT 1");
		$stmt -> execute(array("channelId"=>$channelId));
		$res = $stmt -> fetch(PDO::FETCH_ASSOC);
		return $res["max"];
	}

	protected function getLatestProductLeankTimestamp($channelId=1){
		$latest = $this->getFromCache(self::LATEST_PRODUCT_LEANK_TIMESTAMP."-$channelId");
		if($latest === FALSE){
			$latest = $this->getMaxProductTimestamp($channelId);
		}
		return $latest;
	}

	protected function getMaxProductTimestamp($channelId){
		$stmt = $this->pdo -> prepare("SELECT timestamp as max
												FROM products_matches WHERE channelId=:channelId
												ORDER BY timestamp DESC LIMIT 1");
		$stmt -> execute(array("channelId"=>$channelId));
		$res = $stmt -> fetch(PDO::FETCH_ASSOC);
		return $res["max"];
	}

	protected function getLatestNewsLeankTimestamp($channelId=1){
		$latest = $this->getFromCache(self::LATEST_NEWS_LEANK_TIMESTAMP."-$channelId");
		if($latest === FALSE){
			$latest = $this->getMaxNewsTimestamp($channelId);
		}
		return $latest;
	}

	protected function getMaxNewsTimestamp($channelId){
		$stmt = $this->pdo -> prepare("SELECT timestamp as max
												FROM news_matches WHERE channelId=:channelId
												ORDER BY timestamp DESC LIMIT 1");
		$stmt -> execute(array("channelId"=>$channelId));
		$res = $stmt -> fetch(PDO::FETCH_ASSOC);
		return $res["max"];
	}

}
?>
