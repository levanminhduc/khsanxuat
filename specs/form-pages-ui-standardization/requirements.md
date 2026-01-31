# Form Pages UI Standardization

## Overview

Standardize UI/UX for form-based pages across the application, migrating from inconsistent inline styles to a component-based architecture with responsive design. The initial migration target is `edit_date_clone.php`, establishing patterns for all form pages.

## User Stories

### Story 1: Responsive Form Layout (Priority: P1) MVP

As an Admin, I want form pages to display correctly on all screen sizes so that I can manage production data from any device.

**Independent Test**: Open `edit_date_clone.php` on mobile (< 768px) and verify form is usable without horizontal scrolling.

**Acceptance Criteria**:
- [x] WHEN user views form page on desktop (> 768px) THE SYSTEM SHALL display full-width card-based form layout
- [x] WHEN user views form page on mobile (< 768px) THE SYSTEM SHALL stack form elements vertically with full-width inputs
- [x] WHEN user resizes browser window THE SYSTEM SHALL smoothly transition layout without breaking
- [x] THE SYSTEM SHALL render all form inputs with minimum touch target size of 44x44px on mobile

### Story 2: Readable Information Tables (Priority: P1) MVP

As an Admin, I want data tables to transform into card views on mobile so that I can read record information without horizontal scrolling.

**Independent Test**: View info table in `edit_date_clone.php` at 320px width, verify data displays in stacked card format.

**Acceptance Criteria**:
- [x] WHEN user views info table on desktop THE SYSTEM SHALL display traditional table layout with header/data columns
- [x] WHEN user views info table on mobile (< 768px) THE SYSTEM SHALL transform table rows into stacked card view
- [x] WHILE in card view THE SYSTEM SHALL display field labels above corresponding values
- [x] THE SYSTEM SHALL maintain data alignment and readability in both layouts

### Story 3: Consistent Visual Theme (Priority: P1) MVP

As a user, I want form pages to match the application's Cyan/Slate design theme so that I have a unified visual experience.

**Independent Test**: Compare form page colors against `header.php` theme, verify Cyan (#0891b2) and Slate (#334155) match.

**Acceptance Criteria**:
- [x] THE SYSTEM SHALL apply Cyan primary color (#0891b2) to buttons, links, and focus states
- [x] THE SYSTEM SHALL apply Slate color (#334155) for text and secondary elements
- [x] THE SYSTEM SHALL use Fira Sans font family for all form text
- [x] WHEN user interacts with form elements THE SYSTEM SHALL provide visual feedback using theme colors

### Story 4: Component-Based CSS Implementation (Priority: P2)

As a developer, I want a reusable CSS file for form pages so that I can maintain consistent styling from one location.

**Independent Test**: Modify `assets/css/form-page.css` variable, verify change reflects on all form pages.

**Acceptance Criteria**:
- [x] THE SYSTEM SHALL provide `assets/css/form-page.css` with all form page styles
- [x] THE SYSTEM SHALL use CSS variables with `--form-page-*` prefix for customization
- [x] THE SYSTEM SHALL isolate styles using `.form-page-component` wrapper class
- [x] WHEN CSS file is loaded THE SYSTEM SHALL cache it for subsequent page loads

### Story 5: PHP Helper Functions (Priority: P2)

As a developer, I want PHP helper functions to render form elements so that I can build form pages quickly and consistently.

**Independent Test**: Call `render_form_input(['name' => 'test', 'type' => 'text'])`, verify it outputs correctly structured HTML.

**Acceptance Criteria**:
- [x] THE SYSTEM SHALL provide `render_form_page_start($config)` to open form container with title
- [x] THE SYSTEM SHALL provide `render_form_page_end()` to close form container
- [x] THE SYSTEM SHALL provide `render_form_input($config)` to generate form input elements
- [x] THE SYSTEM SHALL provide `render_modal($config)` to generate modal dialogs
- [x] WHEN dynamic values are rendered THE SYSTEM SHALL escape them to prevent XSS attacks

### Story 6: edit_date_clone.php Migration (Priority: P1) MVP

As a developer, I want `edit_date_clone.php` migrated to use the form page component so that it serves as the reference implementation.

**Independent Test**: Open `edit_date_clone.php`, verify it uses form-page component and all TODO markers are resolved.

**Acceptance Criteria**:
- [x] WHEN edit_date_clone.php loads THE SYSTEM SHALL include form-page component via PHP include
- [x] WHEN edit_date_clone.php loads THE SYSTEM SHALL link assets/css/form-page.css stylesheet
- [x] THE SYSTEM SHALL replace inline styles with CSS classes from form-page.css
- [x] THE SYSTEM SHALL replace static info table with responsive card/table component
- [x] THE SYSTEM SHALL replace custom modal HTML with render_modal() component
- [x] WHEN migration is complete THE SYSTEM SHALL have resolved all TODO comments (form-pages-ui-19 through 24)

## Non-Functional Requirements

### Accessibility

- [x] THE SYSTEM SHALL provide ARIA labels for all form inputs and interactive elements
- [x] THE SYSTEM SHALL maintain keyboard navigation support for all form elements
- [x] THE SYSTEM SHALL ensure color contrast ratio of at least 4.5:1 for text
- [x] THE SYSTEM SHALL associate form labels with inputs using `for`/`id` attributes

### Performance

- [x] THE SYSTEM SHALL load form-page.css in under 50KB (minified)
- [x] THE SYSTEM SHALL add no more than 2 additional HTTP requests (CSS + JS)
- [x] WHEN form-page assets are loaded THE SYSTEM SHALL cache them for subsequent visits
- [x] THE SYSTEM SHALL avoid layout shifts during responsive breakpoint transitions

### Security

- [x] WHEN rendering user-provided data THE SYSTEM SHALL escape using `htmlspecialchars()`
- [x] THE SYSTEM SHALL validate all form inputs server-side before processing
- [x] THE SYSTEM SHALL use CSRF tokens for form submissions

### Maintainability

- [x] THE SYSTEM SHALL provide single source of truth for form styling in `assets/css/form-page.css`
- [x] THE SYSTEM SHALL provide single source of truth for form structure in `components/form-page.php`
- [x] THE SYSTEM SHALL document PHP helper functions with PHPDoc comments

## Assumptions (Auto-inferred)

| Decision | Chosen | Reasoning | Alternatives |
|----------|--------|-----------|--------------|
| Mobile breakpoint | 768px | Matches header.php pattern, standard breakpoint | 640px, 992px |
| Primary color | Cyan #0891b2 | User specified, Tailwind Cyan-600 | Blue #003366 |
| Secondary color | Slate #334155 | User specified, Tailwind Slate-700 | Gray, Navy |
| Font family | Fira Sans | User specified, modern sans-serif | Inter, Roboto |
| Component pattern | PHP include | Matches existing header.php pattern | HEREDOC, template engine |
| CSS isolation | `.form-page-component` wrapper | Prevents conflicts, matches header pattern | BEM, CSS modules |
| Card transformation | CSS-only | No JS needed for layout switch | JS toggle, separate views |

## Out of Scope

- Changes to form business logic or validation rules
- Database schema modifications
- New form fields or functionality
- Other form pages (only `edit_date_clone.php` in scope for initial migration)
- Backend API changes
- Integration with JavaScript frameworks

## Success Metrics

| Metric | Target |
|--------|--------|
| Mobile usability score | 100% usable at 320px width |
| TODO comments resolved | 6/6 (form-pages-ui-19 through 24) |
| Inline styles eliminated | 100% replaced with CSS classes |
| Color theme consistency | 100% Cyan/Slate palette |
| Accessibility violations | 0 critical/major |
| CSS file size | < 50KB |

## Implementation Notes

**Status**: Completed  
**Files**: `components/form-page.php`, `assets/css/form-page.css`, `edit_date_clone.php`  
**Deviations**: None  
**Limitations**: None
