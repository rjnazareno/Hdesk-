# ============================================
# Clean Upload Files - Remove All Attachments
# Date: February 11, 2026
# ============================================
# This script deletes all uploaded attachment files
# WARNING: THIS CANNOT BE UNDONE!
# ============================================

Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Cleaning Upload Directory" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
Write-Host ""

$uploadsPath = Join-Path $PSScriptRoot "uploads"

if (Test-Path $uploadsPath) {
    Write-Host "Found uploads directory: $uploadsPath" -ForegroundColor Yellow
    
    # Count files before deletion
    $files = Get-ChildItem -Path $uploadsPath -File -Recurse
    $fileCount = $files.Count
    
    Write-Host "Files to delete: $fileCount" -ForegroundColor Yellow
    Write-Host ""
    
    # Ask for confirmation
    $confirmation = Read-Host "Are you sure you want to delete ALL uploaded files? (yes/no)"
    
    if ($confirmation -eq "yes") {
        Write-Host ""
        Write-Host "Deleting files..." -ForegroundColor Red
        
        # Delete all files in uploads directory
        Get-ChildItem -Path $uploadsPath -File -Recurse | Remove-Item -Force
        
        # Delete all subdirectories (but keep the main uploads folder)
        Get-ChildItem -Path $uploadsPath -Directory -Recurse | Remove-Item -Force -Recurse
        
        Write-Host "Deleted $fileCount files" -ForegroundColor Green
        Write-Host ""
        Write-Host "Upload directory cleaned successfully!" -ForegroundColor Green
        
        # Verify cleanup
        $remainingFiles = Get-ChildItem -Path $uploadsPath -File -Recurse
        if ($remainingFiles.Count -eq 0) {
            Write-Host "Verification: 0 files remaining ✓" -ForegroundColor Green
        } else {
            Write-Host "Warning: $($remainingFiles.Count) files still remain" -ForegroundColor Yellow
        }
    } else {
        Write-Host ""
        Write-Host "Operation cancelled by user" -ForegroundColor Yellow
    }
} else {
    Write-Host "Uploads directory not found: $uploadsPath" -ForegroundColor Red
    Write-Host "Nothing to clean" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "============================================" -ForegroundColor Cyan
Write-Host "Done" -ForegroundColor Cyan
Write-Host "============================================" -ForegroundColor Cyan
