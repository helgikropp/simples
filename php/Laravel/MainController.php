<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use App\Library\Helpers\CabLib;
use App\Library\Helpers\CabSHA1;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

use Intervention\Validation\Validator;

class MainController extends Controller
{
    /**
     *
     */
    public function getCardPage()
    {
        $acc = Session::get('acc',[]);

        $qstr  = "SELECT C.ClientName, C.fio,  C.email,"
            ." C.phone, C.fax, C.zip, C.city, C.address,"
            ." ISNULL(K.category, '!!!ERROR') category, ISNULL(T.Descriptions, '!!!ERROR') AS RatesPlan,"
            ." C.PostPayment, ISNULL(V.Type, '!!!ERROR') InvoiceType, C.TypeFace, C.PaymentPlace, C.black, dbo.IsCCInUse(C.idclient) HasCCInUse,"
            ." C.balance, CAST(C.LimitTemp AS DECIMAL(18,2)) LimitTemp, dbo.DateTimeToStr(C.paydate) PayDate, dbo.DateTimeToStr(CreateDate) CreateDate, C.AgentBypass,"
            ." C.edpou, C.mfo, C.rs, C.ks, C.ink, C.svid, C.BankName, C.BankAddress,"
            ." CONVERT(varchar(8),password) pwd, ISNULL(N.Description, '!!!ERROR') NodeName, C.deleted, C.InTest,"
            ." ISNULL(C.IsSuspended,0) IsSuspended"
            ." FROM dbo.clients C WITH(NOLOCK)"
            ." LEFT JOIN dbo.Category K ON K.IDCat = C.Category"
            ." LEFT JOIN dbo.RatesPlan T ON C.idtg = T.IDRatesPlan AND T.idnode = C.IDNode"
            ." LEFT JOIN dbo.InvoiceClass V ON C.VidOtchet = V.IDRec"
            ." LEFT JOIN dbo.Nodes N ON N.ID = C.IDNode"
            ." WHERE C.idclient = :id";

        $r = DB::selectOne($qstr,['id' => $acc['id']]);
        $acc['name']  = $r->ClientName;
        $acc['fio']   = $r->fio;
        $acc['email'] = $r->email;
        $acc['phone'] = $r->phone;
        $acc['fax']   = $r->fax;
        $acc['zip']   = $r->zip;
        $acc['city']  = $r->city;
        $acc['address']   = $r->address;
        $acc['category']  = $r->category;
        $acc['ratesplan'] = $r->RatesPlan;
        $acc['postpayment']  = (int)$r->PostPayment;
        $acc['invoice_type'] = (int)$r->InvoiceType;
        $acc['type_face'] = (int)$r->TypeFace;
        $acc['cc']        = (int)$r->HasCCInUse;
        $acc['balance']   = number_format($r->balance,2,'.',' ');
        $acc['pay_date']  = $r->PayDate;
        $acc['create_date']  = $r->CreateDate;
        $acc['bank']   = [
            'edrpou' => $r->edpou,
            'mfo'    => $r->mfo,
            'rs'     => $r->rs,
            'name'   => $r->BankName,
            'addr'   => $r->BankAddress,
            'iban'   => Validator::isIban($r->rs) ? $r->rs : $r->edpou
        ];
        $acc['pwd']  = $r->pwd;
        $acc['node'] = $r->NodeName;
        $acc['is_deleted']   = (int)$r->deleted;
        $acc['in_test']      = (int)$r->InTest;
        $acc['is_suspended'] = (int)$r->IsSuspended;


        Session::put('acc',$acc);

        return view('card', [
            'acc' => $acc
        ]);
    }

    /**
     *
     */
    public function getPaymentsPage()
    {
        $acc  = Session::get('acc',[]);
        $node = Session::get('node',[]);
        $scr  = Session::get('script',[]);
        $pageName = CabLib::getPageName();
        if(empty($scr[$pageName])) { $scr[$pageName] = []; }
        $script = &$scr[$pageName];

        if (request()->isMethod('post')) {
            $script['all'] = trim(request()->post('all','0'));
            $script['out_type'] = trim(request()->post('out_type','0'));
        }
        if(empty($script['all'])) {
            $script['all'] = '0';
            $script['out_type'] = '0';
        }
        Session::put('script',$scr);

        $qstr  = "SELECT M.money0, M.money1, M.money2, N.nds_name, N.language"
            ." FROM dbo.MoneyGroupsDict M WITH(NOLOCK)"
            ." INNER JOIN dbo.Nodes N WITH(NOLOCK) ON N.IDMoneyGroups=M.[ID] AND N.[ID] = :id";

        $resNode = DB::selectOne($qstr,[
            'id' => (int)$node['id'],
        ]);

        $node = array_merge($node,(array)$resNode);

        $qstr  = "SELECT C.balance, dbo.DateTimeToStr(C.paydate) last_pay_date,"
            ." (SELECT TOP 1 dbo.DateTimeToStr(CurrentTime)"
            ."   FROM dbo.AccountRecords WITH(NOLOCK)"
            ."   WHERE IDClient=C.IDClient ORDER BY CurrentTime DESC) last_call_date"
            ." FROM dbo.Clients C WITH(NOLOCK) WHERE IDClient=:id";

        $resAcc = DB::selectOne($qstr,[
            'id' => (int)$acc['id']
        ]);
        $acc['balance']        = $resAcc->balance;
        $acc['pay_date']       = $resAcc->last_pay_date;
        $acc['last_call_date'] = $resAcc->last_call_date;
        if((int)$acc['black'] === 0)       { $acc['black_factor'] = 0; }
        elseif((int)$acc['homepay'] === 0) { $acc['black_factor'] = 2; }
        else                               { $acc['black_factor'] = 1; }

        $qstr = "EXECUTE [ext].[get_client_money_history] :acc_id, :node_id, NULL";
        $resHistory = DB::select($qstr,[
            'acc_id'  => $acc['id'],
            'node_id' => $node['id']
        ]);

        return view('payments', [
            'acc'     => $acc,
            'script'  => $script,
            'node'    => $node,
            'history' => $resHistory
        ]);
    }


    /**
     *
     */
    public function getBillXls($id)
    {
        $acc  = Session::get('acc',[]);
        $node = Session::get('node',[]);

        $data      = request()->post();
        $fileName  = ($data['num']??'invoice') . '_details.xlsx';


        $qstr  = "SELECT [CurrentTime], [CallingStationId], [IDCard], [CalledStationId], [ZoneU], [CalcTime], [Price]"
                    . " FROM [dbo].[CallDetailView] WWITH(NOLOCK)"
                    . " WHERE [IDSchet] = :id"
                    . " ORDER BY [CurrentTime];";
        $resCdr = DB::select($qstr, [ 'id' => (int)$id ]);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet()->setTitle('Calls');
        $sheet->setCellValue('A1', 'Hello World !');

        $writer = new Xlsx($spreadsheet);
        \PhpOffice\PhpSpreadsheet\Settings::setLocale( 'uk_UA' );
        \PhpOffice\PhpSpreadsheet\Shared\StringHelper::setDecimalSeparator( '.' );

        $sheet->setCellValue('A1', '#');
        $sheet->setCellValue('B1','Date');
        $sheet->setCellValue('C1','From');
        $sheet->setCellValue('D1','Card ID');
        $sheet->setCellValue('E1','To');
        $sheet->setCellValue('F1','Zone');
        $sheet->setCellValue('G1','Duration');
        $sheet->setCellValue('H1','Price');

        for($i=0;$i<count($resCdr);++$i) {
            $row = $i+2;
            $sheet->setCellValueExplicit('A'.$row,$i+1,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValue('B'.$row,$resCdr[$i]->CurrentTime);
            $sheet->getStyle('B'.$row)->getNumberFormat()
                ->setFormatCode(\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_YYYYMMDDSLASH);
            $sheet->setCellValueExplicit('C'.$row,$resCdr[$i]->CallingStationId,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('D'.$row,$resCdr[$i]->IDCard,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValueExplicit('E'.$row,$resCdr[$i]->CalledStationId,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('F'.$row,$resCdr[$i]->ZoneU,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('G'.$row,$resCdr[$i]->CalcTime,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC);
            $sheet->setCellValue('H'.$row,$resCdr[$i]->Price);
            $sheet->getStyle('H'.$row)->getNumberFormat()->setFormatCode('#0.0000');
        }

        ob_clean();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($fileName).'"');
        $writer->save('php://output');
    }

    /**
     *
     */
    public function getPayData()
    {
        $acc  = Session::get('acc',[]);

        $data   = request()->post();
        $sys_id = (int)trim($data['sys_id']??'0');
        $ip_addr = request()->ip();
        $amount = str_replace(",", ".", trim($data['amount']??'0'));

        if( empty($sys_id) || $sys_id < 1) {
            return response()->json([ 'code' => 'RC_EMPTY_SYS', 'msg' => '' ]);
        } elseif(empty($amount) || !is_numeric($amount) || (float)$amount <= 0 ) {
            return response()->json([ 'code' => 'RC_EMPTY_AMOUNT', 'msg' => '' ]);
        }

        $qstr = "EXECUTE [pay].[payment_from_paysystem_start] :sys_id, :client_id, :amount, NULL, :ip_addr, :comments;";
        $r = DB::selectOne($qstr,[
            'sys_id'    => $sys_id,
            'client_id' => $acc['id'],
            'amount'    => $amount,
            'ip_addr'   => $ip_addr,
            'comments'  => ''
        ]);

        $qstr = "SELECT [id], [amount], [comments], [transaction_uid] FROM [pay].[pay_payments] WHERE [id] = :id ;";
        $payment = DB::selectOne($qstr,[
            'id' => $r->id
        ]);

        $amount = number_format($payment->amount,2,'.','');

        if($sys_id === 2) {
            return response()->json([
                'code' => 'RC_OK',
                'msg' => '',
                'data' => [
                    'sys_id'    => 2,
                    'order_id'  => $payment->id,
                    'client_id' => $acc['id'],
                    'payee_id'  => 'XXXXX',
                    'amount'    => $amount,
                    'ip_addr'   => $ip_addr
                ]
            ]);
        } else if($sys_id === 9) {
            $sendXml =
                "<request>" .
                    "<version>1.2</version>" .
                    "<result_url>" . route('pay.result',['result' => 1]) . "</result_url>" .
                    "<server_url>https://secure.company.com.ua/gates/liqpay.php</server_url>" .
                    "<merchant_id>XXXXXXX</merchant_id>" .
                    "<order_id>" . $acc['id'] . "-" . $acc['id'] . "-" . $payment->id . "</order_id>" .
                    "<amount>" . $amount . "</amount>" .
                    "<currency>UAH</currency>" .
                    "<description>Company, client " . $acc['id'] . "</description>" .
                    "<default_phone>+38044XXXXXXX</default_phone>" .
                    "<pay_way>card</pay_way>" .
                    "<goods_id>6</goods_id>" .
                "</request>";

            $mercSign = "LHLHLHLK:HLKHKHJLKJLKLKJLK";
            $sendXmlHash = base64_encode($sendXml);

            return response()->json([
                'code' => 'RC_OK',
                'msg' => '',
                'data' => [
                    'sys_id'     => 9,
                    'order_id'   => $payment->id,
                    'client_id'  => $acc['id'],
                    'amount'     => $payment->amount,
                    'ip_addr'    => $ip_addr,
                    'signature'       => CabSHA1::b64_sha1($mercSign . $sendXml . $mercSign) . "=",
                    'hash'  => $sendXmlHash,
                ]
            ]);

        }

        return response()->json([ 'code' => 'RC_EMPTY_AMOUNT', 'msg' => '' ]);
    }


    /**
     *
     */
    public function setPhoneRedirect()
    {
        $types = ['404fwd', '408fwd'];

        $acc  = Session::get('acc',[]);
        $data = request()->post();

        if(empty($data['cmd']) || !in_array($data['cmd'],['set','del'])) {
            return response()->json([
                'code' => 'ERR_CMD_UNKNOWN',
                'msg'  => __('cab.ERR_CMD_UNKNOWN')
            ]);
        }

        $qstr = "EXECUTE [ext].[set_phone_redirect] :from_phone, :to_phone, :type;";
        $r = DB::selectOne($qstr,[
            'type'       => array_search($data['type'],$types),
            'from_phone' => trim($data['phone_src']),
            'to_phone'   => $data['cmd'] === 'del' ? '' : trim($data['phone_dst'])
        ]);

        $msg = match($r->pstn_set_phone_redirect) {
            -2 => "ERR_PH_NOT_NUM",
            -1 => "ERR_REDIR_LOOP",
             0 => "ERR_NOT_SAVED",
             1 => "MSG_REDIR_CHANGED",
             2 => "MSG_REDIR_CREATED",
             3 => "MSG_REDIR_DELETED",
            default => "ERR_EVENT_UNKNOWN"
        };

        return response()->json([
            'code' => in_array($r->pstn_set_redirect,[1,2,3]) ? 'RC_OK': $msg,
            'msg'  => __('cab.' . $msg),
        ]);
    }
}
