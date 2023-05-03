<?php

namespace App\Services;

use App\Console\Commands\CreateTransaction;
use App\Dictionary\Currency;
use App\Dictionary\TransactionDirection;
use App\Models\Transaction;
use App\Models\User;
use App\Models\UserBalance;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Illuminate\Support\Facades\Queu;

class TransactionService
{
    public function create(
        string $email,
        string $direction,
        float $amount,
        string $currency,
        ?string $description = null
    ): void {
        $user = User::firstWhere([
            'email' => $email,
        ]);

        if (!$user) {
            throw new InvalidArgumentException(sprintf('User with email %s not found', $email));
        }

        if (!in_array($direction, TransactionDirection::getList(), true)) {
            throw new InvalidArgumentException(sprintf('Direction must be equal one of [%s]', implode(', ', TransactionDirection::getList())));
        }

        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than zero');
        }

        if (!in_array($currency, Currency::getList(), true)) {
            throw new InvalidArgumentException(sprintf('Currency must be equal one of [%s]', implode(', ', Currency::getList())));
        }

        try {
            DB::beginTransaction();

            $transaction = Transaction::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'currency' => $currency,
                'direction' => $direction,
                'description' => $description ?: null,
            ]);

            $userBalance = UserBalance::firstWhere([
                'user_id' => $user->id,
                'currency' => $currency,
            ]);

            if (!$userBalance) {
                $userBalance = UserBalance::create([
                    'user_id' => $user->id,
                    'balance' => 0,
                    'currency' => $currency,
                ]);
            }

            if ($direction === TransactionDirection::IN) {
                $userBalance->balance += $amount;
            } elseif ($direction === TransactionDirection::OUT) {
                $this->checkBalance($userBalance->balance, $amount);
                $userBalance->balance -= $amount;
            } else {
                throw new InvalidArgumentException('Unexpected direction');
            }

            $userBalance->save();
            $transaction->save();
            $this->lookForUpdate($amount, $currency, $userBalance);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            throw new InvalidArgumentException(sprintf('Undefined exception: %s', $e->getMessage()));
        }
    }

    private function checkBalance(float $balance, float $subAmount)
    {
        if ($balance < $subAmount) {
            throw new InvalidArgumentException('There are not enough funds on the user account');
        }
    }

    private function lookForUpdate(float $amount, string $currency, UserBalance $userBalance)
    {
        $threshold = 1000;
        $blockSize = 100;

        $totalAmount = $userBalance->balance + $amount;

        if ($totalAmount >= $threshold) {
            $blocks = ceil($totalAmount / $blockSize);

            for ($i = 0; $i < $blocks; $i++) {
                TransactionService::dispatch($currency, $blockSize);
            }
        }
    }
}
