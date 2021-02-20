'use strict';

var
    _ = require('underscore'),
    ko = require('knockout'),

    DateUtils = require('%PathToCoreWebclientModule%/js/utils/Date.js'),
    TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
    Types = require('%PathToCoreWebclientModule%/js/utils/Types.js'),

    CAbstractPopup = require('%PathToCoreWebclientModule%/js/popups/CAbstractPopup.js'),
    UserSettings = require('%PathToCoreWebclientModule%/js/Settings.js'),

    CalendarUtils = require('modules/CalendarWebclient/js/utils/Calendar.js'),

    ScheduleUtils = require('modules/%ModuleName%/js/utils/Schedule.js')
;

/**
 * @constructor
 */
function CScheduleSendingPopup() {
    CAbstractPopup.call(this);

    this.aOptions = ScheduleUtils.getPredefinedOptions();
    // this.aOptions.push({
    //     LeftLabel: 'Select date and time',
    //     RightLabel: '',
    //     Handler: this.selectDateTime.bind(this)
    // });

    this.dateFormatDatePicker = 'mm/dd/yy';
    this.startDom = ko.observable(null);
    this.timeOptions = ko.observableArray(CalendarUtils.getTimeListStepHalfHour((UserSettings.timeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
    UserSettings.timeFormat.subscribe(function () {
        this.timeOptions(CalendarUtils.getTimeListStepHalfHour((UserSettings.timeFormat() !== Enums.TimeFormat.F24) ? 'hh:mm A' : 'HH:mm'));
    }, this);
    this.startDate = ko.observable('');
    this.startTime = ko.observable('');
    this.startTime.subscribe(function () {
        this.selectStartDate();
    }, this);
    this.lockSelectStartEndDate = ko.observable(false);
    this.initializeDatePickers();
}

_.extendOwn(CScheduleSendingPopup.prototype, CAbstractPopup.prototype);

CScheduleSendingPopup.prototype.PopupTemplate = '%ModuleName%_ScheduleSendingPopup';

CScheduleSendingPopup.prototype.onOpen = function () {
    // this.dateFormatDatePicker = CalendarUtils.getDateFormatForDatePicker(oParameters.DateFormat);
    this.initializeDatePickers();
};

CScheduleSendingPopup.prototype.initializeDatePickers = function () {
    if (this.startDom()) {
        this.createDatePickerObject(this.startDom(), this.selectStartDate.bind(this));
        this.startDom().datepicker('option', 'dateFormat', this.dateFormatDatePicker);
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
        // firstDay: Settings.WeekStartsOn,
        showOn: 'both',
        buttonText: '',
        buttonImage: './static/styles/images/calendar-icon.png',
        buttonImageOnly: true,
        dateFormat: this.dateFormatDatePicker,
        onSelect: fSelect
    });

    $(oElement).mousedown(function () {
        $('#ui-datepicker-div').toggle();
    });
};

CScheduleSendingPopup.prototype.setStartDate = function (oMomentDate, bChangeInDatepicker) {
    if (bChangeInDatepicker && this.startDom()) {
        this.startDom().datepicker('setDate', oMomentDate.toDate());
    }
    this.startDate(this.getDateWithoutYearIfMonthWord($(this.startDom()).val()));
};

CScheduleSendingPopup.prototype.selectStartDate = function () {
    if (!this.lockSelectStartEndDate() && this.startDate()) {
        this.lockSelectStartEndDate(true);

        var
            oStartDate = this.getDateTime(this.startDom(), this.startTime()),
            oStartMomentDate = moment(oStartDate)
        ;

        this.setStartDate(oStartMomentDate, false);
        this.startTime(oStartMomentDate.format(this.timeFormatMoment));

        this.lockSelectStartEndDate(false);
    }
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

CScheduleSendingPopup.prototype.schedule = function (iUnix) {
    console.log('iUnix', iUnix);
};

CScheduleSendingPopup.prototype.selectDateTime = function () {
    console.log('selectDateTime');
};

CScheduleSendingPopup.prototype.cancelPopup = function () {
    this.closePopup();
};

module.exports = new CScheduleSendingPopup();
