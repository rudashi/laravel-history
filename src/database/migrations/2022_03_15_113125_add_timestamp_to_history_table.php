<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::whenTableDoesntHaveColumn(config('laravel-history.table'), 'updated_at', static function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable()->after('created_at');
        });

        if (Schema::hasTable(config('laravel-history.table'))) {
            Schema::table(config('laravel-history.table'), static function (Blueprint $table) {
                $table->string('model_type')->nullable()->change();
                $table->uuid('model_id')->nullable()->change();
                $table->json('meta')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable(config('laravel-history.table'))) {
            Schema::table(config('laravel-history.table'), static function (Blueprint $table) {
                $table->string('model_type')->nullable(false)->change();
                $table->uuid('model_id')->nullable(false)->change();
                $table->text('meta')->change();
            });
        }

        Schema::whenTableHasColumn(config('laravel-history.table'), 'updated_at', static function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }

};
