import {libRegExpEscape} from "../helpers/libStrUtils.mjs";

/**
 * for Bootstrap 3 modal
 */
export class SpModalWin {
    constructor(id, classes = '') {
        this._elWin = document.getElementById(id);
        if (this._elWin) {
            this._elDlg     = this._elWin.querySelector('.modal-dialog');
            this._elContent = this._elDlg.querySelector('.modal-content');
        } else {
            this._elWin = document.createElement('div');
            this._elWin.id = id || 'modal-' + Date.now(); //TODO проверить на уникальность
            this._elWin.setAttribute('aria-hidden','true');
            this._elWin.dataset['backdrop'] = 'static';
            this._elWin.style.display = 'none';
            this._elWin.classList.add('modal','fade');
            const place = document.getElementById('dialogs-place') || document.body;
            place.appendChild(this._elWin);

            this._elDlg = document.createElement('div');
            this._elDlg.classList.add('modal-dialog','sp-modal-content');
            if(classes && classes.length){
                if(Array.isArray(classes)) {
                    this._elDlg.classList.add(...classes);
                } else {
                    this._elDlg.classList.add(...(classes.trim().replace(new RegExp('[, ]+','g'),',').split(',')));
                }
            }
            this._elWin.appendChild(this._elDlg);

            this._elContent = document.createElement('div');
            this._elContent.classList.add('modal-content');
            this._elDlg.appendChild(this._elContent);
        }
        this._elTitle = this._elContent.querySelector('.modal-title');
        this._$win = $(this._elWin);
    }

    get win() { return this._elWin; }

    getElement(queryStr)  { return this._elWin.querySelector(queryStr); }
    getElements(queryStr) { return this._elWin.querySelectorAll(queryStr); }

    setContent(data){
        //while (this._elDlg.firstChild) { this._elDlg.removeChild(this._elDlg.firstChild); }
        this._elContent.remove();
        if (data instanceof Element || data instanceof Document) {
            this._elDlg.appendChild(data);
        } else if(typeof data === 'string') {
            this._elDlg.insertAdjacentHTML('beforeend', data);
        }
        this._elContent = this._elDlg.querySelector('.modal-content');
        this._elTitle   = this._elContent.querySelector('.modal-title');
        return this;
    }

    setTitle(title){ this._elTitle.innerHTML = title; return this; }
    show(){ this._$win.modal('show');  return this;}
    hide(){ this._$win.modal('hide');  return this;}
}
