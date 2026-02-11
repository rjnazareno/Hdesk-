# Download Performance Assets Locally
# Run this script to download Font Awesome and Chart.js 
# for faster page loading

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Performance Asset Downloader" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

$assetsPath = "$PSScriptRoot\assets"
$jsPath = "$assetsPath\js"
$cssPath = "$assetsPath\css"

# Create directories if they don't exist
if (!(Test-Path $jsPath)) {
    New-Item -ItemType Directory -Path $jsPath -Force | Out-Null
}
if (!(Test-Path $cssPath)) {
    New-Item -ItemType Directory -Path $cssPath -Force | Out-Null
}

# Download Chart.js
Write-Host "1. Downloading Chart.js..." -ForegroundColor Yellow
$chartJsUrl = "https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"
$chartJsPath = "$jsPath\chart.min.js"

try {
    Invoke-WebRequest -Uri $chartJsUrl -OutFile $chartJsPath
    $chartSize = (Get-Item $chartJsPath).Length / 1KB
    $sizeRounded = [Math]::Round($chartSize, 2)
    Write-Host "   [OK] Chart.js downloaded ($sizeRounded KB)" -ForegroundColor Green
    Write-Host "   Location: $chartJsPath" -ForegroundColor Gray
} catch {
    Write-Host "   [ERROR] Failed to download Chart.js" -ForegroundColor Red
    Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# Download Font Awesome (full package)
Write-Host "2. Downloading Font Awesome..." -ForegroundColor Yellow
$faUrl = "https://use.fontawesome.com/releases/v6.4.0/fontawesome-free-6.4.0-web.zip"
$faZipPath = "$env:TEMP\fontawesome.zip"
$faExtractPath = "$env:TEMP\fontawesome-extract"

try {
    Write-Host "   Downloading ZIP package..." -ForegroundColor Gray
    Invoke-WebRequest -Uri $faUrl -OutFile $faZipPath
    
    Write-Host "   Extracting files..." -ForegroundColor Gray
    Expand-Archive -Path $faZipPath -DestinationPath $faExtractPath -Force
    
    $faFolder = Get-ChildItem -Path $faExtractPath -Directory | Select-Object -First 1
    
    if ($faFolder) {
        Write-Host "   Copying CSS files..." -ForegroundColor Gray
        
        Copy-Item "$($faFolder.FullName)\css\fontawesome.min.css" -Destination $cssPath -Force
        Copy-Item "$($faFolder.FullName)\css\solid.min.css" -Destination $cssPath -Force
        Copy-Item "$($faFolder.FullName)\css\brands.min.css" -Destination $cssPath -Force
        
        $webfontsPath = "$assetsPath\webfonts"
        if (Test-Path $webfontsPath) {
            Remove-Item $webfontsPath -Recurse -Force
        }
        Copy-Item "$($faFolder.FullName)\webfonts" -Destination $webfontsPath -Recurse -Force
        
        Write-Host "   [OK] Font Awesome downloaded and extracted" -ForegroundColor Green
        Write-Host "   Location: $cssPath" -ForegroundColor Gray
        Write-Host "   Webfonts: $webfontsPath" -ForegroundColor Gray
        
        Remove-Item $faZipPath -Force
        Remove-Item $faExtractPath -Recurse -Force
    } else {
        Write-Host "   [ERROR] Failed to find extracted folder" -ForegroundColor Red
    }
} catch {
    Write-Host "   [ERROR] Failed to download Font Awesome" -ForegroundColor Red
    Write-Host "   Error: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Summary" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan

$downloaded = @()
if (Test-Path "$jsPath\chart.min.js") {
    $downloaded += "[OK] Chart.js"
}
if (Test-Path "$cssPath\fontawesome.min.css") {
    $downloaded += "[OK] Font Awesome CSS"
}
if (Test-Path "$assetsPath\webfonts") {
    $downloaded += "[OK] Font Awesome Webfonts"
}

if ($downloaded.Count -gt 0) {
    Write-Host ""
    Write-Host "Successfully downloaded:" -ForegroundColor Green
    foreach ($item in $downloaded) {
        Write-Host "  $item" -ForegroundColor Green
    }
    
    Write-Host ""
    Write-Host "Next Steps:" -ForegroundColor Yellow
    Write-Host "1. Upload these files to your Hostinger server" -ForegroundColor White
    Write-Host "2. Maintain the same folder structure:" -ForegroundColor White
    Write-Host "   - assets/css/*.css" -ForegroundColor Gray
    Write-Host "   - assets/js/*.js" -ForegroundColor Gray
    Write-Host "   - assets/webfonts/*.woff2" -ForegroundColor Gray
    Write-Host ""
    Write-Host "3. Test your site - should load much faster!" -ForegroundColor White
    Write-Host "4. Check browser DevTools (F12) Network tab" -ForegroundColor White
    Write-Host "   No more requests to cdnjs.cloudflare.com!" -ForegroundColor White
} else {
    Write-Host "No files were downloaded. Check errors above." -ForegroundColor Red
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
