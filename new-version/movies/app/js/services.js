'use strict';

/* Services */

var moviesServices = angular.module('moviesServices', ['ngResource']);

moviesServices.factory('Movie', ['$resource',
  function($resource){
    return $resource('php/api/api.php', {}, {
      query: {method:'GET', params:{action:'list-movies', from:0, nbr:50}, isArray:true}
    });
  }]);
