<?php

namespace Tests\Feature;

use App\Actions\Schools\AddSchoolMember;
use App\Enums\SchoolRole;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\InteractsWithSchools;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use InteractsWithSchools, RefreshDatabase;

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get(route('academic-years.index'))->assertRedirect(route('login'));
    }

    public function test_login_requires_an_email_and_password(): void
    {
        $this->post(route('login.store'), [])
            ->assertInvalid([
                'email' => 'The email field is required.',
                'password' => 'The password field is required.',
            ]);
    }

    public function test_login_rejects_unknown_credentials(): void
    {
        $this->post(route('login.store'), [
            'email' => 'missing@school.test',
            'password' => 'wrong-password',
        ])->assertInvalid([
            'email' => 'These credentials do not match our records.',
        ]);
    }

    public function test_login_is_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('login.store'), [
                'email' => 'missing@school.test',
                'password' => 'wrong-password',
            ])->assertInvalid(['email']);
        }

        $this->post(route('login.store'), [
            'email' => 'missing@school.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);
    }

    public function test_a_member_of_one_school_lands_on_the_dashboard(): void
    {
        $school = School::factory()->create(['name' => "Ravi's <script>alert('xss')</script> School"]);
        $admin = User::factory()->memberOf($school, SchoolRole::Admin)->create([
            'email' => 'admin@greenwood.test',
        ]);

        $this->post(route('login.store'), [
            'email' => 'admin@greenwood.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee("Ravi's <script>alert('xss')</script> School");
        $response->assertDontSee("<script>alert('xss')</script>", false);
    }

    public function test_a_member_of_several_schools_must_choose_one(): void
    {
        $first = School::factory()->create();
        $second = School::factory()->create();
        $admin = User::factory()->memberOf($first, SchoolRole::Admin)->create([
            'email' => 'multi@school.test',
        ]);
        app(AddSchoolMember::class)->handle($second, [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => SchoolRole::Teacher,
        ]);

        $this->post(route('login.store'), [
            'email' => 'multi@school.test',
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))->assertRedirect(route('schools.select'));
    }

    public function test_a_user_with_no_school_is_told_they_have_no_access(): void
    {
        $user = User::factory()->create([
            'email' => 'lonely@school.test',
        ]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('schools.none'));
        $this->get(route('schools.none'))->assertOk()->assertSee('No school assigned');
    }

    public function test_a_platform_admin_without_a_membership_opens_the_school_list(): void
    {
        $admin = User::factory()->platformAdmin()->create();

        $this->actingAs($admin)
            ->get(route('dashboard'))
            ->assertRedirect(route('platform.schools.index'));
    }

    public function test_users_can_log_out(): void
    {
        $school = School::factory()->create();
        $admin = User::factory()->memberOf($school, SchoolRole::Admin)->create();

        $this->enterSchool($admin, $school)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
