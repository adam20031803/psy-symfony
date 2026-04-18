<?php
/**
 * Downloads face-api.js model files from GitHub into public/face-models/
 * Run once: php download_face_models.php
 */

$targetDir = __DIR__ . '/public/face-models';
if (!is_dir($targetDir)) {
    mkdir($targetDir, 0755, true);
}

$baseUrl = 'https://raw.githubusercontent.com/justadudewhohacks/face-api.js/master/weights/';

$files = [
    // Tiny Face Detector (fast detection)
    'tiny_face_detector_model-weights_manifest.json',
    'tiny_face_detector_model-shard1',
    // 68-point Face Landmarks (tiny, for speed)
    'face_landmark_68_tiny_model-weights_manifest.json',
    'face_landmark_68_tiny_model-shard1',
    // Face Recognition (128-D descriptor)
    'face_recognition_model-weights_manifest.json',
    'face_recognition_model-shard1',
    'face_recognition_model-shard2',
];

$ctx = stream_context_create([
    'http' => [
        'timeout' => 60,
        'header'  => "User-Agent: PHP/download\r\n",
    ],
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
    ],
]);

foreach ($files as $file) {
    $dest = $targetDir . '/' . $file;
    if (file_exists($dest)) {
        echo "  [SKIP] $file (already exists)\n";
        continue;
    }
    echo "  [DL]   $file ... ";
    $data = @file_get_contents($baseUrl . $file, false, $ctx);
    if ($data === false) {
        echo "FAILED\n";
    } else {
        file_put_contents($dest, $data);
        echo 'OK (' . round(strlen($data) / 1024, 1) . " KB)\n";
    }
}

echo "\nDone! Models are in public/face-models/\n";
