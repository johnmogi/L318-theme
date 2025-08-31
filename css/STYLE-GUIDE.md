# LILAC Learning Platform - Design System

## Overview
This document outlines the design system implementation for the LILAC Learning Platform. The system is built with CSS custom properties (variables) and follows a component-based architecture for maintainability and consistency.

## File Structure
```
css/
├── _design-tokens.css    # Design tokens (colors, spacing, typography)
├── _components.css      # Reusable UI components
├── _variables.css       # Legacy variables (being phased out)
├── base.css            # Base styles and resets
└── STYLE-GUIDE.md      # This file
```

## Design Tokens

### Colors
```css
:root {
  /* Primary Colors */
  --color-primary: #4a6cf7;
  --color-primary-dark: #3a5bd9;
  --color-primary-light: #eef1fe;
  
  /* Secondary Colors */
  --color-secondary: #6c757d;
  --color-secondary-dark: #5a6268;
  
  /* Status Colors */
  --color-success: #28a745;
  --color-danger: #dc3545;
  --color-warning: #ffc107;
  --color-info: #17a2b8;
  
  /* Neutral Colors */
  --color-white: #ffffff;
  --color-light: #f8f9fa;
  --color-gray-100: #f8f9fa;
  --color-gray-900: #212529;
  --color-text: #333333;
  --color-text-muted: #6c757d;
}
```

### Spacing
```css
:root {
  --spacing-1: 0.25rem;    /* 4px */
  --spacing-2: 0.5rem;     /* 8px */
  --spacing-3: 1rem;       /* 16px */
  --spacing-4: 1.5rem;     /* 24px */
  --spacing-5: 2rem;       /* 32px */
  --spacing-6: 3rem;       /* 48px */
}
```

### Typography
```css
:root {
  --font-family-base: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
  --font-size-base: 1rem;     /* 16px */
  --line-height-base: 1.5;
  --font-weight-normal: 400;
  --font-weight-medium: 500;
  --font-weight-bold: 700;
}
```

## Components

### Buttons
```html
<!-- Primary Button -->
<button class="button button-primary">Primary Button</button>

<!-- Secondary Button -->
<button class="button button-secondary">Secondary Button</button>


<!-- Button Sizes -->
<button class="button button-sm">Small</button>
<button class="button">Default</button>
<button class="button button-lg">Large</button>
```

### Forms
```html
<div class="form-group">
  <label class="form-label" for="example-input">Label</label>
  <input type="text" class="form-control" id="example-input" placeholder="Placeholder">
  <small class="form-text">Helper text</small>
</div>

<!-- Form Validation -->
<div class="form-group">
  <input type="text" class="form-control is-invalid">
  <div class="invalid-feedback">Error message</div>
</div>
```

### Cards
```html
<div class="card">
  <div class="card-body">
    <h3 class="card-title">Card Title</h3>
    <p class="card-text">Card content goes here.</p>
  </div>
</div>
```

### Alerts
```html
<div class="alert alert-success">Success message</div>
<div class="alert alert-danger">Error message</div>
<div class="alert alert-warning">Warning message</div>
<div class="alert alert-info">Info message</div>
```

## Utility Classes

### Text Colors
```html
<p class="text-primary">Primary text</p>
<p class="text-secondary">Secondary text</p>
<p class="text-success">Success text</p>
<p class="text-danger">Danger text</p>
<p class="text-warning">Warning text</p>
<p class="text-info">Info text</p>
<p class="text-muted">Muted text</p>
```

### Background Colors
```html
<div class="bg-primary">Primary background</div>
<div class="bg-secondary">Secondary background</div>
<div class="bg-success">Success background</div>
<div class="bg-danger">Danger background</div>
<div class="bg-warning">Warning background</div>
<div class="bg-info">Info background</div>
<div class="bg-light">Light background</div>
<div class="bg-dark">Dark background</div>
```

### Spacing Utilities
```html
<!-- Margin Top -->
<div class="mt-1">Margin Top 1</div>
<div class="mt-2">Margin Top 2</div>

<!-- Margin Bottom -->
<div class="mb-1">Margin Bottom 1</div>
<div class="mb-2">Margin Bottom 2</div>
```

## Implementation Notes

### Adding New Components
1. Add new component styles to `_components.css`
2. Use existing design tokens for colors, spacing, and typography
3. Follow the BEM (Block Element Modifier) naming convention
4. Add documentation to this style guide

### Overriding Styles
When customizing components:
1. First check if you can modify the design token
2. If not, use the existing utility classes
3. As a last resort, add custom styles with appropriate specificity

### Browser Support
- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)
- Mobile Safari (iOS 12+)
- Chrome for Android

## Best Practices
1. Always use design tokens instead of hardcoded values
2. Keep component styles modular and reusable
3. Test across different screen sizes and devices
4. Document any new patterns or components
5. Follow the principle of least specificity

## Version History
- **1.0.0** (2025-05-13): Initial design system implementation
