# Login Avatar Shortcode Documentation

## Overview
This document describes the implementation of the `[lilac_user_icon_box]` shortcode used for displaying a user avatar/login button in the site header.

## Shortcode Usage
```php
[lilac_user_icon_box 
   login_text="התחברות" 
   profile_text="פרופיל" 
   login_url="/login" 
   profile_url="/p" 
   class="custom-class"
]
```

### Parameters
- `login_text` (string): Text to display for non-logged-in users (default: "התחברות")
- `profile_text` (string): Text to display for logged-in users (default: "פרופיל")
- `login_url` (string): URL for the login link (default: WordPress login URL)
- `profile_url` (string): URL for the profile link (default: User's profile edit URL)
- `class` (string): Additional CSS class for styling (default: 'elementor-icon-box-wrapper')

## Implementation Details

### PHP Class
- **Location:** `src/Login/LoginManager.php`
- **Class:** `Lilac\Login\LoginManager`
- **Method:** `user_icon_box_shortcode()`

### HTML Structure
```html
<div class="elementor-icon-box-wrapper [custom-class]">
    <div class="elementor-icon-box-icon">
        <a href="[login_or_profile_url]" class="elementor-icon">
            <!-- User icon SVG -->
            <svg>...</svg>
        </a>
    </div>
    <div class="elementor-icon-box-content">
        <h3 class="elementor-icon-box-title">
            <a href="[login_or_profile_url]">
                [display_text]
            </a>
        </h3>
    </div>
</div>
```

## Behavior

### For Logged-in Users
- Displays: User's display name (e.g., "היי [שם משתמש]")
- Links to: Profile page
- Shows: Profile icon

### For Guests
- Displays: Login text (default: "התחברות")
- Links to: Login page
- Shows: User icon

## Integration with Elementor
The shortcode is designed to work within Elementor's widget system and uses Elementor's CSS classes for styling.

## Current Implementation Status
- The shortcode is registered in the `LoginManager` class
- Basic functionality is implemented
- Styling is handled by Elementor's default styles

## Known Issues
1. The shortcode might not be registered properly in some cases
2. Namespace issues might prevent proper initialization
3. May require additional CSS for custom styling

## Testing Instructions
1. Add the shortcode to a page using the example above
2. Test both logged-in and guest views
3. Verify all links work as expected
4. Check mobile responsiveness
5. Verify RTL text alignment for Hebrew

## Future Improvements
1. Add user avatar display for logged-in users
2. Implement dropdown menu for user actions
3. Add loading states for better UX
4. Add custom icon support

<div class="elementor-element elementor-element-1955289 e-con-full e-flex e-con e-child" data-id="1955289" data-element_type="container">
				<div class="elementor-element elementor-element-f5b6604 elementor-widget__width-auto elementor-widget elementor-widget-shortcode" data-id="f5b6604" data-element_type="widget" data-widget_type="shortcode.default">
				<div class="elementor-widget-container">
							<div class="elementor-shortcode">[lilac_user_icon_box 
   login_text="התחברות" 
   profile_text="פרופיל" 
   login_url="/login" 
   profile_url="/p" 
   class="custom-class"
]</div>
						</div>
				</div>
				<div class="elementor-element elementor-element-96a536f elementor-vertical-align-bottom elementor-widget__width-auto elementor-widget elementor-widget-icon-box" data-id="96a536f" data-element_type="widget" data-widget_type="icon-box.default">
				<div class="elementor-widget-container">
							<div class="elementor-icon-box-wrapper">

			
						<div class="elementor-icon-box-content">

									<h3 class="elementor-icon-box-title">
						<a href="/teachers-login">
							כניסת מורים						</a>
					</h3>
				
				
			</div>
			
		</div>
						</div>
				</div>
				</div>