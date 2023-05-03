<?php

namespace App\Console\Commands;

use App\Services\TransactionService;
use Illuminate\Console\Command;
use Symfony\Component\Console\Command\Command as SymfonyCommand;

class CreateTransaction extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'transaction:create {email} {direction} {amount} {currency} {description?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command for create transaction';

    /**
     * The transaction service instance.
     *
     * @var \App\Services\TransactionService
     */
    protected $transactionService;

    /**
     * Create a new command instance.
     *
     * @param  \App\Services\TransactionService  $transactionService
     * @return void
     */
    public function __construct(TransactionService $transactionService)
    {
        parent::__construct();

        $this->transactionService = $transactionService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $email = $this->argument('email');
        $direction = $this->argument('direction');
        $amount = (float) $this->argument('amount');
        $currency = $this->argument('currency');
        $description = $this->argument('description');
        if ($description === null) {
            $description = '';
        } else {
            $description = htmlspecialchars($description);
        }

        $result[] = $this->transactionService->create(
            $email,
            $direction,
            $amount,
            $currency,
            $description
        );

        if (isset($result)) {
            $this->info('Transaction successfully completed');
            return SymfonyCommand::SUCCESS;
        } else {
            $this->error($result->getMessage());
            return SymfonyCommand::FAILURE;
        }
    }
}
