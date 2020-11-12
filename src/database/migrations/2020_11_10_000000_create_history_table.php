<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;

class CreateHistoryTable extends Migration
{

    public function up() : void
    {
        try {
            Schema::create(config('laravel-history.table'), function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->timestamp('created_at')->nullable();
                $table->uuidMorphs('model');
                $table->nullableUuidMorphs('user');
                $table->string('action');
                $table->text('meta')->nullable();
            });

        } catch (PDOException $ex) {
            $this->down();
            throw $ex;
        }
    }

    public function down() : void
    {
        Schema::drop(config('laravel-history.table'));
    }

}