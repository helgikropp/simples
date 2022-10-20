<?php
namespace Contract\Controller\Spc;

use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

use SP\Utils\Lib;

/* ====================================================================== */
/* === API ============================================================== */
/* ====================================================================== */
class ContractController extends \Api\Controller\ApiController
{
    /** ========================================================================
     * @return bool|JsonModel
     * @throws \PHPExcel_Exception
     * @throws \PHPExcel_Reader_Exception
     * @throws \PHPExcel_Writer_Exception
     * @throws \Exception
     */
    public function exportToXlsAction()
    {
        $fail = $this->checkRequest(true,false,false, true,false);
        if($fail) { return $fail; }

        /** @var \PHPExcel $xls */
        /** @var \PHPExcel_Worksheet $sheet */
        (new ViewModel())->setTerminal(true);

        $items = json_decode($this->params()->fromPost('lagers',''));

        // Create new PHPExcel object
        $xls = new \PHPExcel();

        // Set document properties
        $xls->getProperties()->setCreator('Portal')
            ->setLastModifiedBy('Portal')
            ->setTitle('Price list')
            ->setSubject('Price list')
            ->setDescription('Price list')
            ->setKeywords('price list')
            ->setCategory('print form');

        $xls->getDefaultStyle()->getFont()->setName('Arial')->setSize(10);

        $sheet = $xls->getActiveSheet()->setTitle('New contract\'s lagers');

        $sheet->getColumnDimension('A')->setWidth(10.0);
        $sheet->getColumnDimension('B')->setWidth(15.0);
        $sheet->getColumnDimension('C')->setWidth(15.0);
        $sheet->getColumnDimension('D')->setWidth(10.0);
        $sheet->getColumnDimension('E')->setWidth(5.0);
        $sheet->getColumnDimension('F')->setWidth(100.0);

        $hdrArr = [
            'font'    => [ 'bold' => true ],
            'borders' => [ 'allborders' => [ 'style' => \PHPExcel_Style_Border::BORDER_THIN ] ],
            'alignment' => [
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical' => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrap'          => TRUE
            ],
        ];
        $rowArr = [
            'borders' => [ 'allborders' => [ 'style' => \PHPExcel_Style_Border::BORDER_THIN ] ],
            'alignment' => [
                'horizontal' => \PHPExcel_Style_Alignment::HORIZONTAL_CENTER,
                'vertical'   => \PHPExcel_Style_Alignment::VERTICAL_CENTER,
                'wrap'       => TRUE
            ],
        ];

        $sheet->getStyle('A1:F1')->applyFromArray($hdrArr);

        $sheet->setCellValue('A1', 'Артикул');
        $sheet->setCellValue('B1', 'Штрих-код');
        $sheet->setCellValue('C1', 'Ціна без ПДВ, грн');
        $sheet->setCellValue('D1', 'ПДВ, %');
        $sheet->setCellValue('F1', 'Назва артикула (не обов\'язково, тільки для зручності)');

        $currentRow = 2;

        foreach($items as $row) {
            $st = $sheet->getStyle('A'.$currentRow.':F'.$currentRow);
            $st->applyFromArray($rowArr);
            $st->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_TEXT);

            $st = $sheet->getStyle('A'.$currentRow);
            $st->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)->setIndent(1);
            $st->getNumberFormat()->setFormatCode(\PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
            $sheet->setCellValueExplicit('A'.$currentRow, $row[0],\PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $sheet->getStyle('B'.$currentRow)->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->setCellValueExplicit('B'.$currentRow, $row[1],\PHPExcel_Cell_DataType::TYPE_STRING);

            $st = $sheet->getStyle('C'.$currentRow);
            $st->applyFromArray([
                'fill' => [ 'type' => \PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'F5F6CE'] ]
            ]);
            $st->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)->setIndent(1);
            $st->getNumberFormat()->setFormatCode('0.00000');
            $sheet->setCellValueExplicit('C'.$currentRow, $row[3],\PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $st = $sheet->getStyle('D'.$currentRow);
            $st->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_RIGHT)->setIndent(1);
            $st->getNumberFormat()->setFormatCode('0.0');
            $sheet->setCellValueExplicit('D'.$currentRow, $row[4],\PHPExcel_Cell_DataType::TYPE_NUMERIC);

            $st = $sheet->getStyle('F'.$currentRow);
            $st->applyFromArray([
                'fill' => [ 'type' => \PHPExcel_Style_Fill::FILL_SOLID, 'color' => ['rgb' => 'F5F6CE'] ]
            ]);
            $st->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_LEFT)->setIndent(1);
            $sheet->setCellValueExplicit('F'.$currentRow, $row[2],\PHPExcel_Cell_DataType::TYPE_STRING);

            ++$currentRow;
        }

        $sheet->getHeaderFooter()->setOddFooter('Роздруковано з сайту company.ua');

        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="sp-new-contract-lagers-'.Lib::getUniqueTimeStr().'.xls"');
        header('Cache-Control: max-age=0');
        // If you're serving to IE 9, then the following may be needed
        header('Cache-Control: max-age=1');

        // If you're serving to IE over SSL, then the following may be needed
        header ('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header ('Last-Modified: '.gmdate('D, d M Y H:i:s').' GMT'); // always modified
        header ('Cache-Control: no-store, cache, must-revalidate'); // HTTP/1.1
        header ('Pragma: public'); // HTTP/1.0

        $objWriter = \PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        ob_end_clean();
        $objWriter->save('php://output');
    }
}
