<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transaction\TransferRequest;

class TransactionController extends Controller
{
    public function transfer(TransferRequest $request)
    {
        
        return response()->json([
            'message' => 'Transfer successful',
            'data' => $request->all()
        ]);
    }
}
