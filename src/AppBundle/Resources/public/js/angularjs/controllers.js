/*******************************************************************************************************
 * MappController
 ******************************************************************************************************/
app.controller('MapController', [
    '$rootScope',
    '$scope',
    '$http',
    'growl',
    '$document',
    'TwitterUserService',
    'UserMapService',
    function($rootScope, $scope, $http, growl, $document, TwitterUserService, UserMapService) {

    $scope.Math = Math;

    /**
     * init()
     */
    $scope.init = function() {
        if (toString.call($scope.username) == '[object String]' && $scope.username != '') {
            $scope.startUserMapDisplay($scope.username);
        }
    };

    /**
     * Begin all the stuff to show the map of friends and followers
     *
     * @param username
     */
    $scope.startUserMapDisplay = function(username) {

        TwitterUserService.getUserProfile(username)
            .then(
                function (data) {
                    $scope.UserMapService = UserMapService;

                    UserMapService.startUsersFetching(data.user);
                },
                function(error) {
                    var modal = $('#modal-fatal-error');

                    modal.find('.error').hide();
                    modal.find('.custom-error')
                        .html(
                            'We were unable to retrieve the profile information for user <b>"'+username+'"</b>.' +
                            '<br>' +
                            'Maybe the user does not exists? You can chech his Twitter profile <a href="http://twitter.com/'+username+'" target="_blank">here</a>.' +
                            '<br><br>' +
                            'Server responded: <i>'+error+'</i>' +
                            '<br><br><br>' +
                            '<p class="text-right"><a class="btn btn-sm btn-primary" href="'+ Routing.generate('homepage') +'"><span class="glyphicon glyphicon-search"></span> Search another user</a></p>'
                        )
                        .show();
                    modal.modal('show');
                });

    };

    /*
    $rootScope.$on('UserMapService:fetchUsers:end', function() {

    });
    */

    /**
     * Handle click on user profile image in right panel
     *
     * @param user
     */
    $scope.handleClickOnUser = function(user) {
        UserMapService.centerMapOnUser(user);
    };

    /*******************************************************************************************************
     * On document ready
     ******************************************************************************************************/
    $document.ready(function(){
        $scope.init();
    })
}]);