<?php

namespace App\Console\Commands;

use App\Models\RecurringInvoice;
use Illuminate\Console\Command;

class GenerateRecurringInvoices extends Command
{
    protected $signature = 'invoices:generate-recurring';
    protected $description = 'Generate draft invoices from recurring templates due today or earlier';

    public function handle(): int
    {
        $today = now()->toDateString();
        $dueRecurring = RecurringInvoice::where('status', 'active')
            ->where('next_issue_date', '<=', $today)
            ->get();

        $count = 0;
        foreach ($dueRecurring as $recurring) {
            $invoice = $recurring->generateInvoice();
            if ($invoice) {
                $this->info("Generated {$invoice->invoice_number} for client ID {$recurring->client_id}");
                $count++;
            } else {
                $this->warn("Skipped recurring invoice for client ID {$recurring->client_id} (active invoice already exists for date).");
            }
        }

        $this->info("Completed. {$count} recurring invoices generated.");
        return Command::SUCCESS;
    }
}
