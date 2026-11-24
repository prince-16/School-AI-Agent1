<?php

namespace Database\Seeders;

use App\Actions\AcademicYears\SaveAcademicYear;
use App\Actions\Schools\AddSchoolMember;
use App\Actions\Schools\CreateSchool;
use App\Actions\Schools\ManageSchoolMembership;
use App\Actions\Schools\ProvisionSchoolAccess;
use App\Enums\MembershipStatus;
use App\Enums\SchoolPermission;
use App\Enums\SchoolRole;
use App\Exceptions\SchoolRuleException;
use App\Models\School;
use App\Models\User;
use App\Services\SchoolContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    private const string PASSWORD = 'password';

    public function __construct(
        private CreateSchool $createSchool,
        private AddSchoolMember $addSchoolMember,
        private SaveAcademicYear $saveAcademicYear,
        private ManageSchoolMembership $manageSchoolMembership,
        private ProvisionSchoolAccess $provisionSchoolAccess,
        private SchoolContext $schoolContext,
    ) {}

    /**
     * Seed sample schools, people, and academic years for local checking.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        foreach ($this->schools() as $definition) {
            $school = $this->school($definition['name'], $definition['code'], $definition['admin']);

            foreach ($definition['members'] as $member) {
                $this->ensureMember($school, $member);
            }

            $this->academicYears($school);
        }

        $this->directPermissionExample();
        $this->frameworkRows();
    }

    /**
     * @return list<array{name: string, code: string, admin: array{name: string, email: string}, members: list<array{name: string, email: string, role: SchoolRole, suspended?: bool}>}>
     */
    private function schools(): array
    {
        return [
            [
                'name' => 'Greenwood High',
                'code' => 'GWH',
                'admin' => ['name' => 'Meera Iyer', 'email' => 'meera.iyer@greenwood.test'],
                'members' => [
                    ['name' => 'Ravi Menon', 'email' => 'ravi.menon@greenwood.test', 'role' => SchoolRole::Accountant],
                    ['name' => 'Anita Desai', 'email' => 'anita.desai@greenwood.test', 'role' => SchoolRole::Teacher],
                    ['name' => 'Hari Prasad', 'email' => 'hari.prasad@greenwood.test', 'role' => SchoolRole::Teacher],
                    ['name' => 'Neha Kapoor', 'email' => 'neha.kapoor@greenwood.test', 'role' => SchoolRole::Staff],
                    ['name' => 'Suresh Patel', 'email' => 'suresh.patel@greenwood.test', 'role' => SchoolRole::Teacher, 'suspended' => true],
                ],
            ],
            [
                'name' => 'Riverside Public School',
                'code' => 'RPS',
                'admin' => ['name' => 'Asha Verma', 'email' => 'asha.verma@riverside.test'],
                'members' => [
                    ['name' => 'Pooja Nair', 'email' => 'pooja.nair@riverside.test', 'role' => SchoolRole::Accountant],
                    ['name' => 'Imran Qureshi', 'email' => 'imran.qureshi@riverside.test', 'role' => SchoolRole::Teacher],
                    ['name' => 'Lakshmi Rao', 'email' => 'lakshmi.rao@riverside.test', 'role' => SchoolRole::Staff],
                ],
            ],
            [
                'name' => 'St. Mary\'s Academy',
                'code' => 'SMA',
                'admin' => ['name' => 'Fatima Sheikh', 'email' => 'fatima.sheikh@stmarys.test'],
                'members' => [
                    ['name' => 'Asha Verma', 'email' => 'asha.verma@riverside.test', 'role' => SchoolRole::Teacher],
                    ['name' => 'Joseph D\'Souza', 'email' => 'joseph.dsouza@stmarys.test', 'role' => SchoolRole::Accountant],
                    ['name' => 'Priya Banerjee', 'email' => 'priya.banerjee@stmarys.test', 'role' => SchoolRole::Staff],
                    ['name' => 'Rohan Das', 'email' => 'rohan.das@stmarys.test', 'role' => SchoolRole::Staff, 'suspended' => true],
                ],
            ],
        ];
    }

    /**
     * @param  array{name: string, email: string}  $admin
     */
    private function school(string $name, string $code, array $admin): School
    {
        $school = School::query()->where('code', Str::upper($code))->first();

        if ($school === null) {
            return $this->createSchool->handle($name, $code, 'Asia/Kolkata', [
                'name' => $admin['name'],
                'email' => $admin['email'],
                'password' => self::PASSWORD,
                'role' => SchoolRole::Admin->value,
            ]);
        }

        $this->provisionSchoolAccess->handle($school);
        $this->ensureMember($school, [
            'name' => $admin['name'],
            'email' => $admin['email'],
            'role' => SchoolRole::Admin,
        ]);

        return $school;
    }

    /**
     * @param  array{name: string, email: string, role: SchoolRole, suspended?: bool}  $member
     */
    private function ensureMember(School $school, array $member): User
    {
        try {
            $membership = $this->addSchoolMember->handle($school, [
                'name' => $member['name'],
                'email' => $member['email'],
                'password' => self::PASSWORD,
                'role' => $member['role'],
            ]);
            $user = $membership->user;
        } catch (SchoolRuleException $exception) {
            if ($exception->getMessage() !== 'This person is already a member of the school.') {
                throw $exception;
            }

            $user = User::query()->where('email', Str::lower($member['email']))->firstOrFail();
        }

        if (($member['suspended'] ?? false) === true) {
            $membership = $school->memberships()->where('user_id', $user->id)->firstOrFail();

            if ($membership->status === MembershipStatus::Active) {
                $this->manageSchoolMembership->suspend($membership);
            }
        }

        return $user;
    }

    private function academicYears(School $school): void
    {
        $years = [
            ['name' => '2024-25', 'starts_on' => '2024-04-01', 'ends_on' => '2025-03-31'],
            ['name' => '2025-26', 'starts_on' => '2025-04-01', 'ends_on' => '2026-03-31'],
            ['name' => '2026-27', 'starts_on' => '2026-04-01', 'ends_on' => '2027-03-31'],
            ['name' => '2027-28', 'starts_on' => '2027-04-01', 'ends_on' => '2028-03-31'],
        ];

        foreach ($years as $year) {
            $existing = $school->academicYears()->where('name', $year['name'])->first();

            if ($existing !== null) {
                continue;
            }

            $this->saveAcademicYear->handle($school, [
                'name' => $year['name'],
                'starts_on' => $year['starts_on'],
                'ends_on' => $year['ends_on'],
                'make_current' => $year['name'] === '2026-27' && $school->current_academic_year_id === null,
            ]);

            $school->refresh();
        }
    }

    private function directPermissionExample(): void
    {
        $school = School::query()->where('code', 'GWH')->first();
        $user = User::query()->where('email', 'neha.kapoor@greenwood.test')->first();

        if ($school === null || $user === null) {
            return;
        }

        $this->schoolContext->forSchool($school, function () use ($user): void {
            $user->givePermissionTo(SchoolPermission::AcademicYearsView->value);
        });
    }

    private function frameworkRows(): void
    {
        $user = User::query()->where('email', 'meera.iyer@greenwood.test')->first();

        if ($user !== null && ! DB::table('password_reset_tokens')->where('email', $user->email)->exists()) {
            Password::broker()->createToken($user);
        }

        DB::table('sessions')->updateOrInsert(
            ['id' => 'demo-inspection-session'],
            [
                'user_id' => $user?->id,
                'ip_address' => '127.0.0.1',
                'user_agent' => 'Demo seeder',
                'payload' => base64_encode(serialize(['demo' => 'inspection session'])),
                'last_activity' => now()->getTimestamp(),
            ],
        );

        DB::table('cache')->updateOrInsert(
            ['key' => 'demo-inspection'],
            [
                'value' => serialize(['note' => 'Sample cache row for inspection']),
                'expiration' => now()->addYear()->getTimestamp(),
            ],
        );

        DB::table('cache_locks')->updateOrInsert(
            ['key' => 'demo-inspection'],
            [
                'owner' => 'demo-seeder',
                'expiration' => now()->addYear()->getTimestamp(),
            ],
        );

        if (DB::table('jobs')->where('queue', 'demo')->doesntExist()) {
            DB::table('jobs')->insert([
                'queue' => 'demo',
                'payload' => json_encode([
                    'uuid' => (string) Str::uuid(),
                    'displayName' => 'Demo inspection job',
                ]),
                'attempts' => 0,
                'reserved_at' => null,
                'available_at' => now()->addYear()->getTimestamp(),
                'created_at' => now()->getTimestamp(),
            ]);
        }

        DB::table('job_batches')->updateOrInsert(
            ['id' => 'demo-inspection-batch'],
            [
                'name' => 'Demo inspection batch',
                'total_jobs' => 1,
                'pending_jobs' => 0,
                'failed_jobs' => 0,
                'failed_job_ids' => '[]',
                'options' => null,
                'cancelled_at' => null,
                'created_at' => now()->getTimestamp(),
                'finished_at' => now()->getTimestamp(),
            ],
        );

        DB::table('failed_jobs')->updateOrInsert(
            ['uuid' => 'demo-inspection-failed-job'],
            [
                'connection' => 'database',
                'queue' => 'demo',
                'payload' => json_encode(['displayName' => 'Demo inspection failure']),
                'exception' => 'Sample failed job kept so the failed_jobs table can be inspected.',
                'failed_at' => now(),
            ],
        );
    }
}
