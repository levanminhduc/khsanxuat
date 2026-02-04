# Design: Header Migration and CSS Cleanup

## Technical Approach

### 1. PHP Page Migration
For `help_deadline.php` and `settings.php`, the existing header HTML block (typically containing `<nav>` or `<header>` tags and hardcoded links) will be removed.

It will be replaced with the standard component inclusion pattern:

```php
<?php
// ... existing session/logic ...

$pageConfig = [
    'title' => 'Page Title', // Specific to the page
    'current_page' => 'page_key', // e.g., 'help' or 'settings'
    // Any other required config
];

include 'components/header.php';
?>
```

We must ensure the `$pageConfig` correctly highlights the active page in the navigation menu.

### 2. CSS Cleanup
Once the PHP pages are migrated, the legacy CSS handling the old navbar will be dead code.

**Target Files:**
- `style.css`
- `styleindex.css`

**Action:**
- Identify blocks of code targeting `.navbar`, `.nav-links`, or specific IDs used solely by the old header.
- Remove these blocks to reduce file size and specificity conflicts.
- Ensure generic styles (like body font) are preserved if they were intertwined with header styles.

### 3. Verification
- **Functional:** Links in the header must work on migrated pages.
- **Visual:** The header on `help_deadline.php` and `settings.php` must match `index.php` and `dashboard.php` exactly.
- **Regression:** Ensure removing CSS from `style.css` doesn't break layout on pages that might have implicitly relied on those styles (though unlikely for navbar-specific classes).
