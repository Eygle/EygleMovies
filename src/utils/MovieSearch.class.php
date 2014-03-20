<?php

class MovieSearch {
	private $db = null;

	private $genreFilter = null;
	private $search = null;

	private $page = 1;
	private $resPerPage = 25;

	private $total;

	public function __construct($db, $genres) {
		$this->db = $db;
		if (isset($_GET['page'])) {
			$this->page = intval($_GET['page']);
		}
		if (isset($_GET['search']) && $_GET['search'])
			$this->search = strtolower(utf8_encode($_GET['search']));
		foreach ($genres as $g) {
			if (isset($_GET['genre_'.$g['id']])) {
				if (is_array($this->genreFilter)) {
					$this->genreFilter[] = $g['id'];
				} else {
					$this->genreFilter = array($g['id']);
				}
			}
		}
		if ($this->genreFilter)
			$this->genreFilter = implode(',', $this->genreFilter);
	}

	public function getMovies() {
		$this->total = $this->db->getTotalMovies($this->search, $this->genreFilter, ($this->page - 1) * $this->resPerPage, $this->resPerPage);
		return $this->db->getMovies($this->search, $this->genreFilter, ($this->page - 1) * $this->resPerPage, $this->resPerPage);
	}

	public function generatePagination() {
		$nbrPages = ceil($this->total / $this->resPerPage);
		if ($nbrPages < 2)
			return "";
		$genres = explode(',', $this->genreFilter);
		$search = isset($_GET['search']) ? $_GET['search'] : '';
		$url = "?search=".$search;
		foreach ($genres as $g) {
			$url .= "&genre_$g=on";
		}
		$ret = '<div class="pagination">';
		if ($this->page > 1)
			$ret .= '<div class="page"><a href="'.$url.'&page='.($this->page - 1).'"><</a></div>';
		for ($i = 1; $i <= $nbrPages; ++$i) {
			if ($i == $this->page)
				$ret .= '<div class="page selected">'.$i.'</div>';
			else
				$ret .= '<div class="page"><a href="'.$url.'&page='.$i.'">'.$i.'</a></div>';
		}
		if ($this->page < $nbrPages)
			$ret .= '<div class="page"><a href="'.$url.'&page='.($this->page + 1).'">></a></div>';
		$ret .= '</div>';
		return $ret;
	}

	public function getTotalResults() {
		return $this->total;
	}

	public function getNbrPages() {
		return ceil($this->total / $this->resPerPage);
	}
}

?>