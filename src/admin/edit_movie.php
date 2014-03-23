<?php

require_once('db/DBMovies.class.php');
require_once('utils/utils.php');
require_once 'utils/HTTPClient.class.php';
$db  = new DBMovies();

if ($_SERVER['REQUEST_METHOD'] == "POST") {
	$db->editMovie($_POST['movieId'],
		$_POST['allocineId'],
		$_POST['title'],
		$_POST['originalTitle'],
		$_POST['releaseDate'],
		$_POST['pressRating'],
		$_POST['userRating'],
		$_POST['poster'],
		$_POST['synopsis']);
	$directors = explode(',', $_POST['directors']);
	if ($directors) $db->insertPeople($_POST['movieId'], $directors, 'director');
	$actors = explode(',', $_POST['actors']);
	if ($actors) $db->insertPeople($_POST['movieId'], $actors, 'actor');
	$genres = explode(',', $_POST['genres']);
	if ($genres) $db->insertGenres($_POST['movieId'], $genres);
	if ($_POST['poster'] && !empty($_POST['poster'])) {
		$http = new HTTPClient();
		$localPosterName = formatFileNameFromTitle(!empty($_POST['title']) ? $_POST['title'] : $_POST['originalTitle']);
		$localPosterName = $_POST['movieId'].'-'.$localPosterName.'.'.end(explode('.', $_POST['poster']));
		$http->download($_POST['poster'], dirname(__FILE__).'/../posters/'.$localPosterName);
		$db->addLocalPosterForMovie($_POST['movieId'], $localPosterName);
	}
	//if ($_GET['previousAction'] == 'multiple')
	// 	$db->removeMultiplesMovies($_POST['movieId']);
	// if ($_GET['previousAction'] == 'view')
		//header('Location: index.php');
	// else
		header('Location: admin.php?action='.$_GET['previousAction']);
}

if ($_GET['previousAction'] == 'multiple')
	$movie = $db->getMovieMult($_GET['movieId']);
else {
	$movie = $db->getMovieInfos($_GET['movieId']);
	$movie['movieId'] = $movie['id'];
}
?>

<div id="erase">Vider les champs</div>

<h2><?php echo $movie['file']; ?></h2>

<?php
if ($movie['allocineId']) {
	echo '<h3><a href="http://www.allocine.fr/film/fichefilm_gen_cfilm='.$movie['allocineId'].'.html" target="_blank">http://www.allocine.fr/film/fichefilm_gen_cfilm='.$movie['allocineId'].'.html</a></h3>';
}
?>

<div id="autocomplete-allocine"><input type="text" id="search-allocineId" <?php if ($movie['allocineId']) echo 'value="'.$movie['allocineId'].'"';?> /> <input type="submit" value="search" id="searchAllocineId"/></div>

<div id="admin-form">
	<form method="post">
		<input type="hidden" name="movieId" value="<?php echo $movie['movieId'];?>" />

		<div class="line">
			<div class="left"><label for="allocineId">Id Allociné</label></div><div class="right">
				<input type="text" name="allocineId" id="allocineId" <?php if ($movie['allocineId']) echo 'value="'.$movie['allocineId'].'"';?>/>
			</div>
		</div>
		<div class="line">
			<div class="left"><label for="title">Titre</label></div><div class="right">
				<input type="text" name="title" id="title" <?php if ($movie['title']) echo 'value="'.$movie['title'].'"';?>/>
			</div>
		</div>
		<div class="line">
			<div class="left"><label for="originalTitle">Titre original</label></div><div class="right">
				<input type="text" name="originalTitle" id="originalTitle" <?php if ($movie['originalTitle']) echo 'value="'.$movie['originalTitle'].'"';?>/>
			</div>
		</div>
		<div class="line">
			<div class="left"><label for="releaseDate">Date de sortie</label></div><div class="right">
				<input type="text" name="releaseDate" id="releaseDate" <?php if ($movie['releaseDate']) echo 'value="'.$movie['releaseDate'].'"';?>/>
			</div>
		</div>
		<div class="line">
			<div class="left"><label for="directors">Rélisateurs</label></div><div class="right">
				<input type="text" name="directors" id="directors" <?php if (isset($movie['directors']) && $movie['directors']) echo 'value="'.$movie['directors'].'"';?>/>
			</div>
		</div>
		<div class="line">
			<div class="left"><label for="actors">Acteurs</label></div><div class="right">
				<input type="text" name="actors" id="actors" <?php if (isset($movie['actors']) && $movie['actors']) echo 'value="'.$movie['actors'].'"';?>/>
			</div>
		</div>
		<div class="line">
			<div class="left"><label for="genres">Genres</label></div><div class="right">
				<input type="text" name="genres" id="genres" <?php if (isset($movie['genres']) && $movie['genres']) echo 'value="'.$movie['genres'].'"';?>/>
			</div>
		</div>
		<div class="line">
			<div class="left"><label for="pressRating">Note de la presse</label></div><div class="right">
				<input type="text" name="pressRating" id="pressRating" <?php if ($movie['pressRating']) echo 'value="'.$movie['pressRating'].'"';?>/>
			</div>
		</div>
		<div class="line">
			<div class="left"><label for="userRating">Note des utilisateurs</label></div><div class="right">
				<input type="text" name="userRating" id="userRating" <?php if ($movie['userRating']) echo 'value="'.$movie['userRating'].'"';?>/>
			</div>
		</div>
		<div class="line">
			<div class="left"><label for="synopsis">Synopsis</label></div><div class="right">
				<textarea name="synopsis" id="synopsis"><?php if (isset($movie['synopsis']) && $movie['synopsis']) echo $movie['synopsis'];?></textarea>
			</div>
		</div>
		<div class="line">
			<div class="left"><label for="poster">Poster</label></div><div class="right">
				<input type="text" name="poster" id="poster" <?php if ($movie['poster']) echo 'value="'.$movie['poster'].'"';?>/>
			</div>
		</div>
		<div class="submit">
			<input type="submit" value="Enregistrer" />
		</div>
	</form>
</div><div id="admin-form-view">
	<div class="poster"><?php if ($movie['poster']) echo '<img src="'.$movie['poster'].'" />';?></div><div class="infos">
		<div class="line"><div class="label">Titre</div><div class="info title"><?php if ($movie['title']) echo $movie['title'];?></div></div>
		<div class="line"><div class="label">Titre original</div><div class="info originalTitle"><?php if ($movie['originalTitle']) echo $movie['originalTitle'];?></div></div>
		<div class="line"><div class="label">Date de sortie</div><div class="info releaseDate"><?php if ($movie['releaseDate']) echo $movie['releaseDate'];?></div></div>
		<div class="line"><div class="label">Réalisteurs</div><div class="info directors"><?php if (isset($movie['directors']) && $movie['directors']) echo $movie['directors'];?></div></div>
		<div class="line"><div class="label">Acteurs</div><div class="info actors"><?php if (isset($movie['actors']) && $movie['actors']) echo $movie['actors'];?></div></div>
		<div class="line"><div class="label">Genres</div><div class="info genres"><?php if (isset($movie['genres']) && $movie['genres']) echo $movie['genres'];?></div></div>
		<div class="line">
			<div class="label">Note de la presse</div><div class="info pressRating">
				<?php
				if ($movie['pressRating']) {
					echo '<div class="rating"><div class="rate" style="width:'.($movie['pressRating'] * 20).'%;"></div></div><span>('.$movie['pressRating'].')</span>';
				}
				?>
			</div>
		</div>
		<div class="line">
			<div class="label">Note des utilisateurs</div><div class="info userRating">
				<?php
				if ($movie['userRating']) {
					echo '<div class="rating"><div class="rate" style="width:'.($movie['userRating'] * 20).'%;"></div></div><span>('.$movie['userRating'].')</span>';
				}
				?>
			</div>
		</div>
	</div>
	<div class="synopsis"><?php if (isset($movie['synopsis']) && $movie['synopsis']) echo $movie['synopsis'];?></div>
</div>


<script>
	var allocineId = null; //<?php echo isset($_GET['allocineId']) ? $_GET['allocineId'] : 'null';?>;

	$(document).ready(function() {
		initEditMovie();
	});
</script>