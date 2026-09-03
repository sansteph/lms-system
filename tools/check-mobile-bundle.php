<?php

// Standalone, read-only check. No Laravel bootstrap, database connection or file writes.
if (PHP_SAPI !== 'cli' || count($argv) !== 2) {
    fwrite(STDERR, "Usage: php check.php /var/www/tinkedge-lms\n");
    exit(2);
}

function digest(string $path): string
{
    $contents = file_get_contents($path);
    if ($contents === false) {
        throw new RuntimeException('Cannot read '.$path);
    }
    return hash('sha256', str_replace("\r\n", "\n", $contents));
}

try {
    $target = realpath($argv[1]);
    if (!$target || !is_file($target.'/artisan') || !is_file($target.'/.env')) {
        throw new RuntimeException('Target must be the existing Laravel application with its own .env.');
    }
    $manifest = json_decode(file_get_contents(__DIR__.'/manifest.json'), true, 512, JSON_THROW_ON_ERROR);
    if (($manifest['hash_format'] ?? null) !== 'sha256-utf8-lf' || empty($manifest['files'])) {
        throw new RuntimeException('Unsupported or empty manifest.');
    }
    $conflicts = 0;
    $changes = 0;
    $seen = [];
    foreach ($manifest['files'] as $file) {
        $path = $file['path'] ?? '';
        if (isset($seen[$path]) || str_contains($path, '..') || str_contains($path, '\\')
            || !preg_match('~^(?:(?:app|config|routes|resources/views|database/migrations)/[A-Za-z0-9_/.-]+\.php|bootstrap/(?:app|providers)\.php|composer\.(?:json|lock))$~D', $path)) {
            throw new RuntimeException('Unsafe or duplicate manifest path.');
        }
        $seen[$path] = true;
        $source = __DIR__.'/payload/'.$path;
        if (!is_file($source) || !hash_equals($file['sha256'], digest($source))) {
            throw new RuntimeException('Package integrity failure: '.$path);
        }
        $cursor = $target;
        foreach (explode('/', $path) as $part) {
            $cursor .= '/'.$part;
            if (is_link($cursor)) {
                throw new RuntimeException('Refusing a symlink in the deployment path: '.$path);
            }
        }
        $destination = $target.'/'.$path;
        if (file_exists($destination) && !is_file($destination)) {
            throw new RuntimeException('Destination is not a regular file: '.$path);
        }
        if (!is_file($destination)) {
            if ($file['baseline_sha256'] !== null) {
                echo 'CONFLICT missing baseline file: '.$path."\n";
                $conflicts++;
            } else {
                $changes++;
            }
            continue;
        }
        $current = digest($destination);
        if (hash_equals($file['sha256'], $current)) {
            continue;
        }
        if ($file['baseline_sha256'] !== null && hash_equals($file['baseline_sha256'], $current)) {
            $changes++;
        } else {
            echo 'CONFLICT server differs from both source baseline and update: '.$path."\n";
            $conflicts++;
        }
    }
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/payload', FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $entry) {
        $relative = str_replace('\\', '/', substr($entry->getPathname(), strlen(__DIR__.'/payload/')));
        if ($entry->isLink() || !isset($seen[$relative])) {
            throw new RuntimeException('Unlisted package file: '.$relative);
        }
    }
    if ($conflicts > 0) {
        echo "STOP: $conflicts conflict(s). No files changed. Merge against the live source before deployment; do not force overwrite.\n";
        exit(1);
    }
    echo "PASS: package integrity and source compatibility. $changes file(s) to update.\n";
    echo "No files changed. Database/schema, dependencies and production behavior still need the deployment checklist.\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'STOP: '.$error->getMessage()."\n");
    exit(2);
}
