<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('business_profiles', function (Blueprint $table) {
            $table->boolean('show_signature')->default(false)->after('default_footer');
            $table->string('signature_path')->nullable()->after('show_signature');
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('show_signature')->nullable()->after('pdf_template');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('show_signature');
        });

        Schema::table('business_profiles', function (Blueprint $table) {
            $table->dropColumn(['show_signature', 'signature_path']);
        });
    }
};
