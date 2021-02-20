'use strict';

var
    _ = require('underscore'),
    moment = require('moment'),

    Types = require('%PathToCoreWebclientModule%/js/utils/Types.js'),

    CDateModel = require('%PathToCoreWebclientModule%/js/models/CDateModel.js'),

    Settings = require('modules/%ModuleName%/js/Settings.js'),

    ScheduleUtils = {}
;

function getPredefinedHour(oScheduleItem) {
    var iHour = Types.pInt(oScheduleItem.Hour);
    if (Types.isString(oScheduleItem.Hour) && oScheduleItem.Hour.toLowerCase().indexOf('pm') !== -1) {
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
        if (iDay <= oMoment.day())
        {
            iDay += 7;
        }
        oMoment.set('hour', iHour).set('minute', 0).set('second', 0).day(iDay);
    }
    return oMoment;
}

function getWhenLabel(oMoment, iHour) {
    var
        sWhenLabel = '',
        oNowMoment = moment()
    ;
    if (oNowMoment.date() === oMoment.date()) {
        sWhenLabel = 'Today';
    } else if (oNowMoment.add(1, 'd').date() === oMoment.date()) {
        sWhenLabel = 'Tomorrow';
    } else {
        sWhenLabel = oMoment.format('dddd');
    }

    if (iHour >= 0 && iHour <= 3) {
        sWhenLabel += ' night';
    } else if (iHour >= 4 && iHour <= 11) {
        sWhenLabel += ' morning';
    } else if (iHour >= 12 && iHour <= 16) {
        sWhenLabel += ' afternoon';
    } else if (iHour >= 16 && iHour <= 23) {
        sWhenLabel += ' evening';
    }
    return sWhenLabel;
}

ScheduleUtils.getPredefinedOptions = function () {
    // var oDatTime = moment().set('minute', 0).set('second', 0);
    // for(var i=0; i<24; i++)
    // {
    // 	oDatTime.set('hour', i);
    // 	console.log(oDatTime.format('hh:mm a'))
    // }
    // 0-3 - night
    // 4-11 - morning
    // 12-16 - day
    // 17-11 - evening

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
    aOptions.sort(function(left, right) {
        return left.Unix === right.Unix ? 0 : (left.Unix < right.Unix ? -1 : 1);
    });

    var aResultOptions = [];
    _.each(aOptions, function (oOption) {
        if (oOption.Unix > moment().unix() && !_.find(aResultOptions, function (oResOption) { return oOption.Unix === oResOption.Unix }))
        {
            aResultOptions.push(oOption);
        }
    });
    return _.uniq(aResultOptions);
};

module.exports = ScheduleUtils;