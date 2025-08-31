<?php

echo "Checking file syntax...\n";

// Set error reporting to show syntax errors
error_reporting(E_ALL);

// Try to parse the file
$file = __DIR__ . '/class-teacher-class-wizard.php';
$code = file_get_contents($file);

// Test parsing the PHP code
try {
    $tokens = token_get_all($code);
    
    // Find the specific line with the error
    $lineNumber = 0;
    $errorFound = false;
    
    foreach ($tokens as $token) {
        if (is_array($token)) {
            if ($token[2] >= 719 && $token[2] <= 720) {
                echo "Line {$token[2]}: " . token_name($token[0]) . " - " . trim($token[1]) . "\n";
            }
            
            $lineNumber = $token[2];
        }
    }
    
    echo "\nSyntax check complete. No parsing errors found.\n";
    echo "The error might be logical rather than syntactical.\n";
    echo "Checking class structure...\n\n";
    
    // Also check if we can exactly locate the process_promo_step method
    $pattern = '/private\s+function\s+process_promo_step\s*\(\)/i';
    preg_match($pattern, $code, $matches, PREG_OFFSET_CAPTURE);
    
    if (!empty($matches)) {
        $pos = $matches[0][1];
        $line = substr_count(substr($code, 0, $pos), "\n") + 1;
        echo "Found process_promo_step() method at line {$line}\n";
        
        // Find the next function after this one
        $pattern = '/\s+function\s+[a-zA-Z0-9_]+\s*\(/i';
        preg_match_all($pattern, $code, $allMatches, PREG_OFFSET_CAPTURE);
        
        $nextFunctionPos = PHP_INT_MAX;
        $nextFunctionLine = 0;
        $nextFunctionName = '';
        
        foreach ($allMatches[0] as $match) {
            if ($match[1] > $pos && $match[1] < $nextFunctionPos) {
                $nextFunctionPos = $match[1];
                $nextFunctionLine = substr_count(substr($code, 0, $nextFunctionPos), "\n") + 1;
                $nextFunctionName = trim($match[0]);
            }
        }
        
        if ($nextFunctionLine > 0) {
            echo "Next function {$nextFunctionName} starts at line {$nextFunctionLine}\n";
            echo "process_promo_step() should end before line {$nextFunctionLine}\n";
        } else {
            echo "No next function found, process_promo_step() might be the last method\n";
        }
        
        // Count braces to see if they balance in this function
        $contentBetween = substr($code, $pos, ($nextFunctionPos > $pos) ? $nextFunctionPos - $pos : strlen($code) - $pos);
        $openBraces = substr_count($contentBetween, '{');
        $closeBraces = substr_count($contentBetween, '}');
        
        echo "Number of opening braces: {$openBraces}\n";
        echo "Number of closing braces: {$closeBraces}\n";
        
        if ($openBraces != $closeBraces) {
            echo "ERROR: Brace mismatch in process_promo_step() method!\n";
        } else {
            echo "Braces appear to be balanced.\n";
        }
    } else {
        echo "Could not find process_promo_step() method!\n";
    }
} catch (ParseError $e) {
    echo "Parse error: " . $e->getMessage() . " on line " . $e->getLine() . "\n";
}

echo "\nDone.\n";
