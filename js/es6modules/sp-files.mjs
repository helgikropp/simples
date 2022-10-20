/*
options
    listIdAttr
    iconDownload
    multiple
    typeFieldVisibility
    dateFieldVisibility
    onAppend
    onRemove
 */
import {libHtmlAsDom} from '../helpers/libDom.mjs';
import {libGetUniqueNumberStr, libToCamelCase} from '../helpers/libStrUtils.mjs';
import {libAlertOnError} from '../helpers/libMessages.mjs';
import {_T} from "../helpers/libI18n.mjs";

export class SPFiles {
    constructor(/*Array*/ acceptedTypes = [], /*Integer*/ maxSizeMb = 0, options={}) {
        this._maxSize = maxSizeMb;
        this._acceptedTypes = acceptedTypes;

        this._inputName = 'sp-tmp-file';
        this._listClass = 'sp-attachments-list';
        this._listMarkerClass = 'sp-fa-attachment';

        this._listIdStr = options['listIdAttr']===undefined ? 'attachments-list' : options['listIdAttr'];
        this.isButtonTemplate = options['isButtonTemplate'] === true ? true : false;

        this._showButtonDelete = options['iconDelete'] === true ? '' : 'hidden';
        this._showButtonView = options['iconView'] === true ? '' : 'hidden';
        this._showButtonDownload =  options['iconDownload'] === true ? '' : 'hidden';
        this._multiple = options['multiple'] === undefined ? null : 'multiple';
        this._showTypeField = options['typeFieldVisibility']===undefined ? true : options['typeFieldVisibility'];
        this._showDateField = options['dateFieldVisibility']===undefined ? true : options['dateFieldVisibility'];
        this._onAppend = options['onAppend'] && typeof options['onAppend'] === 'function' ? options['onAppend'] : null;
        this._onRemove = options['onRemove'] && typeof options['onRemove'] === 'function' ? options['onRemove'] : null;
        this._onView = options['onView'] && typeof options['onView'] === 'function' ? options['onView'] : null;

        this._mimeTypes = {
            txt   : 'text/plain,text/csv',
            sheet : 'application/vnd.ms-office,application/vnd.oasis.opendocument.spreadsheet,application/vnd.ms-excel,application/vnd.ms-excel.sheet.macroenabled.12,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            doc   : 'application/pdf,application/rtf,application/msword,application/vnd.oasis.opendocument.text,application/vnd.ms-office,vnd.ms-word.document.macroenabled.12,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            img   : 'image/bmp,image/x-windows-bmp,image/gif,image/jpeg,image/pjpeg,image/png,image/x-tiff,image/tiff',
            arc   : 'application/zip,application/x-zip,application/x-zip-compressed,application/gzip,application/x-rar-compressed,application/x-7z-compressed'
        };

        const rowSpan = 8 + (this._showButtonDownload!=='none'?0:1) + (this._showButtonDelete!=='none'?0:1) + (this._showButtonView!=='none'?0:1);


        const buttonsGroup = this.isButtonTemplate
            ?`<button class="btn btn-default btn-sm view ${this._showButtonView}" data-type="view" title="${_T('G_VIEW')}">
                <i class="fa fa-eye" aria-hidden="true" style="font-size: 15px"></i>
            </button>
            <button class="btn btn-default btn-sm download ${this._showButtonDownload}" data-type="download" title="${_T('G_DL_ATTACH')}">
                <i class="fa sp-fa-save" aria-hidden="true" style="font-size: 15px"></i>
            </button>
            <button class="btn btn-default btn-sm delete ${this._showButtonDelete}" data-type="delete" title="${_T('G_DEL')}">
               <i class="fa sp-fa-delete" aria-hidden="true" style="font-size: 15px"></i>
            </button>`
            : `<a class="sp-action view ${this._showButtonView}" data-type="view" title="${_T('G_VIEW')}" data-id="">
                    <i class="fa fa-eye" aria-hidden="true"></i>
                </a>
                <a class="sp-action download ${this._showButtonDownload}" data-type="download" title="${_T('G_DL_ATTACH')}" data-id="">
                    <i class="fa sp-fa-save" aria-hidden="true"></i>
                  </a>
                <a class="sp-action remove ${this._showButtonDelete}" data-type="delete" title="${_T('G_DEL')}" data-id="">
                    <i class="fa sp-fa-delete" aria-hidden="true"></i>
                </a>`;

        this._listItemTpl =
            `<li class="col-sm-12" title="" data-id="" data-name="">
                <div class="col-sm-1 sp-text-center" data-type="marker" style="padding-left:2px;padding-right:2px;">
                    <i class="fa" data-type="marker" aria-hidden="true"></i>
                </div>
                <div class="col-sm-${rowSpan}" data-type="txt"  style="height: 20px;line-height: 20px;">
                    <div class="sp-ellipsis" data-type="type"></div>
                    <div class="sp-ellipsis" data-type="date" style="display:inline-block"></div>
                    <div class="sp-ellipsis" data-type="name" style="display:inline-block"></div>
                </div>
                <div class="col-sm-3 sp-text-center" data-type="btn" style="padding-left:2px;padding-right:10px; display: flex; flex-direction: row;justify-content: flex-end;">
                    ${buttonsGroup}
                </div>
            </li>`;

        this.reset();
    }

    reset() {
        this._files = [];
        this._filesOld = [];
        this._filesDel = [];
        this._$lastListItem = null; //$(_self._listItemTpl);
        this._lastFileData = null;

        this._$list = document.getElementById(this._listIdStr);
        this._$list.innerHTML = '';
        this._$list.classList.add(this._listClass);

        this._acceptedMimes = this._acceptedTypes.map(t=>(this._mimeTypes[t])).join(',').split(',');

        this._fileInputTpl =
            `<input name="${this._inputName}" id="${this._inputName}" size="0" type="file" ${this._multiple} accept="${this._acceptedMimes.join(',')}" style="width:0!important;z-index:-60000!important;display:none!important;">`;

        this._$fileInputTpl = libHtmlAsDom(this._fileInputTpl);
        this._$listItemTpl  = libHtmlAsDom(this._listItemTpl);

        return this;
    }

    /**
     * Заставить объект вызвать диалог загрузки файла с диска
     * @param {string|null} type - какой-то свой логический "тип файла", в каждом случае словарь можно придумать свой (записка, рапорт,...)
     *                      (выводится в списке если options['typeFieldVisibility'] === true)
     * @param {string|null} date - дата создания файла (выводится в списке если options['dateFieldVisibility'] === true)
     * @param filesToAdd
     * @param {function|null} cbOnLoad
     * @returns {boolean}
     */
    load(type = null, date = null, filesToAdd = null, cbOnLoad = null) {
        this._$lastListItem = null;
        this._lastFileData = null;

        const testFileInput = document.getElementById('test-file-input');
        if(testFileInput && testFileInput.files.length ) {
            const f = testFileInput.files[0];
            if(this._validate(f)) {
                this.add(f, type, date);
                if(cbOnLoad) { cbOnLoad(f, type, date); }
            }
            return true;
        } else {
            let fileItem = this._$fileInputTpl.cloneNode(true);
            document.body.appendChild(fileItem);

            fileItem.addEventListener('change',e => {
                if(this._multiple !== null) {
                    const files = e.currentTarget.files;
                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        if(this._validate(file)) {
                            this.add(file, type, date, 0, filesToAdd, cbOnLoad);
                        }
                    }

                } else {
                    const f = e.currentTarget.files[0];
                    if(this._validate(f)) {
                        this.add(f, type, date, 0, filesToAdd, cbOnLoad);
                    }
                }
                e.currentTarget.remove();
            });
            fileItem.dispatchEvent(new MouseEvent('click'));
            return !!this._$lastListItem;
        }
    }

    _validate(file) {
        let result = false;
        if(this._maxSize && file.size > this._maxSize * 1024 * 1024) {
            libAlertOnError(_T('ERR_FILE_OVERSIZED', null, [this._maxSize]));
        } else if(this._acceptedMimes.indexOf(file.type) === -1) {
            libAlertOnError('ERR_WRONG_MIME_T', file.type);
        } else if(this.exists(file)) {
            libAlertOnError('ERR_FILE_ALREADY_ADDED');
        } else {
            result = true;
        }
        return result;
    }

    /**
     *
     * @param {object} file - объект ФАЙЛ, может быть вытянут из <INPUT TYPE="file"> или создать на лету, минимально: {id:, name:}
     * @param {string} type - какой-то свой логический "тип файла", в каждом случае словарь можно придумать свой (записка, рапорт,...)
     *                      (выводится в списке если options['typeFieldVisibility'] === true)
     * @param {string} date - дата создания файла (выводится в списке если options['dateFieldVisibility'] === true)
     * @param {number} src  - признак "откуда взялся файл",  0 - from OS, 1 - from DB
     */

    add(file, type, date, src=0, filesToAdd = null, cbOnLoad= null) { /* origin: 0 - from OS, 1 - from DB, */
        if(filesToAdd && Object.keys(filesToAdd).length) {
            for (let key in filesToAdd) {
                if(filesToAdd[key]['Name'] === file.name) {
                    libAlertOnError('G_FILE_NAME_EXISTS');
                    return;
                }
            }
        }

        (src ? this._filesOld : this._files).push(file);
        this._lastFileData = file;
        let $li = this._$listItemTpl.cloneNode(true);
        $li.dataset['name']  = file['name'];
        $li.dataset['size']  = file['size'];
        $li.dataset['mime']  = file['type'] || '';
        $li.dataset['src']   = src;
        $li.dataset['id']    = file['id'] || libGetUniqueNumberStr() || '';
        $li.dataset['uid']   = file['uid'] || '';
        $li.dataset['doctype'] = file['doctype'] || '';
        $li.dataset['docid'] = file['docid'] || '';
        $li.dataset['path'] = file['path'] || '';
        $li.dataset['nameunique'] = file['nameunique'] || '';
        $li.querySelector('div[data-type="name"]').innerHTML = file['name'];

        let $el = $li.querySelector('div[data-type="type"]');
        if(!this._showTypeField) {
            $el.remove();
        } else if(type) {
            $el.innerHTML = type.toString();
        }
        $el = $li.querySelector('div[data-type="date"]');
        if(!this._showDateField) {
            $el.remove();
        } else if(date) {
            $el.innerHTML = date.toString();
        } else {
            $el.innerHTML = file.date.toString();
        }

        $el = $li.querySelector('.fa[data-type="marker"]');

        $el.className = 'fa';
        this._listMarkerClass.split(' ').forEach((cl1=>{
            cl1.split(',').forEach((cl=>{
                $el.classList.add(cl.trim());
            }));
        }));

        this._$list.appendChild($li);
        this._$lastListItem = $li;
        if(this._onAppend) { this._onAppend(); }
        if(cbOnLoad) { cbOnLoad(file); }
    }

    exists(fileNew) {
        return (this._files.filter(f => (f.name === fileNew.name && f.size === fileNew.size && f.type === fileNew.type)).length > 0);
    }

    /**
     *
     * @param $fLi - element <LI> списка файлов
     * @param callback
     */
    remove($fLi, callback = null) {
        const fLi = ($fLi instanceof jQuery) ? $fLi[0] : $fLi;
        const f = {
            name :  fLi.dataset['name'],
            size : +fLi.dataset['size'],
            mime :  fLi.dataset['mime'],
            src  : +fLi.dataset['src'],
            id   : fLi.dataset['id']
        };
        this._filesDel.push(f.id);
        if(f.src) {
            this._filesOld.splice(this._filesOld.indexOf(f.id), 1);
        } else {
            this._files.splice(this._files.findIndex(it=>(it.name===f.name && it.size===f.size && it.type===f.mime)), 1);
        }

        fLi.remove();
        if(this._onRemove) { this._onRemove(); }
        if(callback) { callback(); }
    }

    getFiles()     { return this._files; };
    get files()    { return this._files; };
    get lastItem() { return this._$lastListItem; };
    get lastFile() { return this._lastFileData; };
    get filesNew() { return this._files; };
    get filesOld() { return this._filesOld; };
    get filesDel() { return this._filesDel; };

    setMarkerClass(classStr) { this._listMarkerClass = classStr; return this; };
    setTypeFieldVisibility(isVisible) { this._showTypeField = isVisible; return this; };
    setDateFieldVisibility(isVisible) { this._showDateField = isVisible; return this; };
    addItemExtraData(/*Object*/ data) {
        if(this._$lastListItem) {
            for(const tag in data) { this._$lastListItem.dataset[libToCamelCase(tag)] = data[tag]; }
        }
    }

    showButtonDelete(isShow) {
        this._showButtonDelete = isShow ? '' : 'hidden';
    }

    download($fLi, callback = null){
        const fLi = ($fLi instanceof jQuery) ? $fLi[0] : $fLi;
        const f = {
            name  :  fLi.dataset['name'],
            size  : +fLi.dataset['size'],
            mime  :  fLi.dataset['mime'],
            src   : +fLi.dataset['src'],
            id    : +fLi.dataset['id'],
            docid : +fLi.dataset['docid'],
        };

        if(!f.src) {
            let URL = window.URL || window.webkitURL,
                imageUrl;
            if (URL) {
                const file = this._files.findIndex(it => (it.name === f.name && it.size === f.size && it.type === f.mime));
                imageUrl = URL.createObjectURL(this._files[file]);

                const link = document.createElement('a');
                link.setAttribute('href', imageUrl);
                link.setAttribute('download', f.name);
                link.click();
                return false;
            }
        } else {
            if(callback) {
                const data = {
                    FileEntityTypeId : f.docid,
                    FileHash : String(f.id)
                };
                callback(data);
            }
        }
    }
}

