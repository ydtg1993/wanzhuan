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
        factory(App\Models\User::class, 10)->create()->each(function ($user) {
            \App\Models\Master::create([
                'user_id' => $user->id,
                'order_count' => mt_rand(1, 99),
                'arg_score' => 3.3
            ]);

            DB::table('teams')->insert([
                'masters' => $user->id,
                'user_id'=> $user->id,
                'name' => 'team_' . $user->id,
                'game' => 'game_' . $user->id,
                'server' => 'server_' . $user->id,
                'level' => 'level_' . $user->id,
                'unit' => '局',
                'price' => 45.69,
                'count'=>5,
                'score'=>3.3,
                'result'=>'1@1@0@1@0@1'
            ]);
        });

        factory(App\Models\User::class, 10)->create()->each(function ($user) {
            \App\Models\NormalOrder::create([
                'user_id' => $user->id,
                'user_id_1' => mt_rand(1, 10),
                'play_order_id' => $user->id,
                'pay_number' => $user->id,
                'fee' => 59.68,
                'reduce' => 10.87,
                'comment' => '{ "detail": "good，很好很强大","score": "3.3", "time": "1555555555"}',
                'type' => 1,
                'result'=>'1@1@0@1@0@1',
                'status'=>1
            ]);

            \App\Models\TeamOrder::create([
                'user_id' => $user->id,
                'team_id' => mt_rand(1, 10),
                'pay_number' => $user->id,
                'fee' => 59.68,
                'reduce' => 10.87,
                'count' => 2,
                'comment'=>'{ "detail": "good，很好很强大","score": "3.3", "time": "1555555555"}',
                'type' => 1,
                'status'=>1
            ]);

            \App\Models\SkillOrder::create([
                'user_id' => $user->id,
                'user_id_1' => mt_rand(1, 10),
                'skill_id' => $user->id,
                'pay_number' => $user->id,
                'reduce' => 10.87,
                'comment'=>'{ "detail": "good，很好很强大","score": "3.3", "time": "1555555555"}',
                'type' => 1,
                'status'=>1
            ]);

//            DB::table('normal_orders_temp')->insert([
//                'order_id' => $user->id,
//                'order_type' => mt_rand(1, 2),
//                'user_id' => $user->id,
//                'game_id' => mt_rand(1, 9),
//                'level_type' => mt_rand(0, 2),
//                'server_id' => mt_rand(1, 9),
//                'level_id' => mt_rand(1, 9),
//                'unit_price' => 45.69,
//                'unit' => '局',
//                'game_num' => 5,
//                'status' => 1
//            ]);

//            DB::table('games')->insert([
//                'name' => 'game_' . $user->id,
//                'game_type' => mt_rand(1, 2)
//            ]);
//
//            DB::table('game_server')->insert([
//                'game_id' => mt_rand(1, 9),
//                'server_name' => 'server_' . $user->id
//            ]);
//
//            DB::table('game_level')->insert([
//                'game_id' => mt_rand(1, 9),
//                'level_name' => 'level_' . $user->id
//            ]);

            DB::table('tickets')->insert([
                'limits'=>'王者荣耀-钻石1区-王者',
                'code'=>$user->id,
                'user_id'=>mt_rand(1,20),
                'status'=>mt_rand(1,3),
            ]);
        });
    }
}
