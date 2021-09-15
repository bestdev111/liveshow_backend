<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAuthRelatedTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('users')) {

            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('token');
                $table->string('token_expiry');
                $table->text('device_token')->nullable();
                $table->integer('user_type')->defaut(0)->comment('0 - UnPaid User, 1 - Paid User');
                $table->string('picture')->default(asset('images/default-profile.jpg'));
                $table->string('social_unique_id')->default('');
                $table->enum('gender',['male','female','others'])->default('male');
                $table->text('description')->nullable();
                $table->text('gallery_description')->nullable();
                $table->string('mobile')->default('');
                $table->string('dob')->default('');
                $table->integer('age')->default(0);
                $table->string('address')->default('');
                $table->double('latitude', 15,8)->default(0.00000000);
                $table->double('longitude',15,8)->default(0.00000000);
                $table->enum('device_type',['web', 'android','ios'])->default('web');
                $table->enum('register_type',['web','android','ios'])->default('web');
                $table->enum('login_by',['manual','facebook','apple','twitter','instagram','google'])->default('manual');
                $table->string('payment_mode')->default(COD);
                $table->integer('card_id')->default(0);
                $table->integer('is_verified')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->tinyInteger('login_status')->default(1);
                $table->tinyInteger('is_content_creator')->default(0);
                $table->tinyInteger('push_status')->default(0);
                $table->string('timezone')->default('America/Los_Angeles');
                $table->string('role')->default('');
                $table->string('cover')->default(asset('images/cover.jpg'));
                $table->dateTime('expiry_date')->nullable();
                $table->integer('no_of_days')->default(0);
                $table->string('paypal_email')->nullable();
                $table->float('amount_paid')->default(0.00);
                $table->float('total')->default(0.00);
                $table->float('total_admin_amount')->default(0.00);
                $table->float('total_user_amount')->default(0.00);
                $table->float('paid_amount')->default(0.00);
                $table->float('remaining_amount')->default(0.00);
                $table->string('verification_code')->default('');
                $table->string('verification_code_expiry')->default('');
                $table->string('chat_picture')->default(asset('images/default-profile.jpg'));
                $table->integer('one_time_subscription')->comment("0 - Not Subscribed , 1 - Subscribed")->default(0);
                $table->softDeletes();
                $table->rememberToken();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('admins')) {

            Schema::create('admins', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('unique_id')->default(uniqid());
                $table->string('name');
                $table->string('email')->unique();
                $table->string('password');
                $table->string('picture')->default(asset('images/default-profile.jpg'));
                $table->enum('gender',['male','female','others'])->default('male');
                $table->string('mobile');
                $table->string('address');
                $table->string('description');
                $table->string('token');
                $table->string('token_expiry');
                $table->tinyInteger('status')->default(YES);
                $table->string('timezone')->default('America/Los_Angeles');
                $table->tinyInteger('admin_type')->default(1)->comments('1 - SUPER ADMIN , 2 - ADMIN , 3 - SUB ADMIN');
                $table->rememberToken();
                $table->softDeletes();
                $table->timestamps();
            
            });
        
        }

        if(!Schema::hasTable('cards')) {

            Schema::create('cards', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('unique_id')->default(uniqid());
                $table->integer('user_id');
                $table->string('card_holder_name')->default("");
                $table->string('card_type');
                $table->string('customer_id');
                $table->string('last_four');
                $table->string('card_token');
                $table->integer('is_default')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
            
        }

        if(!Schema::hasTable('user_notifications')) {

            Schema::create('user_notifications', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('unique_id')->default(rand());
                $table->integer('user_id');
                $table->string('type');
                $table->integer('link_id');
                $table->integer('notifier_user_id')->comment('EX: User A Followed USER B. This column will store USER A ID ( USER B ID = user_id)');
                $table->text('notification');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
            
        }

        if(!Schema::hasTable('followers')) {

            Schema::create('followers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('user_id')->comment('Login User ID');
                $table->integer('follower');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
            
        }

        if(!Schema::hasTable('viewers')) {

            Schema::create('viewers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('video_id');
                $table->integer('user_id');
                $table->integer('count')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
            
        }


        if(!Schema::hasTable('user_coupons')) {

            Schema::create('user_coupons', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('user_id');
                $table->string('coupon_code')->default('');
                $table->tinyInteger('no_of_times_used')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
            
        }

        
        if(!Schema::hasTable('streamer_galleries')) {

            Schema::create('streamer_galleries', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('user_id');
                $table->string('image')->default(asset('images/default-image.jpg'));
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
            
        }


        if(!Schema::hasTable('block_lists')) {

            Schema::create('block_lists', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('user_id');
                $table->integer('block_user_id');
                $table->text('description')->nullable();
                $table->tinyInteger('status')->default(1);
                $table->softDeletes();
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
        Schema::dropIfExists('users');
        Schema::dropIfExists('admins');
        Schema::dropIfExists('cards');
        Schema::dropIfExists('user_notifications');
        Schema::dropIfExists('followers');
        Schema::dropIfExists('viewers');
        Schema::dropIfExists('user_coupons');
        Schema::dropIfExists('streamer_galleries');
        Schema::dropIfExists('block_lists');
    }
}
