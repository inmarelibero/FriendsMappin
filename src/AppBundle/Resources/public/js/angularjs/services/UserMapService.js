/**************************************************************************************************************
 * UserMapService
 *
 * Create a map with the friends and followers of a user
 *************************************************************************************************************/
app.service('UserMapService', function($rootScope, UtilsService, GoogleMapService, OperationService, TwitterUserService) {

    this.user = null;

    this.counters = {
        'fetched_friends':          0,
        'fetched_followers':        0,
        'localized_friends':        0,
        'localized_followers':      0,
        'not_localized_friends':    0,
        'not_localized_followers':  0
    };

    /**
     * associative array of all the fetched users
     *
     * { username: {user} }
     *
     * @type {{}}
     */
    this.fetched_users = {};

    this.fetched_users_organized = {
        'friends': {
            'localized':        {},
            'not_localized':    {}
        },
        'followers': {
            'localized':        {},
            'not_localized':    {}
        }
    };

    this.state_flags = {
        'fetch_friends_finished':   false,
        'fetch_followers_finished': false,
        'fetch_all_users_finished': false
    };

    /**************************************************************************************************************
     * USER
     *************************************************************************************************************/
    /**
     * Set user
     *
     * @param user
     */
    this.setUser = function(user) {
        this.user = user;

        return this;
    };

    /**
     * Get user
     *
     * @returns {*}
     */
    this.getUser = function() {
        return this.user;
    };

    /**************************************************************************************************************
     * USERS FETCHING
     *************************************************************************************************************/
    /**
     * Fetches users and throws an event when finished
     *
     * @param user
     * @returns {*}
     */
    this.startUsersFetching = function(user) {
        var instance = this;

        instance.setUser(user);

        OperationService.showOperationMessage({
            show_spinner: true,
            message: 'Preparing your request...',
            progress: 0
        });

        // fetch friends and followers
        instance.fetchUsers(user, 'friends');
        instance.fetchUsers(user, 'followers');

        return this;
    };

    /**
     *
     * @param username
     * @param type_of_users
     * @param cursor
     */
    this.fetchUsers = function(user, type_of_users, cursor) {
        var instance = this;

        var username = user.screen_name;

        TwitterUserService.fetchTwitterUsers(username, type_of_users, cursor)
            .then(
                function(data) {
                    // process fetched users
                    instance.handleFetchedUsers(data.users, data.type_of_users);

                    // update operation message
                    OperationService.showOperationMessage({
                        message: instance.counters.fetched_friends +'/' + instance.user.friends_count + ' friends and '+ instance.counters.fetched_followers +'/' + instance.user.followers_count + ' followers so far, and counting...',
                        progress: instance.getNumFetchedUsersSoFar() / instance.getTotalUsers() * 100,
                        show_spinner: true
                    });

                    // keep on fetching users of terminate
                    if (_.isNumber(data.next_cursor) && data.next_cursor != 0) {
                        instance.fetchUsers(user, data.type_of_users, data.next_cursor);
                    } else {
                        // users fetching finished
                        instance.setStateFlag('fetch_'+type_of_users+'_finished', true);
                        instance.completeFetchUsersIfNedeed();
                    }
                },
                function(error) {
                    OperationService.showOperationMessage({
                        message: 'We were unable to complete the request! Server responded: "'+error+'".',
                        message_type: 'error'
                    });

                    var modal = $('#modal-fatal-error');

                    modal.find('.error').hide();
                    modal.find('.default-error').show();
                    modal.modal('show');
                });
    };

    /**
     *
     */
    this.completeFetchUsersIfNedeed = function() {
        var instance = this;

        // return if not all type of users have been fetched
        if (instance.getStateFlag('fetch_friends_finished') == false || instance.getStateFlag('fetch_followers_finished') == false) {
            return;
        }

        instance.setStateFlag('fetch_all_users_finished', true);

        $rootScope.$emit('UserMapService:fetchUsers:end');

        OperationService.showOperationMessage({
            message: 'Done!',
            progress: 100,
            show_spinner: false,
            close_after_timeout: 3000
        });
    };

    /**
     * Handle a batch of fetched user from Twitter
     */
    this.handleFetchedUsers = function(users, type_of_users) {
        var instance = this;

        /**
         * increment counter
         */
        angular.forEach(users, function(user, key) {
            instance.addUserToFetchedUsers(user, type_of_users);

            if (GoogleMapService.drawUserOnMap(user, type_of_users) == null) {

                instance.fetched_users_organized[type_of_users]['not_localized'][user.screen_name] = instance.getUserFromFetchedUsers(user.screen_name);
                instance.incrementCounter('not_localized_'+type_of_users);

            } else {

                instance.fetched_users_organized[type_of_users]['localized'][user.screen_name] = instance.getUserFromFetchedUsers(user.screen_name);
                instance.incrementCounter('localized_'+type_of_users);

            }
        });
    };

    /**
     * Return a user object from the already fetched ones
     *
     * @param username
     * @returns {*}
     */
    this.getUserFromFetchedUsers = function(username) {
        var instance = this;

        return instance.fetched_users[username];
    };

    /**
     * Add a user to the list of fetched ones
     *
     * @param user
     */
    this.addUserToFetchedUsers = function(user, type_of_users) {
        var instance = this;

        instance.fetched_users[user.screen_name] = user;

        instance.incrementCounter('fetched_'+type_of_users);
    };

    /**
     * Return the total number of fetched users so far
     */
    this.getNumFetchedUsersSoFar = function() {
        var instance = this;

        return instance.counters.fetched_friends + instance.counters.fetched_followers;
    };

    /**
     *
     */
    this.getTotalUsers = function() {
        var instance = this;

        return instance.getUser().friends_count + instance.getUser().followers_count;
    };

    /**************************************************************************************************************
     * COUNTERS
     *************************************************************************************************************/
    /**
     * Increment a counter
     *
     * @param counter
     */
    this.incrementCounter = function(counter) {
        var instance = this;

        instance.counters[counter]++;
    };

    /**************************************************************************************************************
     * STATES FLAG
     *************************************************************************************************************/
    /**
     *
     * @param flag
     * @param value
     */
    this.setStateFlag = function(flag, value) {
        var instance = this;

        instance.state_flags[flag] = value;
    };

    this.getStateFlag = function(flag) {
        var instance = this;

        return instance.state_flags[flag];
    };

    /**************************************************************************************************************
     * MAP
     *************************************************************************************************************/
    this.centerMapOnUser = function(user) {
        GoogleMapService.centerMapOnLatLon(user.latitude, user.longitude);

        //$scope.showSingleOperationMessage('Map centered on <a href="http://twitter.com/'+user.screen_name+'">@' + user.screen_name + '</a> at '+user.latitude + ',' + user.longitude);
        OperationService.showOperationMessage({
            message: 'Centered on @' + user.screen_name + ' at '+user.latitude + ',' + user.longitude
        });
    };

    /**
     * Toggle Markers
     */
    this.toggleMarkers = function() {
        GoogleMapService.toggleMarkers();
    };

    /**
     * Toggle Heatmap
     */
    this.toggleHeatmap = function() {
        GoogleMapService.toggleHeatmap();
    }
});
