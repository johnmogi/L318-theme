# Registration Codes Admin 

## Roadmap & Documentation

This document outlines the complete roadmap, file structure, key functions, dependencies, and future development milestones for the Registration Codes Admin feature.

---

## 1. Overview

The Registration Codes Admin system provides:  
- **Bulk generation** of registration codes (10/50/100 or custom count)  
- **Grouping** by Class/School  
- **Import/Export** via CSV  
- **Role-based Dashboards**: full admin vs. teacher  
- **Frontend integration** with `[code_registration]` shortcode  

It is implemented as a standalone folder in the child theme under `includes/admin` with its own classes, views, scripts, and styles.

---

## 2. File Structure

```
hello-theme-child-master/
└── includes/admin/
    ├── class-registration-codes.php      # Core PHP class for code management
    ├── docs/
    │   └── README.md                     # This roadmap & documentation
    ├── js/
    │   └── admin.js                      # AJAX handlers & UI interactions
    ├── css/
    │   └── admin.css                     # Admin panel styling
    └── views/
        ├── registration-codes.php       # Admin page for system managers
        └── teacher-dashboard.php        # Dashboard for teachers (subset)
```

---

## 3. Key Components & Functions

### 3.1 Core Class: `CCR_Registration_Codes`
- **Constructor**: sets up hooks for admin menu, scripts, AJAX.  
- **create_table()**: creates WP database table `{$wpdb->prefix}registration_codes`.  
- **add_admin_menu()**: registers "Registration Codes" and "Teacher Dashboard" pages.  
- **generate_codes($count, $group_name)**: bulk-generate unique codes.  
- **export_codes() / import_codes()**: AJAX methods to export/import CSV.  
- **get_codes() / get_groups()**: query methods with filters/pagination.  
- **AJAX handlers**: `ajax_generate_codes`, `ajax_export_registration_codes`, `ajax_import_registration_codes`.

### 3.2 Frontend Helpers (in functions.php)
- **ccr_validate_code($code)**: checks DB table for code validity and status.  
- **ccr_mark_code_used($code, $user_id)**: marks code as used when registration completes.  
- **ccr_render_shortcode()**: main handler for `[code_registration]`.  
- **ccr_show_code_form() / ccr_show_registration_form()**: render forms & conditional Grade 10 fields.

### 3.3 Admin Assets
- **admin.js**: uses jQuery for form handling, AJAX calls (`ccrAdmin.ajaxurl`), dynamic UI tabs, copy/download CSV.  
- **admin.css**: responsive and RTL‑compatible styles for admin panels.

---

## 4. Dependencies

- **WordPress Core**: actions, filters, AJAX (`admin-ajax.php`), DB access via `$wpdb`.  
- **jQuery**: enqueued in admin for AJAX and DOM manipulation.  
- **WordPress Localization**: `wp_localize_script` for passing `ajaxurl`, `nonce`, and messages.  
- **DBDelta**: `dbDelta()` in `create_table()`.  
- **Dashicons**: icon for the admin menu page ('dashicons-tickets').

---

## 5. Future Milestones

| # | Task | Status | Notes |
|---|------|--------|-------|
| 1 | Hook form submission to create WP user & mark code used | 🔲 | Integrate `ccr_mark_code_used()` after successful registration |
| 2 | Email/SMS notifications | 🔲 | Send code & registration confirmation to users |
| 3 | Bulk import of students via Excel | 🔲 | Pre-generate codes and user accounts from XLSX |
| 4 | Teacher reports & analytics | 🔲 | Show registration stats per class/period |
| 5 | Integration with third‑party shipping & authentication | 🔲 | SMS gateway, CRM, delivery system APIs |

---

## 6. Testing & Customization

- **Unit Tests**: add PHPUnit tests for core class methods.  
- **JS Tests**: QUnit or Jest for `admin.js` functions.  
- **CSS Variables**: convert styling to use CSS custom properties for theme alignment.  
- **Hooks & Filters**: expose filters for customizing group query, code format, batch sizes.  

---

## 7. How to Extend

1. Copy `includes/admin` folder into a plugin for portability.  
2. Use `add_filter('ccr_generate_code_prefix', fn($prefix) => 'XYZ');` to customize code prefix.  
3. Hook into `ccr_after_code_generated` action to log or notify external systems.  
4. Override view templates by placing files in your active theme's folder as needed.

---

## 8. טפסי הרשמה וכניסה לפי סוג משתמש

<table>
<thead>
<tr>
<th>סוג משתמש</th>
<th>טופס הרשמה</th>
<th>טופס התחברות</th>
</tr>
</thead>
<tbody>
<tr>
<td>תלמיד עצמאי</td>
<td>[student_independent_registration]</td>
<td>[ultimatemember form_id="1125"]</td>
</tr>
<tr>
<td>תלמיד חינוך תעבורתי</td>
<td>[verify_student_code]<br>[ultimatemember form_id="1128"]</td>
<td>[ultimatemember form_id="1125"]</td>
</tr>
<tr>
<td>מורה / רכז בית ספר</td>
<td>—</td>
<td>—</td>
</tr>
</tbody>
</table>

---

*עודכן לאחרונה: 22 באפריל 2025*
