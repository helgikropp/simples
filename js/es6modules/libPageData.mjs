import {_T} from './libI18n.mjs';

export function libBuildTemplatesList() {
    let res = {};
    [...document.querySelectorAll('template[data-tpl]')]
        .forEach((tpl)=>{
            res[tpl.dataset['tpl']] = tpl;
            tpl.remove(); 
        });
    return res;
}

