# School Manager - SQL Queries Reference

## Table of Contents
1. [Teacher Queries](#teacher-queries)
2. [Class Queries](#class-queries)
3. [Student Queries](#student-queries)
4. [Enrollment Queries](#enrollment-queries)
5. [Reporting Queries](#reporting-queries)

## Teacher Queries

### Get All Teachers with Class Counts
```sql
SELECT 
    t.id,
    t.teacher_id_number,
    CONCAT(t.first_name, ' ', t.last_name) as name,
    t.email,
    t.phone,
    t.status,
    COUNT(c.id) as class_count
FROM wp_edc_school_teachers t
LEFT JOIN wp_edc_school_classes c ON t.id = c.teacher_id
GROUP BY t.id;
```

### Get Available Teachers (Not Teaching Any Classes)
```sql
SELECT 
    t.id,
    CONCAT(t.first_name, ' ', t.last_name) as name,
    t.email,
    t.status
FROM wp_edc_school_teachers t
LEFT JOIN wp_edc_school_classes c ON t.id = c.teacher_id
WHERE c.id IS NULL;
```

## Class Queries

### Get All Classes with Teacher and Enrollment Info
```sql
SELECT 
    c.id,
    c.name,
    c.status,
    CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
    COUNT(e.id) as enrolled_students,
    c.max_students,
    CONCAT(ROUND((COUNT(e.id) / c.max_students) * 100), '%') as capacity_used
FROM wp_edc_school_classes c
JOIN wp_edc_school_teachers t ON c.teacher_id = t.id
LEFT JOIN wp_edc_school_enrollments e ON c.id = e.class_id AND e.status = 'enrolled'
GROUP BY c.id;
```

### Get Class Roster
```sql
SELECT 
    s.student_id_number,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.email,
    s.phone,
    e.enrollment_date,
    e.status as enrollment_status
FROM wp_edc_school_enrollments e
JOIN wp_edc_school_students s ON e.student_id = s.id
WHERE e.class_id = :class_id
ORDER BY s.last_name, s.first_name;
```

## Student Queries

### Get All Students with Enrollment Count
```sql
SELECT 
    s.id,
    s.student_id_number,
    CONCAT(s.first_name, ' ', s.last_name) as name,
    s.email,
    s.phone,
    s.status,
    COUNT(e.id) as enrolled_classes
FROM wp_edc_school_students s
LEFT JOIN wp_edc_school_enrollments e ON s.id = e.student_id AND e.status = 'enrolled'
GROUP BY s.id;
```

### Get Student's Class Schedule
```sql
SELECT 
    c.name as class_name,
    CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
    e.enrollment_date,
    e.status as enrollment_status
FROM wp_edc_school_enrollments e
JOIN wp_edc_school_classes c ON e.class_id = c.id
JOIN wp_edc_school_teachers t ON c.teacher_id = t.id
WHERE e.student_id = :student_id
ORDER BY c.name;
```

## Enrollment Queries

### Enroll Student in Class
```sql
INSERT INTO wp_edc_school_enrollments (
    student_id, 
    class_id, 
    status,
    enrollment_date
) VALUES (
    :student_id,
    :class_id,
    'enrolled',
    NOW()
);
```

### Update Enrollment Status
```sql
UPDATE wp_edc_school_enrollments
SET 
    status = :new_status,
    updated_at = NOW()
WHERE student_id = :student_id
AND class_id = :class_id;
```

## Reporting Queries

### Teacher's Schedule with Students
```sql
SELECT 
    c.name as class_name,
    c.status as class_status,
    COUNT(e.id) as enrolled_students,
    c.max_students,
    GROUP_CONCAT(
        CONCAT(s.first_name, ' ', s.last_name)
        SEPARATOR ', '
    ) as student_names
FROM wp_edc_school_teachers t
JOIN wp_edc_school_classes c ON t.id = c.teacher_id
LEFT JOIN wp_edc_school_enrollments e ON c.id = e.class_id AND e.status = 'enrolled'
LEFT JOIN wp_edc_school_students s ON e.student_id = s.id
WHERE t.id = :teacher_id
GROUP BY c.id;
```

### Class Capacity Report
```sql
SELECT 
    c.name as class_name,
    CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
    COUNT(e.id) as enrolled_students,
    c.max_students,
    CONCAT(ROUND((COUNT(e.id) / c.max_students) * 100), '%') as capacity_used,
    CASE 
        WHEN COUNT(e.id) >= c.max_students THEN 'Full'
        WHEN COUNT(e.id) >= (c.max_students * 0.8) THEN 'Almost Full'
        ELSE 'Available'
    END as availability
FROM wp_edc_school_classes c
JOIN wp_edc_school_teachers t ON c.teacher_id = t.id
LEFT JOIN wp_edc_school_enrollments e ON c.id = e.class_id AND e.status = 'enrolled'
GROUP BY c.id
ORDER BY availability, capacity_used DESC;
```

### Student Enrollment Summary
```sql
SELECT 
    s.student_id_number,
    CONCAT(s.first_name, ' ', s.last_name) as student_name,
    s.email,
    s.status as student_status,
    COUNT(e.id) as total_enrollments,
    SUM(CASE WHEN e.status = 'enrolled' THEN 1 ELSE 0 END) as active_enrollments,
    SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_enrollments
FROM wp_edc_school_students s
LEFT JOIN wp_edc_school_enrollments e ON s.id = e.student_id
GROUP BY s.id
ORDER BY s.last_name, s.first_name;
```
