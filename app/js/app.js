'use strict';

/* App Module */

var EygleMoviesApp = angular.module('EygleMoviesApp', [
  'ngRoute',

  'moviesControllers',
  'moviesFilters'
]);

EygleMoviesApp.config(['$routeProvider',
  function($routeProvider) {
    $routeProvider.
      when('/movies', {
        templateUrl: 'partials/movie-list.html',
        controller: 'moviesListCtrl'
      }).
      when('/movies/:movieId', {
        templateUrl: 'partials/movie-detail.html',
        controller: 'MovieDetailCtrl'
      }).
      when('/admin/', {
        templateUrl: 'partials/panel-admin.html',
        controller: 'panelAdminCtrl'
      }).
        when('/admin/validate', {
          templateUrl: 'partials/admin-to-validate-list.html',
          controller: 'adminValidateListCtrl'
        }).
      when('/admin/doubles', {
        templateUrl: 'partials/admin-doubles-list.html',
        controller: 'adminDoublesListCtrl'
      }).
      when('/admin/doubles/:movieId', {
        templateUrl: 'partials/admin-doubles-details.html',
        controller: 'adminDoublesDetailCtrl'
      }).
      when('/admin/multi', {
        templateUrl: 'partials/admin-multi-list.html',
        controller: 'adminMultiListCtrl'
      }).
      when('/admin/uncomplete', {
        templateUrl: 'partials/admin-uncomplete-list.html',
        controller: 'adminUncompleteListCtrl'
      }).
      when('/admin/edit/:movieId', {
        templateUrl: 'partials/admin-edit-movie.html',
        controller: 'adminEditMovieCtrl'
      }).
      when('/admin/trash', {
        templateUrl: 'partials/admin-trash.html',
        controller: 'adminTrashCtrl'
      }).
      otherwise({
        redirectTo: '/movies'
      });
  }]);
