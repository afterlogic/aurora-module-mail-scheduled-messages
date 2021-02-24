'use strict';

var
	_ = require('underscore'),

	TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),

	AlertPopup = require('%PathToCoreWebclientModule%/js/popups/AlertPopup.js'),
	Popups = require('%PathToCoreWebclientModule%/js/Popups.js'),

	ScheduleSendingPopup = require('modules/%ModuleName%/js/popups/ScheduleSendingPopup.js')
;

/**
 * @constructor for object that display Sensitivity button on Compose
 */
function CComposeSendButtonView() {
	this.sId = '%ModuleName%';
}

CComposeSendButtonView.prototype.ViewTemplate = '%ModuleName%_ComposeSendButtonView';

/**
 * @param {Object} oParameters
 */
CComposeSendButtonView.prototype.doAfterPopulatingMessage = function (oParameters) {
	this.fRecipientsEmpty = oParameters.fRecipientsEmpty;
	this.fGetSendSaveParameters = oParameters.fGetSendSaveParameters;
	this.fGetDraftFolderFullName = oParameters.fGetDraftFolderFullName;
};

CComposeSendButtonView.prototype.scheduleSending = function () {
	if (_.isFunction(this.fRecipientsEmpty) && this.fRecipientsEmpty()) {
		Popups.showPopup(AlertPopup, [TextUtils.i18n('%MODULENAME%/ERROR_RECIPIENTS_EMPTY')]);
	} else {
		Popups.showPopup(ScheduleSendingPopup, [this.fGetSendSaveParameters]);
	}
};

module.exports = new CComposeSendButtonView();
