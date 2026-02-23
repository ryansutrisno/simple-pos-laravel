<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_histories', function (Blueprint $table) {
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE stock_histories MODIFY COLUMN type ENUM('in', 'out', 'adjustment', 'sale', 'opname', 'return')");
            } elseif ($driver === 'sqlite') {
                DB::statement("CREATE TABLE stock_histories_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    product_id INTEGER NOT NULL,
                    type TEXT NOT NULL CHECK(type IN ('in', 'out', 'adjustment', 'sale', 'opname', 'return')),
                    quantity INTEGER NOT NULL,
                    stock_before INTEGER NOT NULL,
                    stock_after INTEGER NOT NULL,
                    reference_type VARCHAR(255),
                    reference_id INTEGER UNSIGNED,
                    note TEXT,
                    user_id INTEGER UNSIGNED,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )");

                DB::statement('INSERT INTO stock_histories_new SELECT * FROM stock_histories');
                DB::statement('DROP TABLE stock_histories');
                DB::statement('ALTER TABLE stock_histories_new RENAME TO stock_histories');

                DB::statement('CREATE INDEX stock_histories_product_id_created_at_index ON stock_histories (product_id, created_at)');
                DB::statement('CREATE INDEX stock_histories_reference_type_reference_id_index ON stock_histories (reference_type, reference_id)');
            }
        });
    }

    public function down(): void
    {
        Schema::table('stock_histories', function (Blueprint $table) {
            $connection = Schema::getConnection();
            $driver = $connection->getDriverName();

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE stock_histories MODIFY COLUMN type ENUM('in', 'out', 'adjustment', 'sale', 'opname')");
            } elseif ($driver === 'sqlite') {
                DB::statement("CREATE TABLE stock_histories_new (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    product_id INTEGER NOT NULL,
                    type TEXT NOT NULL CHECK(type IN ('in', 'out', 'adjustment', 'sale', 'opname')),
                    quantity INTEGER NOT NULL,
                    stock_before INTEGER NOT NULL,
                    stock_after INTEGER NOT NULL,
                    reference_type VARCHAR(255),
                    reference_id INTEGER UNSIGNED,
                    note TEXT,
                    user_id INTEGER UNSIGNED,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
                )");

                DB::statement("INSERT INTO stock_histories_new SELECT id, product_id, type, quantity, stock_before, stock_after, reference_type, reference_id, note, user_id, created_at, updated_at FROM stock_histories WHERE type != 'return'");
                DB::statement('DROP TABLE stock_histories');
                DB::statement('ALTER TABLE stock_histories_new RENAME TO stock_histories');

                DB::statement('CREATE INDEX stock_histories_product_id_created_at_index ON stock_histories (product_id, created_at)');
                DB::statement('CREATE INDEX stock_histories_reference_type_reference_id_index ON stock_histories (reference_type, reference_id)');
            }
        });
    }
};
