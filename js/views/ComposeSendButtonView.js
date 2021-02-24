'use strict';

var
	_ = require('underscore'),

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
	this.sId = '%ModuleName%';
	this.scheduleCommand = Utils.createCommand(this, this.scheduleSending, function () {
		//isEnableSending
		return true;
	}.bind(this));
}

CComposeSendButtonView.prototype.ViewTemplate = '%ModuleName%_ComposeSendButtonView';

CComposeSendButtonView.prototype.assignComposeExtInterface = function (oCompose)
{
	this.oCompose = oCompose;
};

CComposeSendButtonView.prototype.scheduleSending = function () {
	if (_.isFunction(this.oCompose && this.oCompose.getAutoEncryptSignMessage) && this.oCompose.getAutoEncryptSignMessage()) {
		Popups.showPopup(AlertPopup, [TextUtils.i18n('%MODULENAME%/ERROR_AUTOMATIC_ENCRYPTION')]);
	} else if (_.isFunction(this.oCompose && this.oCompose.getRecipientsEmpty) && this.oCompose.getRecipientsEmpty()) {
		Popups.showPopup(AlertPopup, [TextUtils.i18n('%MODULENAME%/ERROR_RECIPIENTS_EMPTY')]);
	} else {
		Popups.showPopup(ScheduleSendingPopup, [this.oCompose]);
	}
};

module.exports = new CComposeSendButtonView();
