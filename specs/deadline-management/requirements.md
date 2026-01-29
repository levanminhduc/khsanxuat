# Deadline Management System - Requirements

## Overview

The Deadline Management System calculates and tracks processing deadlines for production orders and their evaluation criteria. It supports four calculation methods, default settings hierarchy, and bulk updates.

---

## User Stories

### User Story 1 - Configure Deadline Calculation Methods (Priority: P1) 🎯 MVP

As a production manager, I want to configure how deadlines are calculated so that I can align processing timelines with different order requirements.

**Independent Test**: Set calculation method to "ngay_vao" for an order, verify deadline = entry date - processing days.

**Inferred from**: `display_deadline.php:302-309`, `update_deadline_ajax.php:96-120`

**Acceptance Criteria**:

- [x] WHEN the calculation method is set to "ngay_vao", THE SYSTEM SHALL calculate deadline as entry date minus processing days.
- [x] WHEN the calculation method is set to "ngay_vao_cong", THE SYSTEM SHALL calculate deadline as entry date plus processing days.
- [x] WHEN the calculation method is set to "ngay_ra", THE SYSTEM SHALL calculate deadline as exit date (or entry date + 7 days if exit date missing).
- [x] WHEN the calculation method is set to "ngay_ra_tru", THE SYSTEM SHALL calculate deadline as exit date minus processing days (or entry date + 7 - processing days if exit date missing).
- [x] IF entry date is empty, THEN THE SYSTEM SHALL return null as the deadline.
- [x] IF calculation method is invalid, THEN THE SYSTEM SHALL default to "ngay_vao_cong" calculation.

---

### User Story 2 - Manage Default Deadline Settings (Priority: P1) 🎯 MVP

As a department administrator, I want to configure default deadline settings by department and criteria so that new orders automatically inherit appropriate deadlines.

**Independent Test**: Save default setting for "kehoach" department with 7 days and "ngay_vao_cong", create new order, verify criteria inherit these settings.

**Inferred from**: `save_default_settings.php:62-77`, `apply_default_settings.php:89-98`

**Acceptance Criteria**:

- [x] WHEN default settings are saved for a department and criteria, THE SYSTEM SHALL store them in the default_settings table with dept, id_tieuchi, so_ngay_xuly, and ngay_tinh_han.
- [x] WHEN a default setting already exists for dept + criteria, THE SYSTEM SHALL update the existing record instead of creating duplicate.
- [x] WHEN applying defaults to new order criteria, THE SYSTEM SHALL prioritize factory-specific settings (xuong) over department-wide settings.
- [x] WHEN no criteria-specific default exists, THE SYSTEM SHALL use department-wide default (xuong = '').
- [x] WHEN applying defaults, THE SYSTEM SHALL only update criteria that have null or empty han_xuly and so_ngay_xuly.

---

### User Story 3 - Update Deadlines for Individual Orders (Priority: P1) 🎯 MVP

As a production coordinator, I want to update deadlines for specific orders and criteria so that I can adjust timelines when requirements change.

**Independent Test**: Update order deadline via AJAX with new processing days, verify han_xuly, so_ngay_xuly, and ngay_tinh_han columns updated.

**Inferred from**: `update_deadline.php:99-114`, `update_deadline_ajax.php:128-143`

**Acceptance Criteria**:

- [x] WHEN updating an order deadline, THE SYSTEM SHALL recalculate han_xuly based on selected ngay_tinh_han and so_ngay_xuly.
- [x] WHEN updating order deadline, THE SYSTEM SHALL update both khsanxuat (order level) and danhgia_tieuchi (criteria level) tables.
- [x] WHEN updating criteria deadline only for criteria without custom deadlines, THE SYSTEM SHALL only update rows where han_xuly IS NULL OR han_xuly = '' OR so_ngay_xuly IS NULL.
- [x] WHEN AJAX deadline update is triggered, THE SYSTEM SHALL validate presence of ngay_vao before calculation.
- [x] IF ngay_tinh_han column does not exist in danhgia_tieuchi, THEN THE SYSTEM SHALL auto-create the column with default value 'ngay_vao'.

---

### User Story 4 - Batch Update Deadlines (Priority: P2)

As a production manager, I want to update deadlines in bulk for multiple orders so that I can efficiently apply changes after process improvements.

**Independent Test**: Select orders by factory and date range, apply batch update with new calculation method, verify all matching orders updated.

**Inferred from**: `batch_update_deadline.php:30-168`

**Acceptance Criteria**:

- [x] WHEN batch update is triggered, THE SYSTEM SHALL filter orders by optional xuong, start_date, and end_date parameters.
- [x] WHEN batch update runs, THE SYSTEM SHALL wrap all updates in a database transaction.
- [x] WHEN processing each order in batch, THE SYSTEM SHALL recalculate deadline using provided ngay_tinh_han and so_ngay_xuly.
- [x] WHEN batch update completes, THE SYSTEM SHALL log each order's update status to update_deadline_batch.log.
- [x] WHEN batch update succeeds, THE SYSTEM SHALL commit transaction and return count of updated orders.
- [x] IF any batch update fails, THEN THE SYSTEM SHALL rollback all changes and log the error.

---

### User Story 5 - Configure Criteria-Specific Deadlines (Priority: P2)

As a quality manager, I want to set different deadlines for different evaluation criteria within an order so that each department can have appropriate timelines.

**Independent Test**: Open deadline settings modal for an order, configure different processing days for each criteria, save, verify each criteria has custom deadline.

**Inferred from**: `settings_deadline.php:146-351`, `update_deadline_tieuchi.php:1-180`

**Acceptance Criteria**:

- [x] WHEN opening criteria deadline settings modal, THE SYSTEM SHALL load existing deadline configuration for all criteria in the department.
- [x] WHEN configuring criteria deadline, THE SYSTEM SHALL allow setting so_ngay_xuly (1-30 days) and ngay_tinh_han (calculation method) independently for each criteria.
- [x] WHEN "Apply to all criteria" is clicked, THE SYSTEM SHALL copy the bulk values to all criteria rows in the settings table.
- [x] WHEN "Save as default" is checked, THE SYSTEM SHALL update or insert default_settings for each criteria.
- [x] WHEN saving individual criteria deadline, THE SYSTEM SHALL call update_deadline_tieuchi.php to update danhgia_tieuchi table.
- [x] WHEN saving all changes, THE SYSTEM SHALL execute updates in parallel using Promise.all().

---

### User Story 6 - Display Deadline Status Badges (Priority: P2)

As a user viewing the order list, I want to see color-coded deadline badges so that I can quickly identify overdue and urgent tasks.

**Independent Test**: Create order with deadline in the past, verify red "Quá hạn" badge displays. Create order with deadline < 2 days away, verify yellow warning badge.

**Inferred from**: `display_deadline.php:15-93`

**Acceptance Criteria**:

- [x] WHEN deadline is not set, THE SYSTEM SHALL display grey badge with "Chưa thiết lập" text and question icon.
- [x] WHEN deadline is in the past (overdue), THE SYSTEM SHALL display red badge with "Quá hạn X ngày" text and exclamation icon.
- [x] WHEN deadline is less than 2 days away, THE SYSTEM SHALL display yellow warning badge with "Còn X ngày" text and warning icon.
- [x] WHEN deadline is 2 or more days away, THE SYSTEM SHALL display green badge with "Còn X ngày" text and check icon.
- [x] WHEN hovering over badge, THE SYSTEM SHALL display tooltip with full deadline date and calculation method.
- [x] WHEN deadline is custom (criteria-specific), THE SYSTEM SHALL add star icon to badge.

---

### User Story 7 - Auto-Recalculate Deadlines on Date Changes (Priority: P3)

As a production coordinator, I want deadlines to automatically recalculate when I edit order entry/exit dates so that deadlines stay synchronized with order timeline.

**Independent Test**: Edit order's ngayin date, verify all criteria deadlines recalculate using stored so_ngay_xuly and ngay_tinh_han.

**Inferred from**: `display_deadline.php:494-514`, `edit_date.php:13-50`

**Acceptance Criteria**:

- [x] WHEN order ngayin or ngayout is updated, THE SYSTEM SHALL recalculate all criteria deadlines that have so_ngay_xuly and ngay_tinh_han configured.
- [x] WHEN recalculating deadline, THE SYSTEM SHALL use existing so_ngay_xuly and ngay_tinh_han values from danhgia_tieuchi.
- [x] IF calculated deadline differs from current han_xuly, THEN THE SYSTEM SHALL update the deadline automatically.
- [x] WHEN date recalculation completes, THE SYSTEM SHALL return count of updated criteria.

---

## Data Models

See database schema references:

- **default_settings table**: `save_default_settings.php:72-73` (columns: id, dept, xuong, id_tieuchi, so_ngay_xuly, ngay_tinh_han)
- **khsanxuat table**: `update_deadline_ajax.php:128-129` (columns: stt, han_xuly, so_ngay_xuly, ngay_tinh_han, ngayin, ngayout)
- **danhgia_tieuchi table**: `update_deadline_tieuchi.php:46-50` (columns: id, id_sanxuat, id_tieuchi, han_xuly, so_ngay_xuly, ngay_tinh_han)
- **tieuchi_dept table**: `display_deadline.php:107` (columns: id, noidung, dept)

---

## Calculation Method Types

| Method | Formula | Example |
|--------|---------|---------|
| `ngay_vao` | Entry date - processing days | ngayin='2025-01-20', so_ngay=7 → 2025-01-13 |
| `ngay_vao_cong` | Entry date + processing days | ngayin='2025-01-20', so_ngay=7 → 2025-01-27 |
| `ngay_ra` | Exit date (or entry+7 if missing) | ngayout='2025-02-01' → 2025-02-01 |
| `ngay_ra_tru` | Exit date - processing days | ngayout='2025-02-01', so_ngay=7 → 2025-01-25 |

---

## Default Settings Hierarchy

Priority order when applying defaults to new order:

1. **Factory + Department + Criteria**: `dept='kehoach' AND xuong='A' AND id_tieuchi=5`
2. **Department + Criteria**: `dept='kehoach' AND xuong='' AND id_tieuchi=5`
3. **Fallback**: 7 days, ngay_vao_cong method

See: `apply_default_settings.php:89-98`

---

## Implementation Notes

**Status**: Synced from existing implementation  
**Last Sync**: 2026-01-29  
**Synced Files**: display_deadline.php, update_deadline.php, update_deadline_ajax.php, update_deadline_tieuchi.php, batch_update_deadline.php, settings_deadline.php, save_default_settings.php, apply_default_settings.php, get_tieuchi_deadline.php

---

## Assumptions Made

| Decision | Chosen | Reasoning |
|----------|--------|-----------|
| Default processing days | 7 days | Hardcoded fallback in multiple files when no setting exists |
| Default calculation method | ngay_vao_cong | Most frequently used default in codebase |
| Badge color thresholds | <2 days = warning | Business logic in displayDeadlineBadge function |
| Auto-create missing column | Yes | System automatically adds ngay_tinh_han column if missing |
| Transaction handling in batch | Rollback on error | Ensures data consistency for bulk operations |
