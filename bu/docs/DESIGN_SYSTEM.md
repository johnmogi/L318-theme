# Design System Documentation

## Table of Contents
1. [Introduction](#introduction)
2. [Design Tokens](#design-tokens)
   - [Colors](#colors)
   - [Spacing](#spacing)
   - [Typography](#typography)
   - [Shadows](#shadows)
   - [Borders](#borders)
3. [Login Forms](#login-forms)
   - [File Structure](#login-file-structure)
   - [Customization Guide](#login-customization)
   - [Troubleshooting](#login-troubleshooting)
4. [Best Practices](#best-practices)
5. [Maintenance](#maintenance)

## Introduction
This design system uses CSS custom properties (variables) to maintain consistency across the application. All design decisions are centralized in the `_design-tokens.css` file, making global changes simple and consistent.

## Design Tokens

### Colors

| Variable | Default Value | Usage |
|----------|---------------|-------|
| `--color-white` | `#ffffff` | Backgrounds, cards, containers |
| `--color-gray-900` | `#2c3e50` | Headings, important text |
| `--color-text-muted` | `#7f8c8d` | Secondary text, descriptions |
| `--color-primary` | `#3498db` | Primary actions, links |
| `--color-primary-dark` | `#2980b9` | Hover/focus states |
| `--color-error` | `#e74c3c` | Error messages, destructive actions |

### Spacing

| Variable | Value | Usage |
|----------|-------|-------|
| `--spacing-1` | `4px` | Small margins, paddings |
| `--spacing-2` | `8px` | Default margins, paddings |
| `--spacing-3` | `16px` | Section spacing, larger margins |
| `--spacing-4` | `24px` | Container padding |
| `--spacing-5` | `32px` | Large spacing |

### Typography

| Variable | Value | Usage |
|----------|-------|-------|
| `--font-size-base` | `16px` | Base font size |
| `--line-height-base` | `1.5` | Base line height |
| `--font-weight-normal` | `400` | Regular text |
| `--font-weight-bold` | `700` | Headings, important text |

### Shadows

| Variable | Value | Usage |
|----------|-------|-------|
| `--shadow-sm` | `0 2px 10px rgba(0, 0, 0, 0.1)` | Cards, containers |
| `--shadow-md` | `0 4px 20px rgba(0, 0, 0, 0.15)` | Modals, dropdowns |
| `--shadow-lg` | `0 8px 30px rgba(0, 0, 0, 0.2)` | Popovers, dialogs |

### Borders

| Variable | Value | Usage |
|----------|-------|-------|
| `--border-radius-sm` | `4px` | Small buttons, inputs |
| `--border-radius-md` | `8px` | Cards, containers |
| `--border-radius-lg` | `16px` | Large containers |
| `--border-color` | `#e0e0e0` | Default border color |

## Login Forms

### File Structure

```
src/
  Login/
    templates/
      student-login-form.php
      teacher-login-form.php
      teacher-lostpassword-form.php
```

### Login Form Components

#### Container
```css
.lilac-login-container {
    max-width: 500px;
    margin: var(--spacing-4) auto;
    padding: var(--spacing-4);
    background-color: var(--color-white);
    border-radius: var(--border-radius-md);
    box-shadow: var(--shadow-sm);
}
```

#### Headings
```css
.lilac-login-container h2 {
    margin-top: 0;
    margin-bottom: var(--spacing-3);
    color: var(--color-gray-900);
    text-align: center;
}
```

#### Descriptions
```css
.lilac-login-container .description {
    margin-bottom: var(--spacing-4);
    color: var(--color-text-muted);
    text-align: center;
}
```

### Customization Guide

#### Changing Colors
1. Open `_design-tokens.css`
2. Locate the `:root` selector
3. Update the desired color variables:
   ```css
   :root {
       --color-primary: #3498db; /* Change primary color */
       --color-text-muted: #7f8c8d; /* Change muted text color */
   }
   ```

#### Adjusting Spacing
1. In `_design-tokens.css`:
   ```css
   :root {
       --spacing-3: 16px; /* Default is 16px */
       --spacing-4: 24px; /* Default is 24px */
   }
   ```

#### Modifying Shadows
```css
:root {
    --shadow-sm: 0 2px 10px rgba(0, 0, 0, 0.1);
    --shadow-md: 0 4px 20px rgba(0, 0, 0, 0.15);
}
```

### Troubleshooting

#### Issue: Styles not applying
1. Verify the design tokens file is properly enqueued in `functions.php`
2. Check for CSS specificity issues using browser dev tools
3. Ensure there are no syntax errors in your CSS

#### Issue: Colors not matching
1. Make sure you're using the correct CSS variable names
2. Check for any inline styles that might be overriding your variables
3. Verify the variable is defined in the `:root` selector

## Best Practices

1. **Use Design Tokens**
   - Always use design tokens instead of hardcoded values
   - This ensures consistency and makes global changes easier

2. **Naming Conventions**
   - Use semantic names for variables (e.g., `--color-primary` not `--color-blue`)
   - Follow the `--category-specific-name` pattern

3. **Responsive Design**
   - Use relative units (rem, em) for typography
   - Use percentage or viewport units for layouts

## Maintenance

### Adding New Variables
1. Add new variables to `_design-tokens.css`
2. Document the new variables in this file
3. Update any affected components

### Versioning
- Document any breaking changes in the changelog
- Consider using CSS custom properties for theme variants

### Testing
- Test all form states (hover, focus, disabled)
- Verify in multiple browsers
- Check RTL support for Hebrew text

---
*Last Updated: May 13, 2025*
