/**************************************************************************************************************
 * OperationService
 *************************************************************************************************************/
app.service('OperationService', function($rootScope, ngProgress, usSpinnerService) {

    /**
     * Set a message
     *
     * @param message
     */
    this.setOperationMessage = function(message) {
        $rootScope.operation_in_progress = message;
    };

    /**
     * Show a message and, optionally, the spinner and the progress bar
     *
     * @param options
     */
    this.showOperationMessage = function(options) {
        var instance = this;

        options = _.extend({
            'show_spinner':         false,
            'message':              'processing...',
            'progress':             null,
            'close_after_timeout':  null,
            'message_type':         'info'                        // message_type can be 'info|error'
        }, options);

        // handle spinner
        if (options.show_spinner == true) {
            if (options.progress == 0) {
                usSpinnerService.spin('spinner-operation');
            } else if (options.progress == 100) {
                usSpinnerService.stop('spinner-operation');
            }
        } else {
            usSpinnerService.stop('spinner-operation');
        }

        $rootScope.show_spinner = options.show_spinner;

        // handle operation message
        this.setOperationMessage(options.message);

        // handle progress
        if (options.progress != null) {
            ngProgress.set(options.progress);

            if (options.progress == 100) {
                ngProgress.complete();
            }
        }

        // handle cole on timeout
        if (_.isNumber(options.close_after_timeout)) {
            setTimeout(function() {
                instance.setOperationMessage('');
            }, options.close_after_timeout);
        }

        // handle type
        $rootScope.operation_message_type = options.message_type;
    };

});
