<?php

namespace App\Enums;

enum SchoolRole: string
{
    case Admin = 'school_admin';
    case Accountant = 'accountant';
    case Teacher = 'teacher';
    case Staff = 'staff';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'School admin',
            self::Accountant => 'Accountant',
            self::Teacher => 'Teacher',
            self::Staff => 'Staff',
        };
    }

    /**
     * @return list<SchoolPermission>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::Admin => SchoolPermission::cases(),
            self::Accountant, self::Teacher, self::Staff => [
                SchoolPermission::AcademicYearsView,
            ],
        };
    }
}
