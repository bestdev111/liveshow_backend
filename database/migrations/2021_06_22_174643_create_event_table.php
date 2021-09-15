<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEventTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->name();
            $table->email();
            $table->date();
            $table->url()
            $table->description()
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('name');
        Schema::dropIfExists('email');
        Schema::dropIfExists('url');
        Schema::dropIfExists('date');
        Schema::dropIfExists('description');

    }
}
