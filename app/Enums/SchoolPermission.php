<?php

namespace App\Enums;

enum SchoolPermission: string
{
    case SchoolUpdate = 'school.update';
    case MembersView = 'members.view';
    case MembersManage = 'members.manage';
    case AcademicYearsView = 'academic_years.view';
    case AcademicYearsManage = 'academic_years.manage';
}
