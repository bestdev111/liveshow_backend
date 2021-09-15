<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class PaymentRelatedMigrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if(!Schema::hasTable('subscriptions')) {

            Schema::create('subscriptions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('subscription_type')->comment('month,year,days');
                $table->string('plan')->default('');
                $table->float('amount')->default(0.00);
                $table->integer('total_subscription')->default(0);
                $table->tinyInteger('popular_status')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });

        }

        if(!Schema::hasTable('user_subscriptions')) {

            Schema::create('user_subscriptions', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('subscription_id');
                $table->integer('user_id');
                $table->string('payment_id');
                $table->float('amount')->default(0.00);
                $table->float('coupon_amount')->default(0.00);
                $table->float('subscription_amount')->default(0.00);
                $table->string('payment_mode')->default(CARD);
                $table->dateTime('expiry_date')->nullable();
                $table->tinyInteger('is_cancelled')->default(0);
                $table->tinyInteger('is_coupon_applied')->default(0);
                $table->string('coupon_code')->default('');
                $table->text('coupon_reason')->nullable();
                $table->text('cancel_reason')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });

        }

        if(!Schema::hasTable('live_video_payments')) {

            Schema::create('live_video_payments', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('live_video_id');
                $table->integer('user_id');
                $table->integer('live_video_viewer_id');
                $table->string('payment_id');
                $table->string('payment_mode')->default(CARD);
                $table->float('live_video_amount')->default(0.00);
                $table->float('amount')->default(0.00);
                $table->float('admin_amount')->default(0.00);
                $table->float('user_amount')->default(0.00);
                $table->tinyInteger('is_coupon_applied')->default(0);
                $table->float('coupon_amount')->default(0.00);
                $table->string('currency')->default('$');
                $table->string('coupon_code')->default('');
                $table->text('coupon_reason')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });

        }

        if(!Schema::hasTable('redeems')) {

            Schema::create('redeems', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('user_id');
                $table->float('total')->default(0.00);
                $table->float('paid')->default(0.00);     
                $table->float('remaining')->default(0.00);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });

        }

        if(!Schema::hasTable('redeem_requests')) {

            Schema::create('redeem_requests', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('user_id');
                $table->float('request_amount')->default(0.00);
                $table->float('paid_amount')->default(0.00);
                $table->string('payment_mode')->default(CARD);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });

        }

        if(!Schema::hasTable('pay_per_views')) {

            Schema::create('pay_per_views', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('user_id');
                $table->integer('video_id');
                $table->string('payment_id');
                $table->string('type_of_subscription')->default('');
                $table->string('type_of_user')->default('');
                $table->float('amount')->default(0.00);
                $table->float('admin_amount')->default(0.00);
                $table->float('user_amount')->default(0.00);
                $table->string('payment_mode')->default(CARD);
                $table->dateTime('expiry_date')->nullable();
                $table->dateTime('ppv_date')->nullable();
                $table->text('reason')->nullable();
                $table->tinyInteger('is_watched')->default(0);
                $table->tinyInteger('is_coupon_applied')->default(0);
                $table->string('coupon_code')->default('');
                $table->float('coupon_amount')->default(0.00);
                $table->float('ppv_amount')->default(0.00)->comment('Pay per view Amount');
                $table->text('coupon_reason')->nullable();
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
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('user_subscriptions');
        Schema::dropIfExists('live_video_payments');
        Schema::dropIfExists('redeems');
        Schema::dropIfExists('redeem_requests');
        Schema::dropIfExists('pay_per_views');
    }
}
