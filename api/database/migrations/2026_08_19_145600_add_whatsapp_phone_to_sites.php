<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('sites')) {
            return;
        }

        if (! Schema::hasColumn('sites', 'country_code')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->string('country_code', 8)->nullable();
            });
        }

        if (! Schema::hasColumn('sites', 'phone')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->string('phone', 32)->nullable();
            });
        }

        if (! Schema::hasColumn('sites', 'whatsapp_e164')) {
            Schema::table('sites', function (Blueprint $table) {
                $table->string('whatsapp_e164', 32)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('sites')) {
            return;
        }

        Schema::table('sites', function (Blueprint $table) {
            $columns = array_values(array_filter(
                ['whatsapp_e164', 'phone', 'country_code'],
                fn (string $column) => Schema::hasColumn('sites', $column)
            ));

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
