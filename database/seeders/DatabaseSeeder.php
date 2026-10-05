<?php

namespace Database\Seeders;

use App\Models\BusinessProfile;
use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\RecurringInvoice;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // If local uncommitted personal seeder exists, run it and exit
        if (file_exists(database_path('seeders/LocalPersonalSeeder.php'))) {
            $this->call(LocalPersonalSeeder::class);
            return;
        }

        // Generic public demonstration seed (safe for public git repositories)
        $profile = BusinessProfile::current();
        $profile->update([
            'business_name' => 'Invoice Studio',
            'owner_name' => 'Independent Consultant',
            'tax_id' => 'TAX-DEMO-001',
            'email' => 'billing@example.test',
            'phone' => '+1 (555) 010-9988',
            'website' => 'https://example.test',
            'address' => "Demo Street 123\nCity, Country",
            'default_currency' => 'USD',
            'default_payment_terms_days' => 15,
            'default_notes' => 'Professional services rendered according to contract.',
            'default_footer' => 'Demo Business · Wire Transfer Details',
            'invoice_prefix' => '',
            'invoice_number_digits' => 5,
            'next_invoice_number' => 1,
            'default_template' => 'emerald',
            'default_page_size' => 'letter',
            'default_locale' => 'es',
            'timezone' => 'UTC',
        ]);

        $item = CatalogItem::create([
            'name' => 'Software Engineering & Consulting',
            'description' => 'Full-stack software engineering and architecture consulting',
            'unit' => 'hours',
            'unit_price_cents' => 15000,
            'tax_rate_bps' => 0,
            'is_active' => true,
        ]);

        $client = Client::create([
            'name' => 'Acme Corporation',
            'company' => 'Acme Global Holdings',
            'tax_id' => 'ACM-998877',
            'billing_address' => "100 Market St\nSan Francisco, CA",
            'email' => 'accounts@acme.test',
            'currency' => 'USD',
            'locale' => 'en',
        ]);
    }
}
