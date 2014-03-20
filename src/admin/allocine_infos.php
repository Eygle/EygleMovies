<?php
require_once(dirname(__FILE__).'/../utils/AllocineConnector.class.php');

try {
	if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		$allocine = new AllocineConnector();
		$res = $allocine->getCompleteMovieInfosFromId($_GET['allocineId']);
		ob_clean();
		echo json_encode($res);
	} else {
		echo json_encode(array('error'=>'Wrong http method.'));
	}
} catch (Exception $e) {
	echo json_encode(array('error'=>'an error occured'));
}
?>