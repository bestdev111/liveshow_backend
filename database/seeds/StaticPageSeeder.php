<?php

use Illuminate\Database\Seeder;

class StaticPageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        if(Schema::hasTable('pages')) {

        	$static_pages = json_decode(json_encode(['about','privacy','terms','others','faq','help','contact']));

        	foreach ($static_pages as $key => $value) {

    			$page_details = DB::table('pages')->where('type' ,$value)->count();

    			if(!$page_details) {

    				DB::table('pages')->insert([
    	         		[
    				        'unique_id' => $value,
                            'title' => $value,
                            'heading' => $value,
    				        'description' => $value,
    				        'type' => $value,
    				        'created_at' => date('Y-m-d H:i:s'),
    				        'updated_at' => date('Y-m-d H:i:s')
    				    ],
				    ]);

    			}

        	}

		}
    }
}
