#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Build Tailwind CSS for production
    
.DESCRIPTION
    Installs Tailwind CSS dependencies and builds minified CSS file
    for production use.
    
.EXAMPLE
    .\build-tailwind.ps1
#>

$ErrorActionPreference = "Stop"

Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║     Tailwind CSS Production Build Script                  ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

# Check prerequisites
Write-Host "Checking prerequisites..." -ForegroundColor Yellow

if (-not (Get-Command node -ErrorAction SilentlyContinue)) {
    Write-Host "❌ ERROR: Node.js is not installed" -ForegroundColor Red
    Write-Host ""
    Write-Host "Please install Node.js from: https://nodejs.org/" -ForegroundColor Cyan
    Write-Host ""
    Write-Host "Installation steps:" -ForegroundColor Yellow
    Write-Host "  1. Visit https://nodejs.org/" -ForegroundColor White
    Write-Host "  2. Download LTS version" -ForegroundColor White
    Write-Host "  3. Run installer and accept defaults" -ForegroundColor White
    Write-Host "  4. Restart PowerShell/Command Prompt" -ForegroundColor White
    Write-Host "  5. Run this script again" -ForegroundColor White
    Write-Host ""
    exit 1
}

$nodeVersion = (node --version)
$npmVersion = (npm --version)

Write-Host "✅ Node.js: $nodeVersion" -ForegroundColor Green
Write-Host "✅ npm: $npmVersion" -ForegroundColor Green
Write-Host ""

# Step 1: Install dependencies
Write-Host "Step 1: Installing dependencies..." -ForegroundColor Cyan
Write-Host "  Command: npm install -D tailwindcss postcss autoprefixer" -ForegroundColor DarkGray
npm install -D tailwindcss postcss autoprefixer
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ npm install failed" -ForegroundColor Red
    exit 1
}
Write-Host "✅ Dependencies installed" -ForegroundColor Green
Write-Host ""

# Step 2: Initialize Tailwind
Write-Host "Step 2: Initializing Tailwind configuration..." -ForegroundColor Cyan
Write-Host "  Command: npx tailwindcss init -p" -ForegroundColor DarkGray

if (-not (Test-Path "tailwind.config.js")) {
    npx tailwindcss init -p --yes
    if ($LASTEXITCODE -ne 0) {
        Write-Host "❌ Tailwind init failed" -ForegroundColor Red
        exit 1
    }
    Write-Host "✅ Configuration files created" -ForegroundColor Green
} else {
    Write-Host "⚠️  tailwind.config.js already exists" -ForegroundColor Yellow
}
Write-Host ""

# Step 3: Create input.css
Write-Host "Step 3: Creating input CSS file..." -ForegroundColor Cyan

if (-not (Test-Path "assets/css")) {
    New-Item -ItemType Directory -Path "assets/css" -Force | Out-Null
    Write-Host "  Created directory: assets/css" -ForegroundColor DarkGray
}

if (-not (Test-Path "assets/css/input.css")) {
    Write-Host "  Creating: assets/css/input.css" -ForegroundColor DarkGray
    @"
@tailwind base;
@tailwind components;
@tailwind utilities;
"@ | Out-File -Encoding UTF8 "assets/css/input.css" -Force
    Write-Host "✅ Input CSS file created" -ForegroundColor Green
} else {
    Write-Host "⚠️  assets/css/input.css already exists" -ForegroundColor Yellow
}
Write-Host ""

# Step 4: Update tailwind.config.js content paths
Write-Host "Step 4: Updating Tailwind configuration..." -ForegroundColor Cyan
Write-Host "  Updating content paths in tailwind.config.js" -ForegroundColor DarkGray

$tailwindConfig = Get-Content "tailwind.config.js" -Raw

# Replace content array if it's empty
if ($tailwindConfig -match "content:\s*\[\s*\]") {
    $tailwindConfig = $tailwindConfig -replace 'content:\s*\[\s*\]', 'content: [
    "./**/*.php",
    "!./vendor/**/*.php",
    "!./debug_archive/**/*.php",
    "!./node_modules/**/*.php"
  ]'
    Set-Content "tailwind.config.js" $tailwindConfig
    Write-Host "✅ Content paths updated" -ForegroundColor Green
} else {
    Write-Host "⚠️  Content array already configured" -ForegroundColor Yellow
}
Write-Host ""

# Step 5: Build CSS
Write-Host "Step 5: Building production CSS..." -ForegroundColor Cyan
Write-Host "  Command: npx tailwindcss -i ./assets/css/input.css -o ./assets/css/tailwind.min.css --minify" -ForegroundColor DarkGray
Write-Host "  (This may take 30-60 seconds...)" -ForegroundColor DarkGray

npx tailwindcss -i ./assets/css/input.css -o ./assets/css/tailwind.min.css --minify
if ($LASTEXITCODE -ne 0) {
    Write-Host "❌ Tailwind build failed" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 6: Verify output
Write-Host "Step 6: Verifying output..." -ForegroundColor Cyan

if (Test-Path "assets/css/tailwind.min.css") {
    $sizeKB = [Math]::Round((Get-Item "assets/css/tailwind.min.css").Length / 1KB, 2)
    $sizeBytes = (Get-Item "assets/css/tailwind.min.css").Length
    Write-Host "✅ CSS file generated successfully!" -ForegroundColor Green
    Write-Host "   Path: assets/css/tailwind.min.css" -ForegroundColor Green
    Write-Host "   Size: $sizeKB KB ($sizeBytes bytes)" -ForegroundColor Green
    Write-Host ""
    
    if ($sizeKB -lt 40) {
        Write-Host "⚠️  WARNING: CSS file smaller than expected ($sizeKB KB)" -ForegroundColor Yellow
        Write-Host "   This might mean not all classes are being scanned." -ForegroundColor Yellow
        Write-Host "   Check tailwind.config.js content array." -ForegroundColor Yellow
    } elseif ($sizeKB -gt 200) {
        Write-Host "⚠️  WARNING: CSS file larger than expected ($sizeKB KB)" -ForegroundColor Yellow
        Write-Host "   Verify --minify flag is working." -ForegroundColor Yellow
    }
} else {
    Write-Host "❌ CSS file not created" -ForegroundColor Red
    exit 1
}
Write-Host ""

# Step 7: Display next steps
Write-Host "╔════════════════════════════════════════════════════════════╗" -ForegroundColor Green
Write-Host "║                    BUILD SUCCESSFUL! ✅                    ║" -ForegroundColor Green
Write-Host "╚════════════════════════════════════════════════════════════╝" -ForegroundColor Green
Write-Host ""

Write-Host "Next steps:" -ForegroundColor Cyan
Write-Host ""
Write-Host "1. Test in development:" -ForegroundColor Yellow
Write-Host "   - Ensure APP_ENV=development in .env" -ForegroundColor DarkGray
Write-Host "   - Verify pages load correctly with CDN fallback" -ForegroundColor DarkGray
Write-Host ""
Write-Host "2. Deploy to production:" -ForegroundColor Yellow
Write-Host "   - Upload assets/css/tailwind.min.css to server" -ForegroundColor DarkGray
Write-Host "   - Set APP_ENV=production on server" -ForegroundColor DarkGray
Write-Host "   - Verify no console warnings" -ForegroundColor DarkGray
Write-Host ""
Write-Host "3. (Optional) Set up watch mode for development:" -ForegroundColor Yellow
Write-Host "   Run: npx tailwindcss -i ./assets/css/input.css -o ./assets/css/tailwind.css --watch" -ForegroundColor DarkGray
Write-Host ""

Write-Host "Documentation:" -ForegroundColor Cyan
Write-Host "  - docs/PRODUCTION_DEPLOYMENT_CHECKLIST.md" -ForegroundColor DarkGray
Write-Host "  - docs/TAILWIND_TESTING_GUIDE.md" -ForegroundColor DarkGray
Write-Host "  - docs/TAILWIND_QUICK_REFERENCE.md" -ForegroundColor DarkGray
Write-Host ""
