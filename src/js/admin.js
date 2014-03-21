var elems = ['title', 'originalTitle', 'releaseDate', 'directors', 'actors', 'genres', 'pressRating', 'userRating', 'poster', 'synopsis', 'allocineId'];

function initEditMovie() {

	for (var i in elems) {
		$('#' + elems[i]).change(function() {
			if (this.id == 'pressRating' || this.id == 'userRating') {
				var rating = $('<div>').attr('class', 'rating').append($('<div>').attr({'class': 'rate', 'style': 'width:' + (this.value * 20) + '%'}));
				$('.' + this.id).html(rating).append($('<span>').text('(' + this.value + ')'));
			} else if (this.id == 'poster') {
				$('.' + this.id).html('<img src="' + this.value + ' " />');
			}  else {
				$('.' + this.id).html(this.value);
			}
		});
	}

	if (allocineId) {
		searchAllocineInfos(allocineId);
	}

	$('#searchAllocineId').click(function() {
		searchAllocineInfos($('#search-allocineId').val());
	});
}

function searchAllocineInfos(allocineId) {
$.getJSON('admin/allocine_infos.php', {'allocineId': allocineId}, function(data) {
		for (var i in elems) {
			if (data[elems[i]] && !$('#' + elems[i]).val()) {
				$('#' + elems[i]).val(data[elems[i]]);
				if (elems[i] == 'pressRating' || elems[i] == 'userRating') {
					var rating = $('<div>').attr('class', 'rating').append($('<div>').attr({'class': 'rate', 'style': 'width:' + (data[elems[i]] * 20) + '%'}));
					$('.' + elems[i]).append(rating).append($('<span>').text('(' + data[elems[i]] + ')'));
				} else if (elems[i] == 'poster') {
					$('.' + elems[i]).html('<img src="' + data[elems[i]] + ' " />');
				} else {
					$('.' + elems[i]).html(data[elems[i]]);
				}
			}
		}
	});
}