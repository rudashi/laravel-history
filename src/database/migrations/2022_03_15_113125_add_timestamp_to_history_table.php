<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        if (!Schema::hasColumn(config('laravel-history.table'), 'updated_at')) {
            Schema::table(config('laravel-history.table'), static function (Blueprint $table) {
                $table->timestamp('updated_at')->nullable()->after('created_at');
                $table->string("model_type")->nullable()->change();
                $table->uuid("model_id")->nullable()->change();
                $table->json('meta')->change();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn(config('laravel-history.table'), 'updated_at')) {
            Schema::table(config('laravel-history.table'), static function (Blueprint $table) {
                $table->dropColumn('updated_at');
                $table->string("model_type")->nullable(false)->change();
                $table->uuid("model_id")->nullable(false)->change();
                $table->text('meta')->change();
            });
        }
    }

};
