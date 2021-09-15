<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class VideoRelatedMigrations extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {   
        if(!Schema::hasTable('live_videos')) {

            Schema::create('live_videos', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('live_group_id');
                $table->integer('user_id');
                $table->string('virtual_id')->default(uniqid());
                $table->string('type')->default(TYPE_PUBLIC)->comment('Public, Private');
                $table->string('broadcast_type')->default(BROADCAST_TYPE_BROADCAST);
                $table->integer('payment_status')->default(0)->commen('0 - No, 1 - Yes');
                $table->string('title')->default('');
                $table->text('description')->nullabe();
                $table->string('browser_name')->default('')->comment("Store Streamer Browser Name");
                $table->float('amount')->default(0.00);
                $table->integer('is_streaming')->default(0);
                $table->string('snapshot')->default(asset('images/default-image.jpg'));
                $table->text('video_url')->nullabe();
                $table->integer('viewer_cnt')->default(0);
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->integer('no_of_minutes')->default(0);
                $table->string('port_no')->default('');
                $table->tinyInteger('status')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });
            
        }

        if(!Schema::hasTable('vod_videos')) {

            Schema::create('vod_videos', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('user_id');
                $table->string('title')->default('');
                $table->text('description')->nullable();
                $table->string('image')->default(asset('images/default-image.jpg'));
                $table->string('video')->default('');
                $table->string('created_by')->default(ADMIN);
                $table->tinyInteger('is_pay_per_view')->default(0);
                $table->tinyInteger('type_of_user')->default(0);
                $table->tinyInteger('type_of_subscription')->default(0);
                $table->float('amount')->default(0.00);
                $table->float('admin_amount')->default(0.00);
                $table->float('user_amount')->default(0.00);
                $table->datetime('publish_time')->nullable();
                $table->tinyInteger('publish_status')->default(0);
                $table->tinyInteger('viewer_count')->default(0);
                $table->tinyInteger('admin_status')->default(0);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });
            
        }

        if(!Schema::hasTable('custom_live_videos')) {

            Schema::create('custom_live_videos', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('user_id');
                $table->string('title')->default('');
                $table->string('image')->default(asset('images/default-image.jpg'));
                $table->text('description')->nullable();
                $table->string('rtmp_video_url')->default('');
                $table->string('hls_video_url')->default('');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });

        }

        if(!Schema::hasTable('chat_messages')) {

            Schema::create('chat_messages', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('live_video_id');
                $table->integer('user_id');
                $table->integer('live_video_viewer_id');
                $table->text('message')->nullable();
                $table->enum('type',['uv','vu'])->comment('uv - User To Viewer , pu - Viewer to User');
                $table->boolean('delivered');
                $table->tinyInteger('status')->default(1);
                $table->softDeletes();
                $table->timestamps();
            });
        }

        if(!Schema::hasTable('live_groups')) {

            Schema::create('live_groups', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('user_id');
                $table->string('name')->default('');
                $table->text('description')->nullable();
                $table->string('picture')->default(asset('images/default-image.jpg'));
                $table->string('created_by')->default(ADMIN);
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });

        }

        if(!Schema::hasTable('live_group_members')) {

            Schema::create('live_group_members', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('live_group_id');
                $table->integer('owner_id');
                $table->integer('member_id');
                $table->string('reason')->default('');
                $table->string('added_by')->default('');
                $table->tinyInteger('status')->default(1);
                $table->timestamps();
            });

        }

        if(!Schema::hasTable('abuses')) {

            Schema::create('abuses', function (Blueprint $table) {
                $table->increments('id');
                $table->string('unique_id')->default(rand());
                $table->integer('live_streaming_id');
                $table->integer('user_id');
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
        Schema::dropIfExists('live_videos');
        Schema::dropIfExists('vod_videos');
        Schema::dropIfExists('custom_live_videos');
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('live_groups');
        Schema::dropIfExists('live_group_members');
        Schema::dropIfExists('abuses');
    }
}
