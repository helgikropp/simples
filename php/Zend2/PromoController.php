<?php
namespace Promo\Controller\Spc;

use Api\Controller\ApiController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use SP\Utils\Lib;
use SP\Pdf\Pdf as SP_Pdf;

class PromoController extends ApiController
{
    /** ========================================================================
     * @return bool|\Zend\Http\Response|JsonModel
     * @throws \Exception
     */
    public function getApprovalPdfAction() {
        $fail = $this->makeDecision(true,false,true, true,false);
        if($fail) { return $fail; }

        $view = new ViewModel();
        $view->setTerminal(true);

        $outputDestination ='I';
        $postData = file_get_contents('php://input');


        if(!$postData) {
            $postData = $this->params()->fromPost('data','');
            $dt = Lib::deserialize($postData);
            $outputDestination ='D'; 
        } else {
            $dt = json_decode($postData,true);
        }
        $info = [];

        $cp = &$dt['ChangePricePromo'];
        $info['processId']         = $dt['ProcessId']?:'0';
        $info['requestNum']        = !empty($dt['RequestId'])?$dt['RequestId']:'0';
        $info['requestDate']       = $dt['RequestDate'];
        $info['supplierOkpo']      = !empty($dt['SupplierEDRPOU']) ? $dt['SupplierEDRPOU'] : '';
        $info['supplierName']      = !empty($dt['SupplierName']) ? trim($dt['SupplierName']) : '';
        $info['supplierShortName'] = trim($dt['SupplierShortName'] ?? ($info['supplierName']?:''));
        $info['buyerOkpo']         = !empty($cp['buyerOkpo']) ? $cp['buyerOkpo'] : '';
        $info['buyerName']         = !empty($cp['buyerName'])?trim($cp['buyerName']):'';
        $info['buyerShortName']    = !empty($cp['buyerShortName'])?trim($cp['buyerShortName']):'';
        $info['dateFrom']          = $cp['dateFrom'];
        $info['dateTo']            = $cp['dateTo'];
        $info['contractNum']       = $cp['contractNumber'] ?? '';
        $info['contractDate']      = $cp['contractDate'];
        $info['pushSN']            = $dt['K2ORTaskSN'] ?? '';
        $info['shortName']         = $dt['name'] ?? '';
        $info['isDigiSign']        = $dt['isDigiSign']??false;
        $info['storeEvent']        = $dt['storeEvent']??true;
        $info['signer']            = $dt['signer'] ?? '';
        $items                     = !empty($dt['Lagers']) ?  $dt['Lagers'] : $dt['ChangePricePromo']['Lagers'];

        $info['confidant'] = $dt['confidant'] ?? false;

        if($info['confidant']) {
            $info['docType']  = $info['confidant']['confidantDocType'] === '0' ? 'Статуту' : 'Довіреності № '.$info['confidant']['idPOA'].' від '.$info['confidant']['dateFrom'].' року';
        }

        $cfg = $this->getServiceLocator()->get('config')['signers'];
        $poa = $info['isDigiSign'] ? $cfg['power_of_attorney'] : '___________________________________';
        $fullName = $info['isDigiSign'] ? $info['signer'] : '___________________________________';
        $basis = $info['isDigiSign']  ? $info['docType'] : '___________________________________';
        $signerShortName = $info['isDigiSign'] ? $cfg['signer_short_name'] : '_______________________';
        $signerFullName = $info['isDigiSign'] ? $cfg['signer_full_name'] : '_______________________';
        $shortName = $info['isDigiSign'] ? $info['shortName'] : '_______________________';

        if($info['storeEvent'] && !$info['isDigiSign'] ) {
            $this->model('SP_Docs')->storeEvent('ET_PDF','CHANGEPRICEPROMO', $info['requestNum'],'Subject ID - RequestId');
        }

        function drawTableHeader(SP_Pdf $pdf, &$top) {
            $pdf->setFontCondensed(8);
            $pdf->cellCenter(5.0, $top, 8.0, 7.5, "№\nп/п");
            $pdf->cellCenter(13.0, $top, 25.0, 15.0, 'Штрих-код');
            $pdf->cellCenter(38.0, $top, 20.0, 7.5, "Артикул\n(Фоззі-Код)");
            $pdf->cellCenter(58.0, $top, 25.0, 15.0, 'Код УКТЗЕД');

            $pdf->cellCenter(83.0, $top, 122.0, 15.0, 'Назва товару');
            $pdf->cellCenter(205.0, $top, 15.0, 7.5, "Одиниця\nвиміру");
            $pdf->cellCenter(220.0, $top, 20.0, 5.0, "Ціна\nза одиницю\nбез ПДВ, грн");
            $pdf->cellCenter(240.0, $top, 20.0, 5.0, "Ціна\nза одиницю\nз ПДВ, грн");
            $pdf->cellCenter(260.0, $top, 32.0, 5.0, "Період замовлення\nТовару (з … по …\nвключно)");
            $top = $pdf->GetY();
        }

        function drawTableRow(SP_Pdf $pdf, $top, $idx, $rowData, &$topStart) {
            $pdf->setFontCondensed(7);
            $lineHeight = 5.0;
            $pdf->cellLeft(83.0, $top, 122.0, 5.0, $rowData['lagerName']);
            $deltaY = $pdf->GetY() - $top;
            if($deltaY > 5.0) {
                $lineHeight = $deltaY;
                $topStart += ($lineHeight - 5.0);
            }

            $pdf->cellRight(5.0, $top, 8.0, $lineHeight, $idx);
            $pdf->cellCenter(13.0, $top, 25.0, $lineHeight, $rowData['barcode']);
            $pdf->cellCenter(38.0, $top, 20.0, $lineHeight, $rowData['lagerId']);
            $pdf->cellCenter(58.0, $top, 25.0, $lineHeight, $rowData['listValue']);
            $pdf->cellCenter(205.0, $top, 15.0, $lineHeight, $rowData['lagerUnit']);
            $pdf->cellRight(220.0, $top, 20.0, $lineHeight, number_format($rowData['promoPrice'],5,'.',''));
            $pdf->cellRight(240.0, $top, 20.0, $lineHeight, number_format($rowData['promoPricePdv'],2,'.',''));
            $pdf->cellCenter(260.0, $top, 32.0, $lineHeight, $rowData['dateFrom'].' - '.$rowData['dateTo']);
        }

        $pdf = new SP_Pdf();

        if($outputDestination === 'D') {
            $pdf->SetProtection(['print']);
        }

        $pdf->addFonts();

        $pdf->AddPage('L','a4');

        $pageHeight = 210.0;
        $topDef = 5.0;

        $pdf->setFontCondensed(9);

        $pdf->setBorder(0.01);

        /* === QRCODE ==================== */

        $sumWithoutPdv = 0;
        $prices = [];
        foreach($items as $item) {
            $prices[] = $item;
            $sumWithoutPdv += $item['promoPrice'];
        }
        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        $dateOfPrint = date_format(new \DateTime(), 'd.m.Y H:i:s');
        /** @noinspection PhpFullyQualifiedNameUsageInspection */
        $secretString = '110' 
            . '|xxx' 
            . '|' . $info['yyy'] 
            . '|zzzzz'
            . '|' . $this->getIdentity()->login
            . '|' . count($items)
            . '|' . number_format($sumWithoutPdv,5,'.','')
            . '|' . $dateOfPrint ;

        $qrCode = new \SP\Pdf\QrCode\QrCode($secretString, 'H'); 
        $qrCode->displayFPDF($pdf, 6.0, 6.0, 24);

        /* === HEADER ==================== */

        $pdf->textLeft(250.0, 5.0, 287.0, 6.0, 'До типової форми № ');
        $pdf->textCenter(5.0, 7.0, 287.0, 6.0, 'Додаткова угода');
        $pdf->textCenter(5.0, 13.0, 287.0, 6.0, 'до Договору поставки № ' . $info['contractNum'] . ' від ' . $info['contractDate'] . ' року.');
        $top = $pdf->GetY();
        $pdf->textLeft(32.0, $top, 32.0, 6.0, 'м.Київ');

        if($info['isDigiSign']) {
            $day   = date_format(new \DateTime(), 'd');
            $month = date_format(new \DateTime(), 'm');
            $year  = date_format(new \DateTime(), 'Y');
            $pdf->textLeft(237.0, $top, 90.0, 6.0, ' ' . $day . '.' . $month . '.' . $year . ' р.');
        } else {
            $pdf->textLeft(230.0, $top, 90.0, 6.0, '"_______"___________________20_______р.');
        }
        $pdf->setFontCondensed(6);
        $dateOfPrint = date_format(new \DateTime(), 'd.m.Y H:i:s');
        $pdf->textLeft(32.0, 21.0, 50.0, 7.0, 'Сформовано '.$dateOfPrint);
        $pdf->textLeft(32.0, 25.0, 150.0, 7.0, 'Заявка № '.$info['requestNum'] . ' від ' . substr($info['requestDate'],0,16));


        $pdf->setFontCondensed(8);
        $top = $pdf->GetY()+10.0;

        $str = $info['supplierName'] . ', іменоване надалі "Постачальник", від імені якого на підставі '
            . $basis
            . ' діє '.$fullName.', з однієї сторони, і '
            .  strtoupper($info['buyerName']) . ', іменоване надалі "Покупець", від імені якого на підставі '
            . $poa
            . ' діє '
            . $signerFullName
            . ' з іншої сторони, разом іменовані «Сторони»,'
            . ' уклали дану Додаткову угоду  (далі – Додаткова угода)'
            . ' до Договору поставки № ' . $info['contractNum'] . ' від ' . $info['contractDate'] . ' р. (далі – Договір) про нижченаведене:';
        $pdf->textLeft(5.0, $top, 287.0, 5.0, $str);

        $top = $pdf->GetY()+2.0;

        $str = '1. Незалежно від того, що вказано в Договорі,'
            . ' Сторони дійшли згоди, що ціни на зазначений нижче Товар,'
            . ' який замовляється по Договору в період, вказаний в даній Додатковій угоді, становлять:';
        $pdf->textLeft(5.0, $top, 287.0, 6.0, $str);

        $topStart = $pdf->GetY();
        drawTableHeader($pdf, $topStart);

        $currentRow = 0;
        $idx = 0;
        $rowsLeft = count($prices);
        $bottomMarginOffset = $pageHeight - 5.0;

        $afterTableItemsHeightDef = 35.0;
        $footerHeightDef = 55.0;
        $pageNumHeightDef = 5.0; 

        $afterTableItemsHeight = $afterTableItemsHeightDef;
        $footerHeight = $footerHeightDef;
        $pageNumHeight = $pageNumHeightDef;

        $tableBottomPosTest = $topStart + $rowsLeft * 5.0;

        if($tableBottomPosTest < $bottomMarginOffset - $footerHeight - $afterTableItemsHeight) {
            $pageNumHeight = 0; 
        }

        foreach($prices as $row) {
            ++$currentRow;

            $top = $topStart + 5.0 * $idx;

            $rowBottomPosTest = $top + 5.0;

            if($rowBottomPosTest > $bottomMarginOffset - $pageNumHeight) {
                if($pageNumHeight) {
                    $pdf->textRight(5.0, $bottomMarginOffset - $pageNumHeight, 287.0, $pageNumHeight, "Сторінка " . $pdf->PageNo() . ' / {nb}');
                }

                $pdf->AddPage('L','a4');
                $topStart = $topDef;
                drawTableHeader($pdf, $topStart);
                $idx = 0;
                $top = $topStart + 5.0 * $idx;
                $footerHeight = $footerHeightDef;
                $pageNumHeight = $pageNumHeightDef;
            } elseif($rowBottomPosTest > $bottomMarginOffset - $pageNumHeight - $footerHeight - $afterTableItemsHeight) {
                $footerHeight = 0; 
            }

            $idx++;

            drawTableRow($pdf, $top, $currentRow, $row, $topStart);
            $rowsLeft--;
        }

        $dateFrom = (empty($info['dateFrom']) ? '_______________20_______' : $info['dateFrom']).' р.';
        $dateTo   = (empty($info['dateTo'])   ? '_______________20_______' : $info['dateTo']).' р.';
        $strItems = [
            '2. Інші умови Договору залишаються без змін.',
            '3. Сторони, керуючись положеннями статті 631 Цивільного кодексу України, дійшли згоди, що умови цієї додаткової угоди застосовуються до відносин між ними за Договором поставки, які виникли до дати укладення цієї Додаткової угоди, починаючи з ' . $dateFrom
        ];

        $pdf->setFontCondensed(8);

        $currentRow = 0;
        foreach($strItems as $str) {
            $top = $pdf->GetY();
            $topTest = $top + 6.0 * ($currentRow === 2 ? 2 : 1);

            if($topTest > $bottomMarginOffset - $pageNumHeight) {
                if($pageNumHeight) {
                    $pdf->textRight(5.0, $bottomMarginOffset - $pageNumHeight, 287.0, $pageNumHeight, 'Сторінка ' . $pdf->PageNo() . ' / {nb}');
                }
                $pdf->AddPage('L','a4');
                $top = $topDef;
            }

            $pdf->textLeft(5.0, $top, 287.0, 6.0, $str);
            ++$currentRow;
        }

        $footerHeight = $footerHeightDef;
        $top = $pdf->GetY()+5.0;
        if($top > $bottomMarginOffset - $pageNumHeight - $footerHeight) {
            if($pageNumHeight) {
                $pdf->textRight(5.0, $bottomMarginOffset - $pageNumHeight, 287.0, $pageNumHeight, "Сторінка " . $pdf->PageNo() . ' / {nb}');
            }
            $pdf->AddPage('L','a4');
            $top = $topDef+5.0;
        }

        $pdf->setFontCondensed(9);
        $pdf->textCenter(5.0, $top, 287.0, 6.0, 'ПІДПИСИ СТОРІН:');

        $top = $pdf->GetY()+3.0;
        $pdf->textCenter(10.0, $top, 80.0, 6.0, 'ПОКУПЕЦЬ');
        $pdf->textCenter(200.0, $top, 80.0, 6.0, 'ПОСТАЧАЛЬНИК');

        $top = $pdf->GetY()+5.0;
        $pdf->textCenter(10.0, $top, 80.0, 6.0, $info['buyerShortName']);
        $pdf->textCenter(200.0, $top, 80.0, 6.0, $info['supplierShortName']);

        $top = $pdf->GetY()+5.0;
        $pdf->textCenter(10.0, $top, 80.0, 6.0, '___________________  / '.$signerShortName );
        $pdf->textCenter(200.0, $top, 80.0, 6.0, '___________________  / '.$shortName);

        if($pageNumHeight) {
            $pdf->textRight(5.0, $bottomMarginOffset - $pageNumHeight, 287.0, $pageNumHeight, 'Сторінка ' . $pdf->PageNo() . ' / {nb}');
        }

        $fileName = preg_replace(['/[^\w]+/ui','/_{2,}/'],'_',
           $info['supplierShortName'] . '_' . $info['requestNum'] . '_АП');
        $pdf->Output($fileName.'.pdf',$outputDestination);

        return false;
    }

}
