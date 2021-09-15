<?php

if(!defined('APPROVED')) define('APPROVED', 1);
if(!defined('DECLINED')) define('DECLINED', 0);

if(!defined('YES')) define('YES', 1);
if(!defined('NO')) define('NO', 0);

if(!defined('DEFAULT_TRUE')) define('DEFAULT_TRUE', 1);
if(!defined('DEFAULT_FALSE')) define('DEFAULT_FALSE', 0);
if(!defined('DELETE_STATUS')) define('DELETE_STATUS', -1);

if(!defined('TYPE_PUBLIC')) define('TYPE_PUBLIC', 'public');
if(!defined('TYPE_PRIVATE')) define('TYPE_PRIVATE', 'private');

if(!defined('JWT_SECRET')) define('JWT_SECRET', '12345');

if(!defined('DEVICE_ANDROID')) define('DEVICE_ANDROID', 'android');
if(!defined('DEVICE_IOS')) define('DEVICE_IOS', 'ios');
if(!defined('DEVICE_WEB')) define('DEVICE_WEB', 'web');

if(!defined('LIVE_PUSH')) define('LIVE_PUSH', 'live_video_push');
if(!defined('OTHER_PUSH')) define('OTHER_PUSH', 'web');

if(!defined('PUSH_SINGLE_VIDEO')) define('PUSH_SINGLE_VIDEO', 1);
if(!defined('PUSH_OTHER_PROFILE')) define('PUSH_OTHER_PROFILE', 2);
if(!defined('PUSH_HOME')) define('PUSH_HOME', 3);

if (!defined('RTMP_URL')) define('RTMP_URL', '');

// Payment Constants
if(!defined('COD')) define('COD',   'cod');
if(!defined('PAYPAL')) define('PAYPAL', 'paypal');
if(!defined('CARD')) define('CARD',  'card');
if(!defined('APPLE_PAY')) define('APPLE_PAY',  'applepay');

// Coupons amount_type
if(!defined('PERCENTAGE')) define('PERCENTAGE',0);
if(!defined('ABSOULTE')) define('ABSOULTE', 1);


// Coupons status
if(!defined('COUPON_ACTIVE')) define('COUPON_ACTIVE',1);
if(!defined('COUPON_INACTIVE')) define('COUPON_INACTIVE', 0);

// Coupons applied status
if(!defined('COUPON_APPLIED')) define('COUPON_APPLIED',1);
if(!defined('COUPON_NOT_APPLIED')) define('COUPON_NOT_APPLIED', 0);

if (!defined('USER_APPROVED')) define('USER_APPROVED',1);

if (!defined('USER_DECLINED')) define('USER_DECLINED',0);

if (!defined('USER_PENDING')) define('USER_PENDING',2);

if (!defined('USER_EMAIL_VERIFIED')) define('USER_EMAIL_VERIFIED',1);

if (!defined('USER_EMAIL_NOT_VERIFIED')) define('USER_EMAIL_NOT_VERIFIED',0);


// Subscribed user status

if(!defined('SUBSCRIBED_USER')) define('SUBSCRIBED_USER', 1);

if(!defined('NON_SUBSCRIBED_USER')) define('NON_SUBSCRIBED_USER', 0);


// Vod status

if(!defined('VOD_APPROVED_BY_ADMIN')) define('VOD_APPROVED_BY_ADMIN', 1);

if(!defined('VOD_DECLINED_BY_ADMIN')) define('VOD_DECLINED_BY_ADMIN', 0);

if(!defined('VOD_APPROVED_BY_USER')) define('VOD_APPROVED_BY_USER', 1);

if(!defined('VOD_DECLINED_BY_USER')) define('VOD_DECLINED_BY_USER', 0);




// BROWSERS

if (!defined('WEB_SAFARI')) define('WEB_SAFARI', 'Safari');

if (!defined('WEB_OPERA')) define('WEB_OPERA', 'Opera');

if (!defined('WEB_FIREFOX')) define('WEB_FIREFOX', 'Firefox');

if (!defined('WEB_CHROME')) define('WEB_CHROME', 'Chrome');

if (!defined('WEB_IE')) define('WEB_IE', 'IE');

if (!defined('WEB_EDGE')) define('WEB_EDGE', 'Edge');

if (!defined('WEB_BLINK')) define('WEB_BLINK', 'Blink');

if (!defined('UNKNOWN')) define('UNKNOWN', 'Unknown');

if (!defined('ANDROID_BROWSER')) define('ANDROID_BROWSER', 'andriod');

if (!defined('IOS_BROWSER')) define('IOS_BROWSER', 'ios');

// Creator & Viewer status

if (!defined('CREATOR')) define('CREATOR', 'creator');

if (!defined('ADMIN')) define('ADMIN', 'admin');

if (!defined('VIEWER')) define('VIEWER', 'viewer');

if (!defined('CREATOR_STATUS')) define('CREATOR_STATUS', 1);

if (!defined('VIEWER_STATUS')) define('VIEWER_STATUS', 0);

if (!defined('PAID_STATUS')) define('PAID_STATUS', 1);


if (!defined('PAY_AND_WATCH')) define('PAY_AND_WATCH', 1);

if (!defined('NO_NEED_TO_PAY')) define('NO_NEED_TO_PAY', 0);

// Redeeem Request Status

if(!defined('REDEEM_REQUEST_SENT')) define('REDEEM_REQUEST_SENT', 0);
if(!defined('REDEEM_REQUEST_PROCESSING')) define('REDEEM_REQUEST_PROCESSING', 1);
if(!defined('REDEEM_REQUEST_PAID')) define('REDEEM_REQUEST_PAID', 2);
if(!defined('REDEEM_REQUEST_CANCEL')) define('REDEEM_REQUEST_CANCEL', 3);

// AUTORENEWAL STATUS

if(!defined('AUTORENEWAL_ENABLED')) define('AUTORENEWAL_ENABLED',0);

if(!defined('AUTORENEWAL_CANCELLED')) define('AUTORENEWAL_CANCELLED',1);


// publish status
if(!defined('PUBLISH_NOW')) define('PUBLISH_NOW', 1);
if(!defined('PUBLISH_LATER')) define('PUBLISH_LATER', 2);

// Published Status

if (!defined('VIDEO_PUBLISHED')) define('VIDEO_PUBLISHED', 1);

if (!defined('VIDEO_NOT_YET_PUBLISHED')) define('VIDEO_NOT_YET_PUBLISHED', 0);


// User Type
if(!defined('NORMAL_USER')) define('NORMAL_USER', 1);
if(!defined('PAID_USER')) define('PAID_USER', 2);
if(!defined('BOTH_USERS')) define('BOTH_USERS', 3);

// watched status


if(!defined('NOT_YET_WATCHED')) define('NOT_YET_WATCHED', 0);
if(!defined('WATCHED')) define('WATCHED', 1);


// Subscription Type
if(!defined('ONE_TIME_PAYMENT')) define('ONE_TIME_PAYMENT', 1);
if(!defined('RECURRING_PAYMENT')) define('RECURRING_PAYMENT', 2);

//PPv status

if(!defined('PPV_ENABLED')) define('PPV_ENABLED', 1);
if(!defined('PPV_DISABLED')) define('PPV_DISABLED', 0);

if (!defined('PAY_LIVE_VIDEO_INITIAL')) define('PAY_LIVE_VIDEO_INITIAL', 0);
if (!defined('PAY_LIVE_VIDEO_COMPLETED')) define('PAY_LIVE_VIDEO_COMPLETED', 1);

// VIDEO STATUS

if (!defined('VIDEO_STREAMING_STOPPED')) define('VIDEO_STREAMING_STOPPED' , 1);

if (!defined('VIDEO_STREAMING_ONGOING')) define('VIDEO_STREAMING_ONGOING' , 0);

// VIDEO STATUS

if (!defined('IS_STREAMING_YES')) define('IS_STREAMING_YES' , 1);

if (!defined('IS_STREAMING_NO')) define('IS_STREAMING_NO' , 0);


if(!defined('FREE_PLAN')) define('FREE_PLAN','free plan');

if(!defined('PAID_VIDEO')) define('PAID_VIDEO', 1);

if(!defined('FREE_VIDEO')) define('FREE_VIDEO', 0);


if(!defined('LIVE_GROUP_APPROVED')) define('LIVE_GROUP_APPROVED' , 1);

if(!defined('LIVE_GROUP_DECLINED')) define('LIVE_GROUP_DECLINED' , 0);

if(!defined('LIVE_GROUP_MEMBER_YES')) define('LIVE_GROUP_MEMBER_YES' , 1);

if(!defined('LIVE_GROUP_MEMBER_NO')) define('LIVE_GROUP_MEMBER_NO' , 0);

if(!defined('LIVE_GROUP_OWNER_YES')) define('LIVE_GROUP_OWNER_YES' , 1);

if(!defined('LIVE_GROUP_OWNER_NO')) define('LIVE_GROUP_OWNER_NO' , 0);

if(!defined('USER')) define('USER' , 0);


// Search Details

if(!defined('LIVE_VIDEOS')) define('LIVE_VIDEOS', 'live-videos');
if(!defined('LIVE_TV')) define('LIVE_TV', 'live-tv');
if(!defined('USERS')) define('USERS', 'users');

// Admin status

if(!defined('ADMIN_APPROVE_STATUS')) define('ADMIN_APPROVE_STATUS', 1);

if(!defined('ADMiN_DECLINE_STATUS')) define('ADMiN_DECLINE_STATUS', 0);



if(!defined('LIVE_STREAM_STARTED')) define('LIVE_STREAM_STARTED', 'LIVE_STREAM_STARTED');

if(!defined('USER_FOLLOW')) define('USER_FOLLOW', 'USER_FOLLOW');

if(!defined('USER_JOIN_VIDEO')) define('USER_JOIN_VIDEO', 'USER_JOIN_VIDEO');

if(!defined('USER_GROUP_ADD')) define('USER_GROUP_ADD', 'USER_GROUP_ADD');


// NEW CONSTANTS

if(!defined('TAKE_COUNT')) define('TAKE_COUNT', 12);

if(!defined('SHOW')) define('SHOW', 1);

if(!defined('HIDE')) define('HIDE', 0);

if(!defined('READ')) define('READ', 1);

if(!defined('UNREAD')) define('UNREAD', 0);

if(!defined('BROADCAST_TYPE_BROADCAST')) define('BROADCAST_TYPE_BROADCAST', 'broadcast');

if(!defined('BROADCAST_TYPE_CONFERENCE')) define('BROADCAST_TYPE_CONFERENCE', 'conference');

if(!defined('BROADCAST_TYPE_SCREENSHARE')) define('BROADCAST_TYPE_SCREENSHARE', 'screenshare');

if(!defined('IS_CONTENT_CREATOR')) define('IS_CONTENT_CREATOR', 1);

if(!defined('UNPAID')) define('UNPAID', 0);

if(!defined('LOGIN_BY_MANUAL')) define('LOGIN_BY_MANUAL', "manual");


if(!defined('OFF')) define('OFF', 0);

if(!defined('ON')) define('ON', 1);

if(!defined('SORT_BY_APPROVED')) define('SORT_BY_APPROVED',1);

if(!defined('SORT_BY_DECLINED')) define('SORT_BY_DECLINED',2);

if(!defined('SORT_BY_VERIFIED')) define('SORT_BY_VERIFIED',3);

if(!defined('SORT_BY_NOT_VERIFIED')) define('SORT_BY_NOT_VERIFIED',4);