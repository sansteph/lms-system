param([string]$Release = '2026-09-02')
$ErrorActionPreference = 'Stop'
$root = Split-Path $PSScriptRoot -Parent
if ($Release -notmatch '^\d{4}-\d{2}-\d{2}$') { throw 'Release must be YYYY-MM-DD.' }
$outputRoot = Join-Path $root 'dist/mobile-deployment'
$bundleName = "tinkedge-mobile-api-$Release-$(Get-Date -Format 'HHmmss')"
$bundle = Join-Path $outputRoot $bundleName
$payload = Join-Path $bundle 'payload'
New-Item -ItemType Directory -Path $payload -Force | Out-Null

function Text-Hash([string]$text) {
    $normalized = $text.Replace("`r`n", "`n")
    $sha = [System.Security.Cryptography.SHA256]::Create()
    try { return ([BitConverter]::ToString($sha.ComputeHash([Text.Encoding]::UTF8.GetBytes($normalized)))).Replace('-', '').ToLowerInvariant() }
    finally { $sha.Dispose() }
}

Push-Location $root
try {
    $baseCommit = (& git rev-parse HEAD).Trim()
    if ($LASTEXITCODE -ne 0) { throw 'Cannot determine the source revision.' }
    $candidates = @(& git ls-files --cached --others --exclude-standard)
    if ($LASTEXITCODE -ne 0) { throw 'Cannot inventory the source files.' }
    $paths = @($candidates | Where-Object {
        $_ -match '^(app|config|routes|resources/views|database/migrations)/.+\.php$' -or
        $_ -in @('bootstrap/app.php', 'bootstrap/providers.php', 'composer.json', 'composer.lock')
    } | Sort-Object -Unique)
    $files = @()
    foreach ($path in $paths) {
        $source = Join-Path $root $path
        if (!(Test-Path -LiteralPath $source -PathType Leaf)) { throw "Missing source: $path" }
        $content = [IO.File]::ReadAllText($source)
        if ($content -match 'AIza[0-9A-Za-z_-]{30,}|-----BEGIN (?:RSA |OPENSSH |EC )?PRIVATE KEY-----') {
            throw "Potential embedded credential in $path; package creation stopped."
        }
        $baseHash = $null
        & git cat-file -e "${baseCommit}:$path" 2>$null
        if ($LASTEXITCODE -eq 0) {
            $start = [Diagnostics.ProcessStartInfo]::new('git', "show ${baseCommit}:$path")
            $start.WorkingDirectory = $root
            $start.RedirectStandardOutput = $true
            $start.UseShellExecute = $false
            $process = [Diagnostics.Process]::Start($start)
            $original = $process.StandardOutput.ReadToEnd()
            $process.WaitForExit()
            if ($process.ExitCode -ne 0) { throw "Cannot read baseline: $path" }
            $baseHash = Text-Hash $original
        }
        $target = Join-Path $payload $path
        New-Item -ItemType Directory -Path (Split-Path $target -Parent) -Force | Out-Null
        Copy-Item -LiteralPath $source -Destination $target
        $files += [ordered]@{ path=$path; baseline_sha256=$baseHash; sha256=(Text-Hash $content) }
    }
    $manifest = [ordered]@{
        release=$Release; source_commit=$baseCommit; expected_url='https://tinkedge.tech';
        hash_format='sha256-utf8-lf'; files=$files
    }
    [IO.File]::WriteAllText((Join-Path $bundle 'manifest.json'), ($manifest | ConvertTo-Json -Depth 5), [Text.UTF8Encoding]::new($false))
    Copy-Item -LiteralPath (Join-Path $PSScriptRoot 'check-mobile-bundle.php') -Destination (Join-Path $bundle 'check.php')
    $guide = [IO.File]::ReadAllText((Join-Path $root 'docs/manual-mobile-deployment.md')).Replace('__BUNDLE_NAME__', $bundleName)
    [IO.File]::WriteAllText((Join-Path $bundle 'README.md'), $guide, [Text.UTF8Encoding]::new($false))
    Copy-Item -LiteralPath (Join-Path $root 'docs/mobile-parity-sweep.md') -Destination (Join-Path $bundle 'VERIFICATION.md')
    $archive = Join-Path $outputRoot "$bundleName.tar.gz"
    & tar -czf $archive -C $outputRoot $bundleName
    if ($LASTEXITCODE -ne 0) { throw 'Archive creation failed.' }
    $digest = (Get-FileHash -LiteralPath $archive -Algorithm SHA256).Hash.ToLowerInvariant()
    [IO.File]::WriteAllText("$archive.sha256", "$digest  $bundleName.tar.gz`n", [Text.UTF8Encoding]::new($false))
    Write-Output "Archive: $archive"
    Write-Output "SHA256: $digest"
    Write-Output "Source files: $($files.Count). No .env, credentials, uploads, vendor files or database dumps included."
} finally { Pop-Location }
