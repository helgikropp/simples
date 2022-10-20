import {libAlertOnError} from "./libMessages.mjs";
import {libGoToUrl} from "./libUrl.mjs";

const acceptedMimeTypes = {
    txt : 'text/plain,text/csv',
    sheet : 'application/vnd.ms-office,application/vnd.oasis.opendocument.spreadsheet,application/vnd.ms-excel,application/vnd.ms-excel.sheet.macroenabled.12,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    doc : 'application/pdf,application/rtf,application/msword,application/vnd.oasis.opendocument.text,application/vnd.ms-office,vnd.ms-word.document.macroenabled.12,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    img : 'image/bmp,image/x-windows-bmp,image/gif,image/jpeg,image/pjpeg,image/png,image/x-tiff,image/tiff',
    arc : 'application/zip,application/x-zip,application/x-zip-compressed,application/gzip,application/x-rar-compressed,application/x-7z-compressed'
};


export function libObjToFormData(obj) {
    return Object.keys(obj)
        .reduce(
            (formData, key) => {
                formData.append(key, obj[key]);
                return formData;
            }, new FormData()
        );
}

export function detectFormOrElem(formOrElem) {
    if(!formOrElem) {
        return document.body;
    }
    if(typeof formOrElem === 'string') {
        if(formOrElem.indexOf('#') !== 0) {
            return document.querySelector('#' + formOrElem);
        }
        return document.querySelector(formOrElem);
    }

    return formOrElem;
}

export function libResetFileInput(fileInput) {
    const tmpForm = document.createElement('form');
    const parent = fileInput.parentNode;
    parent.insertBefore(tmpForm,fileInput);
    tmpForm.appendChild(fileInput);
    tmpForm.reset();
    parent.insertBefore(fileInput,tmpForm);
    tmpForm.remove();
}

export function libLoadFileXls(cbOnChange) {
    let fi = document.getElementById('tmp-import-xls');
    if(fi) {
        fi.remove();
        fi = null;
    }

    fi = document.createElement('input');
    fi.type = 'file';
    fi.id = 'tmp-import-xls';
    fi.name = 'tmp_import_xls';
    fi.classList.add('sp-tmp-input-file');
    fi.setAttribute('size','0');
    fi.setAttribute('accept', 'application/vnd.oasis.opendocument.spreadsheet,'
        + 'application/vnd.ms-excel,'
        + 'application/vnd.ms-office,'
        + 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    fi.addEventListener('change',function(e){
        cbOnChange(e);
        e.target.remove();
        fi = null;
    });

    document.body.append(fi);

    fi.dispatchEvent(new MouseEvent('click', {
        view: window,
        bubbles: true,
        cancelable: true
    }));
}

export function libLoadFile(cbOnChange, types = []) {
    let fi = document.getElementById('tmp-import-xls');
    if(fi) {
        fi.remove();
        fi = null;
    }

    let accept = '';
    types.forEach((item/*,i,arr*/)=>{
        accept += (accept?',':'') + acceptedMimeTypes[item];
    });

    let acceptArr = accept.split(',');

    fi = document.createElement('input');
    fi.type = 'file';
    fi.id   = 'tmp-file';
    fi.name = 'tmp_file';
    fi.classList.add('sp-tmp-input-file');
    fi.setAttribute('size','0');
    fi.setAttribute('accept', accept);

    fi.addEventListener('change',function(e){
        let fileType = e.target.files[0].type;
        if(acceptArr.indexOf(fileType) === -1) {
            libAlertOnError(__T('ERR_WRONG_MIME_T', fileType));
        } else {
            cbOnChange(e);
        }
        e.target.remove();
        fi = null;
    });

    document.body.append(fi);

    fi.dispatchEvent(new MouseEvent('click', {
        view: window,
        bubbles: true,
        cancelable: true
    }));
}


