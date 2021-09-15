<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use Benwilkins\FCM\FcmMessage;


class PushNotification extends Notification
{
    use Queueable;

    protected $title;

    protected $message;

    protected $data;

    protected $register_ids;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($title , $message, $data = [] , $register_ids = [])
    {
        $this->title = $title;

        $this->message = $message;

        $this->data = $data;

        $this->register_ids = $register_ids;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['fcm'];
    }

    public function toFcm($notifiable) 
    { 
        $message = new FcmMessage();
        $message->setHeaders([
            'project_id'    => env('FCM_SENDER_ID')  // FCM sender_id
        ])->content([
            'title'        => $this->title, 
            'body'         => $this->message, 
            'sound'        => '', // Optional 
            'icon'         => \Setting::get('site_logo'), // Optional
            'click_action' => '' // Optional
        ])->data([
            'data' => $this->data
        ])
        ->to([$this->register_ids])
        ->priority(FcmMessage::PRIORITY_HIGH); // Optional - Default is 'normal'.
        
        return $message;
    }
}
