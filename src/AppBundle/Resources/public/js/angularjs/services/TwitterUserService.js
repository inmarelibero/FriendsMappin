/**************************************************************************************************************
 * TwitterUserService
 *************************************************************************************************************/
app.service('TwitterUserService', function($http, $q) {

    /**
     * Retrieve a Twitter user profile info
     *
     * @param username
     * @returns {*}
     */
    this.getUserProfile = function(username) {
        return $http.get(Routing.generate('get_profile_show', {
            'username': username
        }))
            .then(
                function (response) {
                    return {
                        user: response.data
                    };
                },
                function (response) {
                    return $q.reject(response.data.error);
                }
            );
    };

    /**
     * Fetch the (paginated) list of friends or followers
     *
     * @param username
     * @param cursor
     * @param type_of_users
     */
    this.fetchTwitterUsers = function(username, type_of_users, cursor) {

        // default value for the paramter "cursor" of Twitter
        if (cursor == null || cursor == undefined) { cursor = '-1'; }

        /**
         * Generate url for users fetching
         */
        var url = Routing.generate('get_users_of_username', {
            'username': username,
            'cursor': cursor,
            'type_of_users': type_of_users
        });

        /**
         * request
         */
        return $http.get(url)
            .then(function (response) {
                return {
                    'next_cursor': response.data.next_cursor,
                    'next_cursor_str': response.data.next_cursor_str,
                    'previous_cursor': response.data.previous_cursor,
                    'previous_cursor_str': response.data.previous_cursor_str,
                    'type_of_users': response.data.type_of_users,
                    'users': response.data.users
                };
            },
            function (response) {
                return $q.reject(response.data.error);
            }
        );
    };
});