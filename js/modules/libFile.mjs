export function libBase64ToUint8Arr(base64) {
    const raw = window.atob(base64);
    const rawLength = raw.length;
    let array = new Uint8Array(new ArrayBuffer(rawLength));

    for(let i = 0; i < rawLength; i++) {
        array[i] = raw.charCodeAt(i);
    }
    return array;
}

export function libBase64ToBlob(base64, mimetype = '', slicesize = 512) {
    let bytechars = window.atob(base64);
    let bytearrays = [];
    for (let offset = 0; offset < bytechars.length; offset += slicesize) {
        let slice = bytechars.slice(offset, offset + slicesize);
        let bytenums = new Array(slice.length);
        for (let i = 0; i < slice.length; i++) {
            bytenums[i] = slice.charCodeAt(i);
        }
        bytearrays[bytearrays.length] = new window.Uint8Array(bytenums);
    }
    return new window.Blob(bytearrays, {type: mimetype});
}

export function libBase64ToFile(base64content, mimeType, fileName) {
    const a = document.createElement('a');
    const blob = libBase64ToBlob(base64content, mimeType);
    saveAs(blob, fileName); /* FileSaver.js*/
}

export function libBase64ToFileBlob(base64content, mimeType, fileName) {
    const a = document.createElement('a');
    const blob = libBase64ToBlob(base64content, mimeType);
    return new File([blob], fileName, { type: blob.type });
}

export function libStrToFileBlob(contentStr, mimeType, fileName) {
    return new File([contentStr], fileName, { type: mimeType });
}

export function libGetFileExt(filename,withPoint=false){
    const ext = filename.split('/').pop().split('.').pop();
    return [ext,'.'+ext].includes(filename) ? '' : (withPoint ? '.' : '') + ext;
}

export function libCutFileExt(filename){
    const ext = libGetFileExt(filename);
    return ext ? filename.replace(new RegExp('.'+ ext + '$','g'),'') : filename;
}

export function libFileToBase64(file) {
    return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
    });
}

export async function libUint8ArrToBase64(data) {
    // Use a FileReader to generate a base64 data URI
    const base64url = await new Promise((r) => {
        const reader = new FileReader()
        reader.onload = () => r(reader.result)
        reader.readAsDataURL(new Blob([data]))
    })
    /*
    The result looks like
    "data:application/octet-stream;base64,<your base64 data>",
    so we split off the beginning:
    */
    return base64url.split(",", 2)[1];
}

export async function libUint8ArrToBase64Alt(data) {
    const base64url = await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(reader.result);
        reader.onerror = error => reject(error);
        reader.readAsDataURL(new Blob([data]));
    });
    return base64url;
}

export async function libBlobToUint8Arr(objBlobOrFile) {
    return new Uint8Array(await objBlobOrFile.arrayBuffer());
}
