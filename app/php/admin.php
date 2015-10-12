<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once(dirname(__FILE__) . '/db/DBMovies.class.php');
require_once(dirname(__FILE__) . '/utils/MovieSearch.class.php');

$cssDependencies = array(
	'css/style.css',
	'css/admin.css'
);

$jsDependencies = array(
	'//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js',
	'js/admin.js'
);

$db  = new DBMovies();

$genres = $db->getGenres();

$search = new MovieSearch($db, $genres);

$movies = $search->getMovies();

?>

<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="fr">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="viewport" content="initial-scale=1">
<title>Eygle's films</title>
<?php
foreach ($cssDependencies as $dep) {
	echo '<link rel="stylesheet" href="'.$dep.'" />';
}
?>

<?php
foreach ($jsDependencies as $dep) {
	echo '<script src="'.$dep.'"></script>';
}
?>
</head>

</head>
<body>

<h1>Administration</h1>

<?php

if (!isset($_GET['action'])) {
	echo '
	<a href="?action=multiple"><div class="">Choix multiples</div></a>
	<a href="?action=noresults"><div class="">Sans résultat</div></a>
	';
} else {
	switch ($_GET['action']) {
		case 'multiple':
			include 'admin/admin_mult.php';
		break;
		case 'noresults':
			include 'admin/admin_noresult.php';
		break;
		case 'editMovie':
			include 'admin/edit_movie.php';
		break;
	}
}

?>

</body>
</html>