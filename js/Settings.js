'use strict';

var
	_ = require('underscore'),

	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js')
;

module.exports = {
	ScheduledFolderName: '',
	PredefinedSchedule: [],

	CurrentScheduledFolderName: '',

	/**
	 * Initializes settings from AppData object sections.
	 *
	 * @param {Object} oAppData Object contains modules settings.
	 */
	init: function (oAppData) {
		var oAppDataSection = oAppData['%ModuleName%'];

		if (!_.isEmpty(oAppDataSection)) {
			this.ScheduledFolderName = Types.pString(oAppDataSection.ScheduledFolderName, this.ScheduledFolderName);
			this.PredefinedSchedule = Types.pArray(oAppDataSection.PredefinedSchedule, this.PredefinedSchedule);
		}
	},

	setCurrentScheduledFolder: function (sCurrentScheduledFolderName) {
		this.CurrentScheduledFolderName = sCurrentScheduledFolderName;
	}
};
