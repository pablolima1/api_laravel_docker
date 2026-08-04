<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\TransferMoneyAction;
use App\Dto\TransferData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transaction\TransferRequest;
use App\Http\Resources\Api\V1\TransactionResource;

class TransactionController extends Controller
{
    public function __construct(
        private readonly TransferMoneyAction $transferMoneyAction,
    ) {}
    
    public function transfer(TransferRequest $request)
    {
        $dto = TransferData::fromArray($request->validated());
        
        $transaction = $this->transferMoneyAction->execute($dto);

        return response()->json(new TransactionResource($transaction), 201);
    }
}
