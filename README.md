# School

A Laravel school management app. Each school’s records stay inside that school. Fees, attendance, exams, and the AI agent are later phases.

This document covers what is built today. Update it when a new phase lands.

## What works now

- Create a school and open it. The header shows the open school.
- Log in and log out. Repeated failed logins are rate limited.
- Roles inside one school: school admin, accountant, teacher, and staff. A role in one school does not apply in another.
- Add members, change a role, suspend a member, or remove a member. A school must keep one active administrator.
- An existing email is attached to the school without changing that person’s password.
- Academic years for the open school. One year is current. Dates cannot overlap, and the current year cannot be deleted until another year is current.
- Students, parents or guardians, classes, sections, and enrollments for one academic year. A student has one enrollment per year. Roll numbers are unique inside a section.
- Staff profiles add an employee code to an existing member. A class teacher must be an active teacher at that school.
- A platform admin can create schools and open any school. Everyone else only opens schools they belong to.

After you open a school, use **Academic years**, **Students**, **Classes**, **Staff**, and **Members** in the navigation. The dashboard counts belong to that school only. Creating a school does not copy years, students, or members from another school.

## Not built yet

Fees, attendance, exams, homework, timetables, expenses, reports, and the AI agent.

Next phase: **Fees** — fee heads, assignments, and payments, with dues calculated in PHP.

## Requirements

- PHP 8.3 or newer
- Composer
- Node.js and npm

The app uses SQLite locally. Sessions, cache, and queues use the database.

## Local setup

```bash
composer setup
php -r "file_exists('database/database.sqlite') || touch('database/database.sqlite');"
php artisan migrate
php artisan db:seed
php artisan serve
```

`composer setup` installs PHP and npm packages, creates `.env` when it is missing, generates the app key, and builds the frontend. Seeding runs only when `APP_ENV=local`.

Open http://127.0.0.1:8000.

## Local sample accounts

Every seeded account uses the password `password`. These accounts are for local checking only.

| Email | What they can open |
| --- | --- |
| `admin@school.test` | Platform admin. Can create and open every school. |
| `meera.iyer@greenwood.test` | Greenwood High, school admin |
| `asha.verma@riverside.test` | Riverside Public School as admin, and St. Mary's Academy as teacher |

The seeder also adds accountants, teachers, staff, and two suspended members across Greenwood High (`GWH`), Riverside Public School (`RPS`), and St. Mary's Academy (`SMA`). Greenwood High also gets Class 10, Section A (class teacher Anita Desai), students Tarun Sharma (`GWH-101`) and Abhishek Gupta (`GWH-102`) enrolled for 2026-27, guardian Ramesh Sharma, and staff profile `T-01`. Run `php artisan db:seed` again safely; it skips records that already exist.

## Tests

```bash
php artisan test
```
