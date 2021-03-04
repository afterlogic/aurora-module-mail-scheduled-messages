'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),

	TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
	Utils = require('%PathToCoreWebclientModule%/js/utils/Common.js'),

	AlertPopup = require('%PathToCoreWebclientModule%/js/popups/AlertPopup.js'),
	Popups = require('%PathToCoreWebclientModule%/js/Popups.js'),

	ScheduleSendingPopup = require('modules/%ModuleName%/js/popups/ScheduleSendingPopup.js')
;

/**
 * @constructor for object that display Sensitivity button on Compose
 */
function CComposeSendButtonView() {
	this.bSendButton = true;
	this.oCompose = null;
	this.disableAutosave = ko.observable(false);
}

CComposeSendButtonView.prototype.ViewTemplate = '%ModuleName%_ComposeSendButtonView';

CComposeSendButtonView.prototype.assignComposeExtInterface = function (oCompose)
{
	this.oCompose = oCompose;
	this.scheduleCommand = Utils.createCommand(this, this.scheduleSending, function () {
		return this.oCompose ? this.oCompose.isEnableSending() && this.oCompose.isEnableSaving() : false;
	}.bind(this));
};

CComposeSendButtonView.prototype.scheduleSending = function () {
	if (_.isFunction(this.oCompose && this.oCompose.getAutoEncryptSignMessage) && this.oCompose.getAutoEncryptSignMessage()) {
		Popups.showPopup(AlertPopup, [TextUtils.i18n('%MODULENAME%/ERROR_AUTOMATIC_ENCRYPTION')]);
	} else if (_.isFunction(this.oCompose && this.oCompose.getRecipientsEmpty) && this.oCompose.getRecipientsEmpty()) {
		Popups.showPopup(AlertPopup, [TextUtils.i18n('%MODULENAME%/ERROR_RECIPIENTS_EMPTY')]);
	} else {
		this.disableAutosave(true);
		Popups.showPopup(ScheduleSendingPopup, [this.oCompose, function () {
			this.disableAutosave(false);
		}.bind(this)]);
	}
};

module.exports = new CComposeSendButtonView();
