<?php

require 'vendor/autoload.php';

echo "==========================================================\n";
echo "         MIGRATION VERIFICATION TEST\n";
echo "==========================================================\n\n";

$tests = [];
$passed = 0;
$failed = 0;

// Test 1: Check for old namespace references
echo "Test 1: Checking for old namespace references...\n";
$result = shell_exec('grep -r "Tinderbox" src/ --include="*.php" 2>/dev/null');
if (empty($result)) {
    echo "  ✓ PASS: No Tinderbox references found\n";
    $passed++;
} else {
    echo "  ✗ FAIL: Found Tinderbox references\n";
    $failed++;
}

// Test 2: Check composer.json for old packages
echo "\nTest 2: Checking composer.json for removed packages...\n";
$composer = file_get_contents('composer.json');
if (strpos($composer, 'the-tinderbox/clickhouse-builder') === false &&
    strpos($composer, 'myclabs/php-enum') === false) {
    echo "  ✓ PASS: Old packages removed from composer.json\n";
    $passed++;
} else {
    echo "  ✗ FAIL: Old packages still in composer.json\n";
    $failed++;
}

// Test 3: Test enum loading
echo "\nTest 3: Testing enum classes...\n";
try {
    $format = \LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\Format::CSV;
    $operator = \LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\Operator::EQUALS;
    echo "  ✓ PASS: Enums load correctly\n";
    $passed++;
} catch (Exception $e) {
    echo "  ✗ FAIL: Enum loading failed\n";
    $failed++;
}

// Test 4: Test enum values
echo "\nTest 4: Testing enum value access...\n";
try {
    $value = \LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\Format::CSV->value;
    if ($value === 'CSV') {
        echo "  ✓ PASS: Enum values work correctly\n";
        $passed++;
    } else {
        echo "  ✗ FAIL: Enum value incorrect\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ✗ FAIL: Enum value access failed\n";
    $failed++;
}

// Test 5: Test enum isValid method
echo "\nTest 5: Testing enum isValid method...\n";
try {
    $valid = \LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\Format::isValid('JSON');
    $invalid = \LaravelClickhouseEloquent\ClickhouseBuilder\Query\Enums\Format::isValid('INVALID');
    if ($valid && !$invalid) {
        echo "  ✓ PASS: isValid method works correctly\n";
        $passed++;
    } else {
        echo "  ✗ FAIL: isValid method incorrect\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ✗ FAIL: isValid method failed\n";
    $failed++;
}

// Test 6: Test main classes
echo "\nTest 6: Testing main application classes...\n";
try {
    $grammar = new \LaravelClickhouseEloquent\Grammar();
    $raw = new \LaravelClickhouseEloquent\RawColumn('test');
    echo "  ✓ PASS: Main classes load correctly\n";
    $passed++;
} catch (Exception $e) {
    echo "  ✗ FAIL: Main classes failed to load\n";
    $failed++;
}

// Test 7: Test ClickhouseBuilder classes
echo "\nTest 7: Testing ClickhouseBuilder classes...\n";
try {
    $exists = class_exists('\LaravelClickhouseEloquent\ClickhouseBuilder\Query\BaseBuilder');
    if ($exists) {
        echo "  ✓ PASS: ClickhouseBuilder classes exist\n";
        $passed++;
    } else {
        echo "  ✗ FAIL: ClickhouseBuilder classes not found\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ✗ FAIL: ClickhouseBuilder test failed\n";
    $failed++;
}

// Test 8: Test ClickhouseClient classes
echo "\nTest 8: Testing ClickhouseClient classes...\n";
try {
    $exists = class_exists('\LaravelClickhouseEloquent\ClickhouseClient\Server');
    if ($exists) {
        echo "  ✓ PASS: ClickhouseClient classes exist\n";
        $passed++;
    } else {
        echo "  ✗ FAIL: ClickhouseClient classes not found\n";
        $failed++;
    }
} catch (Exception $e) {
    echo "  ✗ FAIL: ClickhouseClient test failed\n";
    $failed++;
}

// Summary
echo "\n==========================================================\n";
echo "                    SUMMARY\n";
echo "==========================================================\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: $passed ✓\n";
echo "Failed: $failed" . ($failed > 0 ? " ✗" : "") . "\n";

if ($failed === 0) {
    echo "\n✅ ALL TESTS PASSED! Migration successful!\n";
    exit(0);
} else {
    echo "\n❌ SOME TESTS FAILED. Please review the errors above.\n";
    exit(1);
}
