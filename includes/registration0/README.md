# LearnDash Promo Code Registration System

## Overview

This system allows administrators to generate, manage, and validate promotional codes that can be used to enroll users in LearnDash courses. The implementation extends the existing registration code system with support for:

- Course-specific promo codes
- Multiple-use codes with usage limits
- Expiry dates
- Enhanced validation and security

## Features

- **Admin Generation Interface**: Easily generate single or bulk promo codes from the admin dashboard
- **Course Selection**: Associate codes with specific LearnDash courses
- **Usage Limits**: Set maximum number of times a code can be used
- **Expiry Dates**: Set dates after which codes automatically expire
- **Shortcode Integration**: Use `[promo_code]` shortcode to embed code redemption forms
- **Security**: Includes nonce verification, rate limiting, and input sanitization
- **Customization**: Shortcode attributes for customizing appearance and behavior

## Technical Implementation

### Database Structure

The system uses the `wp_registration_codes` table with the following structure:

| Field        | Type         | Description                                       |
|--------------|--------------|---------------------------------------------------|
| id           | int          | Auto-incrementing primary key                     |
| code         | varchar(50)  | The unique promo code                             |
| role         | varchar(50)  | User role to assign (if applicable)               |
| group_name   | varchar(50)  | User group (if applicable)                        |
| course_id    | bigint       | ID of the LearnDash course (null if not specific) |
| max_uses     | int          | Maximum number of uses for this code              |
| used_count   | int          | Current number of times the code has been used    |
| expiry_date  | datetime     | Date after which the code expires (null if none)  |
| is_used      | tinyint      | Flag if code is fully used (legacy compatibility) |
| used_by      | int          | User ID of who last used the code                 |
| used_at      | datetime     | When the code was last used                       |
| created_by   | int          | Admin who created the code                        |
| created_at   | datetime     | When the code was created                         |

### Core Files

- **class-registration-codes.php**: Main singleton class handling CRUD operations for codes
- **db-migrations.php**: Database migration script for schema updates
- **promo-code.php**: Shortcode implementation and validation logic
- **templates/generate-codes.php**: Admin UI template for generating codes

## Usage

### Admin: Generating Codes

1. Navigate to WordPress Admin → Registration Codes → Generate Codes
2. Fill in the form:
   - Number of codes to generate
   - User role (optional)
   - User group (optional)
   - Course ID (optional, select a LearnDash course)
   - Maximum uses (default: 1)
   - Expiry date (optional)
   - Code format options
3. Click "Generate Codes" to create the codes

### Frontend: Validating Codes

Use the shortcode on any page where users should validate their promo codes:

```
[promo_code]
```

#### Shortcode Attributes

All attributes are optional and have reasonable defaults:

```
[promo_code 
    title="Promotion Code"
    description="Enter your promotion code below"
    button_text="Submit Code"
    input_placeholder="Your code here"
    success_message="Success! Redirecting..."
    error_message="Invalid code. Please try again."
    course_id="123"
    redirect_url=""
    redirect_delay="2000"
    auto_redirect="true"
]
```

You can also customize specific error messages:

```
[promo_code
    code_expired="This code has expired"
    code_max_uses="This code has reached maximum usage"
    code_wrong_course="This code is not valid for this course"
    code_already_used="This code has already been used"
    code_not_found="Code not found in our system"
]
```

## Security

The system implements several security measures:

- WordPress nonces to prevent CSRF attacks
- Rate limiting to prevent brute force attempts
- Input sanitization and validation
- Detailed error logging (when WP_DEBUG is enabled)
- Database transactions for data integrity

## Testing

Unit and integration tests are available in the `tests` directory. See the tests README for details on running the test suite.

## Version History

- **1.2.0**: Added course_id, max_uses, used_count, expiry_date
- **1.1.0**: Initial version with basic code functionality

## Troubleshooting

Common issues:

- **Codes not working**: Check if they're expired or have reached max uses
- **Course enrollment issues**: Ensure LearnDash is active and properly configured
- **Form submission errors**: Check console logs for JavaScript errors and server logs for PHP errors
