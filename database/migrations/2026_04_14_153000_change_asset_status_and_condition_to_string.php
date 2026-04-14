<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE assets MODIFY status VARCHAR(120) NOT NULL DEFAULT 'available'");
            DB::statement("ALTER TABLE assets MODIFY `condition` VARCHAR(120) NOT NULL DEFAULT 'good'");

            return;
        }

        if ($driver === 'sqlite') {
            $this->rebuildSqliteAssetsTable(asStringColumns: true);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('assets')
                ->whereNotIn('status', ['available', 'borrowed', 'maintenance', 'lost'])
                ->update(['status' => 'available']);

            DB::table('assets')
                ->whereNotIn('condition', ['good', 'minor_damage', 'major_damage', 'under_repair'])
                ->update(['condition' => 'good']);

            DB::statement("ALTER TABLE assets MODIFY status ENUM('available', 'borrowed', 'maintenance', 'lost') NOT NULL DEFAULT 'available'");
            DB::statement("ALTER TABLE assets MODIFY `condition` ENUM('good', 'minor_damage', 'major_damage', 'under_repair') NOT NULL DEFAULT 'good'");

            return;
        }

        if ($driver === 'sqlite') {
            $this->rebuildSqliteAssetsTable(asStringColumns: false);
        }
    }

    private function rebuildSqliteAssetsTable(bool $asStringColumns): void
    {
        DB::statement('PRAGMA foreign_keys = OFF');

        Schema::create('assets_rebuild', function (Blueprint $table) use ($asStringColumns): void {
            $table->id();
            $table->string('brand');
            $table->string('model');
            $table->string('serial_number')->unique();
            $table->string('category')->default('other');

            if ($asStringColumns) {
                $table->string('condition', 120)->default('good');
                $table->string('status', 120)->default('available');
            } else {
                $table->enum('condition', ['good', 'minor_damage', 'major_damage', 'under_repair'])->default('good');
                $table->enum('status', ['available', 'borrowed', 'maintenance', 'lost'])->default('available');
            }

            $table->string('barcode')->nullable()->unique();
            $table->string('qr_code_hash')->nullable()->unique();
            $table->json('specifications')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('category');
            $table->index('status');
        });

        DB::statement('INSERT INTO assets_rebuild (id, brand, model, serial_number, category, "condition", status, barcode, qr_code_hash, specifications, notes, created_at, updated_at) '
            . 'SELECT id, brand, model, serial_number, category, "condition", status, barcode, qr_code_hash, specifications, notes, created_at, updated_at FROM assets');

        Schema::drop('assets');
        Schema::rename('assets_rebuild', 'assets');

        DB::statement('PRAGMA foreign_keys = ON');
    }
};
