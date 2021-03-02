'use strict';

var
	_ = require('underscore'),
	moment = require('moment'),

	TextUtils = require('%PathToCoreWebclientModule%/js/utils/Text.js'),
	Types = require('%PathToCoreWebclientModule%/js/utils/Types.js'),

	CDateModel = require('%PathToCoreWebclientModule%/js/models/CDateModel.js'),

	Settings = require('modules/%ModuleName%/js/Settings.js'),

	ScheduleUtils = {}
;

function getPredefinedHour(oScheduleItem) {
	var iHour = Types.pInt(oScheduleItem.Hour);
	if (iHour <= 12 && Types.isString(oScheduleItem.Hour) && oScheduleItem.Hour.toLowerCase().indexOf('pm') !== -1) {
		iHour += 12;
	}
	return iHour;
}

function getPredefinedMoment(oScheduleItem, iHour) {
	var oMoment = moment();
	if (oScheduleItem.DayOfWeek.toLowerCase() === 'today') {
		oMoment.set('hour', iHour).set('minute', 0).set('second', 0);
	} else if (oScheduleItem.DayOfWeek.toLowerCase() === 'tomorrow') {
		oMoment.add(1, 'd').set('hour', iHour).set('minute', 0).set('second', 0);
	} else {
		var
			oDays = {
				'sunday': 0,
				'monday': 1,
				'tuesday': 2,
				'wednesday': 3,
				'thursday': 4,
				'friday': 5,
				'saturday': 6
			},
			iDay = Types.pInt(oDays[oScheduleItem.DayOfWeek.toLowerCase()], 1)
		;
		if (iDay <= oMoment.day()) {
			iDay += 7;
		}
		oMoment.set('hour', iHour).set('minute', 0).set('second', 0).day(iDay);
	}
	return oMoment;
}

function getWhenLabel(oMoment, iHour) {
	var
		sWhenLabel = '',
		oDaysTexts = {
			0: TextUtils.i18n('%MODULENAME%/LABEL_WHEN_SUNDAY'),
			1: TextUtils.i18n('%MODULENAME%/LABEL_WHEN_MONDAY'),
			2: TextUtils.i18n('%MODULENAME%/LABEL_WHEN_TUESDAY'),
			3: TextUtils.i18n('%MODULENAME%/LABEL_WHEN_WEDNESDAY'),
			4: TextUtils.i18n('%MODULENAME%/LABEL_WHEN_THURSDAY'),
			5: TextUtils.i18n('%MODULENAME%/LABEL_WHEN_FRIDAY'),
			6: TextUtils.i18n('%MODULENAME%/LABEL_WHEN_SATURDAY')
		},
		oNowMoment = moment()
	;

	if (oNowMoment.date() === oMoment.date()) {
		sWhenLabel = TextUtils.i18n('%MODULENAME%/LABEL_WHEN_TODAY');
	} else if (oNowMoment.add(1, 'd').date() === oMoment.date()) {
		sWhenLabel = TextUtils.i18n('%MODULENAME%/LABEL_WHEN_TOMORROW');
	} else {
		sWhenLabel = oDaysTexts[oMoment.day()];
	}

	if (iHour >= 0 && iHour <= 3) {
		sWhenLabel += ' ' + TextUtils.i18n('%MODULENAME%/LABEL_WHEN_NIGHT');
	} else if (iHour >= 4 && iHour <= 11) {
		sWhenLabel += ' ' + TextUtils.i18n('%MODULENAME%/LABEL_WHEN_MORNING');
	} else if (iHour >= 12 && iHour <= 16) {
		sWhenLabel += ' ' + TextUtils.i18n('%MODULENAME%/LABEL_WHEN_AFTERNOON');
	} else if (iHour >= 16 && iHour <= 23) {
		sWhenLabel += ' ' + TextUtils.i18n('%MODULENAME%/LABEL_WHEN_EVENING');
	}

	return sWhenLabel;
}

ScheduleUtils.getPredefinedOptions = function () {
	var aOptions = [];
	if (_.isArray(Settings.PredefinedSchedule)) {
		_.each(Settings.PredefinedSchedule, function (oScheduleItem) {
			var
				iHour = getPredefinedHour(oScheduleItem),
				oMoment = getPredefinedMoment(oScheduleItem, iHour)
			;

			aOptions.push({
				LeftLabel: getWhenLabel(oMoment, iHour),
				RightLabel: oMoment.format('D MMM, ' + CDateModel.prototype.getTimeFormat()),
				Unix: oMoment.unix()
			});
		});
	}
	aOptions.sort(function (left, right) {
		return left.Unix === right.Unix ? 0 : (left.Unix < right.Unix ? -1 : 1);
	});

	var aResultOptions = [];
	_.each(aOptions, function (oOption) {
		if (oOption.Unix > moment().unix() && !_.find(aResultOptions, function (oResOption) {
			return oOption.Unix === oResOption.Unix
		})) {
			aResultOptions.push(oOption);
		}
	});
	return _.uniq(aResultOptions);
};

ScheduleUtils.getScheduledAtText = function (iUnix) {
	var oMoment = moment.unix(iUnix);
	return TextUtils.i18n('%MODULENAME%/INFO_SENDING_SCHEDULED_FOR', {
		'DATA': oMoment.format('D MMM, ' + CDateModel.prototype.getTimeFormat())
	});
};

module.exports = ScheduleUtils;
