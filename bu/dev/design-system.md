# Design System

## Color Palette

### Primary Colors
- **Primary**: `#000000` (Black)
- **Secondary**: `#EAC6F2` (Light Purple)
- **Text**: `#333333` (Dark Gray)
- **Highlight**: `#C8FFBE` (Light Green)

### Utility Colors
- **White**: `#FFFFFF`
- **Light Blue**: `#FDF6FF`
- **Yellow Accent**: `#FFFB83`
- **Gray Background**: `#F5F5F5`
- **Blue Primary**: `#2C3391`
- **Blue Muted**: `#DDE1FF`

## Typography

### Font Family
- Primary: System UI, sans-serif
- Secondary: Arial, sans-serif

### Text Colors
- Primary Text: `#333333`
- Secondary Text: `#666666`
- Light Text: `#FFFFFF` (on dark backgrounds)

## Form Elements

### Input Fields
```css
.form-control {
    color: #333;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 0.75rem 1rem;
    width: 100%;
}
```

### Select Dropdowns
```css
select,
.form-select {
    color: #333;
    background-color: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 0.75rem 2.5rem 0.75rem 1rem;
    width: 100%;
    -webkit-appearance: none;
    -moz-appearance: none;
    appearance: none;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='%23333' stroke='%23333' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 16px 12px;
}
```

### Buttons
```css
.btn {
    background-color: #000000;
    color: #ffffff;
    border: none;
    border-radius: 4px;
    padding: 0.5rem 1rem;
    cursor: pointer;
    transition: background-color 0.2s;
}

.btn:hover {
    background-color: #333333;
}
```

## Dark Mode Support

### Form Elements in Dark Mode
```css
@media (prefers-color-scheme: dark) {
    .form-control {
        color: #f0f0f0;
        background-color: #2a2a2a;
        border-color: #444;
    }
    
    /* Keep select dropdowns light in dark mode */
    select,
    .form-select {
        color: #333;
        background-color: #fff;
        border-color: #ddd;
    }
}
```

## Spacing System
- **Base Unit**: 4px
- **Small**: 8px (0.5rem)
- **Medium**: 16px (1rem)
- **Large**: 24px (1.5rem)
- **X-Large**: 32px (2rem)
- **XX-Large**: 48px (3rem)

## Border Radius
- **Small**: 2px
- **Medium**: 4px
- **Large**: 8px
- **Full**: 9999px (for pills and circles)

## Shadows
- **Small**: `0 1px 2px 0 rgba(0, 0, 0, 0.05)`
- **Medium**: `0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06)`
- **Large**: `0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05)`

## Z-Index Scale
- **Base**: 0
- **Dropdown**: 1000
- **Sticky**: 1020
- **Fixed**: 1030
- **Modal Backdrop**: 1040
- **Modal**: 1050
- **Popover**: 1060
- **Tooltip**: 1070

## Breakpoints
- **sm**: 640px
- **md**: 768px
- **lg**: 1024px
- **xl**: 1280px
- **2xl**: 1536px

## Transitions
- **Fast**: 150ms
- **Normal**: 200ms
- **Slow**: 300ms

## Usage Notes
- Always use the defined color variables for consistency
- Maintain proper contrast ratios for accessibility
- Test forms in both light and dark modes
- Ensure interactive elements have proper hover and focus states
