<?php

namespace App\Http\Controllers;

use setasign\Fpdi\Tfpdf\Fpdi;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PdfController extends Controller
{
    public function getInvoicePdf()
    {
        $acc  = Session::get('acc',[]);
        $node = Session::get('node',[]);

        $data      = request()->post();
        $subj      = $data['subj']??''; 
        $amountVat = str_replace(",", ".", trim($data['amount']??'0'));

        $qstr  = "INSERT INTO [pay].[ClientInvoices] ([client_id], [amount]) VALUES (:acc_id, :amountVat)";
        DB::insert($qstr,['acc_id' => (int)$acc['id'], 'amountVat' => $amountVat]);
 
        $qstr  = "SELECT TOP 1 P.[id], YEAR(P.[create_date]) yy, MONTH(P.[create_date]) mm, DAY(P.[create_date]) dd"
            ." FROM [pay].[ClientInvoices] P WITH(NOLOCK)"
            ." INNER JOIN [dbo].[Clients] C WITH(NOLOCK) ON C.[IDClient] = P.[client_id]"
            ." WHERE P.[client_id] = :acc_id ORDER BY P.[id] DESC";
        $r = DB::selectOne($qstr,['acc_id' => $acc['id']]);

        $invId = $r->id;
        $year  = $r->yy;
        $month = ['термідора','січня','лютого','березня','квітня','травня','червня',
            'липня','серпня','вересня','жовтня','листопада','грудня'][(int)$r->mm];
        $day   = $r->dd;

        $isValid = (!empty($amountVat) && is_numeric($amountVat)) ? 1: 0;
        $invNum  = $acc['id'] . '/' . $invId;
        $amount  = $amountVat / 1.2;
        $pdv     = $amount * 0.2;

        if( empty($amountVat) || !is_numeric($amountVat) )
        {
            return response()->json([
                'state' => [ 'code' => 'RC_EMPTY_AMOUNT', 'msg' => '' ]
            ]);
        }

        $pdf = new Fpdi();
        $pdf->AddFont('DejaVu', '', 'DejaVuSans.ttf', true);
        $pdf->AddFont('DejaVu', 'B', 'DejaVuSans-Bold.ttf', true);
        $pdf->AddPage();
    
        if($acc['type_face'] === 2) {
            $pdf->setSourceFile(resource_path('extras/bill1.pdf'));
        } else {
            $pdf->setSourceFile(resource_path('extras/bill2.pdf'));
        }    
        $tplidx = $pdf->importPage(1);
        $pdf->useTemplate($tplidx);
    
        $pdf->SetFont('DejaVu','',9);
        $pdf->SetTextColor(0,0,0);
    
        $pdf->SetXY(59.0, 33.5);
        $pdf->MultiCell(140.0,4.0,$subj,0,'L');
    
        $pdf->SetFont('DejaVu','B',11);
    
        $pdf->SetXY(110.0, 54.5);
        $pdf->MultiCell(90.0,4.0,$invNum,0,'L');
    
        $pdf->SetXY(90.0, 60.0);
        $pdf->MultiCell(90.0,4.0,($day . " " . $month . " " . $year . " р."),0,'L');
    
        $pdf->SetFont('DejaVu','',9);
        $srv = "ACC " . $acc['id'] . ($node['id'] === 13 ? ". Послуги такі собі" : ". Інші послуги");
        $pdf->SetXY(16.0, 75.0);
        $pdf->MultiCell(99.0,4.0,$srv,0,'L');
    
        $pdf->SetFont('DejaVu','',10);
    
        if($acc['type_face'] !== 2 || $node['id'] !== 13 ) {
            $pdf->SetXY(153.0, 75.0);
            $pdf->MultiCell(22.0,9.0,number_format($amount,2,'.',''),0,'C');
    
            $pdf->SetXY(178.0, 75.0);
            $pdf->MultiCell(22.0,9.0,number_format($amount,2,'.',''),0,'C');
        } else {
            $pdf->SetXY(153.0, 75.0);
            $pdf->MultiCell(22.0,9.0,number_format($amountVat,2,'.',''),0,'C');
    
            $pdf->SetXY(178.0, 75.0);
            $pdf->MultiCell(22.0,9.0,number_format($amountVat,2,'.',''),0,'C');
        }
    
        if($acc['type_face'] !== 2 || $node['id'] !== 13 ) {
            $pdf->SetXY(178.0, 86.0);
            $pdf->MultiCell(22.0,6.0,number_format($amount,2,'.',''),0,'C');
    
            $pdf->SetXY(178.0, 94.0);
            $pdf->MultiCell(22.0,8.0,number_format($pdv,2,'.',''),0,'C');
        }
        
        $pdf->SetXY(178.0, 104.0);
        $pdf->MultiCell(22.0,6.0,number_format($amountVat,2,'.',''),0,'C');
    
    
        $pdf->SetXY(40.0, 118.0);
        $pdf->MultiCell(25.0,4.0,number_format($amountVat,2,'.',''),0,'R');
    
        if($acc['type_face'] !== 2 || $node['id'] !== 13 ) {
            $pdf->SetXY(40.0, 125.0);
            $pdf->MultiCell(25.0,4.0,number_format($pdv,2,'.',''),0,'R');
        }
    
        $pdf->Output("invoice_" . $acc['id'] . "_" . $invId . ".pdf", 'I');
    }    
}
