/* global aAPP */

import {_T} from "./libI18n.mjs";
import {libReplaceAll} from "./libStrUtils.mjs";

export const libCONSTS = {
    SQL_INT_MAX : 2147483647
};

/**
 *
 * @param {String} pwd
 * @param {String} login
 * @returns {Number} Пароль: 0 - не задан, 1 - не катит, 2 - так себе, 3 - тошонада
 */
 export function libPwdLevel(pwd, login = '') {
    let level = 0;
    let strong = 0;
    let cntAll = pwd.length;
    let hasSpaces = /\s/.test(pwd);
    if(pwd.length
        && (!login.length || pwd.indexOf(login) < 0)
        && !hasSpaces
        && cntAll > 7) {
        let cntDigits = pwd.match(/([0-9])/g);
        strong += cntDigits ? 1 : 0;
        let cntLowercase = pwd.match(/([a-z])/g);
        strong += cntLowercase ? 1 : 0;
        let cntUppercase = pwd.match(/([A-Z])/g);
        strong += cntUppercase ? 1 : 0;
        let cntSymbols = pwd.match(/([\W\_])/g);
        strong += cntSymbols ? 1 : 0;

        if(strong === 4 && cntAll > 9) {
            level = 2;
        } else if(strong === 3) {
            level = 1;
        }
    }
    return pwd.length
        ? level + 1
        : 0;
}

export function libGetDataTablesOptions() {
    return {
        processing: _T('DT_PROCESSING'),
        search: _T('G_SEARCH'),
        lengthMenu: _T('DT_LENGTH_MENU'),
        info: _T('DT_INFO'),
        infoEmpty: _T('DT_INFO_EMPTY'),
        infoFiltered: _T('DT_INFO_FILTERED'),
        infoPostFix: '',
        loadingRecords: _T('DT_LOADING_RECS'),
        zeroRecords: _T('DT_ZERO_RECORDS'),
        emptyTable: _T('DT_EMPTY_TABLE'),
        paginate: {
            first: _T('G_1ST'),
            previous: _T('DT_PAGE_PREV'),
            next: _T('DT_PAGE_NEXT'),
            last: _T('DT_PAGE_LAST')
        },        
        aria: {}
    };
}

export function libFullNameCompact(fullName) {
    let pib = libReplaceAll(fullName,'    ',' ');
    pib = libReplaceAll(pib,'   ',' ');
    pib = libReplaceAll(pib,'  ',' ');
    pib = libReplaceAll(pib,' - ','-');
    pib = libReplaceAll(pib,' -','-');
    pib = libReplaceAll(pib,'- ','-');

    let parts = pib.split(' ');
    if(parts.length === 3) {
        for(let i = 1; i < parts.length; ++i) {
            if(parts[i].indexOf('-') === -1) {
                parts[i] = parts[i].substr(0,1) + '.';
            } else {
                let subParts = parts[i].split('-');
                parts[i] = subParts.join('-');
            }
        }
    }
    return parts.join(' ');
}
