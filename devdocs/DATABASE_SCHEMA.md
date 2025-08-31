# School Manager - Database Schema Documentation

## Table of Contents
1. [Teachers Table](#1-teachers-table-wp_edc_school_teachers)
2. [Students Table](#2-students-table-wp_edc_school_students)
3. [Classes Table](#3-classes-table-wp_edc_school_classes)
4. [Enrollments Table](#4-enrollments-table-wp_edc_school_enrollments)
5. [Promo Codes Table](#5-promo-codes-table-wp_edc_school_promo_codes)
6. [Relationships](#relationships)
7. [Sample Queries](#sample-queries)

## 1. Teachers Table (`wp_edc_school_teachers`)
Stores information about teachers.

| Column | Type | Description | Constraints |
|--------|------|-------------|-------------|
| id | BIGINT(20) UNSIGNED | Primary key | AUTO_INCREMENT, NOT NULL |
| wp_user_id | BIGINT(20) UNSIGNED | WordPress user ID | UNIQUE, NOT NULL |
| teacher_id_number | VARCHAR(50) | Teacher's ID number | UNIQUE, NOT NULL |
| first_name | VARCHAR(100) | First name | NOT NULL |
| last_name | VARCHAR(100) | Last name | NOT NULL |
| email | VARCHAR(100) | Email address | UNIQUE, NOT NULL |
| phone | VARCHAR(20) | Phone number | NULL |
| bio | TEXT | Biography | NULL |
| subjects_taught | TEXT | Subjects taught | NULL |
| status | ENUM | Account status | 'active','inactive','on_leave' |
| created_at | DATETIME | Creation timestamp | DEFAULT CURRENT_TIMESTAMP |
| updated_at | DATETIME | Last update timestamp | ON UPDATE CURRENT_TIMESTAMP |

## 2. Students Table (`wp_edc_school_students`)
Stores information about students.

| Column | Type | Description | Constraints |
|--------|------|-------------|-------------|
| id | BIGINT(20) UNSIGNED | Primary key | AUTO_INCREMENT, NOT NULL |
| wp_user_id | BIGINT(20) UNSIGNED | WordPress user ID | UNIQUE, NOT NULL |
| student_id_number | VARCHAR(50) | Student's ID number | UNIQUE, NOT NULL |
| first_name | VARCHAR(100) | First name | NOT NULL |
| last_name | VARCHAR(100) | Last name | NOT NULL |
| email | VARCHAR(100) | Email address | UNIQUE, NOT NULL |
| phone | VARCHAR(20) | Phone number | NULL |
| address | TEXT | Street address | NULL |
| city | VARCHAR(100) | City | NULL |
| state | VARCHAR(100) | State/Province | NULL |
| postal_code | VARCHAR(20) | ZIP/Postal code | NULL |
| country | VARCHAR(100) | Country | NULL |
| date_of_birth | DATE | Date of birth | NULL |
| status | ENUM | Account status | 'active','inactive','suspended' |
| created_at | DATETIME | Creation timestamp | DEFAULT CURRENT_TIMESTAMP |
| updated_at | DATETIME | Last update timestamp | ON UPDATE CURRENT_TIMESTAMP |

## 3. Classes Table (`wp_edc_school_classes`)
Stores information about classes.

| Column | Type | Description | Constraints |
|--------|------|-------------|-------------|
| id | BIGINT(20) UNSIGNED | Primary key | AUTO_INCREMENT, NOT NULL |
| name | VARCHAR(255) | Class name | NOT NULL |
| description | TEXT | Class description | NULL |
| teacher_id | BIGINT(20) UNSIGNED | Teacher ID | FOREIGN KEY (teachers.id) |
| max_students | INT(11) UNSIGNED | Maximum students allowed | DEFAULT 30 |
| status | ENUM | Class status | 'active','inactive','archived' |
| created_at | DATETIME | Creation timestamp | DEFAULT CURRENT_TIMESTAMP |
| updated_at | DATETIME | Last update timestamp | ON UPDATE CURRENT_TIMESTAMP |

## 4. Enrollments Table (`wp_edc_school_enrollments`)
Tracks student enrollments in classes.

| Column | Type | Description | Constraints |
|--------|------|-------------|-------------|
| id | BIGINT(20) UNSIGNED | Primary key | AUTO_INCREMENT, NOT NULL |
| student_id | BIGINT(20) UNSIGNED | Student ID | FOREIGN KEY (students.id) |
| class_id | BIGINT(20) UNSIGNED | Class ID | FOREIGN KEY (classes.id) |
| enrollment_date | DATETIME | When student enrolled | DEFAULT CURRENT_TIMESTAMP |
| completion_date | DATETIME | When course was completed | NULL |
| status | ENUM | Enrollment status | 'enrolled','completed','withdrawn','failed' |
| grade | VARCHAR(10) | Final grade | NULL |
| notes | TEXT | Additional notes | NULL |
| created_at | DATETIME | Creation timestamp | DEFAULT CURRENT_TIMESTAMP |
| updated_at | DATETIME | Last update timestamp | ON UPDATE CURRENT_TIMESTAMP |

## 5. Promo Codes Table (`wp_edc_school_promo_codes`)
Manages promo codes for class enrollments.

| Column | Type | Description | Constraints |
|--------|------|-------------|-------------|
| id | BIGINT(20) UNSIGNED | Primary key | AUTO_INCREMENT, NOT NULL |
| code | VARCHAR(50) | Promo code | UNIQUE, NOT NULL |
| prefix | VARCHAR(10) | Code prefix | NULL |
| class_id | BIGINT(20) UNSIGNED | Class ID | FOREIGN KEY (classes.id) |
| teacher_id | BIGINT(20) UNSIGNED | Teacher ID | FOREIGN KEY (teachers.id) |
| expiry_date | DATETIME | When code expires | NULL |
| usage_limit | INT(11) | Max uses | DEFAULT 1 |
| used_count | INT(11) | Times used | DEFAULT 0 |
| status | ENUM | Code status | 'active','inactive','expired' |
| created_at | DATETIME | Creation timestamp | DEFAULT CURRENT_TIMESTAMP |
| updated_at | DATETIME | Last update timestamp | ON UPDATE CURRENT_TIMESTAMP |

## Relationships
1. **Classes to Teachers**: One-to-Many (One teacher can teach multiple classes)
2. **Enrollments**: 
   - Many-to-Many between Students and Classes
   - Each enrollment links one student to one class
3. **Promo Codes**:
   - Many-to-One with Classes
   - Many-to-One with Teachers

## Sample Queries

### Get all active teachers
```sql
SELECT * 
FROM wp_edc_school_teachers 
WHERE status = 'active' 
ORDER BY last_name, first_name;
```

### Get all classes with teacher information
```sql
SELECT 
    c.*, 
    CONCAT(t.first_name, ' ', t.last_name) as teacher_name
FROM wp_edc_school_classes c
JOIN wp_edc_school_teachers t ON c.teacher_id = t.id
WHERE c.status = 'active'
ORDER BY c.name;
```

### Get all students in a specific class
```sql
SELECT 
    s.first_name, 
    s.last_name, 
    s.email,
    e.enrollment_date,
    e.status as enrollment_status
FROM wp_edc_school_enrollments e
JOIN wp_edc_school_students s ON e.student_id = s.id
WHERE e.class_id = [CLASS_ID]
AND e.status = 'enrolled'
ORDER BY s.last_name, s.first_name;
```

### Get teacher's class schedule
```sql
SELECT 
    c.name as class_name,
    COUNT(e.id) as enrolled_students,
    c.max_students,
    c.status
FROM wp_edc_school_classes c
LEFT JOIN wp_edc_school_enrollments e ON c.id = e.class_id AND e.status = 'enrolled'
WHERE c.teacher_id = [TEACHER_ID]
GROUP BY c.id
ORDER BY c.name;
```

### Get student's class schedule
```sql
SELECT 
    c.name as class_name,
    CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
    e.enrollment_date,
    e.status as enrollment_status
FROM wp_edc_school_enrollments e
JOIN wp_edc_school_classes c ON e.class_id = c.id
JOIN wp_edc_school_teachers t ON c.teacher_id = t.id
WHERE e.student_id = [STUDENT_ID]
ORDER BY c.name;
```

### Get available promo codes for a class
```sql
SELECT 
    p.code,
    p.expiry_date,
    p.usage_limit - p.used_count as remaining_uses
FROM wp_edc_school_promo_codes p
WHERE p.class_id = [CLASS_ID]
AND p.status = 'active'
AND (p.expiry_date IS NULL OR p.expiry_date > NOW())
AND (p.usage_limit = 0 OR p.used_count < p.usage_limit);
```
