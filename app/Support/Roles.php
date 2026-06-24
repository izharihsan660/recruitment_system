<?php

namespace App\Support;

final class Roles
{
    public const Admin = 'admin';
    public const HrRecruiter = 'hr_recruiter';
    public const HrManager = 'hr_manager';
    public const HiringManager = 'hiring_manager';
    public const Approver = 'approver';
    public const PicPreboarding = 'pic_preboarding';

    public static function all(): array
    {
        return [
            self::Admin,
            self::HrRecruiter,
            self::HrManager,
            self::HiringManager,
            self::Approver,
            self::PicPreboarding,
        ];
    }
}
