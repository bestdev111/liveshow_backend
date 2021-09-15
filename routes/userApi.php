<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
Route::get('/clear-cache', function() {
    $run = Artisan::call('config:clear');
    $run = Artisan::call('cache:clear');
    $run = Artisan::call('config:cache');
    return 'FINISHED';  
});

Route::group(['prefix' => 'userApi', 'middleware' => 'cors'], function(){

    Route::post('/register','UserApiController@register');
    
    Route::post('/login','UserApiController@login');

    Route::get('/userDetails','UserApiController@user_details');

    Route::post('/updateProfile', 'UserApiController@update_profile');

    Route::post('/forgotpassword', 'UserApiController@forgot_password');

    Route::post('/changePassword', 'UserApiController@change_password');

    Route::post('/deleteAccount', 'UserApiController@delete_account');

    Route::post('/settings', 'UserApiController@settings');


    // Videos and home

    Route::post('/home' , 'UserApiController@home');

    Route::post('/popular_videos', 'UserApiController@popular_videos');

    Route::post('/subscription_plans', 'UserApiController@subscriptions');
    
    Route::post('/suggestions', 'UserApiController@suggestions');

    Route::post('/add_follower', 'UserApiController@add_follower');

    Route::post('/remove_follower', 'UserApiController@remove_follower');

    Route::post('/save_live_video', 'UserApiController@save_live_video');

    Route::post('/single_video', 'UserApiController@single_video');

    Route::post('/save_chat', 'UserApiController@save_chat');

    Route::post('/block_user', 'UserApiController@block_user');

    Route::post('/unblock_user', 'UserApiController@unblock_user');

    Route::post('/followers_list', 'UserApiController@followers_list');

    Route::post('/followings_list', 'UserApiController@followings_list');

    Route::post('/blockersList', 'UserApiController@blockersList');

    Route::post('/pay_now', 'UserApiController@pay_now');

    Route::post('/video_subscription', 'UserApiController@video_subscription');

    Route::post('/get_viewers', 'UserApiController@get_viewers');

    Route::post('/subscribedPlans', 'UserApiController@subscribedPlans');

    Route::post('/peerProfile', 'UserApiController@peerProfile');

    Route::post('/close_streaming', 'UserApiController@close_streaming');

    Route::get('/checkVideoStreaming/{id?}', 'UserApiController@checkVideoStreaming');

    Route::post('check_user_call', 'UserApiController@check_user_call');

    Route::post('erase_videos', 'UserApiController@erase_videos');

    Route::get('/privacy', 'UserApiController@privacy');

    Route::get('/terms_condition', 'UserApiController@terms');

    Route::post('/live-video/snapshot' , 'UserApiController@live_video_snapshot');

    Route::post('/subscription/invoice', 'UserApiController@subscription_invoice');

    Route::post('/videos/info', 'UserApiController@videos_info');

    Route::post('/videos/paid-info', 'UserApiController@paid_videos');

    Route::post('/video/invoice', 'UserApiController@live_video_invoice');

    Route::post('redeems/list', 'UserApiController@redeems');

    Route::post('redeems/request', 'UserApiController@send_redeem_request');

    Route::post('redeem/request/cancel', 'UserApiController@redeem_request_cancel');

    Route::post('redeem/request/list', 'UserApiController@redeem_request_list');

    Route::post('logout', 'UserApiController@logout');

    Route::post('check/token-valid', 'UserApiController@check_token_valid');

    Route::post('plan_detail', 'UserApiController@plan_detail');

    Route::post('video_details', 'UserApiController@video_details');

    Route::post('streaming/status', 'UserApiController@streaming_status');

   // Stripe Payment

    Route::post('/stripe_payment', 'UserApiController@stripe_payment');

    Route::post('card_details', 'UserApiController@card_details');

    Route::post('payment_card_add', 'UserApiController@cards_add');

    Route::post('default_card', 'UserApiController@default_card');

    Route::post('delete_card', 'UserApiController@delete_card');

    Route::post('stripe/live/ppv', 'UserApiController@stripe_live_ppv');

    Route::post('admin', 'UserApiController@admin');

    // Automatic subscription with cancel

    Route::post('/cancel/subscription', 'UserApiController@autorenewal_cancel');

    Route::post('/autorenewal/enable', 'UserApiController@autorenewal_enable');


    // VOD

    Route::post('/vod/videos/save', 'UserApiController@vod_videos_save');

    Route::post('/vod/videos/delete', 'UserApiController@vod_videos_delete'); 

    Route::post('/vod/videos/list', 'UserApiController@vod_videos_list'); 

    Route::post('/vod/videos/status', 'UserApiController@vod_videos_status');

    Route::post('/vod/videos/view', 'UserApiController@vod_videos_view');

    Route::post('vod_videos_owner_list', 'UserApiController@vod_videos_owner_list');
    
    Route::post('vod_videos_owner_view', 'UserApiController@vod_videos_owner_view');

    Route::post('/vod/videos/set/ppv', 'UserApiController@vod_videos_set_ppv');

    Route::post('/vod/videos/remove/ppv', 'UserApiController@vod_videos_remove_ppv');

    Route::post('/vod/videos/ppv/history', 'UserApiController@ppv_history');

    Route::post('/vod/videos/pay/now', 'UserApiController@vod_videos_payment');

    Route::post('/ppv/revenue', 'UserApiController@ppv_revenue');

    Route::post('/vod/videos/search', 'UserApiController@vod_videos_search');

    Route::post('vod/videos/oncomplete/ppv', 'UserApiController@vod_videos_oncomplete_ppv');

    Route::post('vod/videos/stripe_ppv', 'UserApiController@vod_videos_stripe_ppv');

    Route::post('vod/videos/publish', 'UserApiController@vod_videos_publish');

    Route::post('vod/invoice', 'UserApiController@vod_invoice');


    Route::post('vod_payment_apple_pay', 'UserApiController@vod_payment_apple_pay');

    Route::post('subscriptions_payment_apple_pay', 'UserApiController@subscriptions_payment_apple_pay');

    Route::post('ppv_payment_apple_pay', 'UserApiController@ppv_payment_apple_pay');

    // Streamer Gallery

    Route::post('streamer/galleries/save', 'UserApiController@streamer_galleries_save');

    Route::post('streamer/galleries/list', 'UserApiController@streamer_galleries_list');

    Route::post('streamer/galleries/delete', 'UserApiController@streamer_galleries_delete');

    // Coupons

    Route::post('/apply/coupon/subscription', 'UserApiController@apply_coupon_subscription');

    Route::post('apply/coupon/live-videos', 'UserApiController@apply_coupon_live_videos');

    Route::post('apply/coupon/vod-videos', 'UserApiController@apply_coupon_vod_videos');

    // Pages 

    Route::post('/pages/list', 'UserApiController@pages_list');

    Route::post('/pages/view', 'UserApiController@pages_view');

    Route::get('check/social', 'UserApiController@check_social');

    Route::get('/daily/page' , 'UserApiController@daily_view_count');

    Route::post('/become/operator', 'UserApiController@become_creator');


    // site_settings
    Route::get('site/settings', 'UserApiController@site_settings');

    Route::post('user/view', 'UserApiController@user_view');


    // Groups 

    Route::post('groups/index' , 'UserApiController@live_groups_index');

    Route::post('groups/view' , 'UserApiController@live_groups_view');

    Route::post('groups/save' , 'UserApiController@live_groups_save');

    Route::post('groups/delete' , 'UserApiController@live_groups_delete');

    Route::post('groups/members' , 'UserApiController@live_groups_members');

    Route::post('groups/members/add' , 'UserApiController@live_groups_members_add');

    Route::post('groups/members/remove' , 'UserApiController@live_groups_members_remove');


    // Live tv video

    Route::post('/single/live/video' , 'UserApiController@custom_live_videos_view');

    Route::post('/custom/live/videos' , 'UserApiController@custom_live_videos');

    Route::post('/custom/video/save', 'UserApiController@custom_live_videos_save');

    Route::post('/custom/video/delete', 'UserApiController@custom_live_videos_delete');

    Route::post('/custom/video/status', 'UserApiController@custom_live_videos_change_status');

    Route::post('/custom/videos/search', 'UserApiController@custom_live_videos_search');

    // search

    Route::post('/search', 'UserApiController@search');

    Route::post('/search/user', 'UserApiController@searchDetails')->name('search.users');

    Route::post('/search/live/videos', 'UserApiController@searchDetails')->name('search.live_videos');

    Route::post('/search/livetv', 'UserApiController@searchDetails')->name('search.live_tv');

    Route::post('/searchUser','UserApiController@searchUser'); // Dont use

    // Bell Notificatioons

    Route::post('/user/notifications', 'UserApiController@user_notifications');
    
    Route::post('/status/notifications', 'UserApiController@change_notifications_status');

    Route::post('/get/notification/count', 'UserApiController@notification_count');

});

Route::group(['prefix' => 'api/user', 'middleware' => 'cors'], function() {

    Route::any('pages_list' , 'ApplicationController@static_pages_api');

    Route::any('/get_settings_json','ApplicationController@get_settings_json');

    /***
     *
     * User Account releated routs
     *
     */

    Route::post('register', 'Api\UserApiController@register');

    Route::post('login', 'Api\UserApiController@login');

    Route::post('forgot_password', 'Api\UserApiController@forgot_password');

    // Route::group(['middleware' => ''], function () {

        Route::post('profile', 'Api\UserApiController@profile');

        Route::post('update_profile', 'Api\UserApiController@update_profile');

        Route::post('change_password', 'Api\UserApiController@change_password');

        Route::post('delete_account', 'Api\UserApiController@delete_account');
       
        Route::post('logout', 'Api\UserApiController@logout');
        
        Route::post('become_creator', 'Api\UserApiController@become_creator');

        Route::post('users_suggestions', 'Api\UserApiController@users_suggestions');

        Route::post('users_follow', 'Api\UserApiController@users_follow');

        Route::post('users_unfollow', 'Api\UserApiController@users_unfollow');

        Route::post('followers', 'Api\UserApiController@followers');

        Route::post('followings', 'Api\UserApiController@followings');

        Route::post('users_block', 'Api\UserApiController@users_block');

        Route::post('users_unblock', 'Api\UserApiController@users_unblock');

        Route::post('users_blocked_list', 'Api\UserApiController@users_blocked_list');


        Route::post('other_profile', 'Api\UserApiController@other_profile');

        Route::post('other_profile_followers', 'Api\UserApiController@other_profile_followers');

        Route::post('other_profile_followings', 'Api\UserApiController@other_profile_followings');

        Route::post('other_profile_galleries', 'Api\UserApiController@other_profile_galleries');

        // User Cards Management

        Route::post('cards_add', 'Api\UserApiController@cards_add');

        Route::post('cards_list' , 'Api\UserApiController@cards_list');

        Route::post('cards_default', 'Api\UserApiController@cards_default');

        Route::post('cards_delete', 'Api\UserApiController@cards_delete');

        Route::post('payment_mode_default', 'Api\UserApiController@payment_mode_default');

        // Bell notifications
        Route::post('bell_notifications', 'Api\UserApiController@bell_notifications');

        Route::post('bell_notifications_count', 'Api\UserApiController@bell_notifications_count');

        // Groups Management 

        Route::post('live_groups_index' , 'Api\UserApiController@live_groups_index');

        Route::post('live_groups_view' , 'Api\UserApiController@live_groups_view');

        Route::post('live_groups_save' , 'Api\UserApiController@live_groups_save');

        Route::post('live_groups_delete' , 'Api\UserApiController@live_groups_delete');

        Route::post('live_groups_members' , 'Api\UserApiController@live_groups_members');
        
        Route::post('live_groups_members_search' , 'Api\UserApiController@live_groups_members_search');

        Route::post('live_groups_members_add' , 'Api\UserApiController@live_groups_members_add');

        Route::post('live_groups_members_remove' , 'Api\UserApiController@live_groups_members_remove');


        // VOD MANAGEMENT OWNER ROUTES

        Route::group(['middleware' => 'StreamerAllowed'], function() {

            // Gallery options

            Route::post('galleries' , 'Api\UserApiController@galleries');

            Route::post('galleries_save' , 'Api\UserApiController@galleries_save');

            Route::post('galleries_delete' , 'Api\UserApiController@galleries_delete');

            Route::post('galleries_other_profile' , 'Api\UserApiController@galleries_other_profile');

            // VOD management

            Route::post('vod_videos_owner_dashboard', 'Api\UserApiController@vod_videos_owner_dashboard');

            Route::post('vod_videos_owner_list', 'Api\UserApiController@vod_videos_owner_list');

            Route::post('vod_videos_owner_view', 'Api\UserApiController@vod_videos_owner_view');

            Route::post('vod_videos_owner_save', 'Api\UserApiController@vod_videos_owner_save');

            Route::post('vod_videos_owner_delete', 'Api\UserApiController@vod_videos_owner_delete');

            Route::post('vod_videos_owner_publish_status', 'Api\UserApiController@vod_videos_owner_publish_status');

            Route::post('vod_videos_owner_set_ppv', 'Api\UserApiController@vod_videos_owner_set_ppv');

            Route::post('vod_videos_owner_remove_ppv', 'Api\UserApiController@vod_videos_owner_remove_ppv');

            // Live TV management

            Route::post('livetv_owner_list', 'Api\UserApiController@livetv_owner_list');

            Route::post('livetv_owner_view', 'Api\UserApiController@livetv_owner_view');

            Route::post('livetv_owner_save', 'Api\UserApiController@livetv_owner_save');

            Route::post('livetv_owner_delete', 'Api\UserApiController@livetv_owner_delete');
            
            Route::post('livetv_owner_status', 'Api\UserApiController@livetv_owner_status');
        
        });

        // VOD Videos for other users

        Route::post('vod_videos_list', 'Api\UserApiController@vod_videos_list');

        Route::post('vod_videos_suggestions', 'Api\UserApiController@vod_videos_suggestions');
        
        Route::post('vod_videos_view', 'Api\UserApiController@vod_videos_view');

        Route::post('vod_videos_invoice', 'Api\UserApiController@vod_videos_invoice');

        Route::post('vod_videos_check_coupon_code', 'Api\UserApiController@vod_videos_check_coupon_code');

        Route::post('vod_videos_payment_by_card', 'Api\UserApiController@vod_videos_payment_by_card');

        Route::post('vod_videos_payment_by_paypal', 'Api\UserApiController@vod_videos_payment_by_paypal');


        // Live TV for other users

        Route::post('livetv_list', 'Api\UserApiController@livetv_list');

        Route::post('livetv_suggestions', 'Api\UserApiController@livetv_suggestions');

        Route::post('livetv_search', 'Api\UserApiController@livetv_search');

        Route::post('livetv_view', 'Api\UserApiController@livetv_view');

        // Redeems management

        Route::post('redeems', 'Api\UserApiController@redeems_index');

        Route::post('redeems_requests', 'Api\UserApiController@redeems_requests');

        Route::post('redeems_requests_send', 'Api\UserApiController@redeems_requests_send');

        Route::post('redeems_requests_cancel', 'Api\UserApiController@redeems_requests_cancel');

        // Subscriptions management

        Route::get('subscriptions_index', 'Api\UserApiController@subscriptions_index');

        Route::post('subscriptions_view', 'Api\UserApiController@subscriptions_view');

        Route::post('subscriptions_check_coupon_code', 'Api\UserApiController@subscriptions_check_coupon_code');
        
        Route::post('subscriptions_payment_by_card', 'Api\UserApiController@subscriptions_payment_by_card');

        Route::post('subscriptions_payment_by_paypal', 'Api\UserApiController@subscriptions_payment_by_paypal');

        Route::post('subscriptions_history', 'Api\UserApiController@subscriptions_history');

        Route::post('subscriptions_autorenewal_status', 'Api\UserApiController@subscriptions_autorenewal_status');

    // });

    // Route::group(['middleware' => ''], function () {
    // 
        
        Route::post('users_search' , 'Api\UserApiController@users_search');

        Route::post('home', 'Api\LiveVideoApiController@home');

        Route::post('live_videos_public', 'Api\LiveVideoApiController@live_videos_public');

        Route::post('live_videos_private', 'Api\LiveVideoApiController@live_videos_private');

        Route::post('live_videos_suggestions', 'Api\LiveVideoApiController@live_videos_suggestions');

        Route::post('live_videos_popular', 'Api\LiveVideoApiController@live_videos_popular');

        Route::post('live_videos_search', 'Api\LiveVideoApiController@live_videos_search');

        Route::post('live_videos_view', 'Api\LiveVideoApiController@live_videos_view');

        Route::post('live_videos_chat', 'Api\LiveVideoApiController@live_videos_chat');

        Route::post('live_videos_check_coupon_code', 'Api\LiveVideoApiController@live_videos_check_coupon_code');
        
        Route::post('live_videos_payment_by_card', 'Api\LiveVideoApiController@live_videos_payment_by_card');

        Route::post('live_videos_payment_by_paypal', 'Api\LiveVideoApiController@live_videos_payment_by_paypal');

        Route::post('live_videos_payment_history', 'Api\LiveVideoApiController@live_videos_payment_history');

        // Live videos owner list

        Route::post('live_videos_groups_list', 'Api\LiveVideoApiController@live_videos_groups_list');
        
        Route::post('live_videos_broadcast_start', 'Api\LiveVideoApiController@live_videos_broadcast_start');

        Route::post('live_events_schedule_start', 'Api\LiveVideoApiController@live_events_schedule_start');

        Route::get('live_events_schedule_get', 'Api\LiveVideoApiController@live_events_schedule_get');
        
        Route::get('live_events_schedule_all', 'Api\LiveVideoApiController@live_events_schedule_all');

        Route::post('live_videos_check_streaming', 'Api\LiveVideoApiController@live_videos_check_streaming');

        Route::post('live_videos_viewer_update', 'Api\LiveVideoApiController@live_videos_viewer_update');

        Route::post('live_videos_snapshot_save', 'Api\LiveVideoApiController@live_videos_snapshot_save');

        Route::post('live_videos_broadcast_stop', 'Api\LiveVideoApiController@live_videos_broadcast_stop');

        Route::post('live_videos_erase_old_streamings', 'Api\LiveVideoApiController@live_videos_erase_old_streamings');

        Route::post('live_videos_owner_list', 'Api\LiveVideoApiController@live_videos_owner_list');

        Route::post('live_videos_owner_view', 'Api\LiveVideoApiController@live_videos_owner_view');


    // });
});
