# Shared Header Component

## Overview

Consolidate 16 duplicate navbar implementations across all PHP pages into a single reusable header component, ensuring consistent styling (#003366 color scheme), responsive behavior, and mobile menu functionality while supporting page-specific parameters.

## User Stories

### Story 1: Consistent Header Appearance (Priority: P1) 🎯 MVP

As a user, I want all pages to have consistent header styling so that I have a unified experience across the application.

**Independent Test**: Navigate to any 3 random pages and verify header color is #003366 with identical structure.

**Acceptance Criteria**:
- [x] WHEN user visits any page THE SYSTEM SHALL display the header with consistent #003366 primary color
- [x] WHEN user visits any page THE SYSTEM SHALL render the header with identical structure and sizing
- [x] WHILE user navigates between pages THE SYSTEM SHALL maintain visual consistency in header appearance

### Story 2: Mobile Responsive Header (Priority: P1) 🎯 MVP

As a mobile user, I want the header to adapt to my screen size so that I can navigate the application on any device.

**Independent Test**: Resize browser to 767px width, verify hamburger menu appears and functions correctly.

**Acceptance Criteria**:
- [x] WHEN user resizes browser below 768px THE SYSTEM SHALL display the mobile hamburger menu icon
- [x] WHEN user clicks the mobile menu icon THE SYSTEM SHALL open the dropdown navigation
- [x] WHEN user clicks a menu item THE SYSTEM SHALL navigate to the selected page
- [x] WHEN user clicks outside the dropdown THE SYSTEM SHALL close the mobile menu

### Story 3: Configurable Page Title (Priority: P1) 🎯 MVP

As a developer, I want to configure the page title in the header so that each page displays contextually appropriate information.

**Independent Test**: Include header component with custom title, verify title displays correctly.

**Acceptance Criteria**:
- [x] WHEN developer sets `title` parameter THE SYSTEM SHALL display that title in the header
- [x] WHEN developer sets `title_short` parameter THE SYSTEM SHALL display short title on mobile screens
- [x] IF title parameter is not provided THEN THE SYSTEM SHALL display a default application title

### Story 4: Configurable Action Buttons (Priority: P2)

As a developer, I want to configure action buttons in the header so that each page can have relevant quick actions.

**Independent Test**: Include header with custom actions array, verify buttons appear with correct icons and links.

**Acceptance Criteria**:
- [x] WHEN developer provides `actions` array THE SYSTEM SHALL render action buttons with specified icons and links
- [x] WHEN developer omits `actions` parameter THE SYSTEM SHALL display no action buttons
- [x] WHEN user clicks an action button THE SYSTEM SHALL navigate to the specified link

### Story 5: Configurable Search Form (Priority: P2)

As a developer, I want to show/hide the search form in the header so that pages can optionally include date filtering.

**Independent Test**: Include header with `show_search: true` and search_params, verify form renders with correct values.

**Acceptance Criteria**:
- [x] WHEN developer sets `show_search` to true THE SYSTEM SHALL display the month/year search form
- [x] WHEN developer sets `show_search` to false THE SYSTEM SHALL hide the search form
- [x] WHEN developer provides `search_params` THE SYSTEM SHALL populate form with those values

### Story 6: Reusable Component Integration (Priority: P1) 🎯 MVP

As a developer, I want a single header component file so that I can maintain header styling in one place.

**Independent Test**: Modify header.php CSS, verify change reflects across all 16 pages.

**Acceptance Criteria**:
- [x] WHEN header component is included THE SYSTEM SHALL render complete header structure
- [x] WHEN header component is used THE SYSTEM SHALL produce no JavaScript console errors
- [x] WHEN header CSS is modified THE SYSTEM SHALL reflect changes on all pages using the component

## Non-Functional Requirements

### Performance
- [x] WHEN CSS file is loaded THE SYSTEM SHALL cache it in browser for subsequent page loads
- [x] THE SYSTEM SHALL add no more than 2 additional HTTP requests (CSS + JS files)

### Security
- [x] WHEN dynamic values are rendered THE SYSTEM SHALL escape them with `htmlspecialchars()` to prevent XSS

### Maintainability
- [x] THE SYSTEM SHALL provide a single source of truth for header styling in `assets/css/header.css`
- [x] THE SYSTEM SHALL provide a single source of truth for header structure in `components/header.php`

### Backwards Compatibility
- [x] WHEN component replaces inline header THE SYSTEM SHALL preserve existing JavaScript functionality
- [x] WHEN component is used THE SYSTEM SHALL maintain exact DOM structure and IDs for JavaScript compatibility

## Assumptions (Auto-inferred)

| Decision | Chosen | Reasoning | Alternatives |
|----------|--------|-----------|--------------|
| Component pattern | PHP include | Simple, no framework needed, matches existing codebase | Template engine, HEREDOC |
| CSS isolation | `.header-component` wrapper | Prevents conflicts with existing page styles | CSS modules, BEM naming |
| Default logo link | `/khsanxuat/index.php` | Most pages link to main dashboard | Configurable only |
| Mobile breakpoint | 768px | Standard Bootstrap/mobile breakpoint | 640px, 992px |
| JS extraction | External file | Enables caching, cleaner code | Inline in component |

## Out of Scope

- Complete redesign of header UI/UX
- New menu items or navigation structure changes
- Authentication/login state handling (use existing session)
- Footer component synchronization
- Sidebar/side navigation components

## Success Metrics

| Metric | Target |
|--------|--------|
| Lines of duplicate code eliminated | ~2100+ lines inline CSS |
| Pages using shared component | 16/16 (100%) |
| Mobile menu coverage | 16/16 pages (up from 1/16) |
| Color consistency | 100% #003366 across all headers |
| JavaScript errors after migration | 0 |

## Implementation Notes

### Completed: January 30, 2026

**Files Created:**
- `components/header.php` (284 lines) - Reusable header component
- `assets/css/header.css` (671 lines) - Shared styles with CSS variables
- `assets/js/header.js` (221 lines) - Mobile menu JavaScript

**Pages Migrated (16 total):**
- index.php, indexdept.php, indexdept1.php
- import.php, dept_statistics.php, dept_statistics_month.php
- file_templates.php, theodoi.php, danhgia_hethong.php
- factory_templates.php, image_handler.php, image_handler_1.php
- image_handler_2.php, incomplete_criteria.php
- required_images_criteria.php, manage_required_images.php

**Key Achievements:**
- Consistent #003366 color across all 16 pages
- Mobile menu on all pages (up from 1/16)
- ~2100 lines of duplicate inline CSS eliminated
- XSS protection via htmlspecialchars() wrapper function
- ARIA accessibility labels implemented

**Spec Additions During Implementation:**
- Added `search_value` to search_params for preserving user search input
- Added `search_type` to search_params for dropdown selection state

**Phase 7 Cleanup (Optional):**
- Remove legacy inline CSS in index.php (marked with comments)
- Delete test file: components/test_header.php
