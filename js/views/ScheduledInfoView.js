'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),

	TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js'),

	AlertPopup = require('%PathToCoreWebclientModule%/js/popups/AlertPopup.js'),
	App = require('%PathToCoreWebclientModule%/js/App.js'),
	ModulesManager = require('%PathToCoreWebclientModule%/js/ModulesManager.js'),
	Popups = require('%PathToCoreWebclientModule%/js/Popups.js'),
	Screens = require('%PathToCoreWebclientModule%/js/Screens.js'),

	ComposeUtils = require('modules/MailWebclient/js/utils/Compose.js'),

	ConfirmAnotherMessageComposedPopup = require('modules/%ModuleName%/js/popups/ConfirmAnotherMessageComposedPopup.js'),
	Schedule = require('modules/%ModuleName%/js/utils/Schedule.js'),

	Settings = require('modules/%ModuleName%/js/Settings.js')
;

function CScheduledInfoView() {
	this.iAccountId = 0;
	this.sFolderFullName = '';
	this.sMessageUid = '';

	this.scheduledText = ko.observable('');
	this.visible = ko.observable(false);
	this.disableAllSendTools = ko.computed(function () {
		return this.visible();
	}, this);

	this.oMessagePane = null;

	this.bWaitDraftSaving = false;
	App.subscribeEvent('MailWebclient::ComposeMessageLoaded', function (aParams) {
		if (this.iAccountId === aParams.AccountId && this.sFolderFullName === aParams.FolderFullName && this.sMessageUid === aParams.MessageUid) {
			this.bWaitDraftSaving = true;
			aParams.Compose.executeSaveCommand();
		}
	}.bind(this));
	App.subscribeEvent('ReceiveAjaxResponse::after', function (oParams) {
		if (this.bWaitDraftSaving && oParams.Request.Module === 'Mail' && oParams.Request.Method === 'SaveMessage')
		{
			this.bWaitDraftSaving = false;
			ModulesManager.run('MailWebclient', 'deleteMessages', [this.iAccountId, this.sFolderFullName, [this.sMessageUid]]);
			Screens.showReport(TextUtils.i18n('%MODULENAME%/REPORT_SENDING_CANCELED'));
		}
	}.bind(this));
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
	this.bWaitDraftSaving = false;
	this.iAccountId = 0;
	this.sFolderFullName = '';
	this.sMessageUid = '';
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
			this.iAccountId = oMessageProps.iAccountId;
			this.sFolderFullName = oMessageProps.sFolderFullName;
			this.sMessageUid = oMessageProps.sMessageUid;
			this.scheduledText(Schedule.getScheduledAtText(oSchedule.ScheduleTimestamp));
			this.visible(true);
		}
	}
};

CScheduledInfoView.prototype.cancelSending = function () {
	if (this.iAccountId !== 0) {
		var oCompose = Popups.getOpenedMinimizedPopup('MailWebclient_ComposePopup');
		if (oCompose && oCompose.hasUnsavedChanges())
		{
			oCompose.maximize();
			oCompose.disableAutosave(true);
			Popups.showPopup(ConfirmAnotherMessageComposedPopup, [function (sAnswer) {
				switch (sAnswer)
				{
					case Enums.AnotherMessageComposedAnswer.Discard:
						oCompose.commit();
						ComposeUtils.composeMessageFromDrafts(this.iAccountId, this.sFolderFullName, this.sMessageUid);
						break;
					case Enums.AnotherMessageComposedAnswer.SaveAsDraft:
						if (oCompose.hasUnsavedChanges())
						{
							oCompose.executeSave(true, false);
						}
						ComposeUtils.composeMessageFromDrafts(this.iAccountId, this.sFolderFullName, this.sMessageUid);
						break;
					case Enums.AnotherMessageComposedAnswer.Cancel:
						Screens.showLoading(TextUtils.i18n('%MODULENAME%/ERROR_SENDING_CANCELED'));
						setTimeout(function () {
							Screens.hideLoading();
						}, 10000);
						break;
				}
				oCompose.disableAutosave(false);
			}.bind(this)]);
		} else {
			ComposeUtils.composeMessageFromDrafts(this.iAccountId, this.sFolderFullName, this.sMessageUid);
		}
	}
};

module.exports = new CScheduledInfoView();
