<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once('db/DBMovies.class.php');
require_once('utils/MovieSearch.class.php');

$cssDependencies = array(
	'css/style.css'
);

$jsDependencies = array(
	'//ajax.googleapis.com/ajax/libs/jquery/2.1.0/jquery.min.js',
	'js/index.js'
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
</head>

</head>
<body>
<div id="panel-left">
	<div id="new-search-cntr">
		<div id="new-search-caption">Nouvelle recherche</div>
		<form method="get" action="index.php">
		<div class="btn-bar">
			<div class="btn-bar-wrapper">
				<button onclick="window.location = 'index.php';return false;">Reset</button>
			</div>
			<div class="btn-bar-wrapper">
				<button type="submit">Search</button>
			</div>
		</div>
		<ul id="filters-list">
			<li class="filter-group">
				<div class="filter-group-caption"><span>Rechercher</span></div>
				<ul class="filter-group-cnt">
					<!-- search -->
					<li class="filter-item-cntr">
						<div class="filter-label-cntr">
							<label for="title">Titre :</label>
						</div>
						<div class="filter-value-cntr">
							<input type="text" name="search" autocomplete="off" class="acInput">
						</div>
					</li>
				</ul>
			</li>
			<li class="filter-group">
				<div class="filter-group-caption"><span>Genres</span></div>
				<ul class="filter-group-cnt">
					<!-- genres -->
					
					<?php 
					foreach ($genres as $g) {
						echo '<li class="filter-item-cntr">
							<div class="filter-label-cntr"><label for="genre_id_'.$g['id'].'">'.$g['name'].'</label></div>
							<div class="filter-value-cntr"><input type="checkbox" id="genre_id_'.$g['id'].'" name="genre_'.$g['id'].'" '.(isset($_GET['genre_'.$g['id']]) ? 'checked="checked"' : '').'/></div>
						</li>';
					}
					?>
				</ul>
			</li>
		</ul>
		</form>
	</div>
</div><div id="table-cntr">
	<div id="hidden-cover-div">
		<div id="background"></div>
		<div id="movieInfos">
			<div id="close">X</div>
			<div class="poster"></div><div class="infos">
				<div class="line"><div class="label">Titre</div><div class="info title"></div></div>
				<div class="line"><div class="label">Titre original</div><div class="info originalTitle"></div></div>
				<div class="line"><div class="label">Date de sortie</div><div class="info releaseDate"></div></div>
				<div class="line"><div class="label">Réalisteurs</div><div class="info directors"></div></div>
				<div class="line"><div class="label">Acteurs</div><div class="info actors"></div></div>
				<div class="line"><div class="label">Genres</div><div class="info genres"></div></div>
				<div class="line"><div class="label">Note de la presse</div><div class="info pressRating"></div></div>
				<div class="line"><div class="label">Note des utilisateurs</div><div class="info userRating"></div></div>
			</div>
			<div class="synopsis"></div>
			<div class="admin"><a class="refresh">Auto edition</a><a class="edit">Editer</a></div>
		</div>
	</div>
	<div id="table-header">
		<div class="table-header-elem">Résultats : <?php echo $search->getTotalResults();?></div>
		<?php
			if ($search->getNbrPages() > 1)
				echo $search->generatePagination();
		?>
		</div>
		<div id="table-wrapper">
			<div id="search-content">
				<?php
				foreach ($movies as $m) {
					if ($m['title']) {
					echo '<div class="search-result" id="'.$m['id'].'">
						<div class="search-poster">
							<img src="'. ($m['localPoster'] ? 'posters/'.$m['localPoster'] : $m['poster']) .'" />
						</div><div class="search-infos">
							<div class="search-title">'.$m['title'].'</div>
							<div class="search-year">'.$m['year'].'</div>
						</div>
					</div>';
					} else {
						echo '<a href=""><div class="search-result">
							<div class="search-title">'.$m['file'].'</div>
						</div></a>';
					}
				}
				?>
			</div>
		</div>
	</div>

	<?php
	foreach ($jsDependencies as $dep) {
		echo '<script src="'.$dep.'"></script>';
	}
	?>
	<script type="text/javascript">
		$(document).ready(function() {
			initIndex();
		});
	</script>
</body>
</html>