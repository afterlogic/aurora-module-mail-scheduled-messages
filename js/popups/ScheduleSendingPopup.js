'use strict';

var
	_ = require('underscore'),
	ko = require('knockout'),
	moment = require('moment'),

	CalendarUtils = require('%PathToCoreWebclientModule%/js/utils/Calendar.js'),
	DateUtils = require('%PathToCoreWebclientModule%/js/utils/Date.js'),
	TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
	Utils = require('%PathToCoreWebclientModule%/js/utils/Common.js'),

	Ajax = require('%PathToCoreWebclientModule%/js/Ajax.js'),
	Api = require('%PathToCoreWebclientModule%/js/Api.js'),
	CAbstractPopup = require('%PathToCoreWebclientModule%/js/popups/CAbstractPopup.js'),
	ModulesManager = require('%PathToCoreWebclientModule%/js/ModulesManager.js'),
	Screens = require('%PathToCoreWebclientModule%/js/Screens.js'),
	UserSettings = require('%PathToCoreWebclientModule%/js/Settings.js'),

	ScheduleUtils = require('modules/%ModuleName%/js/utils/Schedule.js'),

	Settings = require('modules/%ModuleName%/js/Settings.js')
;

/**
 * @constructor
 */
function CScheduleSendingPopup() {
	CAbstractPopup.call(this);

	this.oCompose = null;
	this.fOnClose = null;

	this.aOptions = ScheduleUtils.getPredefinedOptions();
	this.scheduledTime = ko.observable(0);

	this.timeFormatMoment = 'HH:mm';
	this.dateFormatMoment = 'MM/DD/YYYY';
	this.dateFormatDatePicker = 'mm/dd/yy';
	this.dateDom = ko.observable(null);
	this.timeOptions = ko.observableArray(CalendarUtils.getTimeListStepHalfHour((UserSettings.timeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
	UserSettings.timeFormat.subscribe(function () {
		this.timeOptions(CalendarUtils.getTimeListStepHalfHour((UserSettings.timeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
	}, this);
	this.selectedDate = ko.observable('');
	this.selectedTime = ko.observable('');
	this.selectedTime.subscribe(function () {
		if (this.selectedTime() !== '')
		{
			this.selectDatetime();
		}
	}, this);
	this.lockSelectStartEndDate = ko.observable(false);
	this.initializeDatePickers();

	this.scheduleInProcess = ko.observable(false);
	this.scheduleCommand = Utils.createCommand(this, this.schedule, function () {
		return this.scheduledTime() !== 0 && !this.scheduleInProcess();
	}.bind(this));
}

_.extendOwn(CScheduleSendingPopup.prototype, CAbstractPopup.prototype);

CScheduleSendingPopup.prototype.PopupTemplate = '%ModuleName%_ScheduleSendingPopup';

CScheduleSendingPopup.prototype.onOpen = function (oCompose, fOnClose) {
	this.oCompose = oCompose;
	this.fOnClose = fOnClose;
	this.timeFormatMoment = (UserSettings.timeFormat() === Enums.TimeFormat.F24) ? 'HH:mm' : 'hh:mm A';
	this.dateFormatMoment = Utils.getDateFormatForMoment(UserSettings.dateFormat());
	this.dateFormatDatePicker = CalendarUtils.getDateFormatForDatePicker(UserSettings.dateFormat());
	this.selectedDate('');
	this.selectedTime('');
	this.scheduledTime(0);
	this.initializeDatePickers();
};

CScheduleSendingPopup.prototype.initializeDatePickers = function () {
	if (this.dateDom()) {
		this.createDatePickerObject(this.dateDom(), this.selectDatetime.bind(this));
		this.dateDom().datepicker('option', 'dateFormat', this.dateFormatDatePicker);
	}
};

CScheduleSendingPopup.prototype.createDatePickerObject = function (oElement, fSelect) {
	$(oElement).datepicker({
		showOtherMonths: true,
		selectOtherMonths: true,
		monthNames: DateUtils.getMonthNamesArray(),
		dayNamesMin: TextUtils.i18n('COREWEBCLIENT/LIST_DAY_NAMES_MIN').split(' '),
		nextText: '',
		prevText: '',
		firstDay: ModulesManager.run('CalendarWebclient', 'getWeekStartsOn', []),
		showOn: 'both',
		buttonText: ' ',
		dateFormat: this.dateFormatDatePicker,
		onSelect: fSelect
	});

	$(oElement).mousedown(function () {
		$('#ui-datepicker-div').toggle();
	});
};

CScheduleSendingPopup.prototype.selectDatetime = function () {
	if (!this.lockSelectStartEndDate()) {
		this.lockSelectStartEndDate(true);

		var
			oSelectedDate = this.getDateTime(this.dateDom(), this.selectedTime()),
			oSelectedMoment = moment(oSelectedDate)
		;

		this.selectedDate(this.getDateWithoutYearIfMonthWord($(this.dateDom()).val()));
		this.selectedTime(oSelectedMoment.isValid() ? oSelectedMoment.format(this.timeFormatMoment) : '');
		this.scheduledTime(oSelectedMoment.isValid() ? oSelectedMoment.unix() : 0);

		this.lockSelectStartEndDate(false);
	}
};

CScheduleSendingPopup.prototype.getDateTime = function (oInput, sTime) {
	sTime = sTime ? moment(sTime, this.timeFormatMoment).format('HH:mm') : '';

	var
		oDate = oInput.datepicker('getDate'),
		aTime = sTime ? sTime.split(':') : []
	;

	//in some cases aTime is a current time (it happens only once after page loading), in this case oDate is null, so code falls.
	// the checking if oDate is not null is necessary
	if (aTime.length === 2 && oDate !== null) {
		oDate.setHours(aTime[0]);
		oDate.setMinutes(aTime[1]);
	} else if (oDate !== null) {
		oDate.setHours(8);
		oDate.setMinutes(0);
	}

	return oDate;
};

CScheduleSendingPopup.prototype.getDateWithoutYearIfMonthWord = function (sDate) {
	var
		aDate = sDate.split(' '),
		oNowMoment = moment(),
		oNowYear = oNowMoment.format('YYYY')
	;

	if (aDate.length === 3 && oNowYear === aDate[2]) {
		return aDate[0] + ' ' + aDate[1];
	}
	return sDate;
};

CScheduleSendingPopup.prototype.selectScheduledTime = function (iUnix) {
	this.dateDom().val('');
	this.selectedDate('');
	this.selectedTime('');
	if (this.scheduledTime() === iUnix) {
		this.scheduledTime(0);
	} else {
		this.scheduledTime(iUnix);
	}
};

CScheduleSendingPopup.prototype.schedule = function () {
	if (_.isFunction(this.oCompose && this.oCompose.koAllAttachmentsUploaded)) {
		if (this.oCompose.koAllAttachmentsUploaded()) {
			this.scheduleAfterAllUploaded();
		} else {
			var oSubscription = this.oCompose.koAllAttachmentsUploaded.subscribe(function () {
				if (this.oCompose.koAllAttachmentsUploaded()) {
					this.scheduleAfterAllUploaded();
				}
				oSubscription.dispose();
			}, this);
		}
	} else {
		this.scheduleAfterAllUploaded();
	}
};

CScheduleSendingPopup.prototype.scheduleAfterAllUploaded = function () {
	if (_.isFunction(this.oCompose && this.oCompose.getSendSaveParameters)) {
		if (this.scheduledTime() < moment().unix()) {
			Screens.showError(TextUtils.i18n('%MODULENAME%/ERROR_LATER_SCHEDULED_TIME'));
			return;
		}

		var oParameters = this.oCompose.getSendSaveParameters();
		if (oParameters.DraftUid && _.isFunction(this.oCompose && this.oCompose.getDraftFolderFullName)) {
			oParameters.DraftFolder = this.oCompose.getDraftFolderFullName(oParameters.AccountID);
		}
		oParameters.ScheduleDateTime = this.scheduledTime();
		this.scheduleInProcess(true);
		Ajax.send('%ModuleName%', 'SaveScheduledMessage', oParameters, function (oResponse) {
			this.scheduleInProcess(false);
			if (oResponse && oResponse.Result) {
				Screens.showReport(TextUtils.i18n('%MODULENAME%/REPORT_SENDING_SCHEDULED'));
				this.closePopup();
				ModulesManager.run('MailWebclient', 'removeMessageFromCurrentList', [oParameters.AccountID, oParameters.DraftFolder, oParameters.DraftUid]);
				if (this.oCompose) {
					if (_.isFunction(this.oCompose.commitAndClose)) {
						this.oCompose.commitAndClose();
					}
					if (_.isFunction(this.oCompose.clearFolderCache)) {
						if (oParameters.DraftFolder) {
							this.oCompose.clearFolderCache(oParameters.AccountID, oParameters.DraftFolder);
						}
						if (Settings.CurrentScheduledFolderName) {
							this.oCompose.clearFolderCache(oParameters.AccountID, Settings.CurrentScheduledFolderName);
						}
					}
				}
			} else {
				Api.showErrorByCode(oResponse);
			}
		}, this);
	}
};

CScheduleSendingPopup.prototype.cancelPopup = function () {
	if (!this.scheduleInProcess()) {
		this.closePopup();
	}
};

CScheduleSendingPopup.prototype.onClose = function () {
	if (_.isFunction(this.fOnClose)) {
		this.fOnClose();
	}
};

module.exports = new CScheduleSendingPopup();
