<?php
require_once (dirname(__FILE__).'/DAO.class.php');

class DBMovies extends DAO {


	public function __construct() {
		parent::__construct();
	}

	public function issetFile($file) {
		$stmt = $this->pdo->prepare('SELECT id FROM movies WHERE file LIKE :file');
		$stmt->execute(array('file'=>$file));
		return $stmt->fetch(PDO::FETCH_ASSOC);
	}
}