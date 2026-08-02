<?php

namespace App\Http\Controllers\Api\V1;

use App\Dto\TransferData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transaction\TransferRequest;

class TransactionController extends Controller
{
    public function transfer(TransferRequest $request)
    {
        $dto = TransferData::fromArray($request->validated());
        
        return response()->json([
            'message' => 'Transfer successful',
            'data' => $dto
        ]);
    }
}
