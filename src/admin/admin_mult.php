<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('db/DBMovies.class.php');

$db  = new DBMovies();

if (isset($_GET['id']) && isset($_GET['allocineId'])) {
	$search = new AllocineConnector();
	$res = $db->getFromMultMovies($_GET['movieId'], $_GET['allocineId']);
	//$search->
}


$movies = $db->getMoviesMult();

foreach($movies as $m) {
	echo '<div class="movie_mult">
		<div class="file-title">'.$m['file'].'</div>
		<div class="file-folder">'.$m['path'].'</div>
		<div class="allocine-choices">';
			foreach ($m['choices'] as $c) {
				echo '<a href="?action=editMovie&previousAction='.$_GET['action'].'&movieId='.$c['id'].'&allocineId='.$c['allocineId'].'"><div class="search-result">
					<div class="search-poster">
						<img src="'.$c['poster'].'" />
					</div><div class="search-infos">
						<div class="search-title">'.$c['title'].'</div>
					</div>
				</div></a>';
			}
		echo '</div>
	</div>';
}

foreach ($jsDependencies as $dep) {
	echo '<script src="'.$dep.'"></script>';
}
?>
</body>
</html>