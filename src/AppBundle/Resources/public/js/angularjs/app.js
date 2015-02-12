/*******************************************************************************************************
 * MAIN APP
 ******************************************************************************************************/
var app = angular.module('friendsMappinApp', ['ui.bootstrap', 'ngProgress', 'angularSpinner', 'mobile-angular-ui'])
    .config(function($interpolateProvider){
        $interpolateProvider.startSymbol('{[{').endSymbol('}]}');
    });

/*******************************************************************************************************
 * DIRECTIVES
 ******************************************************************************************************/
app.directive('headBar', function() {
    return {
        restrict: 'E',
        templateUrl: 'head_bar.html',
        transclude: true,
        scope: true,
        controller: function($scope, UserMapService) {
            $scope.UserMapService = UserMapService;
        }
    };
})
;

/**
 * Let's you use underscore as a service from a controller.
 * Got the idea from: http://stackoverflow.com/questions/14968297/use-underscore-inside-controllers
 * @author: Andres Aguilar https://github.com/andresesfm
 */
angular.module('underscore', []).factory('_', function() {
    return window._; // assumes underscore has already been loaded on the page
});
