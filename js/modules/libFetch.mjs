import { libBusyStart, libBusyStop } from './libBusy.mjs';
import { libAlertLogout } from './libMessages.mjs';
import { libCreateCustomError} from './libCustomError.mjs';

const rootPath = `${window.location.protocol}//${window.location.host}`;
const strNoRoute = 'ERR_ROUTE_404';

export const fetchGet = async (url) => {
    libBusyStart();

    const response =  await fetch(`${rootPath}${url}`, {
        method:  'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        }
    });

    if (response.status !== 200) {
        throw new Error(`Error from server ${response.status}`);
    }

    const { data: result, code, msg, redirect, account = '' } = await response.json();

    return checkResponseCode(result, code, msg, account);
};

export const fetchGetHeaders = async (options={}) => {
    if(!options['noLoader']) { libBusyStart(); }

    const response =  await fetch(rootPath, {
        method:  'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        }
    });

    if (response.status !== 200) {
        throw new Error(`Error from server ${response.status}`);
    }

    libBusyStop();
    return await response.headers.get('Date');
};

export const fetchPost = async (url, data = '',options={}) => {
    if(!url) { throw new Error(strNoRoute); }
    if(!options['noLoader']) { libBusyStart(); }

    const response = await fetch(`${rootPath}${url}`, {
        method:  'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(data),
    });

    if (response.status !== 200) {
        throw new Error(`Error from server ${response.status}`);
    }

    const { data: result, code, msg, redirect, account = '' } = await response.json();

    return checkResponseCode(result, code, msg, account);
};

export const fetchPostForm = async (url, formData,options={}) => {
    if(!url) { throw new Error(strNoRoute); }
    if(!options['noLoader']) { libBusyStart(); }

    const response = await fetch(`${rootPath}${url}`, {
        method:  'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: formData,
    });

    if (response.status !== 200) {
        throw new Error(`Error from server ${response.status}`);
    }

    const { data: result, code, msg = '', redirect, account = '' } = await response.json();

    return checkResponseCode(result, code, msg, account);
};

export const checkResponseCode = async (result, code, msg, account) => {
    const message = msg 
        ? (!Array.isArray(msg) ? [msg] : msg).map(item=>(_T(item))).join('<br>')
        : code.replace('RC_','ERR_');
    if(code !== 'RC_OK') {
        libBusyStop();
        switch (code) {
            case 'RC_FORBIDDEN':
            case 'RC_RELOGON_REQUIRED':
            case 'RC_SESSION_CLOSED':
            case 'RC_ACCOUNT_LOCKED':
                throw libCreateCustomError(libAlertLogout,code);
            default:
                throw libCreateCustomError(message,code);
        }
    } else {
        libBusyStop();
        return {
            data: result,
            account,
            msg
        };
    }
};