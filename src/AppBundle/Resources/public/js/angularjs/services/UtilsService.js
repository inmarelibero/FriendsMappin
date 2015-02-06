/*******************************************************************************************************
 * UtilsService
 ******************************************************************************************************/
app.service('UtilsService', function() {

    this.rainbow = null;

    this.addElementsToList = function(users, list) {
        var output = list;

        angular.forEach(users, function(user, key) {
            if (output.indexOf(user) < 0) {
                output = output.concat(user);
            }
        });

        return output;
    };

    /**
     * Create a Rainbow object
     *
     * @returns {Rainbow}
     */
    this.createRainbow = function() {
        var rainbow = new Rainbow();
        rainbow.setSpectrum('#FFFF99', 'red');
        rainbow.setNumberRange(0, 100);

        return rainbow;
    };

    /**
     * Get Rainbow object
     *
     * @returns {null|*}
     */
    this.getRainbow = function() {
        var instance = this;

        if (instance.rainbow == null) {
            instance.rainbow = instance.createRainbow();
        }

        return instance.rainbow;
    }

});
