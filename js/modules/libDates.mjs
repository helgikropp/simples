/* global _T, moment */
import {libReplaceAll} from "./libStrUtils.mjs";

function libGetDayCode(localDate) {
    const daysArr = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    const idx = moment(localDate,moment.localeData().longDateFormat('L')).day();
    return daysArr[idx];
}

function libAddDaysToDateAsMilliseconds(date, days){ return date.valueOf() + days * 24 * 60 * 60 * 1000; }

function libDateLocalToUnixMs(localDate) {
    return moment(localDate,moment.localeData().longDateFormat('L')).valueOf();
}
function libDateUnixMsToLocal(unixTimeStampMs) {
    return moment(unixTimeStampMs).format(moment.localeData().longDateFormat('L'));
}
function libDateUnixMsToXml(unixTimeStampMs) {
    return moment(unixTimeStampMs).format('YYYY-MM-DDTHH:mm:ss');
}
function libDateXmlToUnixMs(xmlDate) {
    return moment(xmlDate,'YYYY-MM-DDTHH:mm:ss').valueOf();
}
function libDateXmlToUnix(xmlDate) {
    return moment(xmlDate,'YYYY-MM-DDTHH:mm:ss').unix();
}
function libDateXmlToLocal(xmlDate,withTime=true,timeLong=false) {
    const t = moment(xmlDate.substr(0,19),'YYYY-MM-DDTHH:mm:ss');
    return t.format('L') + (withTime?(' '+t.format(timeLong?'LTS':'LT')):'');
}
function libDateUnixToLocal(unixTimeStamp,outFmt='L'/*LT,LTS*/) {
    return moment.unix(unixTimeStamp).format(moment.localeData().longDateFormat(outFmt));
}
function libDateUnixToXml(unixTimeStamp) {
    return moment.unix(unixTimeStamp).format(withTime?'YYYY-MM-DDTHH:mm:ss':'YYYY-MM-DD');
}
function libDateLocalToUnix(localDate) {
    return moment(localDate,moment.localeData().longDateFormat('L')).unix();
}
function libDateLocalToXml(localDate) {
    return moment(localDate,moment.localeData().longDateFormat('L')).format('YYYY-MM-DDTHH:mm:ss');
}


export {
    libGetDayCode,
    libAddDaysToDateAsMilliseconds,
    libDateXmlToLocal,
    libDateXmlToUnix,
    libDateXmlToUnixMs,
    libDateUnixToLocal,
    libDateUnixToXml,
    libDateUnixMsToLocal,
    libDateUnixMsToXml,
    libDateLocalToUnix,
    libDateLocalToUnixMs,
    libDateLocalToXml
};
