/* global aAPP */
import {libBusyStart} from './libBusy.mjs';
import {libOnAjaxFail} from "./libAjax.mjs";
import {sApp} from "../app.mjs";
import {checkResponseCode} from "./libFetch.mjs";
import {libAlertLogout, libCatchError} from "./libMessages.mjs";
import {CustomError} from "./libCustomError.mjs";

function _goToUrl(path, withHistory, showBusy){
    if(!path) { return; }
    if(showBusy) { libBusyStart(); }
    if(withHistory) {
        window.location = (sApp||{vars:{}}).vars[path] || path;
    } else {
        window.location.replace((sApp||{vars:{}}).vars[path] || path);
    }
}

function libGoToUrl(path, showBusy=true) { _goToUrl(path, true, showBusy); }
function libDownloadByUrl(path) { libGoToUrl(path,false); }

function libDownloadBySubmit(url, elemsObj) {
    let tmpForm = document.createElement('form');
    tmpForm.name = 'file-download-tmp';
    tmpForm.method = 'post';
    tmpForm.enctype = 'multipart/form-data';
    tmpForm.style.display = 'none';
    tmpForm.action = url;
    document.body.appendChild(tmpForm);

    Object.entries(elemsObj).forEach(([key,val])=>{
        let tmpInput = document.createElement('input');
        tmpInput.type = 'hidden';
        tmpInput.name = key;
        tmpInput.value = val;
        tmpForm.appendChild(tmpInput);
    });

    tmpForm.submit();
    tmpForm.remove();
}

function Utf8ArrayToStr(str) {
    let array = Uint8Array.from(str);
    var out, i, len, c;
    var char2, char3;

    out = "";
    len = array.length;
    i = 0;
    while(i < len) {
        c = array[i++];
        switch(c >> 4)
        {
            case 0: case 1: case 2: case 3: case 4: case 5: case 6: case 7:
            // 0xxxxxxx
            out += String.fromCharCode(c);
            break;
            case 12: case 13:
            // 110x xxxx   10xx xxxx
            char2 = array[i++];
            out += String.fromCharCode(((c & 0x1F) << 6) | (char2 & 0x3F));
            break;
            case 14:
                // 1110 xxxx  10xx xxxx  10xx xxxx
                char2 = array[i++];
                char3 = array[i++];
                out += String.fromCharCode(((c & 0x0F) << 12) |
                    ((char2 & 0x3F) << 6) |
                    ((char3 & 0x3F) << 0));
                break;
        }
    }

    return out;
}


async function libLoadFileByUrl(url,data) {
    let fileName = '';
    let fileType = 'application/octet-stream';
    try {
        const response = await fetch(url, {
            method: 'POST', // *GET, POST, PUT, DELETE, etc.
            mode: 'cors', // no-cors, *cors, same-origin
            cache: 'no-cache', // *default, no-cache, reload, force-cache, only-if-cached
            credentials: 'same-origin', // include, *same-origin, omit
            headers: {
                'Content-Type': 'application/json'
                //'Content-Type': 'application/x-www-form-urlencoded',
                //'Content-Type': 'multipart/form-data'
            },
            redirect: 'follow', // manual, *follow, error
            referrerPolicy: 'no-referrer', // no-referrer, *client
            body: JSON.stringify(data) // body data type must match "Content-Type" header
        });
        if (response.ok) { // если HTTP-статус в диапазоне 200-299
            const mimeType = response.headers.get('content-type');
            //'application/json; charset=utf-8'
            let hdr = response.headers.get("content-disposition");
            if(hdr) { // це точно файл
                fileName = hdr.match(/filename\*="?([^";]+)[";]?/);
                if (fileName && fileName.length === 2) {
                    fileName = fileName[1];
                    if (fileName.indexOf("UTF-8''") === 0 || fileName.indexOf("utf-8''") === 0) {
                        fileName = fileName.slice(7);
                    }
                } else {
                    fileName = hdr.match(/filename="?([^";]+)[";]?/)[1];
                }
                fileName = decodeURIComponent((fileName + '').replace(/\+/g, '%20'));
                const blob = await response.blob();
                return new File([blob], fileName, {type: mimeType});
            } else { // нежданчик
                if(mimeType.indexOf('application/json') !== -1) { // схоже на коректний формат помилки
                    response.json()
                        .then(r=>{
                            return checkResponseCode([], r['code']||'RC_BAD_RESP_T', r['msg']||'', '');
                        })
                        .then(d=>{ throw new CustomError('ERR_BAD_RESP_T'); })
                        .catch(err => { libCatchError(err); });
                } else { // нє, ну це вже ващє якась дурня неочікувана
                    libOnAjaxFail('ERR_BAD_RESP_T');
                    return null;
                }
            }
        } else {
            libOnAjaxFail(response.statusText);
            return null;
        }
    } catch(error) {
        libOnAjaxFail(error);
        return null;
    }
}

export {
    libGoToUrl,
    libDownloadByUrl,
    libDownloadBySubmit,
    libLoadFileByUrl
};