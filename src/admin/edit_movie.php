<?php

require_once('db/DBMovies.class.php');
$db  = new DBMovies();

if ($_GET['previousAction'] == 'multiple')
	$movie = $db->getMovieMult($_GET['movieId']);
?>

<h2><?php echo $movie['file']; ?></h2>

<?php
if ($movie['allocineId']) {
	echo '<h3><a href="http://www.allocine.fr/film/fichefilm_gen_cfilm='.$movie['allocineId'].'.html" target="_blank">http://www.allocine.fr/film/fichefilm_gen_cfilm='.$movie['allocineId'].'.html</a></h3>';
}
?>

<div id="admin-form">
	<form method="post">
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
				<input type="text" name="directors" id="directors" <?php if ($movie['directors']) echo 'value="'.$movie['directors'].'"';?>/>
			</div>
		</div>
		<div class="line">
			<div class="left"><label for="actors">Acteurs</label></div><div class="right">
				<input type="text" name="actors" id="actors" <?php if ($movie['actors']) echo 'value="'.$movie['actors'].'"';?>/>
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
	</form>
</div><div id="admin-form-view">
	<div class="poster"><?php if ($movie['poster']) echo '<img src="'.$movie['poster'].'" />';?></div><div class="infos">
		<div class="line"><div class="label">Titre</div><div class="title"><?php if ($movie['title']) echo $movie['title'];?></div></div>
		<div class="line"><div class="label">Titre original</div><div class="originalTitle"><?php if ($movie['originalTitle']) echo $movie['originalTitle'];?></div></div>
		<div class="line"><div class="label">Date de sortie</div><div class="releaseDate"><?php if ($movie['releaseDate']) echo $movie['releaseDate'];?></div></div>
		<div class="line"><div class="label">Réalisteurs</div><div class="directors"><?php if ($movie['directors']) echo $movie['directors'];?></div></div>
		<div class="line"><div class="label">Acteurs</div><div class="actors"><?php if ($movie['actors']) echo $movie['actors'];?></div></div>
		<div class="line">
			<div class="label">Note de la presse</div><div class="pressRating">
				<?php
				if ($movie['pressRating']) {
					echo '<div class="rating"><div class="rate" style="width:'.($movie['pressRating'] * 20).'%;"></div></div><span>('.$movie['pressRating'].')</span>';
				}
				?>
			</div>
		</div>
		<div class="line">
			<div class="label">Note des utilisateurs</div><div class="userRating">
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
	var allocineId = <?php echo isset($_GET['allocineId']) ? $_GET['allocineId'] : 'null';?>;

	$(document).ready(function() {
		initEditMovie();
	});
</script>