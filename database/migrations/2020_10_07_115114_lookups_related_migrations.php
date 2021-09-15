<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class LookupsRelatedMigrations extends Migration
{

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function up()
    {   
        if(!Schema::hasTable('settings')) {

            Schema::create('settings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('key');
                $table->text('value');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
            
        }

        if (!Schema::hasTable('pages')) {

            Schema::create('pages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('unique_id')->default(uniqid());
                $table->string('title')->unique();
                $table->string('heading');
                $table->text('description');
                $table->enum('type',['about','privacy','terms','faq','help','contact','others'])->default('others');
                $table->tinyInteger('status')->default(APPROVED);
                $table->softDeletes();
                $table->timestamps();
            });

        }

        if (!Schema::hasTable('mobile_registers')) {

            Schema::create('mobile_registers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('type');
                $table->integer('count');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('page_counters')) {

            Schema::create('page_counters', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('page');
                $table->integer('count');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });

        }

        if (!Schema::hasTable('languages')) {

            Schema::create('languages', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('folder_name');
                $table->string('language');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });

        }

        if (!Schema::hasTable('notification_templates')) {

            Schema::create('notification_templates', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('type');
                $table->string('subject');
                $table->text('content');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });

        }

        if (!Schema::hasTable('coupons')) {

            Schema::create('coupons', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('unique_id')->default(uniqid());
                $table->string('title');
                $table->string('coupon_code')->unique();
                $table->string('amount_type')->default("");
                $table->float('amount')->default(0.00);
                $table->date('expiry_date')->nullable();
                $table->mediumText('description')->nullable();
                $table->integer('no_of_users_limit')->default(0);
                $table->integer('per_users_limit')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('settings');
        Schema::dropIfExists('mobile_registers');
        Schema::dropIfExists('page_counters');
        Schema::dropIfExists('languages');
        Schema::dropIfExists('pages');
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('coupons');
    }
}
