# TMVARI Design System Analysis

## Color Palette 🎨

### Primary Colors
```css
--tmvari-shell-bg: #dfe7f5          /* Light blue-gray background */
--tmvari-shell-ink: #162742          /* Dark navy text */
--tmvari-shell-muted: #657892        /* Muted gray-blue */
--tmvari-shell-border: rgba(20, 39, 67, 0.11)  /* Subtle border */
--tmvari-shell-blue: #2f62af         /* Primary blue */
--tmvari-shell-blue-deep: #1e3f78    /* Deep blue */
--tmvari-shell-navy: #183154         /* Navy */
```

### Sidebar Colors
```css
--tmvari-sidebar-navy: #1a2f53       /* Sidebar background */
--tmvari-sidebar-navy-2: #223b65     /* Sidebar hover */
```

### Surface Colors
```css
--tmvari-card: rgba(255, 255, 255, 0.96)  /* Card background */
```

### Shadows & Radius
```css
--tmvari-shadow: 0 18px 42px rgba(14, 34, 63, 0.16)
--tmvari-radius-lg: 22px
--tmvari-radius-md: 16px
```

## Background Gradients

### Body Background
```css
background:
  radial-gradient(circle at top right, rgba(67, 116, 199, 0.3), transparent 24%),
  linear-gradient(180deg, #edf2fb 0%, #dae4f4 100%);
```

### Topbar Gradient
```css
background: linear-gradient(90deg, #183154 0%, #245ea8 54%, #2f76d4 100%);
box-shadow: 0 12px 28px rgba(15, 37, 74, 0.28);
```

### Sidebar Gradient
```css
background:
  radial-gradient(circle at top right, rgba(68, 130, 218, 0.26), transparent 28%),
  linear-gradient(180deg, #142947 0%, #1c365e 52%, #1a2f53 100%);
```

## Typography

### Brand
- **Strong**: 1.18rem, font-weight: 900, letter-spacing: 0.06em
- **Small**: 0.72rem, font-weight: 700, letter-spacing: 0.02em

### Headers
- **H1**: 1.95rem, line-height: 1.1
- **H2**: 1.5rem, line-height: 1.15
- **H3**: 1rem, font-weight: 800

### Body
- **Standard**: 0.96rem
- **Small**: 0.78rem - 0.88rem
- **Muted**: color: var(--tmvari-shell-muted)

### Labels
- **Uppercase**: letter-spacing: 0.12em, font-weight: 800
- **Eyebrow**: 0.78rem, letter-spacing: 0.12em

## Border Radius System

### Sizes
- **Large**: 22-24px (panels, cards)
- **Medium**: 16-18px (sections)
- **Standard**: 12-14px (inputs, buttons)
- **Small**: 10px (small buttons)
- **Pill**: 999px (badges, chips)

## Shadow System

### Levels
1. **Subtle**: `0 10px 20px rgba(9, 24, 46, 0.18)`
2. **Light**: `0 10px 24px rgba(17, 35, 66, 0.16)`
3. **Medium**: `0 12px 28px rgba(15, 37, 74, 0.28)`
4. **Strong**: `0 18px 42px rgba(14, 34, 63, 0.16)`
5. **Heavy**: `0 28px 60px rgba(17, 39, 72, 0.22)`

## Component Styles

### Buttons
```css
min-height: 42-48px
border-radius: 10-12px
font-weight: 700-800
padding: varies
```

### Cards
```css
border: 1px solid var(--tmvari-shell-border)
border-radius: 22px (large) or 16-18px (medium)
background: rgba(255, 255, 255, 0.96)
box-shadow: 0 18px 42px rgba(14, 34, 63, 0.16)
padding: 18-24px
```

### Inputs
```css
min-height: 42-50px
border-radius: 10-14px
background: #f8fbff or rgba(255, 255, 255, 0.98)
font-weight: 600
```

### Badges/Chips
```css
border-radius: 999px
padding: 4px 10px
font-size: 0.77rem
font-weight: 800
letter-spacing: 0.05em
```

### Tables
```css
thead: linear-gradient(180deg, #f0f4fa 0%, #e6ecf6 100%)
tbody tr: cursor: pointer
hover: rgba(255, 207, 63, 0.18)
border-color: rgba(20, 39, 67, 0.08)
```

## Layout Patterns

### Topbar
- Height: 70px
- Gradient background with logo, search, and user menu
- Sticky header with shadow

### Sidebar
- Width: 246px
- Dark gradient background
- Active item: inset 3px 0 0 #ffd07e
- Hover: rgba(255, 255, 255, 0.12)

### Content Area
- Background: transparent
- Cards with glassmorphism effect
- Generous padding: 16-24px

### Property Popup
- Position: absolute, top-right
- Width: min(760px, calc(100% - 36px))
- Border-radius: 24px
- Background: rgba(255, 255, 255, 0.97)
- Backdrop-filter: blur(18px)

## Special Effects

### Glassmorphism
```css
background: rgba(255, 255, 255, 0.97)
backdrop-filter: blur(18px)
border: 1px solid rgba(18, 36, 63, 0.14)
```

### Gradient Overlays
```css
background:
  radial-gradient(circle at top right, rgba(62, 121, 217, 0.16), transparent 40%),
  linear-gradient(180deg, rgba(247, 250, 255, 0.98) 0%, rgba(239, 245, 253, 0.96) 100%);
```

### Status Colors
- **Land**: rgba(39, 87, 173, 0.12) / #234786
- **Building**: rgba(65, 171, 255, 0.14) / #1a6798
- **Machinery**: rgba(79, 195, 161, 0.18) / #1b735e
- **Cancelled**: #d45f1f

## Spacing System

### Padding
- **Compact**: 10-12px
- **Standard**: 14-18px
- **Comfortable**: 20-24px
- **Spacious**: 28-32px

### Gaps
- **Tight**: 8-10px
- **Standard**: 12-14px
- **Comfortable**: 16-18px
- **Wide**: 20-24px

## Responsive Breakpoints

### Desktop (>1320px)
- Full layout with sidebar
- Two-column workspace

### Tablet (1024-1320px)
- Single column workspace
- Sidebar collapsible

### Mobile (<1024px)
- Mobile menu
- Stacked layout
- Full-width components

## Key Design Principles

1. **Professional Navy/Blue Theme**: Deep blues and navies for authority
2. **Glassmorphism**: Frosted glass effects with blur
3. **Generous Spacing**: Breathing room between elements
4. **Subtle Gradients**: Depth without being overwhelming
5. **Strong Typography**: Bold weights for hierarchy
6. **Rounded Corners**: Modern, friendly appearance
7. **Layered Shadows**: Depth and elevation
8. **Muted Colors**: Professional, not flashy
9. **High Contrast**: Readable text on all backgrounds
10. **Consistent Patterns**: Repeatable design language

## Notable Features

### Brand Logo
- White background with border
- Rounded corners (14px)
- Shadow for elevation
- 42x42px size

### Search Bar
- Prominent in topbar
- White background with shadow
- Dropdown with suggestions
- Municipality selector

### Navigation
- Vertical sidebar
- Icons + labels
- Active state with gold accent
- Hover effects

### Cards
- Glassmorphism effect
- Large border radius (22px)
- Subtle shadows
- Gradient headers

### Forms
- Light blue backgrounds (#f8fbff)
- Uppercase labels
- Generous padding
- Clear hierarchy

### Tables
- Gradient headers
- Hover highlights
- Clean borders
- Responsive design

## Color Usage Guidelines

### Text
- **Primary**: #162742 (dark navy)
- **Secondary**: #657892 (muted blue-gray)
- **Links**: #2f62af (primary blue)
- **On Dark**: #ffffff (white)

### Backgrounds
- **Page**: #dfe7f5 with gradients
- **Cards**: rgba(255, 255, 255, 0.96)
- **Inputs**: #f8fbff
- **Hover**: rgba(255, 255, 255, 0.12)

### Borders
- **Subtle**: rgba(20, 39, 67, 0.08-0.11)
- **Medium**: rgba(20, 39, 67, 0.14)
- **Strong**: rgba(20, 39, 67, 0.18)

### Accents
- **Primary**: #2f62af (blue)
- **Success**: #4fc3a1 (teal)
- **Warning**: #ffd07e (gold)
- **Danger**: #d45f1f (orange)

## Implementation Notes

1. Use CSS custom properties for theming
2. Implement glassmorphism with backdrop-filter
3. Layer gradients for depth
4. Use generous border-radius for modern look
5. Apply shadows consistently for elevation
6. Maintain high contrast for accessibility
7. Use font-weight 700-900 for emphasis
8. Implement responsive breakpoints
9. Add hover states to interactive elements
10. Use transitions for smooth interactions
