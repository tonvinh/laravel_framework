<?php

namespace App\Enums;

enum DemoStatusEnum: string
{
    case Draft     = 'draft';
    case Published = 'published';
    case Archived  = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft     => 'Nháp',
            self::Published => 'Đã đăng',
            self::Archived  => 'Lưu trữ',
        };
    }
}
