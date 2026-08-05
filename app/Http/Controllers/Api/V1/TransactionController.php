<?php

namespace App\Http\Controllers\Api\V1;

use App\Actions\TransferMoneyAction;
use App\Dto\TransferData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Transaction\TransferRequest;
use App\Http\Resources\Api\V1\TransactionResource;
use App\Http\Traits\ApiResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

class TransactionController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly TransferMoneyAction $transferMoneyAction,
    ) {}

    public function transfer(TransferRequest $request): JsonResponse
    {
        $dto = TransferData::fromArray($request->validated());
        
        $transaction = $this->transferMoneyAction->execute($dto);

        return $this->success(
            new TransactionResource($transaction),
            'Transferência realizada com sucesso.',
        );
    }
}
