import { libLoadFileByUrl } from './libUrl.mjs';

export const libSetFilePdf = function({ url, data }) {
    return new Promise((resolve, reject) => {
        libLoadFileByUrl(url, data)
            .then((file)=>{
                if(file) {
                    resolve(file);
                }
            })
            .catch(err => {
                reject(new Error(_T(err.msg)));
            });
    });
};

export const libSetFileZip = function(file) {
    return new Promise((resolve, reject) => {
        if(file.length) {
            resolve(file[0]);
        } else {
            // Файл не загрузили или он не менялся
            resolve();
        }
    });
};


