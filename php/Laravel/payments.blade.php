@extends('layouts.app')

@section('pageType','payments')
@section('pageTitle',__('cab.INV_PAY'))

@section('content')

<div class="row">
    <div class="col-12 col-sm-12 col-md-8 col-lg-6 col-xl-4 mb-3">
        <div class="card bg-light cab-border-color">
            <div class="card-body">
                <h5 class="cab-text-color">{{ __('cab.STATE') }}</h5>
                <div class="row" style="margin-bottom:7px;">
                    <div class="col-8 col-sm-7">{{ __('cab.SALDO') }}</div>
                    <div class="col-4 col-sm-5 text-end text-{!! ($acc['balance'] < 0?'danger':'success') !!}">{{ $acc['balance'] }} {{ __('cab.ABR_TE') }}</div>
                </div>
                <div class="row">
                    <div class="col-8 col-sm-7">{{ __('cab.DATE_LAST_PAY') }}</div>
                    <div class="col-4 col-sm-5 text-end text-primary">{{ substr($acc['pay_date'],0,16) }}</div>
                </div>
                <div class="row">
                    <div class="col-8 col-sm-7">{{ __('cab.DATE_LAST_CALL') }}</div>
                    <div class="col-4 col-sm-5 text-end text-primary">{{ substr($acc['last_call_date'],0,16) }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-12 col-md-8 col-lg-6 col-xl-5 col-xxl-4 mb-3">
        <form id="form1" onsubmit="event.preventDefault(); return false;" data-role="validated">
        <div class="card bg-light cab-border-color" data-role="form" id="f1">
            <div class="card-body">
                <h5 class="cab-text-color">{{ __('cab.INV_NEW') }}</h5>
                @csrf
                <div class="row mb-1">
                    <label class="col-form-label col-form-label-sm col-4">{{ __('cab.CLIENT') }}</label>
                    <div class="col-8">
                        <input type="text" id="inv-subj" 
                            value="{{ $acc['name'] }}" 
                            class="form-control form-control-sm"
                            data-rule="required"
                            data-jv-rules="required">
                    </div>
                </div>
                <div class="row mb-1">
                    <label class="col-form-label col-form-label-sm col-4">{{ __('cab.AMOUNT') }} {{ __('cab._WITH') }} {{ __('cab.VAT') }}</label>
                    <div class="col-3">
                        <input type="text" id="inv-amount" 
                            value="" 
                            class="form-control form-control-sm" 
                            tabindex="0" 
                            data-bs-toggle="popover" 
                            data-bs-placement="bottom" 
                            data-bs-content="{{ __('cab.MSG_WHITE_INV') }}"
                            data-rule="required|float|min-1"
                            data-jv-rules="required,number,minNumber-1">
                    </div>
                    <div class="col-2">
                        <button type="button" id="inv-type-pdf" value="0" class="btn btn-sm btn-outline-success">PDF</button>
                    </div>
                    <div class="col-3 text-end">
                        <button type="button" id="inv-type-html" value="1" class="btn btn-sm btn-outline-success">HTML</button>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>

    @if($node['id'] === 13 || $acc['black'] === 0)

    <div class="col-6 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-2 mb-3">
        <div class="card bg-light cab-border-color">
            <img src="{{ mix('images/easypay.svg') }}" alt="EasyPay" class="card-img-top mt-3" style="width:75px;height:30px;margin:2px auto 0 auto;">
            <div class="card-body" style="margin-top:1px;">
                <a class="btn btn-sm btn-outline-primary w-100 mt-4" target="_blank"  href="https://easypay.ua/{{ CabLib::getLang() }}/catalog/bla/bla">{{ __('cab.PAY_OL') }}</a>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-2 mb-3">
        <div class="card bg-light cab-border-color">
            <img src="{{ mix('images/portmone.svg') }}" alt="Portmone.com" class="card-img-top mt-4" style="width:128px;height:17px;margin:2px auto 0 auto;">
            <div class="card-body" style="margin-top:1px;">
                <p class="card-text text-muted mb-0">{{ __('cab.BILL_PAY') }}</p>
                <a class="btn btn-sm btn-outline-primary w-100 mt-2" target="_blank" href="https://www.portmone.com.ua/{{ CabLib::getLang() }}/auth" data-bs-toggle="popover" data-bs-placement="bottom" data-bs-content="{{ __('cab.PORTMONE_HINT') }}">{{ __('cab.PAY_OL') }}</a>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-2 mb-3">
        <div class="card bg-light cab-border-color">
            <img src="{{ mix('images/ipay.svg') }}" alt="IPay.ua" class="card-img-top mt-4" style="width:75px;height:30px;margin:2px auto 0 auto;">
            <div class="card-body" style="margin-top:1px;">
                <a class="btn btn-sm btn-outline-primary w-100 mt-3" target="_blank" href="https://www.ipay.ua/{{ CabLib::getLang() }}/bills/bla">{{ __('cab.PAY_OL') }}</a>
            </div>
        </div>
    </div>

    <div class="col-6 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-2 mb-3">
        <form id="form2" onsubmit="event.preventDefault(); return false;" data-role="validated">
        <div class="card bg-light cab-border-color" data-role="form">
            <img src="{{ mix('images/portmone.svg') }}" alt="Portmone.com" class="card-img-top mt-4" style="width:128px;height:17px;margin:2px auto 0 auto;">
            <div class="card-body" style="padding-top:11px;">
                <div class="cab-form-floating">
                    <input type="text" id="pm-amount" 
                        class="form-control form-control-sm" 
                        value="" 
                        placeholder="100.0"
                        maxlength="30"
                        data-rule="required|float|min-30"
                        data-jv-rules="required,number,minNumber-30">
                    <label for="amount">{{ __('cab.AMOUNT') }} (> 30 {{ __('cab._UAH') }})</label>
                </div>
                <button type="button" id="pm-btn" data-sys-id="2" class="btn btn-sm btn-outline-primary w-100 mt-1">{{ __('cab.PAY_OL') }}</button>
            </div>
        </div>
        </form>
    </div>

    <div class="col-6 col-sm-6 col-md-4 col-lg-3 col-xl-3 col-xxl-2 mb-3">
        <form id="form3" onsubmit="event.preventDefault(); return false;" data-role="validated">
        <div class="card bg-light cab-border-color" data-role="form">
            <img src="{{ mix('images/liqpay.svg') }}" alt="Liqpay" class="card-img-top" style="width:125px;height:26px;margin:15px auto 0 auto;">
            <div class="card-body pt-2" style="margin-top:3px;">
                <div class="cab-form-floating">
                    <input type="text" id="lp-amount" 
                        class="form-control form-control-sm" 
                        value="" 
                        placeholder="100.0"
                        data-rule="required|float|min-1"
                        data-jv-rules="required,number,minNumber-1">
                    <label for="amount">{{ __('cab.AMOUNT')}}</label>
                </div>
                <button type="button" id="lp-btn" data-sys-id="9" class="btn btn-sm btn-outline-primary w-100 mt-1">{{ __('cab.PAY_OL') }}</button>
            </div>
        </div>
        </form>
    </div>

    @endif
</div>
</form>

            
<div class="row py-2">
    <div class="col-4"></div>
    @csrf
    <label class="col-form-label col-1 fw-bold">{{ __('cab.VIEW') }}</label>
    <div class="col-1">
        <div class="form-check col-form-label mb-0">
            <input class="form-check-input" type="radio" name="all" id="all-short" value="0" @checked((int)$script['all'] === 0)>
            <label class="form-check-label" for="all">
                {{ __('cab._SHORT') }}
            </label>
        </div>
    </div>
    <div class="col-1">
        <div class="form-check col-form-label mb-0">
            <input class="form-check-input" type="radio" name="all" id="all-full" value="1" @checked((int)$script['all'] === 1)>
            <label class="form-check-label" for="all">
                {{ __('cab._FULL') }}
            </label>
        </div>
    </div>
    <div class="col-1">
        <button type="submit" name="cmd_reload" class="btn btn-primary btn-sm">{{ __('cab._SHOW') }}</button>
    </div>                            
    <div class="col-4"></div>
</div>

    @php
        $hiddenCount = $script['all'] ? 0 : count($history) - 10;
        $i = 0;
        $balanceUSD = 0.0;
        $balanceUAH = 0.0;
        $balanceEUR = 0.0;
    @endphp

<div class="row">
    <div class="col">
    <table class="table table-sm table-striped table-hover table-bordered mx-auto">
    <thead>
        <tr>
            <th scope="col" rowspan="2" class="align-middle">#</th>
            <th scope="col" rowspan="2" class="align-middle">ID</th>
            <th scope="col" rowspan="2" class="align-middle">{{ __('cab.DATE') }}</th>
            <th scope="col" colspan="2" class="align-middle">{{ __('cab.AMOUNT') }}</th>
    @if ($acc['black_factor'] !== 1)
            <th scope="col">{{ __('cab.EXCH_RATE') }}</th>
    @endif
            <th scope="col" rowspan="2" class="align-middle">{{ __('cab.PAY_TYPE') }}</th>
            <th scope="col" rowspan="2" class="align-middle"></th>
            <th scope="col">{{ __('cab.REST') }}</th>
            <th scope="col" colspan="2" rowspan="2" class="align-middle">{{ __('cab.DETAILS') }}</th>
        </tr>
        
        <tr>
            <th scope="col">{{ __('cab.ABR_TE') }}</th>
    @if ($acc['black_factor'] === 0)
            <th scope="col">{{ $node['money1'] }} {{ __('cab._WITH') }} {{ $node['nds_name'] }}</th>
            <th scope="col">{{ $node['money1'] }} / {{ $node['money0'] }}</th>
            <th scope="col">{{ $node['money1'] }} {{ __('cab._WITH') }} {{ $node['nds_name'] }}</th>
    @elseif ($acc['black_factor'] === 1)
            <th scope="col">{{ $node['money0'] }}</th>
            <th scope="col">{{ $node['money0'] }}</th>
    @else
            <th scope="col">{{ $node['money2'] }}</th>
            <th scope="col">{{ $node['money2'] }} / {{ $node['money0'] }}</th>
            <th scope="col">{{ $node['money2'] }}</th>
    @endif
        </tr>
    </thead>
        
    <tbody>
    @if ($hiddenCount > 0)
        <tr>
            <td class="text-center" colspan="{{ ($acc['black_factor'] === 1 ? 12 : 13) }}">... {{ trans_choice('cab.OLD_ROWS_SKIP',$hiddenCount) }}</td>
        </tr>
    @endif

    @foreach($history as $h) 
        @php
            ++$i;
            if ($hiddenCount > 0) {
                $hiddenCount--;
                continue;
            }
            if ($h->doc_type === 'inv') {
                if($h->is_black === 1) {
                    $balanceUSD = $h->saldo;
                    $balanceUAH = $h->saldo * $h->rate_1_0 * (1.0 + $h->tax_factor);
                } else {
                    $balanceUAH =  $h->saldo;
                    $balanceUSD =  $h->saldo / $h->rate_1_0 / (1.0 + $h->tax_factor);
                }
            } else {
                $balanceUSD += $h->amount_0;
                $balanceUAH += $h->amount_1;
            }
            $balanceEUR = $balanceUSD * $h->rate_2_0;

            if ($balanceUSD < 0)  $FontClass = 'wMoneyRed';
            else                  $FontClass = 'wMoneyBlue';


            if($h->doc_type === 'inv') $clr = '#d3d3d3'; else $clr = '#fff';

        @endphp                       
        <tr>
            <td class="text-end">{{ $i }}</td>
            <td>{{ ($h->doc_type == 'inv' ? $h->doc_num : $h->doc_id) }}</td>
            <td>{!! $h->doc_date !!}</td>
            <td class="text-end fw-bold">{!! number_format($h->amount_0,2,'.','') !!}</td>
        @if ($acc['black_factor'] === 0)
            <td class="text-end fw-bold">{!! number_format($h->amount_1,2,'.','') !!}</td>
            <td class="text-end fw-bold">{!! $h->rate_1_0 !!}</td>
        @elseif ($acc['black_factor'] === 1)
            <td class="text-end fw-bold">{!! number_format($h->amount_0,2,'.','') !!}</td>
        @else
            <td class="text-end fw-bold">{{ number_format($h->amount_2,2,'.','') }}</td>
            <td class="text-end fw-bold">{{ $h->rate_1_0 }}</td>
        @endif
            <td>{{ $h->doc_name }}</td>
            <td></td>
        @if ($acc['black_factor'] === 0)
            <td class="text-end fw-bold text-{!! ($balanceUSD < 0?'danger':'primary') !!}">{!! number_format($balanceUAH,2,'.','') !!}</td>
        @elseif ($acc['black_factor'] === 1)
            <td class="text-end fw-bold text-{!! ($balanceUSD < 0?'danger':'primary') !!}">{!! number_format($balanceUSD,2,'.','') !!}</td>
        @else
            <td class="text-end fw-bold text-{!! ($balanceUSD < 0?'danger':'primary') !!}">{!! number_format($balanceEUR,2,'.','') !!}</td>
        @endif
        @if($h->doc_type === 'inv')
            <td>
                <button name="cmd_bill_html" data-id="{!! $h->doc_id !!}" class="btn cab-btn-xs btn-outline-primary w-100">{{ __('cab._DETAILS') }}</button>
            </td>
            <td>
                <button name="cmd_bill_xls" data-id="{!! $h->doc_id !!}" data-num="{!! $h->doc_num !!}" class="btn cab-btn-xs btn-outline-primary w-100">XLS</button>
            </td>
        @else
            <td colspan="2">&nbsp;</td>
        @endif
        </tr>
    @endforeach 
    </tbody>
    </table>            
    </div>

</div>
    
@endsection

@section('templates')
<hr>
<template data-tpl="cc-pay-portmone">
    <div id="cc-pay-portmone" class="card" style="width:350px;text-align: center;margin:0 auto;">
        <img src="{{ mix('/images/portmone.svg') }}" class="card-img-top" alt="..." style="width:90%;margin:10px auto;">
        <div class="card-body">
            <h5 class="card-title">{{ __('cab.RQ_READY') }}</h5>
            <p class="card-text">{{ __('cab.RQ_NUM') }}: <span id="order_id_portmone"></span></p>
            <p class="card-text">{{ __('cab.PAY_SYS') }}: <span id="sys_id_portmone">PORTMONE.COM</span></p>
            <p class="card-text">{{ __('cab.AMOUNT') }}: <span id="amount_value_portmone"></span> грн</p>
            <form name="pm_send" action="https://www.portmone.com.ua/gateway/" method="post" target="_blank">
                <input type="hidden" name="payee_id" value="">
                <input type="hidden" name="shop_order_number" value="">
                <input type="hidden" name="bill_amount" value="0">
                <input type="hidden" name="description" value="">
                <input type="hidden" name="success_url" value="{{ route('pay.result',['result' => 1]) }}">
                <input type="hidden" name="failure_url" value="{{ route('pay.result',['result' => 0]) }}">
                <input type="hidden" name="lang" value="{ { $lng }}">
                <button type="button" class="btn btn-warning" style="margin-left:30px;" onclick="">{{ __('cab.CANCEL') }}</button>
                <button type="submit" class="btn btn-success">{{ __('cab.PAY') }}</button>
            </form>
        </div>
    </div>
</template>

<template data-tpl="cc-pay-liqpay">
    <div id="cc-pay-liqpay" class="card" style="width:350px;text-align: center;margin:0 auto;">
        <img src="{{ mix('/images/liqpay.svg') }}" class="card-img-top" alt="..." style="width:90%;margin:10px auto;">
        <div class="card-body">
            <h5 class="card-title">{{ __('cab.RQ_READY') }}</h5>
            <p class="card-text">{{ __('cab.RQ_NUM') }}: <span id="order_id_liqpay"></span></p>
            <p class="card-text">{{ __('cab.PAY_SYS') }}: <span id="sys_id_liqpay">Liqpay.COM</span></p>
            <p class="card-text">{{ __('cab.AMOUNT') }}: <span id="amount_value_liqpay"></span> грн</p>
            <form name="lq_send" action="https://www.liqpay.ua/?do=clickNbuy" method="post" target="_blank">
                <input type="hidden" name="operation_xml" value="">
                <input type="hidden" name="signature" value="">
                <button type="button" class="btn btn-warning" style="margin-left:30px;" onclick="">{{ __('cab.CANCEL') }}</button>
                <button type="submit" class="btn btn-success">{{ __('cab.PAY') }}</button>
            </form>
        </div>
    </div> 
</template>

@endsection
