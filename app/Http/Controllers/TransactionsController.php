<?php

namespace App\Http\Controllers;

use App\Models\Transactions;
use Illuminate\Http\Request;
use App\Models\User;

class TransactionsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //

        $transactions=new Transactions();

        $transactions->code=$request->TransID;

        $transactions->amount=$request->TransAmount;
        $transactions->mssidn=$request->mssidn;

        //get from users table where mobile is this mssidn

        if($transactions->save()){

            $check=User::where('mobile',$request->mssidn)->first();

        if($check && $request->TransAmount >=500 ){

            $wallet_balance=$check->wallet;

            $wallet_balance=$wallet_balance+$request->TransAmount;

            $update=User::where('mobile',$request->mssidn)->update(['wallet'=>$wallet_balance,'status'=>1]);

            return response()->json(['status'=>'saved']);

        }else{
            return response()->json(['status'=>'saved']);
        }

            
        }else{
            return response()->json(['status'=>'error']);
        }

        


        $content = $request;
        $fp = fopen("myText.txt","wb");
        fwrite($fp,$content);
        fclose($fp);

    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Transactions  $transactions
     * @return \Illuminate\Http\Response
     */
    public function show(Transactions $transactions)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Transactions  $transactions
     * @return \Illuminate\Http\Response
     */
    public function edit(Transactions $transactions)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Transactions  $transactions
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Transactions $transactions)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Transactions  $transactions
     * @return \Illuminate\Http\Response
     */
    public function destroy(Transactions $transactions)
    {
        //
    }
}
