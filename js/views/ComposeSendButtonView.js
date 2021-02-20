'use strict';

var
    _ = require('underscore'),
    // ko = require('knockout'),

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
CComposeSendButtonView.prototype.doAfterApplyingMainTabParameters = function (oParameters) {
    console.log('doAfterApplyingMainTabParameters', oParameters);
};

/**
 * @param {Object} oParameters
 */
CComposeSendButtonView.prototype.doAfterPreparingMainTabParameters = function (oParameters) {
    console.log('doAfterPreparingMainTabParameters', oParameters);
};

/**
 * @param {Object} oParameters
 */
CComposeSendButtonView.prototype.doAfterPopulatingMessage = function (oParameters) {
    this.fRecipientsEmpty = oParameters.fRecipientsEmpty;
};

/**
 * @param {Object} oParameters
 */
CComposeSendButtonView.prototype.doAfterPreparingSendMessageParameters = function (oParameters) {
    console.log('doAfterPreparingSendMessageParameters', oParameters);
};

CComposeSendButtonView.prototype.scheduleSending = function () {
    if (_.isFunction(this.fRecipientsEmpty) && this.fRecipientsEmpty()) {
        Popups.showPopup(AlertPopup, [TextUtils.i18n('%MODULENAME%/ERROR_RECIPIENTS_EMPTY')]);
    } else {
        Popups.showPopup(ScheduleSendingPopup, []);
    }
};

module.exports = new CComposeSendButtonView();
