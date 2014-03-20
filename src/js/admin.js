function initEditMovie() {
	var elems = ['title', 'originalTitle', 'releaseDate', 'directors', 'actors', 'pressRating', 'userRating', 'poster', 'synopsis'];

	for (var i in elems) {
		$('#' + elems[i]).change(function() {
			$('.' + this.id).html(this.value);
		});
	}

	if (allocineId) {
		$.getJSON('admin/allocine_infos.php', {'allocineId': allocineId}, function(data) {
			for (var i in elems) {
				if (data[elems[i]] && !$('#' + elems[i]).val()) {
					$('#' + elems[i]).val(data[elems[i]]);
					$('.' + elems[i]).html(data[elems[i]]);
				}
			}
		});
	}
}