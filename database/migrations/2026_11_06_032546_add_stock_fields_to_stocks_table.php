
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('sku')->nullable()->unique();
            $table->text('description')->nullable();

            $table->unsignedInteger('quantity')->default(0);
            $table->string('unit')->default('pcs');
            $table->decimal('price', 15, 2)->nullable();
            $table->unsignedInteger('min_stock')->default(5);

            $table->string('status')->default('available');
            $table->string('cover_image_path')->nullable();
        });

        // Hubungkan data stok lama ke akun member.
        $memberId = DB::table('users')
            ->where('username', 'member')
            ->value('id');

        if ($memberId === null) {
            throw new RuntimeException(
                'Akun member tidak ditemukan.'
            );
        }

        DB::table('stocks')
            ->whereNull('user_id')
            ->update(['user_id' => $memberId]);

        // Jadikan user_id wajib diisi.
        Schema::table('stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')
                ->nullable(false)
                ->change();
        });
    }

    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropUnique(['sku']);

            $table->dropColumn([
                'user_id',
                'name',
                'sku',
                'description',
                'quantity',
                'unit',
                'price',
                'min_stock',
                'status',
                'cover_image_path',
            ]);
        });
    }
};
