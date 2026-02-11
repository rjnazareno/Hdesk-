<?php
/**
 * Performance Check - See what's loading from CDN vs Local
 * Access: https://hdesk.resourcestaffonline.com/performance_check.php
 * DELETE THIS FILE after checking!
 */

// Check if running from CLI or web
$isCLI = php_sapi_name() === 'cli';

if (!$isCLI) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html>
<html>
<head>
    <title>Performance Check - HDesk</title>
    <style>
        body { font-family: system-ui; padding: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #3b82f6; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; }
        .check-item { padding: 15px; margin: 10px 0; border-radius: 4px; border-left: 4px solid; }
        .exists { background: #d1fae5; border-color: #10b981; }
        .missing { background: #fee2e2; border-color: #ef4444; }
        .info { background: #dbeafe; border-color: #3b82f6; }
        .status { font-weight: bold; }
        .path { font-family: monospace; font-size: 12px; color: #666; }
        .size { color: #888; font-size: 14px; }
        table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f9fafb; font-weight: 600; }
        .priority-high { color: #dc2626; font-weight: bold; }
        .priority-low { color: #059669; }
    </style>
</head>
<body>
<div class="container">';
}

function formatBytes($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

function checkFile($path, $name, $description) {
    global $isCLI;
    
    $exists = file_exists($path);
    $size = $exists ? filesize($path) : 0;
    
    if ($isCLI) {
        echo ($exists ? "✓" : "✗") . " $name\n";
        echo "  Path: $path\n";
        if ($exists) {
            echo "  Size: " . formatBytes($size) . "\n";
        }
        echo "  Info: $description\n\n";
    } else {
        $class = $exists ? 'exists' : 'missing';
        $status = $exists ? '✓ EXISTS' : '✗ MISSING';
        echo '<div class="check-item ' . $class . '">';
        echo '<div class="status">' . $status . ' - ' . htmlspecialchars($name) . '</div>';
        echo '<div class="path">' . htmlspecialchars($path) . '</div>';
        if ($exists) {
            echo '<div class="size">Size: ' . formatBytes($size) . '</div>';
        }
        echo '<div>' . htmlspecialchars($description) . '</div>';
        echo '</div>';
    }
    
    return $exists;
}

// Start output
if (!$isCLI) {
    echo '<h1>🚀 HDesk Performance Check</h1>';
    echo '<p>Checking which assets are loaded locally vs from CDN...</p>';
}

// Check assets
$baseDir = __DIR__;

if (!$isCLI) echo '<h2>📦 Core Assets</h2>';

$tailwindMin = checkFile(
    $baseDir . '/assets/css/tailwind.min.css',
    'Tailwind CSS (Minified)',
    'Optimized Tailwind build for faster loading'
);

$tailwindReg = checkFile(
    $baseDir . '/assets/css/tailwind.css',
    'Tailwind CSS (Regular)',
    'Full Tailwind build (larger file size)'
);

if (!$isCLI) echo '<h2>🎨 Font Awesome</h2>';

$faCore = checkFile(
    $baseDir . '/assets/css/fontawesome.min.css',
    'Font Awesome Core CSS',
    'Required for icons to work'
);

$faSolid = checkFile(
    $baseDir . '/assets/css/solid.min.css',
    'Font Awesome Solid Icons',
    'Solid style icons (most commonly used)'
);

$faBrands = checkFile(
    $baseDir . '/assets/css/brands.min.css',
    'Font Awesome Brand Icons',
    'Brand logos (GitHub, Facebook, etc.)'
);

$faWebfonts = checkFile(
    $baseDir . '/assets/webfonts',
    'Font Awesome Webfonts Folder',
    'Font files (.woff2) for rendering icons'
);

if (!$isCLI) echo '<h2>📊 Chart.js</h2>';

$chartJs = checkFile(
    $baseDir . '/assets/js/chart.min.js',
    'Chart.js Library',
    'Used for dashboard charts and graphs'
);

// Summary
if (!$isCLI) {
    echo '<h2>📊 Loading Summary</h2>';
    echo '<table>';
    echo '<tr><th>Resource</th><th>Status</th><th>Impact</th><th>Priority</th></tr>';
    
    $resources = [
        ['Tailwind CSS', $tailwindMin || $tailwindReg, 'All pages', 'HIGH'],
        ['Font Awesome', $faCore && $faSolid, 'All pages (icons)', 'HIGH'],
        ['Chart.js', $chartJs, 'Dashboard only', 'MEDIUM'],
    ];
    
    foreach ($resources as $r) {
        $status = $r[1] ? '<span style="color: #10b981;">✓ Local</span>' : '<span style="color: #ef4444;">✗ Using CDN</span>';
        $priorityClass = $r[3] === 'HIGH' ? 'priority-high' : 'priority-low';
        echo '<tr>';
        echo '<td><strong>' . htmlspecialchars($r[0]) . '</strong></td>';
        echo '<td>' . $status . '</td>';
        echo '<td>' . htmlspecialchars($r[2]) . '</td>';
        echo '<td class="' . $priorityClass . '">' . htmlspecialchars($r[3]) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
    
    echo '<h2>⚡ Performance Recommendations</h2>';
    
    $missing = [];
    if (!$faCore || !$faSolid) {
        $missing[] = '<strong style="color: #dc2626;">HIGH PRIORITY:</strong> Download Font Awesome - Icons load from CDN on every page (adds 200-400ms delay)';
    }
    if (!$chartJs) {
        $missing[] = '<strong style="color: #f59e0b;">MEDIUM:</strong> Download Chart.js - Charts load from CDN on dashboard (adds ~150ms)';
    }
    if (!$tailwindMin && $tailwindReg) {
        $missing[] = '<strong>OPTIMIZATION:</strong> Use tailwind.min.css instead of tailwind.css';
    }
    
    if (count($missing) > 0) {
        echo '<div class="check-item missing">';
        echo '<div class="status">⚠️ Actions Needed:</div>';
        foreach ($missing as $m) {
            echo '<div style="margin: 10px 0;">• ' . $m . '</div>';
        }
        echo '</div>';
        
        echo '<div class="check-item info">';
        echo '<div class="status">📥 How to Fix:</div>';
        echo '<ol style="margin: 10px 0; padding-left: 20px;">';
        echo '<li>Run <code>download-performance-assets.ps1</code> locally</li>';
        echo '<li>Upload downloaded files to Hostinger File Manager</li>';
        echo '<li>Maintain folder structure: assets/css/, assets/js/, assets/webfonts/</li>';
        echo '<li>Refresh this page to verify</li>';
        echo '</ol>';
        echo '</div>';
    } else {
        echo '<div class="check-item exists">';
        echo '<div class="status">✓ All assets are local!</div>';
        echo '<div>Your site is optimized for performance. All resources load from your server.</div>';
        echo '</div>';
    }
    
    echo '<h2>🔒 Security Note</h2>';
    echo '<div class="check-item info">';
    echo '<strong>DELETE THIS FILE after checking!</strong><br>';
    echo 'File: <code>performance_check.php</code><br>';
    echo 'This file exposes your server file structure and should not be public.';
    echo '</div>';
    
    echo '</div></body></html>';
} else {
    // CLI Summary
    echo "\n=================================\n";
    echo "SUMMARY\n";
    echo "=================================\n";
    $localCount = 0;
    $total = 7;
    
    if ($tailwindMin || $tailwindReg) $localCount++;
    if ($faCore) $localCount++;
    if ($faSolid) $localCount++;
    if ($faBrands) $localCount++;
    if ($faWebfonts) $localCount++;
    if ($chartJs) $localCount++;
    
    echo "Local assets: $localCount / $total\n\n";
    
    if ($localCount < $total) {
        echo "Run: download-performance-assets.ps1\n";
        echo "Then upload files to Hostinger.\n";
    } else {
        echo "All assets are local! ✓\n";
    }
}
