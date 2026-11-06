<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // The stock fields are already created by create_stocks_table.
    }

    public function down(): void
    {
        // The create_stocks_table migration owns the stock schema.
    }
};
