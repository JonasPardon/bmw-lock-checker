<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class BmwTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['bmw.token_file' => sys_get_temp_dir().'/bmw-test-tokens.json', 'bmw.vin' => 'WBA123', 'bmw.container_id' => 'c-1', 'bmw.ntfy_topic' => 'test-topic', 'bmw.api_key' => 'secret']);
        File::put(config('bmw.token_file'), json_encode(['access_token' => 'at', 'expires_in' => 3600, 'obtained_at' => time()]));
    }

    public function test_requests_without_api_key_are_rejected(): void
    {
        $this->get('/api/bmw/status')->assertStatus(401);
        $this->withHeader('X-Api-Key', 'wrong')->post('/api/bmw/check')->assertStatus(401);
    }

    public function test_lock_endpoint_reports_not_supported(): void
    {
        $this->withHeader('X-Api-Key', 'secret')->postJson('/api/bmw/lock', ['vin' => 'WBA123'])
            ->assertStatus(501)
            ->assertJson(['supported' => false]);
    }

    public function test_check_sends_push_when_unlocked(): void
    {
        Http::fake([
            'api-cardata.bmwgroup.com/*' => Http::response(['telematicData' => [
                'vehicle.cabin.door.lock.status' => ['value' => 'UNLOCKED', 'timestamp' => '2026-09-10T10:00:00Z'],
            ]]),
            'ntfy.sh/*' => Http::response('', 200),
        ]);

        $this->withHeader('X-Api-Key', 'secret')->post('/api/bmw/check')->assertOk()->assertJson(['locked' => false, 'lock_status' => 'UNLOCKED', 'notified' => true]);
        Http::assertSent(fn ($r) => str_contains($r->url(), 'ntfy.sh/test-topic'));
    }

    public function test_check_is_quiet_when_secured(): void
    {
        Http::fake(['api-cardata.bmwgroup.com/*' => Http::response(['telematicData' => [
            'vehicle.cabin.door.lock.status' => ['value' => 'SECURED', 'timestamp' => 't'],
        ]])]);

        $this->withHeader('X-Api-Key', 'secret')->post('/api/bmw/check')->assertOk()->assertJson(['locked' => true, 'notified' => false]);
        Http::assertNotSent(fn ($r) => str_contains($r->url(), 'ntfy'));
    }

    public function test_telematic_request_carries_required_headers(): void
    {
        Http::fake(['api-cardata.bmwgroup.com/*' => Http::response(['telematicData' => []])]);
        $this->get('/api/bmw/status?key=secret')->assertOk()->assertJson(['locked' => null]);
        Http::assertSent(fn ($r) => $r->hasHeader('x-version', 'v1')
            && $r->hasHeader('Authorization', 'Bearer at')
            && str_contains($r->url(), '/customers/vehicles/WBA123/telematicData?containerId=c-1'));
    }
}
