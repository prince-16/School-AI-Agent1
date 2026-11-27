<?php

namespace App\Actions\People;

use App\Enums\GuardianRelationship;
use App\Exceptions\SchoolRuleException;
use App\Models\Guardian;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Services\SchoolContext;
use Illuminate\Support\Str;

class AttachGuardian
{
    /**
     * @param  array{name: string, relationship: string, phone?: string|null, email?: string|null, is_primary?: bool}  $attributes
     */
    public function handle(Student $student, array $attributes): StudentGuardian
    {
        $school = $student->school()->firstOrFail();

        return app(SchoolContext::class)->forSchool($school, function () use ($student, $attributes): StudentGuardian {
            $email = filled($attributes['email'] ?? null) ? Str::lower(trim($attributes['email'])) : null;
            $phone = filled($attributes['phone'] ?? null) ? trim($attributes['phone']) : null;
            $guardian = null;

            if ($email !== null) {
                $guardian = Guardian::query()->where('email', $email)->first();
            }

            if ($guardian === null && $phone !== null) {
                $guardian = Guardian::query()
                    ->where('name', trim($attributes['name']))
                    ->where('phone', $phone)
                    ->first();
            }

            if ($guardian === null) {
                $guardian = new Guardian;
                $guardian->school_id = $student->school_id;
                $guardian->name = $attributes['name'];
                $guardian->phone = $phone;
                $guardian->email = $email;
                $guardian->save();
            }

            $alreadyLinked = StudentGuardian::query()
                ->where('student_id', $student->id)
                ->where('guardian_id', $guardian->id)
                ->exists();

            if ($alreadyLinked) {
                throw new SchoolRuleException('This guardian is already linked to the student.');
            }

            $isPrimary = (bool) ($attributes['is_primary'] ?? false);

            if ($isPrimary) {
                StudentGuardian::query()
                    ->where('student_id', $student->id)
                    ->update(['is_primary' => false]);
            }

            $link = new StudentGuardian;
            $link->school_id = $student->school_id;
            $link->student_id = $student->id;
            $link->guardian_id = $guardian->id;
            $link->relationship = GuardianRelationship::from($attributes['relationship']);
            $link->is_primary = $isPrimary;
            $link->save();

            return $link->load('guardian');
        });
    }
}
