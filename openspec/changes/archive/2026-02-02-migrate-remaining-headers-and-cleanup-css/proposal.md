# Proposal: Migrate Remaining Headers and Cleanup CSS

## Problem Statement
Currently, the application has inconsistent header implementations. While most pages have been migrated to use the shared `components/header.php` and `assets/css/header.css`, a few specific pages (`help_deadline.php` and `settings.php`) still rely on legacy, hardcoded HTML headers. Additionally, the main stylesheets (`style.css` and `styleindex.css`) still contain redundant CSS rules for the legacy navbar, creating maintenance overhead and potential style conflicts.

## Proposed Solution
We will standardize the header implementation across the remaining pages by replacing the legacy HTML with the component-based approach. Following this migration, we will refactor the global stylesheets to remove the obsolete navbar styles, ensuring that `assets/css/header.css` is the single source of truth for header styling.

## Scope
### Files to Modify
- `help_deadline.php`
- `settings.php`
- `style.css` (or `css/style.css` depending on location)
- `styleindex.css` (or `css/styleindex.css`)

### Out of Scope
- Redesigning the header visuals (aim is parity/consistency).
- Modifying other components aside from the header/navbar.
