<?php
Route::group(['middleware' => 'web'], function() {

	Route::get('/payment/failure' , 'ApplicationController@payment_failure')->name('payment.failure');

	Route::get('/clear-cache', 'ApplicationController@clear_cache')->name('clear-cache');

	// Generral configuration routes 

	Route::any('users_unique_id_update' , 'ApplicationController@users_unique_id_update');

	Route::post('project/configurations' , 'ApplicationController@configuration_site');

	Route::get('demo_credential_cron' , 'ApplicationController@demo_credential_cron');

	Route::get('video_tapes_auto_clear_cron' , 'ApplicationController@video_tapes_auto_clear_cron');


	Route::post('save_admin_control', 'ApplicationController@save_admin_control');

	Route::get('/generate/index' , 'HomeController@generate_index');

	Route::get('/sendpush' , 'HomeController@send_push');

	Route::get('/message/save' , 'HomeController@message_save');

	Route::get('video_detail/{id}', 'ApplicationController@video_detail');

	Route::get('/privacy', 'UserApiController@privacy')->name('user.privacy');

	Route::get('/terms_condition', 'UserApiController@terms')->name('user.terms');

	//Cron publish video

	Route::get('cron/publish/video', 'ApplicationController@cron_vod_publish_video');

	// Social Login

	Route::get('/test',  'HomeController@test');

	Route::get('/social', array('as' => 'SocialLogin' , 'uses' => 'SocialAuthController@redirect'));

	Route::get('/callback/{provider}', 'SocialAuthController@callback');

	Route::get('/email/verification' , 'HomeController@email_verify')->name('email.verify');

	Route::get('cron_delete_video', 'ApplicationController@cron_delete_video');

	Route::get('remainder-for-subscription', 'ApplicationController@send_notification_user_payment');

	Route::get('user_payment_expiry', 'ApplicationController@user_payment_expiry_new');

	Route::get('/', 'AdminController@dashboard')->name('dashboard');


	Route::group(['prefix' => 'admin' , 'as' => 'admin.'], function() {

		Route::get('/login', 'Auth\AdminAuthController@showLoginForm')->name('login');

	    Route::post('login', 'Auth\AdminAuthController@login')->name('login.post');

	    // Registration Routes...

	    Route::get('register', 'Auth\AdminAuthController@showRegistrationForm');

	    Route::post('register', 'Auth\AdminAuthController@register');

	    // Password Reset Routes...
	    Route::get('password/reset/{token?}', 'Auth\AdminPasswordController@showResetForm');

	    Route::post('password/email', 'Auth\AdminPasswordController@sendResetLinkEmail');

	    Route::post('password/reset', 'Auth\AdminPasswordController@reset');

	});

	Route::group(['middleware' => 'AdminMiddleware', 'prefix' => 'admin' , 'as' => 'admin.'], function() {

	    Route::get('/control', 'AdminController@control')->name('control');
	    
	    Route::get('logout', 'Auth\AdminAuthController@logout')->name('logout');

	    Route::get('/', 'AdminController@dashboard')->name('dashboard');

	    Route::get('/profile', 'AdminController@profile')->name('profile');

	    Route::post('/profile/save', 'AdminController@profile_process')->name('save.profile');

	    Route::post('/change/password', 'AdminController@change_password')->name('change.password');

	    Route::get('/redeems/{id?}', 'AdminController@user_redeem_requests')->name('users.redeems');

	    // Route::post('/redeems/pay', 'AdminController@user_redeem_pay')->name('users.redeem.pay');
        Route::get('/reset_password','Auth\AdminAuthController@showLinkRequestForm')->name('reset_password.request');

		Route::post('forgot_password_update', 'Auth\AdminAuthController@forgot_password_update')->name('forgot_password.update');

        Route::post('reset_password_update', 'Auth\AdminAuthController@reset_password_update')->name('reset_password.update');

        Route::get('/reset/password', 'Auth\AdminAuthController@reset_password')->name('reset_password');


	    Route::any('/payout/invoice', 'AdminController@users_redeems_payout_invoice')->name('payout.invoice');

	    Route::post('/payout/direct', 'AdminController@users_redeems_payout_direct')->name('payout.direct');

	    Route::any('/payout/response', 'AdminController@users_redeems_payout_response')->name('payout.response');

	    // users

	    Route::any('/users', 'AdminController@users_index')->name('users.index');

	    Route::get('/users/create', 'AdminController@user_create')->name('users.create');

	    Route::get('/users/edit', 'AdminController@user_edit')->name('users.edit');

	    Route::post('/users/create', 'AdminController@user_save')->name('users.save');

	    Route::get('/users/delete', 'AdminController@user_delete')->name('users.delete');

	    Route::get('/users/view', 'AdminController@user_view')->name('users.view');

	    Route::get('/users/blocklist/{id}', 'AdminController@user_block_list')->name('users.block_list');

	    Route::post('/users/payout', 'AdminController@user_payout')->name('users.payout');

	    Route::get('/user/verify/{id?}', 'AdminController@user_verify_status')->name('users.verify');

	    Route::post('/users/bulk_action', 'AdminController@users_bulk_action')->name('users.bulk_action');



	    // Subscriptions

	    Route::get('/subscriptions', 'AdminController@subscriptions')->name('subscriptions.index');

	    Route::get('/user_subscriptions/{id}', 'AdminController@user_subscriptions')->name('subscriptions.plans');

	    Route::get('/subscription/save', 'AdminController@user_subscription_save')->name('subscription.save');

	    Route::get('/subscriptions/create', 'AdminController@subscription_create')->name('subscriptions.create');

	    Route::get('/subscriptions/edit/{id}', 'AdminController@subscription_edit')->name('subscriptions.edit');

	    Route::post('/subscriptions/create', 'AdminController@subscription_save')->name('subscriptions.save');

	    Route::get('/subscriptions/delete/{id}', 'AdminController@subscription_delete')->name('subscriptions.delete');

	    Route::get('/subscriptions/view/{id}', 'AdminController@subscription_view')->name('subscriptions.view');

	    Route::get('/subscriptions/status/{id}', 'AdminController@subscription_status')->name('subscriptions.status');

	    Route::get('/subscriptions/popular/status/{id}', 'AdminController@subscription_popular_status')->name('subscriptions.popular.status');

	    Route::get('/subscriptions/users/{id}', 'AdminController@subscription_users')->name('subscriptions.users');

	    
	    Route::get('settings' , 'AdminController@settings')->name('settings');

	    Route::post('save_common_settings' , 'AdminController@save_common_settings')->name('save.common-settings');

	    Route::get('payment/settings' , 'AdminController@payment_settings')->name('payment.settings');

	    Route::get('theme/settings' , 'AdminController@theme_settings')->name('theme.settings');
	    
	    Route::post('settings' , 'AdminController@settings_process')->name('save.settings');

	    Route::get('settings/email' , 'AdminController@email_settings')->name('email.settings');

	    Route::post('settings/email' , 'AdminController@email_settings_process')->name('email.settings.save');

	    Route::get('help' , 'AdminController@help')->name('help');

	    // Pages

	    Route::get('/pages/index', 'AdminController@pages_index')->name('pages.index');

	    Route::get('/pages/edit/{id}', 'AdminController@pages_edit')->name('pages.edit');

	    Route::get('/pages/view', 'AdminController@pages_view')->name('pages.view');

	    Route::get('/pages/create', 'AdminController@pages_create')->name('pages.create');

	    Route::post('/pages/create', 'AdminController@pages_save')->name('pages.save');

	    Route::get('/pages/delete/{id}', 'AdminController@pages_delete')->name('pages.delete');

	    // Videos

	    Route::any('/videos/streaming/index', 'AdminController@videos_index')->name('videos.index');

	    Route::any('/videos/index', 'AdminController@videos_list')->name('videos.videos_list');

	    Route::get('/videos/view', 'AdminController@videos_view')->name('videos.view');

	    Route::get('/videos/delete/{id}', 'AdminController@live_videos_delete')->name('videos.delete');

	    Route::post('/videos/bulk_action_delete', 'AdminController@live_videos_bulk_action_delete')->name('videos.bulk_action_delete');


	    // User Subscriptions

	    Route::get('user/payments', 'AdminController@subscription_payments')->name('subscription.payments');
	   
	    Route::get('video/payments', 'AdminController@video_payments')->name('videos.payments');

	    Route::get('revenue/system', 'AdminController@revenue_system')->name('revenue.system');

	    Route::get('users/followers/{id}', 'AdminController@followers')->name('users.followers');

	    Route::get('users/followings/{id}', 'AdminController@followings')->name('users.followings');

	    Route::get('/user/approve', 'AdminController@user_approve')->name('users.approve');

	    Route::get('/user/clear-login', 'AdminController@clear_login')->name('users.clear-login');

	    // Coupons

	    // Get the add coupon forms
	    Route::get('/coupons/add','AdminController@coupon_create')->name('add.coupons');

	    // Get the edit coupon forms
	    Route::get('/coupons/edit/{id}','AdminController@coupon_edit')->name('edit.coupons');

	    // Save the coupon details
	    Route::post('/coupons/save','AdminController@coupon_save')->name('save.coupon');

	    // Get the list of coupon details
	    Route::get('/coupons/list','AdminController@coupon_index')->name('coupon.list');

	    //Get the particular coupon details
	    Route::get('/coupons/view/{id}','AdminController@coupon_view')->name('coupon.view');

	    // Delete the coupon details
	    Route::get('/coupons/delete/{id}','AdminController@coupon_delete')->name('delete.coupon');

	    //Coupon approve and decline status
	    Route::get('/coupon/status','AdminController@coupon_status_change')->name('coupon.status');

	    //ios control settings

	    // Get ios control page
	    Route::get('/ios-control','AdminController@ios_control')->name('ios_control');

	    //Save the ios control status
	    Route::post('/ios-control/save','AdminController@ios_control_save')->name('ios_control.save');


	    // Export Section
	    Route::get('/users/export/','AdminExportController@users_export')->name('users.export');

	    Route::get('/live/videos/export/','AdminExportController@livevideos_export')->name('livevideos.export');

	    Route::get('/subscriptions/payments/export/','AdminExportController@subscriptions_export')->name('subscription.export');


	    Route::get('/payperview/payments/export/','AdminExportController@payperview_export')->name('payperview.export');


	    Route::get('/vod/payments/export/','AdminExportController@vod_payments_export')->name('vod-payments.export');

	    Route::get('/vod/videos/export/','AdminExportController@vod_videos_export')->name('vod-videos.export');


	    // Automatic subscription
	    Route::post('/user/subscription/cancel', 'AdminController@user_subscription_pause')->name('automatic.subscription.cancel');

	    Route::get('/user/subscription/enable', 'AdminController@user_subscription_enable')->name('automatic.subscription.enable');

	    // VodVideo methods starts
	    Route::any('/vod/video/index','AdminController@vod_videos_index')->name('vod-videos.index');

	    Route::get('/vod/videos/upload','AdminController@vod_videos_create')->name('vod-videos.create');

	    Route::get('/vod/video/edit/{id}','AdminController@vod_videos_edit')->name('vod-videos.edit');

	    Route::post('/vod/video/save','AdminController@vod_videos_save')->name('vod-videos.save');

	    Route::get('/vod/video/view','AdminController@vod_videos_view')->name('vod-videos.view');

	    Route::get('/vod/video/delete','AdminController@vod_videos_delete')->name('vod-videos.delete');

	    Route::get('/vod/video/status','AdminController@vod_videos_status_update')->name('vod-videos.status');

	    Route::get('/vod/video/publish','AdminController@vod_videos_publish')->name('vod-videos.publish');

	    // Add ppv on the particular video
	    Route::post('/vod/video/ppv/create','AdminController@vod_videos_ppv_create')->name('vod-videos.ppv.create');

	    // Remove PPV amount particular video
	    Route::get('/vod/video/ppv/delete','AdminController@vod_videos_ppv_delete')->name('vod-videos.ppv.delete');

	    // Get vod video payments list
	    Route::get('/vod/payments','AdminController@vod_payments_list')->name('vod-videos.payments.list');
	   
	    Route::get('/vod/payments/view','AdminController@vod_payments_view')->name('vod-videos.payments.view');

	    Route::post('/vod/bulk_action','AdminController@vod_bulk_action')->name('vod-videos.bulk_action');

	    
	    // VodVideo methods ends


	    // Become Creator

	    Route::get('/users/become/creator', 'AdminController@become_creator')->name('become.creator');

	    // Streamer Gallery

	    Route::get('streamer/galleries/upload', 'AdminController@streamer_galleries_upload')->name('streamer_galleries.upload');

	    Route::post('streamer/galleries/save', 'AdminController@streamer_galleries_save')->name('streamer_galleries.save');

	    Route::get('streamer/galleries/list/{user_id}', 'AdminController@streamer_galleries_list')->name('streamer_galleries.list');

	    Route::get('streamer/galleries/delete', 'AdminController@streamer_galleries_delete')->name('streamer_galleries.delete');


	        // Subscribers

	    Route::get('automatic/subscribers', 'AdminController@automatic_subscribers')->name('automatic.subscribers');

	    Route::get('cancelled/subscribers', 'AdminController@cancelled_subscribers')->name('cancelled.subscribers');

	    /** @@@@@@@@@@@@@@@ LIVE GROUPS @@@@@@@@@@@@@ */

	    Route::any('/groups/index' , 'AdminController@live_groups_index')->name('live_groups.index');

	    Route::get('/groups/delete' , 'AdminController@live_groups_delete')->name('live_groups.delete');

	    Route::get('/groups/view' , 'AdminController@live_groups_view')->name('live_groups.view');

	    Route::post('/groups/bulk_action_delete' , 'AdminController@live_group_bulk_action_delete')->name('live_groups.bulk_action_delete');


	    // Custom Live Videos

	    Route::any('custom/live/videos', 'AdminController@custom_live_videos')->name('custom.live');

	    Route::get('custom/live/create', 'AdminController@custom_live_videos_create')->name('custom.live.create');

	    Route::get('custom/live/edit', 'AdminController@custom_live_videos_edit')->name('custom.live.edit');

	    Route::post('custom/live/save', 'AdminController@custom_live_videos_save')->name('custom.live.save');

	    Route::get('custom/live/delete', 'AdminController@custom_live_videos_delete')->name('custom.live.delete');

	    Route::get('custom/live/view/{id}', 'AdminController@custom_live_videos_view')->name('custom.live.view');

	    Route::get('custom/live/change-status', 'AdminController@custom_live_videos_change_status')->name('custom.live.change_status');

	    Route::post('custom/live/bulk_action', 'AdminController@custom_live_videos_bulk_action')->name('custom.live.bulk_action');


	    Route::get('notification_templates', 'TemplateController@notification_template_index')->name('templates.notification_template_index');

	    Route::get('notification_template_view', 'TemplateController@notification_template_view')->name('templates.notification_template_view');

	    Route::get('notification_template_edit', 'TemplateController@notification_template_edit')->name('templates.notification_template_edit');

	    Route::post('save_notification_template', 'TemplateController@save_notification_template')->name('templates.save_notification_template');

	    Route::get('notification_template_credential', 'TemplateController@notification_template_credential')->name('templates.notification_template_credential');

	    // Languages

	    Route::get('/languages/index', 'LanguageController@languages_index')->name('languages.index'); 

	    Route::get('/languages/download/', 'LanguageController@languages_download')->name('languages.download'); 

	    Route::get('/languages/create', 'LanguageController@languages_create')->name('languages.create');
	    
	    Route::get('/languages/edit', 'LanguageController@languages_edit')->name('languages.edit');

	    Route::get('/languages/status', 'LanguageController@languages_status_change')->name('languages.status');   

	    Route::post('/languages/save', 'LanguageController@languages_save')->name('languages.save');

	    Route::get('/languages/delete', 'LanguageController@languages_delete')->name('languages.delete');

	    Route::get('/languages/set_default', 'LanguageController@languages_set_default')->name('languages.set_default');

	    Route::get('user_subscription_payments/view' , 'AdminController@user_subscription_payments_view')->name('user_subscription_payments.view');

	    Route::get('live_video_payments/view' , 'AdminController@live_video_payments_view')->name('live_video_payments.view');


	    Route::get('automatic/subscribers/export', 'AdminExportController@automatic_subscribers_export')->name('automatic.subscribers.export');

	    Route::get('cancelled/subscribers/export', 'AdminExportController@cancelled_subscribers_export')->name('cancelled.subscribers.export');

	});

	Route::group(['as' => 'user.' , 'middleware' => 'cors'] ,function() {

	    Route::get('delete-video/{id}/{user_id}', 'UserController@delete_video')->name('delete_video');


	    // Paypal Payment
	    Route::get('paypal/{id}/{user_id}/{coupon_code?}','PaypalController@pay')->name('paypal');


	    Route::get('user/payment/status','PaypalController@getPaymentStatus')->name('paypalstatus');


	    Route::get('paypal_video/{id}/{user_id}/{coupon_code?}','PaypalController@payPerVideo')->name('paypalvideo');


	    Route::get('user/payment_video','PaypalController@getVideoPaymentStatus')->name('videopaypalstatus');


	    Route::get('/vod/paypal/{id}/{user_id}/{coupon_code?}','PaypalController@videoSubscriptionPay')->name('videoPaypal');

	    Route::get('/user/vod-status','PaypalController@getVODPaymentStatus')->name('videoPaypalstatus');


	    Route::post('video/{mid}', 'UserController@video')->name('live.video');


	    Route::post('live_streaming/{mid}', 'UserController@live_streaming')->name('live.streaming');


	    Route::post('close_streaming', 'UserController@close_streaming')->name('live.close_streaming');


	    Route::post('appSettings/{mid}', 'UserController@appSettings')->name('live.appSettings');
	    
	    Route::post('get_videos/{type}', 'UserController@get_videos')->name('live.get_videos');
	   
	    Route::post('check_subscription_plan', 'UserController@check_subscription_plan')->name('check_subscription_plan');

	    Route::post('delete_streaming/{id}', 'UserController@delete_streaming')->name('delete_streaming');

	    Route::post('userDetails', 'UserController@userDetails')->name('userDetails');

	    Route::post('take_snapshot/{rid}', 'UserController@setCaptureImage')->name('setCaptureImage');

	    Route::post('getChatMessages/{mid}', 'UserController@getChatMessages')->name('getChatMessages');

	    Route::post('allPages', 'UserController@allPages')->name('allPages');

	    Route::get('getPage/{id}', 'UserController@getPage')->name('getPage');

	    Route::get('/daily/page' , 'UserApiController@daily_view_count')->name('daily.page.count');

	    Route::any('searchall' , 'UserController@searchall')->name('search');

	    Route::post('zero_plan', 'UserController@zero_plan')->name('zero_plan');

	    Route::get('settings' , 'UserController@settings')->name('settings');

	    Route::get('check_social', 'UserController@check_social')->name('check_social');

	    Route::post('get_live_url', 'UserController@get_live_url')->name('get_live_url');

	});
});