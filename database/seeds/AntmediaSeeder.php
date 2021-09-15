<?php

use Illuminate\Database\Seeder;

class AntmediaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('settings')->insert([
    		[
		        'key' => 'antmedia_base_url',
		        'value' => 'https://ant-media.startstreaming.co:5443/'
		    ],
		    [
		        'key' => 'antmedia_stream_url',
		        'value' => 'ws://ant-media.startstreaming.co:5443/'
		    ],
		    [
		        'key' => 'is_antmedia_enabled',
		        'value' => 1
		    ]
		]);
    }
}
