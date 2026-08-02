<?php

namespace Tests\Feature;

use App\Models\Employer;
use App\Models\Job;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JobFilterTest extends TestCase
{
    use RefreshDatabase;

    private function job(array $attributes = [], ?string $companyName = null): Job
    {
        return Job::factory()
            ->for(Employer::factory()
                ->for(User::factory())
                ->state(['company_name' => $companyName ?? fake()->unique()->company()]))
            ->create($attributes);
    }

    /** @return array<int, int> */
    private function filteredIds(array $query): array
    {
        return Job::query()->filter($query)->pluck('id')->all();
    }

    public function test_search_matches_the_job_title(): void
    {
        $match = $this->job(['title' => 'Senior Rust Engineer']);
        $miss = $this->job(['title' => 'Cake Decorator', 'description' => 'Nothing relevant here.']);

        $ids = $this->filteredIds(['search' => 'Rust']);

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($miss->id, $ids);
    }

    public function test_search_matches_the_job_description(): void
    {
        $match = $this->job(['title' => 'Analyst', 'description' => 'You will maintain a Kubernetes cluster.']);
        $miss = $this->job(['title' => 'Baker', 'description' => 'You will bake bread.']);

        $ids = $this->filteredIds(['search' => 'Kubernetes']);

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($miss->id, $ids);
    }

    public function test_search_matches_the_employer_company_name(): void
    {
        // The orWhereRelation branch: neither the title nor the description
        // contains the term — only the employer's company name does.
        $match = $this->job(
            ['title' => 'Analyst', 'description' => 'Spreadsheets and coffee.'],
            'Petronas Digital Sdn Bhd'
        );
        $miss = $this->job(
            ['title' => 'Analyst', 'description' => 'Spreadsheets and coffee.'],
            'Maybank Islamic Berhad'
        );

        $ids = $this->filteredIds(['search' => 'Petronas']);

        $this->assertContains($match->id, $ids);
        $this->assertNotContains($miss->id, $ids);
    }

    public function test_the_min_salary_boundary_is_inclusive(): void
    {
        $below = $this->job(['salary' => 4999]);
        $onBoundary = $this->job(['salary' => 5000]);
        $above = $this->job(['salary' => 5001]);

        $ids = $this->filteredIds(['min_salary' => 5000]);

        $this->assertNotContains($below->id, $ids);
        $this->assertContains($onBoundary->id, $ids);
        $this->assertContains($above->id, $ids);
    }

    public function test_the_max_salary_boundary_is_inclusive(): void
    {
        $below = $this->job(['salary' => 9999]);
        $onBoundary = $this->job(['salary' => 10000]);
        $above = $this->job(['salary' => 10001]);

        $ids = $this->filteredIds(['max_salary' => 10000]);

        $this->assertContains($below->id, $ids);
        $this->assertContains($onBoundary->id, $ids);
        $this->assertNotContains($above->id, $ids);
    }

    public function test_min_and_max_salary_combine_into_a_range(): void
    {
        $under = $this->job(['salary' => 5000]);
        $inside = $this->job(['salary' => 8000]);
        $over = $this->job(['salary' => 20000]);

        $ids = $this->filteredIds(['min_salary' => 6000, 'max_salary' => 10000]);

        $this->assertSame([$inside->id], $ids);
        $this->assertNotContains($under->id, $ids);
        $this->assertNotContains($over->id, $ids);
    }

    public function test_the_experience_filter_is_an_exact_match(): void
    {
        $entry = $this->job(['experience' => 'entry']);
        $senior = $this->job(['experience' => 'senior']);

        $ids = $this->filteredIds(['experience' => 'senior']);

        $this->assertSame([$senior->id], $ids);
        $this->assertNotContains($entry->id, $ids);
    }

    public function test_the_category_filter_is_an_exact_match(): void
    {
        $it = $this->job(['category' => 'IT']);
        $finance = $this->job(['category' => 'Finance']);

        $ids = $this->filteredIds(['category' => 'Finance']);

        $this->assertSame([$finance->id], $ids);
        $this->assertNotContains($it->id, $ids);
    }

    public function test_an_empty_filter_set_returns_everything(): void
    {
        $this->job();
        $this->job();

        $this->assertCount(2, $this->filteredIds([]));
        $this->assertCount(2, $this->filteredIds(['search' => null, 'min_salary' => null]));
    }

    public function test_the_listing_page_applies_the_filters_from_the_query_string(): void
    {
        $match = $this->job(['title' => 'Senior Rust Engineer', 'category' => 'IT']);
        $miss = $this->job(['title' => 'Cake Decorator', 'category' => 'Sales', 'description' => 'Bake.']);

        $this->get(route('jobs.index', ['search' => 'Rust']))
            ->assertOk()
            ->assertSee($match->title)
            ->assertDontSee($miss->title);
    }

    public function test_filters_survive_pagination_links(): void
    {
        // 20 per page is the paginator default, so 25 matches force a second page.
        Job::factory()
            ->count(25)
            ->for(Employer::factory()->for(User::factory()))
            ->create(['category' => 'IT', 'experience' => 'senior']);

        $response = $this->get(route('jobs.index', ['category' => 'IT', 'experience' => 'senior']));

        $response->assertOk()
            ->assertSee('category=IT', false)
            ->assertSee('experience=senior', false)
            ->assertSee('page=2', false);
    }
}
