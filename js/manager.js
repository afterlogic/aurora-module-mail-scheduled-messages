'use strict';

module.exports = function (oAppData) {
	var
		ko = require('knockout'),

		TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),

		App = require('%PathToCoreWebclientModule%/js/App.js'),

		Settings = require('modules/%ModuleName%/js/Settings.js')
	;

	require('jquery-ui/ui/widgets/datepicker');

	Settings.init(oAppData);

	var
		sScheduledName = Settings.ScheduledFolderName,
		sScheduledFullName = Settings.ScheduledFolderName
	;

	function SetScheduledFolder(koFolderList) {
		var
			sNameSpace = koFolderList().sNamespaceFolder,
			sDelimiter = koFolderList().sDelimiter
		;
		if (sNameSpace !== '') {
			sScheduledFullName = sNameSpace + sDelimiter + sScheduledName;
		} else {
			sScheduledFullName = sScheduledName;
		}

		var oScheduledFolder = koFolderList().getFolderByFullName(sScheduledFullName);
		if (oScheduledFolder) {
			Settings.setCurrentScheduledFolder(sScheduledFullName);
			oScheduledFolder.displayName = ko.observable(TextUtils.i18n('%MODULENAME%/LABEL_FOLDER_SCHEDULED'));
			oScheduledFolder.usedAs = ko.observable(TextUtils.i18n('%MODULENAME%/LABEL_USED_AS_SCHEDULED'));
			oScheduledFolder.setDisableMoveTo(true);
			oScheduledFolder.setDisableMoveFrom(true);
			oScheduledFolder.setShowTotalInsteadUnseenCount(true);
		} else {
			Settings.setCurrentScheduledFolder('');
		}
	}

	if (App.isUserNormalOrTenant()) {
		return {
			start: function (ModulesManager) {
				if (ModulesManager.isModuleEnabled('MailWebclient')) {

					ModulesManager.run('MailWebclient', 'registerComposeToolbarController', [require('modules/%ModuleName%/js/views/ComposeSendButtonView.js')]);

					App.subscribeEvent('MailWebclient::ConstructView::before', function (oParams) {
						if (oParams.Name === 'CMailView') {
							var
								koFolderList = oParams.MailCache.folderList,
								koCurrentFolder = ko.computed(function () {
									return oParams.MailCache.folderList().currentFolder();
								})
							;
							SetScheduledFolder(koFolderList);
							koFolderList.subscribe(function () {
								SetScheduledFolder(koFolderList);
							});
							koCurrentFolder.subscribe(function () {
								var sFullName = koCurrentFolder() ? koCurrentFolder().fullName() : '';
								if (sFullName === sScheduledFullName) {
									oParams.View.resetDisabledTools('%ModuleName%', ['spam', 'move']);
								} else {
									oParams.View.resetDisabledTools('%ModuleName%', []);
								}
							});
						}
					});

					App.subscribeEvent('MailWebclient::RegisterMessagePaneController', function (fRegisterMessagePaneController) {
						fRegisterMessagePaneController(require('modules/%ModuleName%/js/views/ScheduledInfoView.js'), 'BeforeMessageBody');
					});
				}
			}
		};
	}

	return null;
};
