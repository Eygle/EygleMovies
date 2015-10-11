'use strict';

/* Filters */

angular.module('moviesFilters', []).filter('checkmark', function() {
  return function(input) {
    return input ? '\u2713' : '\u2718';
  };
});

angular.module('moviesFilters', []).filter("sanitize", ['$sce', function($sce) {
  return function(htmlCode){
    return $sce.trustAsHtml(htmlCode);
  }
}]);
