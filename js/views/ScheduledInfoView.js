'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),

	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js'),

	Schedule = require('modules/%ModuleName%/js/utils/Schedule.js'),

	Settings = require('modules/%ModuleName%/js/Settings.js')
;

function CScheduledInfoView() {
	this.visible = ko.observable(false);
	this.scheduledText = ko.observable('');

	this.oMessagePane = null;
}

CScheduledInfoView.prototype.ViewTemplate = '%ModuleName%_ScheduledInfoView';

/**
 * Receives properties of the message that is displayed in the message pane.
 * It is called every time the message is changing in the message pane.
 * Receives null if there is no message in the pane.
 *
 * @param {Object|null} oMessageProps Information about message in message pane.
 * @param {string} oMessageProps.sFolderFullName
 * @param {array} oMessageProps.aExtend
 * @param {number} oMessageProps.aExtend[].ScheduleTimestamp
 */
CScheduledInfoView.prototype.doAfterPopulatingMessage = function (oMessageProps) {
	this.scheduledText('');
	this.visible(false);
	if (oMessageProps && oMessageProps.sFolderFullName === Settings.CurrentScheduledFolderName) {
		var
			aExtend = Types.pArray(oMessageProps.aExtend),
			oSchedule = _.find(aExtend, function (oExtend) {
				return Types.isPositiveNumber(oExtend.ScheduleTimestamp);
			})
		;
		if (oSchedule) {
			this.scheduledText(Schedule.getScheduledAtText(oSchedule.ScheduleTimestamp));
			this.visible(true);
		}
	}
};

CScheduledInfoView.prototype.assignMessagePaneExtInterface = function (oMessagePane) {
	this.oMessagePane = oMessagePane;
	console.log('assignMessagePaneExtInterface', oMessagePane);
};

CScheduledInfoView.prototype.cancelSending = function () {
	console.log('cancelSending');
};

module.exports = new CScheduledInfoView();
