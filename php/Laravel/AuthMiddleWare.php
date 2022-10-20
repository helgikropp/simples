<?php

namespace App\Http\Middleware;

use Closure;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;

class AuthMiddleWare
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $ip   = $request->ip();
        $host = $_SERVER['REMOTE_HOST'] ?? gethostbyaddr($ip);
        if(!$host) { $host = $ip; }

        //=================================================================
        $scriptName = str_replace('/','_',$request->getPathInfo());
        Session::put('script_name', $scriptName);
        //=================================================================
        if(!Session::has('script')) { Session::put('script', []); }
        $script = Session::get('script');
        if(!isset($script[$scriptName])) { $script[$scriptName] = []; }
        Session::put('script', $script);

        //=================================================================
        $user = Session::get('user',['idkey'=>-1, 'acc'=>'', 'pwd'=>'']);
        $post = $request->post()??[];
        $isLogonSession = false;
        if(array_key_exists('acc',$post) && array_key_exists('pwd',$post)) {
            $isLogonSession = true;
            $user['acc'] = $post['acc'];
            $user['pwd'] = $post['pwd']; 
        }

        $qstr = "EXECUTE [auth].[get_acc_key] :acc, :pwd, :idKey, :ip, :host, :script";

        $vars = [
          'acc'    => $user['acc'], 
          'pwd'    => $user['pwd'], 
          'idKey'  => $user['idkey'], 
          'ip'     => $ip, 
          'host'   => $host,
          'script' => $scriptName
        ];
        $r = DB::selectOne($qstr,$vars);

        $idkeyOld = $user['idkey'];
        $user['idkey'] = $r->key_id;

        $node = [];

        Session::put('user',$user);

        $acc = Session::get('acc',[]);
        if($r->key_id > 0) {

            $acc['id']   = (int)$r->client_id;
            $acc['name'] = $r->client_name;
            $acc['agent_id'] = (int)$r->manager_id;
            $node['id']     = (int)$r->node_id;
            $node['name']   = $r->node_name;
            Session::put('acc',$acc);
            Session::put('node',$node);
            Session::put('auth_error',$r->error_code);
            return $next($request);
        }

        if(!$isLogonSession && request()->ajax()) {
            return response()->json([ 
                'code' => 'ERR_DO_RELOGON', 
                'msg'  => __('cab.ERR_DO_RELOGON')
            ]);
        }

        return redirect()->route('login');
    }
}
