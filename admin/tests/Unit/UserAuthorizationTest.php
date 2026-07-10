<?php

namespace Tests\Unit;

use App\Models\User;
use Filament\Panel;
use PHPUnit\Framework\TestCase;

class UserAuthorizationTest extends TestCase
{
    public function test_only_enabled_administrators_can_access_the_panel(): void
    {
        $panel = Panel::make();

        $enabledAdmin = new User(['is_admin' => true, 'status' => 1]);
        $disabledAdmin = new User(['is_admin' => true, 'status' => 0]);
        $regularUser = new User(['is_admin' => false, 'status' => 1]);

        $this->assertTrue($enabledAdmin->canAccessPanel($panel));
        $this->assertFalse($disabledAdmin->canAccessPanel($panel));
        $this->assertFalse($regularUser->canAccessPanel($panel));
    }
}
