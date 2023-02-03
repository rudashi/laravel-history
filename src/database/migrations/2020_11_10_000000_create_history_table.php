<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        try {
            Schema::create(config('laravel-history.table'), static function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->timestamp('created_at')->nullable();
                $table->uuidMorphs('model');
                $table->nullableUuidMorphs('user');
                $table->string('action');
                $table->text('meta')->nullable();
            });
        } catch (RuntimeException $exception) {
            $this->down();
            throw $exception;
        }
    }

    public function down(): void
    {
        Schema::drop(config('laravel-history.table'));
    }
};
