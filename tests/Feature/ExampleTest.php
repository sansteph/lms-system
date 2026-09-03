<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('SQLite PDO driver is not available in this PHP runtime.');
        }

        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'database.connections.sqlite.url' => null]);
        DB::purge('sqlite');
        foreach (['students', 'independent_learners', 'assessments', 'institutes'] as $table) {
            Schema::create($table, function (Blueprint $table) {
                $table->id();
                $table->boolean('status')->default(true);
            });
        }
        $response = $this->get('/');

        $response->assertStatus(200);
    }
}
