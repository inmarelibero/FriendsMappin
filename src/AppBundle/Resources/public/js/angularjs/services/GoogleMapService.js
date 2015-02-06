/**************************************************************************************************************
 * GoogleMapService
 *
 * wrap google maps functions
 *
 * useful doc:
 *  - http://chariotsolutions.com/blog/post/angularjs-corner-using-promises-q-handle-asynchronous-calls/
 *************************************************************************************************************/
app.service('GoogleMapService', function(UtilsService) {

    this.map = null;
    this.markers = {};

    this.markerClusterer = null;
    this.is_markerclusterer_visible = false;

    this.heatmap = null;
    this.is_heatmap_visible = false;

    this.markers_visible = true;

    /**************************************************************************************************************
     * MAP
     *************************************************************************************************************/
    /**
     * Get map
     *
     * @returns {*}
     */
    this.getMap = function() {
        var instance = this;

        if (instance.map == null) {
            instance.map = instance.createMap();
        }

        return instance.map;
    };

    /**
     * Create map
     *
     * @returns {google.maps.Map}
     */
    this.createMap = function() {
        return new google.maps.Map(document.getElementById('map_canvas'), {
            center: { lat: 48.856614, lng: 2.352222},
            zoom: 3
        });
    };

    /**
     * Set the center on a given map
     */
    this.setCenterOnMap = function(map, position) {
        map.setCenter(position);
        map.setZoom(9);
    };

    /**
     * Center map on coordinates
     *
     * @param lat
     * @param lon
     */
    this.centerMapOnLatLon = function(lat, lon) {
        var instance = this;

        var marker = instance.retrieveMarker(lat, lon);

        if (marker == null) {
            return;
        }

        instance.getMap().setCenter(new google.maps.LatLng(lat, lon));
        instance.getMap().setZoom(9);

        google.maps.event.trigger(marker, 'click');
    };

    /**************************************************************************************************************
     * USERS
     *************************************************************************************************************/
    /**
     * Draws a single user on the map
     *
     * @param user
     * @param type_of_user
     * @returns {null}
     */
    this.drawUserOnMap = function(user, type_of_user) {
        var instance = this;

        if (user.latitude == null || user.longitude == null) {
            return null;
        }

        return instance.addUserToMarker(
            user,
            type_of_user,
            instance.createOrRetrieveMarker(user.latitude, user.longitude)
        );
    };

    /**
     * Add a user to a marker
     *
     * @param user
     * @param type_of_user
     * @param marker
     * @returns {*}
     */
    this.addUserToMarker = function(user, type_of_user, marker) {
        var instance = this;

        // add user the list stored in marker
        marker.users[type_of_user] = UtilsService.addElementsToList([user], marker.users[type_of_user]);

        // update marker "locations" variable
        marker.locations = UtilsService.addElementsToList([user.location], marker.locations);

        var users_count = marker.users.friends.length + marker.users.followers.length;

        // update marker icon
        marker.setIcon(instance.getIconForMarker(users_count));

        // update marker title
        marker.title = users_count + ' users';

        return marker;
    };

    /**************************************************************************************************************
     * MARKERS
     *************************************************************************************************************/
    /**
     * Get all markers
     *
     * @returns {{}|*|markers}
     */
    this.getMarkers = function() {
        return this.markers;
    };

    /**
     * Get a specific marker by coordinates
     *
     * @param lat
     * @param lon
     * @returns {*}
     */
    this.retrieveMarker = function(lat, lon) {
        var instance = this;

        // retrieve marker if already present in list
        var latLonKey = instance.getCoordinatesKeyFromCoordinates(lat, lon);

        var marker = instance.markers[latLonKey];

        if (marker == undefined) {
            return null;
        }

        return marker;
    };

    /**
     * Retrieve a marker for given coordinates, or return one if already existing
     *
     * @param lat
     * @param lon
     * @returns {*}
     */
    this.createOrRetrieveMarker = function(lat, lon) {
        var instance = this;

        var marker = instance.retrieveMarker(lat, lon);

        if (marker == null) {
            marker = instance.createMarker(lat, lon);

            // retrieve marker if already present in list
            var latLonKey = instance.getCoordinatesKeyFromCoordinates(lat, lon);
            instance.markers[latLonKey] = marker;
        }

        return marker;
    };

    /**
     * Create a Marker
     *
     * @param lat
     * @param long
     * @returns {google.maps.Marker}
     */
    this.createMarker = function(lat, long) {
        var instance = this;

        var position = new google.maps.LatLng(lat, long);

        var marker = new google.maps.Marker({
            position:   position,
            //animation:  google.maps.Animation.DROP,
            //draggable:  true,
            map:        instance.getMap()
        });

        // initialize empty "users" property
        marker.users = {
            'friends': [],
            'followers': []
        };

        marker.locations = [];

        // add listener
        instance.addMarkerListener(marker);

        instance.getMarkerClusterer().addMarker(marker);

        return marker;
    };

    /**
     * Calculate the icon for a marker
     *
     * @param number_of_users
     * @returns {string}
     */
    this.getIconForMarker = function(number_of_users) {
        var color = UtilsService.getRainbow().colourAt(number_of_users);
        return 'http://chart.apis.google.com/chart?chst=d_map_pin_letter&chld='+number_of_users+'|' + color + '|000000';
    };

    /**
     * Add the listener to a marker
     *
     * @param marker
     */
    this.addMarkerListener = function(marker) {
        var instance = this;

        marker.addListener('click', function() {
            // close all infowindows
            angular.forEach(instance.getMarkers(), function(marker, key) {
                if (marker.infowindow) {
                    marker.infowindow.close();
                }
            });

            // create the new infowindow
            marker.infowindow = instance.createInfoWindowForMarker(marker);

            // open infowindow
            marker.infowindow.open(instance.getMap(), marker);
        });
    };

    /**
     * Toggle Markers
     */
    this.toggleMarkers = function() {
        var instance = this;

        return (instance.markers_visible == true) ? instance.hideMarkers() : instance.showMarkers();
    };

    /**
     * Show all markers
     */
    this.showMarkers = function() {
        var instance = this;

        angular.forEach(instance.getMarkers(), function(marker, key) {
            marker.setVisible(true);
        });

        instance.showMarkerClusterer();

        instance.markers_visible = true;
    };

    /**
     * Hide all markers
     */
    this.hideMarkers = function() {
        var instance = this;

        angular.forEach(instance.getMarkerClusterer().getMarkers(), function(marker, key) {
            marker.setVisible(false);
        });

        instance.hideMarkerClusterer();

        instance.markers_visible = false;
    };

    /**************************************************************************************************************
     * INFOWINDOW
     *************************************************************************************************************/
    /**
     * Create an infowindow for a marker
     *
     * @param marker
     * @returns {google.maps.InfoWindow}
     */
    this.createInfoWindowForMarker = function(marker) {
        var instance = this;

        var infowindow_content = '';

        var latLonHashKey = instance.getCoordinatesKeyHashFromCoordinates(marker.getPosition().lat(), marker.getPosition().lng());
        infowindow_content += '<div class="location-info-window" id="info-window-'+latLonHashKey+'">';

        infowindow_content += '<p class="location">' + _.first(marker.locations) +'</p><br>';

        angular.forEach(marker.users, function(users, type_of_users) {

            var users_length = users.length;

            if (users_length <= 0) return;

            var uppercase_type_of_users = type_of_users.charAt(0).toUpperCase() + type_of_users.slice(1);

            infowindow_content += '<p>' + uppercase_type_of_users + ' ('+users_length+'):</p>';

            infowindow_content += "<div class='users-wrapper'>";

            angular.forEach(users, function(user, key) {
                infowindow_content += '<span>' +
                '<img src="' + user.profile_image_url + '" width="30" height="30" /> ' +
                '</span>';
            });

            infowindow_content += '</div>';
        });

        infowindow_content += '</div>';

        return new google.maps.InfoWindow({
            content: infowindow_content
        });
    };

    /**************************************************************************************************************
     * MARKERCLUSTERER
     *************************************************************************************************************/
    /**
     * Get MarkerClusterer
     *
     * @returns {*|markerClusterer}
     */
    this.getMarkerClusterer = function() {
        var instance = this;

        if (instance.markerClusterer == null) {
            instance.markerClusterer = instance.createMarkerClusterer();
        }

        return instance.markerClusterer;
    };

    /**
     * Create a MarkerClusterer
     *
     * @param map
     * @returns {Window.MarkerClusterer}
     */
    this.createMarkerClusterer = function() {
        var instance = this;

        return new MarkerClusterer(instance.getMap(), [], {
            gridSize: 40,
            minimumClusterSize: 5
            /*
             calculator: function (markers, numStyles) {
             return {
             text: markers.length,
             index: numStyles
             };
             }
             */
        });
    };

    /**
     * Show all MarkerClusterer
     */
    this.showMarkerClusterer = function() {
        var instance = this;

        var markerClusterer = instance.getMarkerClusterer();

        markerClusterer.map = instance.getMap();
        markerClusterer.resetViewport();
        markerClusterer.redraw();

        instance.is_markerclusterer_visible = true;
    };

    /**
     * Hide MarkerClusterer
     */
    this.hideMarkerClusterer = function() {
        var instance = this;

        instance.is_markerclusterer_visible = false;


        /**
         * @TODO: refactor 'fake-map' id usage
         */
        instance.getMarkerClusterer().map = new google.maps.Map(document.getElementById('fake-map'), {
            center: { lat: 0, lng: 0},
            zoom: 3
        });
        instance.getMarkerClusterer().resetViewport();
        instance.getMarkerClusterer().redraw();
    };


    /**************************************************************************************************************
     * HEATMAP
     *************************************************************************************************************/
    /**
     * Create a HeatMap
     *
     * @returns {google.maps.visualization.HeatmapLayer}
     */
    this.createHeatMap = function() {
        var instance = this;

        var heatmapData = [];

        angular.forEach(instance.getMarkers(), function(marker, key) {
            var users_count = marker.users.friends.length + marker.users.followers.length;

            heatmapData = heatmapData.concat([{
                'location': marker.position,
                'weight': users_count
            }]);
        });

        return new google.maps.visualization.HeatmapLayer({
            data: heatmapData,
            radius: 50
        });
    };

    /**
     * Toggle Heatmap
     */
    this.toggleHeatmap = function() {
        var instance = this;

        return (instance.is_heatmap_visible == true) ? instance.hideHeatMap() : instance.showHeatMap();
    };

    /**
     * Show Heatmap
     */
    this.showHeatMap = function() {
        var instance = this;

        instance.is_heatmap_visible = true;

        // delete current heatmap
        instance.heatmap = null;
        delete instance.heatmap;

        // create and show heatmap
        var heatmap = instance.createHeatMap();
        heatmap.setMap(instance.getMap());

        instance.heatmap = heatmap;
    };

    /**
     * Hide Heatmap
     */
    this.hideHeatMap = function() {
        var instance = this;

        instance.is_heatmap_visible = false;

        // hide heatmap
        if (instance.heatmap) {
            instance.heatmap.setMap(null);
        }
    };

    /**************************************************************************************************************
     * COORDINATES
     *************************************************************************************************************/
    /**
     * Return a string merging two coordinates.
     *
     * eg: "45.234432,-8.234"
     *
     * @param lat
     * @param lon
     * @returns {string}
     */
    this.getCoordinatesKeyFromCoordinates = function(lat, lon) {
        return lat + ',' + lon;
    };

    /**
     * Return a string representing some kind of hashing of two coordinates
     *
     * eg: "452344328234"
     *
     * @param lat
     * @param lng
     * @returns {string}
     */
    this.getCoordinatesKeyHashFromCoordinates = function(lat, lng) {
        var key = (Math.abs(lat) + '' + Math.abs(lng)).replace(/\./g, '');

        return key;
    };

});
