'use strict';

var
    _ = require('underscore'),

    Types = require('%PathToCoreWebclientModule%/js/utils/Types.js')
;

module.exports = {
    PredefinedSchedule: [],

    /**
     * Initializes settings from AppData object sections.
     *
     * @param {Object} oAppData Object contains modules settings.
     */
    init: function (oAppData) {
        var oAppDataSection = oAppData['%ModuleName%'];

        if (!_.isEmpty(oAppDataSection)) {
            this.PredefinedSchedule = Types.pArray(oAppDataSection.PredefinedSchedule, this.PredefinedSchedule);
        }
    }
};
