'use strict';

/* Controllers */

var moviesControllers = angular.module('moviesControllers', ['infinite-scroll']);



function showInfo(text, isError) {
    $(".info-pop").addClass(isError ? "label-danger" : "label-success").removeClass(isError ? "label-success" : "label-danger").text(text).show();
    setTimeout(function() {
        $(".info-pop").fadeOut();
    }, 2000);
}

moviesControllers.controller('topController', ['$scope',
    function($scope) {
        $scope.showMenu = true;

        $scope.openLists = function() {

        }
    }]);

moviesControllers.controller('moviesListCtrl', ['$scope', '$http',
    function($scope, $http) {
        $scope.nbrPerPage = 50;
        $scope.movies = [];
        $scope.load = false;
        $scope.totalMovies = 0;

        $scope.genres = [];

        $scope.selectedGenre = null;
        $scope.searchTerm = "";

        $scope.selectGenre = function(genre) {
            for (var i in $scope.genres) {
                $scope.genres[i].active = false;
            }
            if ($scope.selectedGenre != genre.id) {
                $scope.selectedGenre = genre.id;
                genre.active = true;
            } else {
                $scope.selectedGenre = null;
            }
            $scope.loadMore(true);
            $scope.getTotalMovies();
            $scope.showMenu = !$scope.showMenu;
        };

        $scope.loadMore = function(clean) {
            var from = $scope.movies.length;
            if (clean) {
                from = 0;
            } else {
                if ($scope.load) return;
            }
            $scope.load = true;

            var opts = ['from='+from, 'nbr='+$scope.nbrPerPage];
            if ($scope.searchTerm != null)
                opts.push('search=' + $scope.searchTerm);
            if ($scope.selectedGenre != null)
                opts.push('genre=' + $scope.selectedGenre);

            $http.get('php/api/api.php?action=list-movies&' + opts.join('&'))
                .success(function(data) {
                    if (clean)
                        $scope.movies = [];
                    for (var i in data) {
                        $scope.movies.push(data[i]);
                    }
                }).then(function() {
                    $scope.load = false;
                });
        };

        $scope.getTotalMovies = function() {
            var opts = [];
            if ($scope.searchTerm != null)
                opts.push('&search=' + $scope.searchTerm);
            if ($scope.selectedGenre != null)
                opts.push('&genre=' + $scope.selectedGenre);
            $http.get('php/api/api.php?action=get-total-movies' + opts.join(""))
                .success(function(data) {
                    $scope.totalMovies = parseInt(data);
                });
        };

        $http.get('php/api/api.php?action=list-genres')
            .success(function(data) {
                for (var i in data) {
                    $scope.genres.push(data[i]);
                }
            });
        $scope.getTotalMovies();
    }]);

moviesControllers.controller('MovieDetailCtrl', ['$scope', '$routeParams', '$http', '$window',
    function($scope, $routeParams, $http, $window) {
        $scope.months = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Décembre"];

        $scope.movie = {};

        $http.get('php/api/api.php?action=get-movie&id=' + $routeParams.movieId)
            .success(function(data) {
                $scope.movie = data;
                $scope.movie.date = new Date(data["releaseDate"]);
                $scope.movie.pressRating = parseFloat(data["pressRating"]);
                $scope.movie.userRating = parseFloat(data["userRating"]);
                $scope.movie.image = data["localPoster"] != null ? "posters/" + data["localPoster"] : data["poster"];
            });
        $scope.delete = function(movie) {
            console.log("delete");
            var title = movie.title ? movie.title : (movie.originalTitle ? movie.originalTitle : movie.file);
            if (confirm("Voulez vous supprimer " + title + " ?")) {
                $.post('php/api/api.php', {action:'delete-movie', id: movie.id}, function() {
                    showInfo("Success", false);
                    $window.history.back();
                }).fail(function() {
                    showInfo("Error", true);
                });
            }
        };
    }]);

var adminMenu = [{title:'Doublons', action:'doubles'}, {title:'Multi résultats', action:'multi'}, {title:'Films incomplets', action:'uncomplete'}, {title:'Corbeille', action:'trash'}];
var unActivateMenu = function() {
    for (var i in adminMenu) {
        adminMenu[i].active = false;
    }
};

var getAdminTotal = function($http, $scope) {
    $http.get('php/api/api.php?action=get-admin-total').success(function(data) {
        $scope.adminTotal = data;
    });
};

moviesControllers.controller('panelAdminCtrl', ['$scope', '$http', '$location',
    function($scope, $http, $location) {
        $scope.menuTotal = null;
        $scope.menu = adminMenu;
        unActivateMenu();
        getAdminTotal($http, $scope);
    }]);

moviesControllers.controller('adminDoublesListCtrl', ['$scope', '$http',
    function($scope, $http) {
        $scope.menuTotal = null;
        $scope.menu = adminMenu;
        unActivateMenu();
        getAdminTotal($http, $scope);
        $scope.menu[0].active = true;
        $scope.movies = [];

        $http.get('php/api/api.php?action=get-doubles').success(function(data) {
            $scope.movies = data;
        });
    }]);

moviesControllers.controller('adminDoublesDetailCtrl', ['$scope', '$http', '$routeParams',
    function($scope, $http, $routeParams) {
        $scope.months = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Décembre"];
        $scope.movies = [];

        $scope.load = function () {
            $http.get('php/api/api.php?action=get-doubles-id&id=' + $routeParams.movieId).success(function(data) {
                $scope.movies = data;

                for (var i in data) {
                    data[i].releaseDate = new Date(data[i].releaseDate);
                    data[i].userRating = parseFloat(data[i].userRating);
                    data[i].pressRating = parseFloat(data[i].pressRating);
                }
            });
        };

        $scope.load();

        $scope.delete = function(movie) {
            if (confirm("Supprimer " + movie.file + " ?")) {
                $.post('php/api/api.php', {action:'delete-movie', id:movie.id}, function() {
                    $scope.movies = [];
                    $scope.load();
                    showInfo("Success", false);
                }).fail(function() {
                    showInfo("Error", true);
                });
            }
        };
    }]);

moviesControllers.controller('adminMultiListCtrl', ['$scope', '$http', '$routeParams', '$location',
    function($scope, $http, $routeParams, $location) {
        $scope.menuTotal = null;
        $scope.menu = adminMenu;
        unActivateMenu();
        getAdminTotal($http, $scope);
        $scope.menu[1].active = true;

        $scope.movies = [];

        $scope.load = function() {
            $http.get('php/api/api.php?action=get-multi').success(function(data) {
                $scope.movies = data;
            });
        };
        $scope.load();

        $scope.chose = function(movie, choice) {
            if (confirm('Choisir le film ' + choice.title + " pour le fichier " + movie.file + " ?")) {
                $.post('php/api/api.php', {action:'choose-multi-item', 'movie-id': movie.id, 'choice-id': choice.id}, function() {
                    $scope.load();
                    showInfo("Success", false);
                }).fail(function() {
                    showInfo("Error", true);
                });
            }
        };

        $scope.chooseNone = function(movie) {
            if (confirm('Aucun de ces choix n\'est le bon ?')) {
                $.post('php/api/api.php', {action:'delete-mult', 'movie-id': movie.id}, function() {
                    console.log('redirect');
                    $location.path('/admin/edit/' + movie.id);
                    if(!$scope.$$phase) $scope.$apply();
                    showInfo("Success", false);
                }).fail(function() {
                    showInfo("Error", true);
                });
            }
        };

        $scope.delete = function(movie) {
            if (confirm('Supprimer ' + movie.file + " ?")) {
                $.post('php/api/api.php', {action:'delete-mult', 'movie-id': movie.id, 'remove-movie': true}, function() {
                    $scope.load();
                    showInfo("Success", false);
                }).fail(function() {
                    showInfo("Error", true);
                });
            }
        };
    }]);

moviesControllers.controller('adminUncompleteListCtrl', ['$scope', '$http',
    function($scope, $http) {
        $scope.menuTotal = null;
        $scope.menu = adminMenu;
        unActivateMenu();
        getAdminTotal($http, $scope);
        $scope.menu[2].active = true;

        $scope.movies = [];

        $scope.load = function() {
            $http.get('php/api/api.php?action=get-uncomplete').success(function(data) {
                $scope.movies = data;
            });
        };
        $scope.load();
    }]);

moviesControllers.controller('adminEditMovieCtrl', ['$scope', '$http', '$routeParams', '$window',
    function($scope, $http, $routeParams, $window) {
        $scope.menuTotal = null;
        $scope.menu = adminMenu;
        unActivateMenu();
        getAdminTotal($http, $scope);
        $scope.menu[2].active = true;

        $scope.months = ["Janvier", "Février", "Mars", "Avril", "Mai", "Juin", "Juillet", "Août", "Septembre", "Décembre"];
        $scope.movie = {};

        $scope.load = function () {
            $http.get('php/api/api.php?action=get-movie&id=' + $routeParams.movieId).success(function(data) {
                $scope.movie = data;
                data.userRating = parseFloat(data.userRating);
                data.pressRating = parseFloat(data.pressRating);
            });
        };

        $scope.load();

        $scope.getAllocineInfos = function(allocineId) {

            $http.get('php/api/api.php?action=get-movie-from-allocine&allocineId=' + allocineId).success(function(data) {
                console.log(data);
                $scope.movie.title = data["title"];
                $scope.movie.originalTitle = data["originalTitle"];
                $scope.movie.releaseDate = data["releaseDate"];
                $scope.movie.directors = data["directors"];
                $scope.movie.actors = data["actors"];
                $scope.movie.genres = data["genres"];
                $scope.movie.synopsis = data["synopsis"];
                $scope.movie.userRating = parseFloat(data["userRating"]);
                $scope.movie.pressRating = parseFloat(data["pressRating"]);
                $scope.movie.poster = data["poster"];
            });
        };

        $scope.save = function() {
            $scope.movie["action"] = "edit-movie";
            $.post('php/api/api.php', $scope.movie, function() {
                $window.history.back();
                showInfo("Success", false);
            }).fail(function() {
                showInfo("Error", true);
            });
        };

        $scope.saveAndReloadImage = function() {
            $scope.movie["action"] = "edit-movie-reload-image";
            $.post('php/api/api.php', $scope.movie, function() {
                $window.history.back();
                showInfo("Success", false);
            }).fail(function() {
                showInfo("Error", true);
            });
        }
    }]);

moviesControllers.controller('adminTrashCtrl', ['$scope', '$http',
    function($scope, $http) {
        $scope.menuTotal = null;
        $scope.menu = adminMenu;
        unActivateMenu();
        getAdminTotal($http, $scope);
        $scope.menu[3].active = true;

        $scope.nbrPerPage = 50;
        $scope.movies = [];
        $scope.load = false;
        $scope.totalMovies = 0;

        $scope.loadMore = function() {
            if ($scope.load) return;

            var from = $scope.movies.length;

            $scope.load = true;

            var opts = ['from='+from, 'nbr='+$scope.nbrPerPage];

            $http.get('php/api/api.php?action=get-trash&' + opts.join('&'))
                .success(function(data) {
                    for (var i in data) {
                        $scope.movies.push(data[i]);
                    }
                }).then(function() {
                    $scope.load = false;
                });
        };

        $scope.getTotal = function() {
            $http.get('php/api/api.php?action=get-total-trash')
                .success(function(data) {
                    $scope.totalMovies = parseInt(data);
                });
        };
        $scope.getTotal();
    }]);