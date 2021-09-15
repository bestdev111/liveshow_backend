<?php

use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->delete();
    	DB::table('settings')->insert([
    		[
		        'key' => 'site_name',
		        'value' => 'StreamNow'
		    ],
		    [
		        'key' => 'site_logo',
		        'value' => asset('site_logo.png'),
		    ],
		    [
		        'key' => 'site_icon',
		        'value' => asset('site_icon.png'),
		    ],
		    [
		        'key' => 'browser_key',
		        'value' => ''
		    ],
		    [
		        'key' => 'default_lang',
		        'value' => 'en'
		    ], 
		    [
		        'key' => 'currency',
		        'value' => '$'
		    ],
		    [
        		'key' => 'admin_take_count',
        		'value' => 12
        	],
		    [
		        'key' => 'google_analytics',
		        'value' => ""
		    ],
		    [
		        'key' => 'ios_certificate',
		        'value' => ""
		    ],
		    [
	            'key' => 'admin_delete_control',
			    'value' => 0       	
			],
			[
		        'key' => 'is_subscription',
		        'value' => 1
		    ],
		    [
		        'key' => 'push_notification',
		        'value' => 1
		    ],
		    [
		        'key' => 'cross_platform_url',
		        'value' => "104.236.1.170:1935",
		    ],
		    [
		        'key' => 'home_bg_image',
		        'value' => asset('live-now.webm'),
		    ],
		    [
		        'key' => 'common_bg_image',
		        'value' => asset('background_picture.jpg'),
		    ],
		    [
		        'key' => 'mobile_rtmp',
		        'value' => "",
		    ],
		    [
		        'key' => 'header_scripts',
		        'value' => "",
		    ],
		    [
		        'key' => 'body_scripts',
		        'value' => "",
		    ],
		    [
		        'key' => 'wowza_server_url',
		        'value' => "https://104.236.1.170:8087",
		    ],
		    [
		        'key' => 'kurento_socket_url',
		        'value' => "livetest.streamhash.info:8443",
		    ],
		    [
		    	'key' => 'stripe_publishable_key' ,
		    	'value' => "pk_test_uDYrTXzzAuGRwDYtu7dkhaF3",
		    ],
		    [
		    	'key' => 'stripe_secret_key' ,
		    	'value' => "sk_test_lRUbYflDyRP3L2UbnsehTUHW",
		    ],
		    [
	            'key' => "facebook_link",
	            'value' => '',
        	],
        	[
	            'key' => "linkedin_link",
	            'value' => '',
        	],
        	[
	            'key' => "twitter_link",
	            'value' => '',
        	],
        	[
	            'key' => "google_plus_link",
	            'value' => '',
        	],
        	[
	            'key' => "pinterest_link",
	            'value' => '',
        	],
        	[
	            'key' => "appstore",
	            'value' => "",
        	],
        	[
	            'key' => "playstore",
	            'value' => "",
        	],
        	[
		        'key' => 'chat_socket_url',
		        'value' => ''
		    ],
		    [
		        'key' => 'is_live_streaming_configured',
		        'value' => 1,
		    ],
		    [
		        'key' => 'live_streaming_placeholder_img',
		        'value' => asset('images/default-image.jpg'),
		    ],
            [
                'key' => 'contact_address',
                'value' => '776 Birchpond Rd.Kissimmee, FL 34741',
            ],
            [
                'key' => 'contact_number',
                'value' => '+1-202-555-0126',
            ],
            [
                'key' => 'contact_email',
                'value' => '',
            ],
            [
                'key' => 'support_link',
                'value' => '',
            ],
            [
                'key' => 'FB_CLIENT_ID',
                'value' => '',
            ],
            [
                'key' => 'FB_CLIENT_SECRET',
                'value' => '',
            ],
            [
                'key' => 'FB_CALL_BACK',
                'value' => '',
            ],
            [
                'key' => 'GOOGLE_CLIENT_ID',
                'value' => '',
            ],
            [
                'key' => 'GOOGLE_CLIENT_SECRET',
                'value' => '',
            ],
            [
                'key' => 'GOOGLE_CALL_BACK',
                'value' => '',
            ],
            [
                'key' => 'mobile_live_streaming_url',
                'value' => '',
            ],
            [
		        'key' => 'admin_commission',
		        'value' => 10
		    ],
		    [
		        'key' => 'user_commission',
		        'value' => 90
		    ],
		    [
		        'key' => 'delete_video_hour',
		        'value' => 2,
		    ],
		    [
		        'key' => 'email_notification',
		        'value' => 1
		    ],
		    [
		        'key' => "ios_payment_subscription_status",
	            'value' => 0,
		    ],
		    [
		        'key' => 'live_url',
		        'value' => "https://streamnow.botfingers.com/"
		    ],
		    [
		        'key' => 'delete_video',
		        'value' => 0
		    ],
		    [
		        'key' => 'email_verify_control',
		        'value' => 1
		    ],
		    [
		        'key' => 'ANGULAR_URL',
		        'value' => ""
		    ],
		    [
		        'key' => 'minimum_redeem',
		        'value' => 1
		    ],
		    [
		        'key' => 'no_of_static_pages',
		        'value' => 8
		    ],
		    [
		        'key' => 'MAILGUN_PUBLIC_KEY',
		        'value' => "pubkey-7dc021cf4689a81a4afb340d1a055021"
		    ],
		    [
		        'key' => 'MAILGUN_PRIVATE_KEY',
		        'value' => ""
		    ],
		    [
		        'key' => 'jwplayer_key',
		        'value' => ''
		    ],
		    [
		        'key' => 'admin_vod_commission',
		        'value' => 10
		    ],

		    [
		        'key' => 'user_vod_commission',
		        'value' => 90
		    ],
		    [
		        'key' => 'SOCKET_URL',
		        'value' => ""
		    ],
		    [
		        'key' => 'BASE_URL',
		        'value' => "/"
		    ],
		    [
		        'key' => 'token_expiry_hour',
		        'value' =>1
		    ],
		    [
                'key' => 'is_multilanguage_enabled',
                'value' => 1
            ],
            [
		        'key' => 'demo_users',
		        'value' => 'user@streamnow.com,viewer@streamnow.com,streamer@streamnow.com'
		    ],
		    [
                'key' => 'user_fcm_sender_id',
                'value' => '865212328189'                
            ],
            [
              'key' => 'user_fcm_server_key' ,
              'value' => 'AAAASJFloB0:APA91bHBe54g5RP63U3EMTRClOVIXV3R8dwQ0xdwGTimGIWuKklipnpn3a7ASHDmEIuZ_OHTUDpWPYIzsXLTXXPE_UEJOz0BR1GgZ7s_gF41DKZjmJVsO3qfUOpZT2SqVMInOcL1Z55e'
            ],
            [
		        'key' => 'admin_demo_email',
		        'value' => 'admin@streamnow.com'
		    ],

		    [
		        'key' => 'admin_demo_password',
		        'value' => '123456'
		    ],
		    [
		        'key' => 'meta_title',
		        'value' => "STREAMNOW",
		    ],
		    [
		        'key' => 'meta_description',
		        'value' => "STREAMNOW",
		    ],
		    [
		        'key' => 'meta_author',
		        'value' => "STREAMNOW",
		    ],

		    [
		        'key' => 'meta_keywords',
		        'value' => "STREAMNOW",
		    ],
		    [
		        'key' => 'RTMP_STREAMING_URL',
		        'value' => ""
		    ],

		    [
		        'key' => 'HLS_STREAMING_URL',
		        'value' => ""
		    ],
		    [
		        'key' => 'RTMP_SECURE_VIDEO_URL',
		        'value' => ""
		    ],
		    [
		        'key' => 'HLS_SECURE_VIDEO_URL',
		        'value' => ""
		    ],

		    [
		        'key' => 'VIDEO_SMIL_URL',
		        'value' => ""
		    ],
		    [
		        'key' => 'wowza_port_number',
		        'value' => '1935'
		    ],
		    [
		        'key' => 'wowza_app_name',
		        'value' => 'live'
		    ],
		    [
		        'key' => 'wowza_username',
		        'value' => 'streamnow'
		    ],
		    [
		        'key' => 'wowza_password',
		        'value' => 'streamnow'
		    ],
		    [
		    	'key'=>'wowza_license_key',
		    	'value'=>'GOSK-8F45-010C-C962-ABB0-264A'
		    ],
		    [
		        'key' => 'wowza_is_ssl',
		        'value' => 1
		    ],
		    [
		        'key' => 'is_wowza_configured',
		        'value' => 0
		    ],
		    [
		        'key' => 'wowza_ip_address',
		        'value' => ''
		    ]
		]);
    }
}
