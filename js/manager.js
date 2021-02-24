'use strict';

module.exports = function (oAppData) {
	var
		App = require('%PathToCoreWebclientModule%/js/App.js'),

		Settings = require('modules/%ModuleName%/js/Settings.js')
	;

	Settings.init(oAppData);

	require('jquery-ui/ui/widgets/datepicker');

	if (App.isUserNormalOrTenant()) {
		return {
			start: function (ModulesManager) {
				if (ModulesManager.isModuleEnabled('MailWebclient')) {
					ModulesManager.run('MailWebclient', 'registerComposeToolbarController', [require('modules/%ModuleName%/js/views/ComposeSendButtonView.js')]);
				}
			}
		};
	}

	return null;
};
