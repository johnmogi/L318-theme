# Promo Code Persistence & Teacher-Class Management Fix

## Issue Fixed

This update addresses several related issues with the Teacher Class Wizard:

1. **Promo Codes Persistence** - Previously, promo codes generated in the wizard were only stored temporarily in memory and were lost after page refresh. Now they are properly saved as Custom Post Type (CPT) entries in the database.

2. **Teacher-Class Association** - Teachers were not being properly linked to their classes after promo code generation. Now teachers are automatically set as group leaders for the classes they create or manage.

3. **Student-Class Enrollment** - Students created in the wizard are now properly enrolled in their classes and linked to their promo codes.

4. **AJAX-based Promo Code Generation** - Added AJAX-based promo code generation and CSV download functionality to improve user experience. Users can now generate codes and download them without page reloads.

## Implementation Details

### 1. Modified Files

- **`includes/admin/class-teacher-class-wizard.php`** - Added AJAX handlers for promo code generation and CSV download
- **`includes/admin/class-teacher-class-wizard-fix.php`** - Implements filters for promo code persistence and teacher-class associations
- **`includes/school-class-helper.php`** - Helper functions for retrieving promo codes and classes by teacher or group
- **`assets/js/admin/teacher-class-wizard.js`** - JavaScript for AJAX-based promo code generation and CSV download

### 2. Main Components

#### Filter-Based Architecture

The system now uses a filter-based approach for promo code persistence:

1. `Teacher_Class_Wizard` class provides the UI and AJAX handlers for the wizard
2. `Teacher_Class_Wizard_Fix` class implements the backend persistence logic via filters
3. The integration happens through the WordPress filter system:
   - `ajax_generate_promo_codes()` in `Teacher_Class_Wizard` generates basic codes
   - It then passes these codes through the `teacher_class_wizard_generate_promo_codes` filter
   - `save_promo_codes()` in `Teacher_Class_Wizard_Fix` receives these codes via the filter and persists them to the database

This approach ensures separation of concerns and allows for easier maintenance and extension.

#### Promo Code Storage

Promo codes are stored as Custom Post Type entries with the following metadata:

- `_ld_promo_code_code` - The actual promo code
- `_ld_promo_code_group_id` - The LearnDash group/class ID
- `_ld_promo_code_teacher_id` - The teacher user ID
- `_ld_promo_code_student_id` - The student user ID (if assigned)
- `_ld_promo_code_expiry_date` - The expiry date for the code (if set)

#### Teacher-Class Association

When a teacher creates or is assigned to a class, they are now automatically set as a group leader, which gives them access to:

- View and manage the class in the LearnDash dashboard
- Manage students enrolled in the class
- Access class statistics and reports

#### Student-Promo Code Linking

Students created in the wizard are now:

- Enrolled in the selected class/group
- Linked to their promo code via user metadata
- The promo code is linked back to the student

### 3. Helper Functions

The following helper functions have been added for easier data retrieval:

- `lilac_get_promo_codes_by_teacher($teacher_id)` - Get all promo codes created by a specific teacher
- `lilac_get_promo_codes_by_group($group_id)` - Get all promo codes for a specific class/group
- `lilac_get_groups_by_teacher($teacher_id)` - Get all classes/groups where a teacher is a group leader

## Technical Implementation

The fix uses a direct implementation approach to ensure reliability:

1. **Direct Code Modification** - The original wizard class was modified to save promo codes immediately during generation

2. **New Methods Added**:
   - `save_promo_code_to_db()` - Creates promo code CPT entries with proper metadata
   - `assign_teacher_to_group()` - Sets teacher as group leader for selected class

3. **Enhanced Student Creation** - When students are created, they are directly linked to their promo codes, and the promo code entries are updated to reference the students

## How to Verify the Fix

1. **Test Promo Code Generation**:
   - Create a new class in the wizard
   - Generate promo codes
   - Refresh the page or log out and back in
   - Verify the class is still visible in the dashboard

2. **Check Admin Area**:
   - Go to LearnDash > Promo Codes
   - Verify the newly created codes appear with proper class association

3. **Verify Teacher Access**:
   - Log in as a teacher
   - Check if they can view and manage their assigned classes
   - Verify students appear correctly in their classes
