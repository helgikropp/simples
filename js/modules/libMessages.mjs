/* global Swal (SweetAlert) */
import {_T} from './libI18n.mjs';

let _errorMutex = false;

export function libConfirm(msg=null, confirmButtonText = 'G_YES') {
    const opt = {
        title: _T('MSG_TITLE_CONFIRM'),
        html: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#DD6B55',
        confirmButtonText,
        cancelButtonText:  _T('G_NO'),
        reverseButtons: false,
        focusCancel: true,
        onOpen: ()=>{  }
    };

    return  Swal.fire(opt)
        .then((result)=>{
            if (result.value) { 
                return new Promise((resolve,reject)=>{resolve(true);}); 
            }
            else if (result.dismiss === Swal.DismissReason.cancel) { 
                return new Promise((resolve,reject)=>{resolve(false);}); 
            }
        });
}

export async function libConfirmAsync(msg=null, confirmButtonText = 'G_YES') {
    try {
        return await Swal.fire({
            title: _T('MSG_TITLE_CONFIRM'),
            html: libMsgVarToStr(msg),
            //type: 'warning', //deprecated
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: libMsgVarToStr(confirmButtonText),
            cancelButtonText:  _T('G_NO'),
            reverseButtons: false,
            focusCancel: true,
            onOpen: ()=>{ libBusyStop(); }
        }).then((result)=>{
                if (result.value) { return new Promise((resolve,reject)=>{resolve(true);}); }
                else if (result.dismiss === Swal.DismissReason.cancel) { return new Promise((resolve,reject)=>{resolve(false);}); }
            });
    } catch (e) {
        console.log('error:', e);
        return false;
    }
}

