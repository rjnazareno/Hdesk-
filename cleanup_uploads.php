<?php
/**
 * Production Upload Cleanup Script
 * Deletes all uploaded attachment files
 * Access: https://hdesk.resourcestaffonline.com/cleanup_uploads.php
 * 
 * WARNING: This deletes ALL files in uploads/ directory!
 * Remove this file after use for security!
 */

// Security: Require confirmation parameter
$confirmKey = 'CONFIRM_DELETE_ALL_UPLOADS_2026';
$providedKey = $_GET['confirm'] ?? '';

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Cleanup - IT Help Desk</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen py-12 px-4">
        <div class="max-w-3xl mx-auto">
            
            <div class="bg-white border border-gray-200 p-8 mb-6">
                <h1 class="text-3xl font-bold text-gray-900 mb-2">
                    🗑️ Upload Files Cleanup
                </h1>
                <p class="text-gray-600">
                    Production Server: hdesk.resourcestaffonline.com
                </p>
                <p class="text-sm text-red-600 font-semibold mt-2">
                    ⚠️ WARNING: This will permanently delete ALL uploaded files!
                </p>
            </div>

            <?php if ($providedKey !== $confirmKey): ?>
                <!-- Show warning and confirmation form -->
                <div class="bg-red-50 border border-red-300 p-6 mb-6">
                    <h2 class="text-xl font-bold text-red-900 mb-4">⚠️ Confirmation Required</h2>
                    <div class="space-y-3 text-red-800 mb-6">
                        <p class="font-semibold">This will delete:</p>
                        <ul class="list-disc ml-6 space-y-1">
                            <li>All ticket attachment files</li>
                            <li>All uploaded documents</li>
                            <li>All files in /uploads/ directory</li>
                        </ul>
                        <p class="font-semibold mt-4">This action CANNOT be undone!</p>
                    </div>
                    
                    <div class="bg-white p-4 border border-red-300 mb-4">
                        <p class="text-sm text-gray-700 mb-2">Before proceeding:</p>
                        <ol class="list-decimal ml-6 text-sm text-gray-700 space-y-1">
                            <li>Have you run the database cleanup SQL first?</li>
                            <li>Have you backed up any files you need? (Optional)</li>
                            <li>Are you sure you want to delete ALL uploads?</li>
                        </ol>
                    </div>

                    <form method="GET" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-900 mb-2">
                                Type the confirmation key to proceed:
                            </label>
                            <input 
                                type="text" 
                                name="confirm" 
                                class="w-full border border-gray-300 px-4 py-2 text-sm font-mono"
                                placeholder="<?php echo $confirmKey; ?>"
                                required
                            >
                            <p class="text-xs text-gray-500 mt-1">
                                Copy and paste: <code class="bg-gray-100 px-2 py-1"><?php echo $confirmKey; ?></code>
                            </p>
                        </div>
                        
                        <button 
                            type="submit" 
                            class="bg-red-600 text-white px-6 py-3 hover:bg-red-700 font-semibold"
                        >
                            Delete All Upload Files
                        </button>
                    </form>
                </div>

                <div class="bg-blue-50 border border-blue-300 p-4">
                    <p class="text-sm text-blue-800">
                        <strong>Tip:</strong> If you only want to delete specific files, use Hostinger File Manager instead.
                    </p>
                </div>

            <?php else: ?>
                <!-- Execute cleanup -->
                <div class="bg-white border border-gray-200 p-8 mb-6">
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">🔄 Processing...</h2>
                    
                    <?php
                    $uploadsDir = __DIR__ . '/uploads';
                    $deletedFiles = 0;
                    $deletedDirs = 0;
                    $errors = [];
                    
                    if (!is_dir($uploadsDir)) {
                        echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 mb-4">';
                        echo 'Upload directory not found: ' . htmlspecialchars($uploadsDir);
                        echo '</div>';
                    } else {
                        echo '<div class="space-y-2 mb-6">';
                        
                        // Function to recursively delete directory contents
                        function deleteDirectoryContents($dir, &$fileCount, &$dirCount, &$errors) {
                            if (!is_dir($dir)) return;
                            
                            $items = array_diff(scandir($dir), ['.', '..']);
                            
                            foreach ($items as $item) {
                                $path = $dir . DIRECTORY_SEPARATOR . $item;
                                
                                if (is_dir($path)) {
                                    // Recursively delete subdirectory
                                    deleteDirectoryContents($path, $fileCount, $dirCount, $errors);
                                    if (@rmdir($path)) {
                                        $dirCount++;
                                        echo '<div class="text-sm text-gray-600">Deleted directory: ' . htmlspecialchars(basename($path)) . '</div>';
                                    } else {
                                        $errors[] = "Failed to delete directory: $path";
                                    }
                                } else {
                                    // Delete file
                                    if (@unlink($path)) {
                                        $fileCount++;
                                        echo '<div class="text-sm text-gray-600">Deleted file: ' . htmlspecialchars(basename($path)) . '</div>';
                                    } else {
                                        $errors[] = "Failed to delete file: $path";
                                    }
                                }
                            }
                        }
                        
                        // Execute cleanup
                        deleteDirectoryContents($uploadsDir, $deletedFiles, $deletedDirs, $errors);
                        
                        echo '</div>';
                        
                        // Show results
                        if ($deletedFiles > 0 || $deletedDirs > 0) {
                            echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 mb-4">';
                            echo '<strong>✓ Cleanup completed successfully!</strong><br>';
                            echo 'Deleted ' . $deletedFiles . ' files and ' . $deletedDirs . ' directories';
                            echo '</div>';
                        } else {
                            echo '<div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 mb-4">';
                            echo 'No files found to delete. Directory is already empty.';
                            echo '</div>';
                        }
                        
                        // Show errors if any
                        if (!empty($errors)) {
                            echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 mb-4">';
                            echo '<strong>Errors encountered:</strong><br>';
                            foreach ($errors as $error) {
                                echo htmlspecialchars($error) . '<br>';
                            }
                            echo '</div>';
                        }
                    }
                    ?>
                </div>

                <div class="bg-white border border-gray-200 p-8 mb-6">
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">✅ Next Steps</h3>
                    <div class="space-y-2 text-gray-700">
                        <div class="flex items-start">
                            <span class="text-green-600 mr-3">1.</span>
                            <span>Verify cleanup at: <a href="verify_reset.php" class="text-blue-600 hover:underline">verify_reset.php</a></span>
                        </div>
                        <div class="flex items-start">
                            <span class="text-green-600 mr-3">2.</span>
                            <span>Clear your browser cache (Ctrl + Shift + Delete)</span>
                        </div>
                        <div class="flex items-start">
                            <span class="text-green-600 mr-3">3.</span>
                            <span>Logout and login again</span>
                        </div>
                        <div class="flex items-start">
                            <span class="text-red-600 mr-3">4.</span>
                            <span class="font-semibold">DELETE this file (cleanup_uploads.php) for security!</span>
                        </div>
                    </div>
                </div>

                <div class="flex gap-4">
                    <a href="verify_reset.php" class="bg-gray-900 text-white px-6 py-3 hover:bg-gray-800">
                        Verify Reset →
                    </a>
                    <a href="login.php" class="bg-white border border-gray-300 text-gray-700 px-6 py-3 hover:bg-gray-50">
                        Go to Login
                    </a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</body>
</html>
