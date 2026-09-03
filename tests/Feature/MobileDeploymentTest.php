<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MobileDeploymentTest extends TestCase
{
    public function test_public_contract_marker_contains_no_environment_or_database_details(): void
    {
        $this->getJson('/api/mobile-status')->assertOk()->assertExactJson([
            'service' => 'innovatEdge-mobile',
            'api_version' => 1,
            'release' => '2026-09-02',
        ])->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_preflight_reports_missing_schema_without_creating_it(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('SQLite required.');
        }
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.url' => null, 'cache.default' => 'array']);
        DB::purge('sqlite');
        $before = DB::select("SELECT name FROM sqlite_master WHERE type='table'");
        $this->artisan('mobile:preflight')
            ->expectsOutput('FAIL Required schema: personal_access_tokens')
            ->expectsOutput('FAIL Migration history exists; inspect the live database before proceeding')
            ->assertFailed();
        $this->assertEquals($before, DB::select("SELECT name FROM sqlite_master WHERE type='table'"));
    }
}
