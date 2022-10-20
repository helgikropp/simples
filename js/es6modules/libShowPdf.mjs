import {libAlertOnError} from "./libMessages.mjs";
import {libBusyStart, libBusyStop} from "./libBusy.mjs";
import {libGetFileExt} from "./libFile.mjs";
import {EUVerifySign} from "./../lib/iit/euverifysign.mjs"

export async function libShowSignZip(file,opts=null){
    if(typeof file !== 'object' && !file.name) {
        return libAlertOnError(_T('ERR_WRONG_FILE_DT'));
    }

    if(file.type === 'application/zip') {
        async function modifyPdf(pdfName, pdfBytes, keysInfo) {
            const { degrees, PDFDocument, rgb/*, StandardFonts*/ } = PDFLib;
            const pdfDoc = await PDFDocument.load(pdfBytes, { ignoreEncryption: true });

            const fontBytes = await fetch('/css/fonts/DejaVuSans.ttf').then((res) => res.arrayBuffer());
            const fontBytesCond = await fetch('/css/fonts/DejaVuSansCondensed.ttf').then((res) => res.arrayBuffer());
            const fontBytesBold = await fetch('/css/fonts/DejaVuSans-Bold.ttf').then((res) => res.arrayBuffer());
            pdfDoc.registerFontkit(fontkit);
            const customFont = await pdfDoc.embedFont(fontBytes);
            const customFontCond = await pdfDoc.embedFont(fontBytesCond);
            const customFontBold = await pdfDoc.embedFont(fontBytesBold);
            const textSize = 7;

            const pages = pdfDoc.getPages();

            let opt = {
                x: 0,
                y: 0,
                size: textSize,
                font: customFont,
                color: rgb(0, 0, 0),
                rotate: degrees(0),
            };
            let box = [0.0,0.0, 0.0,0.0];

            pages.forEach((page)=> {
                const { width, height } = page.getSize();
                const rightStampOffset = width > height ? 510 : width - 210;

                if (keysInfo['key2'] && keysInfo['key2']['verified']) {

                    box = [10.0,10.0, 210.0,75.0];

                    opt['color'] = rgb(1/255, 150/255, 13/255);
                    page.drawRectangle({
                        borderColor:opt['color'],
                        borderWidth:1,
                        height:65,
                        width:200,
                        x:10,
                        y:10});
                    opt['x'] = 10.0;
                    opt['y'] = 75.0;

                    opt.x += 10;
                    opt.y -= 17;
                    page.drawText(keysInfo['key2']['info'].subjFullName, opt);
                    opt.y -= 11;
                    opt.font = customFontBold;
                    page.drawText('ЄДРПОУ/ІПН: '+(keysInfo['key2']['info'].subjEDRPOUCode || keysInfo['key2']['info'].subjDRFOCode), opt);
                    opt.y -= 11;
                    opt.font = customFontCond;
                    page.drawText('ЕЦП: '+keysInfo['key2']['info'].serial, opt);
                    opt.y -= 11;
                    opt.font = customFont;
                    page.drawText(keysInfo['key2']['info'].time, opt);
                }

                if (keysInfo['key1'] && keysInfo['key1']['verified']) {
                    box = [510.0,10.0, 710.0,75.0];

                    opt['color'] = rgb(0, 79/255, 198/255);
                    page.drawRectangle({
                        borderColor:opt['color'],
                        borderWidth:1,
                        height:65,
                        width:200,
                        x:rightStampOffset,
                        y:10});
                    opt['x'] = rightStampOffset;
                    opt['y'] = 75.0;

                    opt.x += 10;
                    opt.y -= 17;
                    page.drawText(keysInfo['key1']['info'].subjFullName, opt);
                    opt.y -= 11;
                    opt.font = customFontBold;
                    page.drawText('ЄДРПОУ/ІПН: '+(keysInfo['key1']['info'].subjEDRPOUCode || keysInfo['key1']['info'].subjDRFOCode), opt);
                    opt.y -= 11;
                    opt.font = customFontCond;
                    page.drawText('ЕЦП: '+keysInfo['key1']['info'].serial, opt);
                    opt.y -= 11;
                    opt.font = customFont;
                    page.drawText(keysInfo['key1']['info'].time, opt);
                }
            });

            // Serialize the PDFDocument to bytes (a Uint8Array)
            const newPdfBytes = await pdfDoc.save();

            // Trigger the browser to download the PDF document
            let blob = new Blob([newPdfBytes], {type: 'application/pdf'});
            let blobURL = URL.createObjectURL(blob);

            window.open(blobURL, pdfName);
        }


        async function tryToShow(pdfName,pdfBlob,keysInfo) {
            const pdfFile = new File([pdfBlob],pdfName,{type:'application/octet-stream'});

            let cnt = Object.keys(keysInfo).length;
            if(cnt) {
                Object.entries(keysInfo).forEach(([key,val])=>{
                    (new EUVerifySign()).verifyFile(
                        pdfFile,
                        val['file'],
                        (signInfo) => {
                            keysInfo[key]['verified'] = true;
                            keysInfo[key]['info'] = Object.assign({},signInfo.ownerInfo);
                            //Fri Jul 03 2020 14:45:14 GMT+0300 (за східноєвропейським літнім часом) {}
                            const timeStamp = (signInfo.timeInfo.time || signInfo.timeInfo.signTimeStamp).toString();
                            keysInfo[key]['info']['time'] = signInfo.timeInfo.isTimeAvail && signInfo.timeInfo.isTimeStamp
                                ? moment(timeStamp.replace('GMT',''),'ddd MMM DD YYYY HH:mm:ss ZZ','en').format('DD.MM.YYYY HH:mm:ss')
                                : '';

                            keysInfo[key]['info']['signTimeStamp'] = keysInfo[key]['info']['time'];

                            --cnt;
                            if(!cnt && pdfBlob){
                                modifyPdf(pdfName, pdfBlob, keysInfo);
                                libBusyStop();
                            }
                        },
                        (/*result*/) => {
                            keysInfo[key]['verified'] = false;
                            --cnt;
                            if(!cnt && pdfBlob){
                                modifyPdf(pdfName, pdfBlob, keysInfo);
                                libBusyStop();
                            }
                        });
                });
            }
        }

        libBusyStart();

        let new_zip = new JSZip();

        let pdfBlob = null;
        let pdfName = '';
        let keysInfo = {};

        new_zip.loadAsync(file)
            .then(async function(zip) {
                let filesCount = Object.keys(zip.files).length;
                zip.forEach((relativePath, zipEntry) => {  // 2) print entries
                    const ext = libGetFileExt(zipEntry.name);
                    if(ext === 'pdf') {
                        zip.file(zipEntry.name).async("uint8array").then(function (data) {
                            // data is Uint8Array { 0=72, 1=101, 2=108, more...}
                            pdfBlob = data;
                            pdfName = zipEntry.name;
                            filesCount--;
                            if(!filesCount){
                                tryToShow(pdfName,pdfBlob, keysInfo);
                            }
                        });

                    } else if(ext === 'json') {
                        filesCount--;
                       if(!filesCount){
                           tryToShow(pdfName,pdfBlob, keysInfo);
                       }

                    } else { //p7s
                        const p7sName = zipEntry.name.substr(0,zipEntry.name.length-ext.length-1);
                        if(p7sName.substr(0,3) === 'key') { //це підпис
                            zip.file(zipEntry.name).async("uint8array").then(function (data) {
                                // data is Uint8Array { 0=72, 1=101, 2=108, more...}
                                keysInfo[p7sName] = {
                                    file: new File([data], zipEntry.name, {type: 'application/octet-stream'}),
                                    //type: opts[p7sName],
                                    info: {}
                                };
                                filesCount--;
                                if (!filesCount) {
                                    tryToShow(pdfName, pdfBlob, keysInfo);
                                }
                            });
                        } else { //це підписаний файл з підписом
                            //
                        }
                    }
                });
            });
    } else  {
        libAlertOnError('ERR_FILE_NOT_ZIP');
    }

}

export const libShowPdf = async function(data, nameOriginal) {
    const { PDFDocument } = PDFLib;
    const pdfName = nameOriginal;
    const pdfDoc = await PDFDocument.load(data.file, { ignoreEncryption: true });
    const newPdfBytes = await pdfDoc.save();

    // Trigger the browser to download the PDF document
    const blob = new Blob([newPdfBytes], {type: 'application/pdf'});
    const blobURL = URL.createObjectURL(blob);

    libBusyStop();
    window.open(blobURL, pdfName);
};