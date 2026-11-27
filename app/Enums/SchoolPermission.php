<?php

namespace App\Enums;

enum SchoolPermission: string
{
    case SchoolUpdate = 'school.update';
    case MembersView = 'members.view';
    case MembersManage = 'members.manage';
    case AcademicYearsView = 'academic_years.view';
    case AcademicYearsManage = 'academic_years.manage';
    case StudentsView = 'students.view';
    case StudentsManage = 'students.manage';
    case ClassesView = 'classes.view';
    case ClassesManage = 'classes.manage';
    case StaffView = 'staff.view';
    case StaffManage = 'staff.manage';
}
