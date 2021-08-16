<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Origin, Content-Type, X-Auth-Token');
header('content-type: application/json');

$inputPath = $_POST['input_path'];
$outputPath = $_POST['output_path'];
$quality = $_POST['quality'];
$maxWidth = $_POST['max_width'];
$maxHeight = $_POST['max_height'];

$cliCmd = 'php imageconverter convert ' . $inputPath . ' ' . $outputPath . ' ' .$maxWidth . ' ' . $maxHeight . ' ' . $quality . ' --json-output';

$success = false;

try {
    $cmdData = shell_exec($cliCmd);
    $cmdData = trim(preg_replace('/\s\s+/', ' ', $cmdData));
    $data = json_decode($cmdData);

    $success = true;
} catch (Exception $e) {
    $success = false;
}

$response = [
    'success' => $success,
    'data' => $data
];
echo json_encode($response);


?>
