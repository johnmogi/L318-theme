<?php
// Generate 300 unique test codes
$codes = [];
for ($i = 1; $i <= 300; $i++) {
    $prefix = strtoupper(substr(md5(uniqid()), 0, 3));
    $codes[] = sprintf('TEST-%s-%04d', $prefix, $i);
}

// Create CSV content
$csv = "Code,Group\n"; // Header
foreach ($codes as $i => $code) {
    $group = ($i % 5 == 0) ? "Group-A" : "Group-B"; 
    $csv .= "{$code},{$group}\n";
}

echo $csv;
?>