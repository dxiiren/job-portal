<?php

namespace Database\Seeders;

use App\Models\Employer;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Job;
use App\Models\JobApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    /**
     * Deterministic demo logins (both use password "password"):
     * akmal@gmail.com is always a plain job seeker with no applications,
     * employer@gmail.com always owns "Akmal Recruitment Sdn Bhd" with jobs.
     */
    public const DEMO_SEEKER_EMAIL = 'akmal@gmail.com';

    public const DEMO_EMPLOYER_EMAIL = 'employer@gmail.com';

    public function run(): void
    {
        User::factory()->create([
            'name' => 'akmal',
            'email' => self::DEMO_SEEKER_EMAIL,
            'password' => bcrypt('password'),
        ]);
        $demoEmployerUser = User::factory()->create([
            'name' => 'Akmal Recruiter',
            'email' => self::DEMO_EMPLOYER_EMAIL,
            'password' => bcrypt('password'),
        ]);
        User::factory(300)->create();

        // Keep both demo accounts out of the random pools so their roles stay
        // deterministic: the seeker never becomes an employer or picks up
        // random applications, and the employer user isn't doubled up.
        $users = User::query()
            ->whereNotIn('email', [self::DEMO_SEEKER_EMAIL, self::DEMO_EMPLOYER_EMAIL])
            ->get()
            ->shuffle();

        $this->createEmployer($users);

        $demoEmployer = Employer::factory()->create([
            'user_id' => $demoEmployerUser->id,
            'company_name' => 'Akmal Recruitment Sdn Bhd',
        ]);

        $this->createJob();
        Job::factory(3)->create(['employer_id' => $demoEmployer->id]);

        $this->createJobApplication($users);
    }

    private function createEmployer(Collection $users)
    {
        for ($i = 0; $i < 20; $i++) {
            Employer::factory()->create([
                'user_id' => $users->pop()->id,
            ]);
        }
    }

    private function createJob()
    {
        $employers = Employer::all();

        for ($i = 0; $i < 100; $i++) {
            Job::factory()->create([
                'employer_id' => $employers->random()->id,
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
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
