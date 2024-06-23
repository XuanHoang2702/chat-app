<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Conversation;
use App\Models\Friendship;
use App\Models\Message;
use App\Models\Notification;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run()
    {
        User::factory()->count(100)->create();
        Conversation::factory()->count(50)->create();
        Message::factory()->count(200)->create();
        Participant::factory()->count(100)->create();
        Friendship::factory()->count(100)->create();
        Notification::factory()->count(100)->create();
    }
}
