#!/bin/php
<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') throw new Exception("CLI usage only");

error_reporting(E_ALL);
ini_set('display_errors', 1);

require(__DIR__.'/../vendor/autoload.php');

$args = $_SERVER['argv'];
$mode = 'help';

if (in_array('--save', $args)) {
    $mode = 'save';
    $args = array_diff($args, ['--save']);
}
if (in_array('--load', $args)) {
    $mode = 'load';
    $args = array_diff($args, ['--load']);
}
if (in_array('--delete', $args)) {
    $mode = 'delete';
    $args = array_diff($args, ['--delete']);
}
if (in_array('--replace', $args)) {
    $mode = 'replace';
    $args = array_diff($args, ['--replace']);
}

$args = array_values($args);

$defaultFile = __DIR__.'/../data/users.json';

if (isset($args[1])) {
    $file = $args[1];
} else {
    $file = $defaultFile;
}

$factory = new \Demo\DemoRepositoryFactory();

$factory->configFile = __DIR__.'/../dbconfig.php';

$factory->replace = $mode === 'replace';

if (!is_file($factory->configFile)) {
    $path = realpath($factory->configFile);
    throw new Exception("Config file '{$path}' not found. Please copy dbconfig.example.php"
        ." to dbconfig.php and change the values");
}

$demoService = new \Demo\DemoSavingService($file, $factory);

if ($mode === 'help') {
?>    
Usage: php bin/demo.php [--load|--save|--replace|--delete] [jsonFileName]

jsonFilename: name of file with JSON data (array of objects with keys "id" and "name", and string values)

--load      Load data from repository and dump it in JSON format
--save      Validate and save records (don't save any if some IDs already exist)
--replace   Validate and save records (replace if some IDs already exist)
--delete    Delete records with matching IDs

If filename is not provided, by default will use '<?php echo realpath($defaultFile); ?>'.
<?php    
}
else if ($mode === 'load') {
    echo json_encode(array_values([...$demoService->loadUsers()]), JSON_PRETTY_PRINT)."\n";
} else if ($mode === 'delete') {
    $demoService->deleteUsers();
    echo json_encode(['message' => 'Deleted users', 'success' => 1], JSON_PRETTY_PRINT)."\n";
} else if ($mode === 'save' || $mode === 'replace') {
    
    $problems = [];
    if (!$demoService->saveUsers($problems)) {
        echo json_encode(['message' => 'Cannot save users', 'success' => 0, 'problems' => (object) $problems], JSON_PRETTY_PRINT)."\n";
        die(-1);
    } else {
        echo json_encode(['message' => 'Users saved', 'success' => 1], JSON_PRETTY_PRINT)."\n";
    }
    
}