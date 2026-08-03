<?php

namespace Tests\Feature\Vasws;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DisplayAllTest extends TestCase
{
    use RefreshDatabase;

    protected $connectionsToTransact = ['sqlite', 'profiles'];

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.vas_ws.username' => 'vasuser', 'app.vas_ws.password' => 'vaspass']);
    }

    public function test_lists_all_configured_services(): void
    {
        $response = $this->withBasicAuth('vasuser', 'vaspass')->getJson('/vasws/displayall');

        $response->assertOk();
        $response->assertJson(['success' => true, 'msg' => 'successful operation']);
        $response->assertJsonFragment(['id' => 1, 'englishname' => 'News', 'arabicname' => 'الأخبار']);
    }

    public function test_missing_auth_returns_401(): void
    {
        $response = $this->getJson('/vasws/displayall');

        $response->assertStatus(401);
    }

    public function test_wrong_credentials_return_401(): void
    {
        $response = $this->withBasicAuth('vasuser', 'wrong')->getJson('/vasws/displayall');

        $response->assertStatus(401);
    }
}
