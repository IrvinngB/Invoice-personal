<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->boolean('show_payment_instructions')->default(true)->after('show_signature');
        });

        // Nullable on invoices: null means "follow the business profile default".
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('show_payment_instructions')->nullable()->after('show_signature');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('show_payment_instructions');
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn('show_payment_instructions');
        });
    }
};
