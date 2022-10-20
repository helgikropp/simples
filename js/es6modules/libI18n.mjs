/* global i18nKeysJs */
const i18nKeysJsFake = {};
export const _T = (key = 'UNKNOWN DATA', def = null, params = []) => {
    let _str = (i18nKeysJs.lng || i18nKeysJsFake)[key] || def || key;
    params.forEach((item)=>{ _str = _str.replace('%s', item); });
    return _str;
};

