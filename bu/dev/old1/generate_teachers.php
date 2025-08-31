<?php
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="teachers_150.csv"');

$courses = ['Math-101', 'Science-202', 'English-103', 'History-104', 'Art-105'];
$firstNames = ['David', 'Sarah', 'Michael', 'Rachel', 'James', 'Emma', 'Noam', 'Tamar', 'Avi', 'Maya'];
$lastNames = ['Cohen', 'Levi', 'Mizrahi', 'Peretz', 'Biton', 'Azulai', 'Friedman', 'Goldberg'];

$output = fopen('php://output', 'w');
fputcsv($output, ['phone', 'email', 'first_name', 'last_name', 'courses']);

for($i = 1; $i <= 150; $i++) {
    $phone = '05' . rand(0, 4) . '-' . sprintf('%07d', rand(0, 9999999));
    $email = 'teacher' . $i . '@school.edu';
    $first = $firstNames[array_rand($firstNames)];
    $last = $lastNames[array_rand($lastNames)];
    $teacherCourses = implode(',', array_rand(array_flip($courses), rand(1, 3)));
    
    fputcsv($output, [$phone, $email, $first, $last, $teacherCourses]);
}

fclose($output);
?>
