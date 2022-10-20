import {libBusyStart} from "./libBusy.mjs";

const strNoRoute = 'ERR_ROUTE_404';
const localApiPath = `${window.location.protocol}//${window.location.host}`;

const METHOD = {
    HEADERS: { name: 'GET',  noContentType: false },
    GET:     { name: 'GET',  noContentType: false },
    POST:    { name: 'POST', noContentType: false },
    FORM:    { name: 'POST', noContentType: true }
};

export class ExtendedError extends Error {
    constructor(message) {
        super(message);
        this.type = 'EXTENDED';
    }
}

function _composeUrl(url,params) {
    return url + (params ? '/' + (new URLSearchParams(params)).toString() : '');
}

export const fetchAsync = Object.freeze({
    _do: (method, url, data, options) => {
        if(!url) {
            throw new ExtendedError(strNoRoute);
        } else {
            if(!options['noLoader']) { libBusyStart(); }

            const init = {
                method:  'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body:    data
            };

            if(!method.noContentType) {
                init.headers['Content-Type'] = 'application/json';
            }

            fetch(url, init)
                .catch(err => { throw new Error(`Error from server ${response.status}`);} )
                .then((response) => {
                    return response.json();
                })
                .catch(err => { throw new Error(`Error from server ${response.status}`);} )
            ;
        }
    },

    headers: (options={}) => {
        return this._do(METHOD.HEADERS, localApiPath, null, options);
    },

    get: (url, data = null, options={}) => {
        return this._do(METHOD.GET, _composeUrl(url,data), null, options);
    },

    post: (url, data = null, options={}) => {
        return this._do(METHOD.POST, url, data?JSON.stringify(data):null, options);
    },

    form: (url, data = null, options={}) => {
        if(data && !(data instanceof FormData)) {
            throw new ExtendedError(strNoRoute);
        } else {
            return this._do(METHOD.FORM, url, data, options);
        }
    }
});
