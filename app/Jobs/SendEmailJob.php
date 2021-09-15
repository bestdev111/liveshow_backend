<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Exception;

use App\Mail\CommonMail;

use Log, Setting;

class SendEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $email_data;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 2;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email_data)
    {
        $this->email_data = $email_data; 
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {      
        try {
            
            if(Setting::get('email_notification') == YES) {

                $mail_model = new CommonMail($this->email_data);

                \Mail::queue($mail_model);

                Log::info("EmailJob Success");

            }

        } catch(Exception $e) {

            Log::info("SendEmailJob Error".print_r($e->getMessage(), true));

        }

    }
}
