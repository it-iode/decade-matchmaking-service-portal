<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UserRolesSeeder;
use Database\Seeders\AddRequestStatus;

class DatabaseSeeder extends Seeder
{

    public function __construct(
        protected UserRolesSeeder $userRolesSeeder,
        protected AddRequestStatus $addRequestStatus
    ) {}


    /**
     * Seed the application's database.
     */
    public function run(): void
    {
    //    $this->userRolesSeeder->run();
      //  $this->addRequestStatus->run();
    }
}
