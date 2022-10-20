import {libUuid} from "../../../../helpers/libStrUtils.mjs";
import {libMapToObject} from "../../../../helpers/libTypes.mjs";

export class SpDraftNovelties {
    constructor(owner) {

        this._owner = owner;

        function *uid(){ for (let i=1;i<10000;i++) { yield 'new'+i.toString().padStart(4, '0'); } }

        this._uid = uid();
        this.data = new Map();
    }

    _new(dt)     {
        dt['uid']   = this._generateUid();
        dt['hash']  = this._generateHash();
        dt['state'] = 0;
        return this._set(dt);
    }

    _generateUid() { return this._uid.next().value; }
    
    _generateHash() { return libUuid('x'.repeat(32)); }
    
    _set(dt)     { this.data.set(dt['hash'],dt); return dt['hash']; }
    
    _delete(hash) {
        let dt = this._get(hash);
        if(dt['state'] === 1) {
            dt['state'] = -1;
            this._set(dt);
        } else {
            this.data.delete(hash);
        }
    }
    
    _asObject()  { return libMapToObject(this.data); }
    
    _get(hash)   { return this.data.get(hash) || null; }
    
    _first(activeOnly=true) {
        const vals = this.data.values();
        let val = null;
        if(activeOnly) {
            let result = vals.next();
            while (!result.done) {
                if(result.value['state'] !== -1) {
                    val = result.value;
                    break;
                }
                result = vals.next();
            }
            return val;
        }
        return this.data.values().next().value;
    }

    get _def()   {
        return {
            uid: '', //uid orr idAAK-rowNum>: {
            hash: '', //uid orr idAAK-rowNum>: {
            name: '', //<product name>,
            listexState: '200', //
            sku: '', //<sku code>,
            trademarkName: '',
            barcode: '', //<barcode>,
            status: '', //<status>,
            state: 0, // 0 - new, 1 - exist, -1 - deleted,
            isBulk: true,
            data: {
                main: {},
                log: {},
                tare: {}
            }
        };
    }
    get _size() { return this.data.size; }
    get _count() { return [...this.data.values()].filter((v)=>{return v['state']!==-1;}).length; }
}