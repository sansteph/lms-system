<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;

class MobileBundleCheckTest extends TestCase
{
    private string $directory;
    private string $target;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir().'/mobile-bundle-test-'.bin2hex(random_bytes(8));
        $this->target = $this->directory.'/site';
        mkdir($this->target.'/app', 0700, true);
        mkdir($this->directory.'/payload/app', 0700, true);
        file_put_contents($this->target.'/artisan', '<?php');
        file_put_contents($this->target.'/.env', 'TEST_ONLY=true');
        copy(dirname(__DIR__, 2).'/tools/check-mobile-bundle.php', $this->directory.'/check.php');
        $this->writeManifest();
    }

    private function writeManifest(string $path = 'app/Example.php'): void
    {
        file_put_contents($this->directory.'/payload/app/Example.php', "<?php\n// New\n");
        file_put_contents($this->target.'/app/Example.php', "<?php\r\n// Old\r\n");
        file_put_contents($this->directory.'/manifest.json', json_encode([
            'hash_format' => 'sha256-utf8-lf',
            'files' => [[
                'path' => $path,
                'sha256' => hash('sha256', "<?php\n// New\n"),
                'baseline_sha256' => hash('sha256', "<?php\n// Old\n"),
            ]],
        ], JSON_THROW_ON_ERROR));
    }

    private function check(): Process
    {
        $process = new Process([PHP_BINARY, $this->directory.'/check.php', $this->target]);
        $process->setTimeout(15)->run();
        return $process;
    }

    public function test_baseline_and_updated_files_pass_without_modifying_them(): void
    {
        $before = file_get_contents($this->target.'/app/Example.php');
        $this->assertTrue($this->check()->isSuccessful());
        $this->assertSame($before, file_get_contents($this->target.'/app/Example.php'));
        copy($this->directory.'/payload/app/Example.php', $this->target.'/app/Example.php');
        $this->assertTrue($this->check()->isSuccessful());
    }

    public function test_live_source_conflicts_are_not_overwritten(): void
    {
        file_put_contents($this->target.'/app/Example.php', '// Live edit');
        $process = $this->check();
        $this->assertSame(1, $process->getExitCode());
        $this->assertStringContainsString('CONFLICT', $process->getOutput());
        $this->assertSame('// Live edit', file_get_contents($this->target.'/app/Example.php'));
    }

    public function test_tampered_payload_and_unlisted_files_are_rejected(): void
    {
        file_put_contents($this->directory.'/payload/app/Example.php', '// Tampered');
        $this->assertSame(2, $this->check()->getExitCode());
        $this->writeManifest();
        file_put_contents($this->directory.'/payload/extra.txt', 'Unexpected');
        $this->assertSame(2, $this->check()->getExitCode());
    }

    public function test_path_traversal_is_rejected(): void
    {
        $this->writeManifest('../.env');
        $this->assertSame(2, $this->check()->getExitCode());
        $this->assertSame('TEST_ONLY=true', file_get_contents($this->target.'/.env'));
    }

    protected function tearDown(): void
    {
        $root = realpath($this->directory);
        $temp = realpath(sys_get_temp_dir());
        if ($root === false || $temp === false || !str_starts_with($root, $temp.DIRECTORY_SEPARATOR.'mobile-bundle-test-')) {
            throw new \RuntimeException('Refusing cleanup outside the temporary test directory.');
        }
        $entries = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($entries as $entry) {
            $entry->isDir() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }
        rmdir($root);
    }
}
