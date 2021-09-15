<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->call(DemoDataSeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(MobileRegisterSeeder::class);
        $this->call(NotificationSeeder::class);
        $this->call(SettingsTableSeeder::class);
        $this->call(StaticPageSeeder::class);
        $this->call(AntmediaSeeder::class);
    }
}
