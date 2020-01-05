<?php

use Illuminate\Database\Seeder;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('tmrol')->insert([
            'idtmrol' => 1,
            'nombre_rol' => 'Invitado'
        ]);

        DB::table('tdrol')->insert([
            'idtmrol' => 1,
            'scope' => 'is_guest',
        ]);

        DB::table('tmrol')->insert([
            'idtmrol' => 2,
            'nombre_rol' => 'Administrador'
        ]);

        DB::table('tdrol')->insert([
            'idtmrol' => 2,
            'scope' => 'is_admin',
        ]);

        DB::table('users')->insert([
            'name' => "abraham",
            'email' => "abraham.vado@gmail.com",
            'password' => Hash::make('12345678'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'idtmrol' => 2
        ]);

        DB::table('users')->insert([
            'name' => "abraham",
            'email' => "abraham.dev@gmail.com",
            'password' => Hash::make('12345678'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
            'idtmrol' => 1
        ]);
    }
}
