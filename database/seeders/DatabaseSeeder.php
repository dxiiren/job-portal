<?php

namespace Database\Seeders;

use App\Models\Job;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\User;
use App\Models\Employer;
use App\Models\JobApplication;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Collection;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'akmal',
            'email' => 'akmal@gmail.com',
            'password' => bcrypt('password')
        ]);
        User::factory(300)->create();

        $users = User::all()->shuffle();

        $this->createEmployer($users);
        $this->createJob();
        $this->createJobApplication($users);
    }

    private function createEmployer(Collection $users)
    {
        for ($i = 0; $i < 20; $i++) {
            Employer::factory()->create([
                'user_id' => $users->pop()->id
            ]);
        }
    }

    private function createJob()
    {
        $employers = Employer::all();

        for ($i = 0; $i < 100; $i++) {
            Job::factory()->create([
                'employer_id' => $employers->random()->id
            ]);
        }
    }

    private function createJobApplication(Collection $users)
    {
        foreach ($users as $user) {
            $jobs = Job::inRandomOrder()
                ->take(rand(0, 4))
                ->get();

            foreach ($jobs as $job) {
                JobApplication::factory()->create([
                    'job_id' => $job->id,
                    'user_id' => $user->id
                ]);
            }
        }
    }
}