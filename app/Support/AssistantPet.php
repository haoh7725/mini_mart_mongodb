<?php

namespace App\Support;

use App\Models\User;

// Thú cưng rau củ đại diện cho trợ lý AI. Khách đăng nhập tự chọn trong trang Hồ sơ.
class AssistantPet
{
    public const DEFAULT = 'broccoli';

    public const PETS = [
        'broccoli' => ['name' => 'Bông Cải Xanh', 'greeting' => 'Ăn xanh sống khỏe nha!'],
        'carrot' => ['name' => 'Cà Rốt Cam', 'greeting' => 'Hôm nay nấu gì ngon nè?'],
        'tomato' => ['name' => 'Cà Chua Đỏ', 'greeting' => 'Mình gợi ý món liền!'],
        'eggplant' => ['name' => 'Cà Tím', 'greeting' => 'Hỏi mình bất cứ món gì!'],
        'corn' => ['name' => 'Bắp Vàng', 'greeting' => 'Bữa nay ăn gì đây ta?'],
        'avocado' => ['name' => 'Bơ Sáp', 'greeting' => 'Cùng lên thực đơn nhé!'],
    ];

    public static function for(?User $user): string
    {
        $pet = $user?->assistant_pet;

        return array_key_exists((string) $pet, self::PETS) ? $pet : self::DEFAULT;
    }
}
