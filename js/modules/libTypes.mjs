/* global aAPP */
function *uid(){ for (let i=1;i<10000;i++) { yield Date.now()+i.toString().padStart(4, '0'); } }
const _uid = uid();

function libArrayToObject(arr,keyField=null) {
    return arr.reduce((obj, item) => {
        obj[keyField ? item[keyField] : _uid.next().value] = item;
        return obj;
    }, {});
}

/**
 *
 * @param items
 * @param filterFunc (return true - leave item)
 * @param keyFunc (compose temporary unique key using item fields)
 * @param sortFunc (function for JS sort() method)
 * @returns array
 */
function libDistinctArray(items, filterFunc, keyFunc, sortFunc) {
    return [...(new Map([...items
        .filter(filterFunc)
        .reduce((a, it) => {a.set(keyFunc?keyFunc(it):'key',it); return a;}, new Map())
        .entries()]
        .sort(sortFunc)))
        .values()];
}

function libIsObject(v) { return (v && typeof v === 'object' && !Array.isArray(v)); }
function libIsTrue(v)   { return (!!v && [true,'true',1,'1'].includes(v)); }
function libIsEmpty(v)  { return (typeof v === 'object' ? !Object.keys(v).length : !v); }
function libIsValidFloat(str){ return (/^-?[\d]*(\.[\d]+)?$/g).test(str); }

function libExtend(target, ...sources) {
    if (!sources.length) return target;
    const source = sources.shift();

    if (libIsObject(target) && libIsObject(source)) {
        for (const key in source) {
            if (libIsObject(source[key])) {
                if (!target[key]) { Object.assign(target, { [key]: {} }); }
                libExtend(target[key], source[key]);
            } else {
                Object.assign(target, { [key]: source[key] });
            }
        }
    }
    return libExtend(target, ...sources);
}

function libToInt(v) { if(/^(\-|\+)?([0-9]+|Infinity)$/.test(v)) { return Number(v); } return NaN; }
function libMapToObject(map) { return Object.assign(Object.create(null), ...[...map].map(v => ({[v[0]]: v[1]}))); }
function libMapToArray(map)  { return [...map].map(v => v[1]); }
function libMapToArrays(map) { return [...map]; }
function libMapToJson(map)   { return JSON.stringify(this.mapToObject(map)); }

export {
    libIsObject,
    libIsTrue,
    libIsEmpty,
    libIsValidFloat,
    libExtend,
    libToInt,
    libMapToObject,
    libMapToArray,
    libMapToArrays,
    libMapToJson,
    libArrayToObject,
    libDistinctArray
};