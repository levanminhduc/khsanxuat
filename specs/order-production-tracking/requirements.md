# Order & Production Tracking - Requirements

## Feature Overview

The Order & Production Tracking feature manages the entire lifecycle of production orders from intake to completion. It provides a centralized dashboard for tracking orders across multiple factories and production lines, supports bulk data import via Excel, enables comprehensive search capabilities, and tracks department-level completion status for each order.

## User Stories

### User Story 1 - View Production Orders by Month (Priority: P1) 🎯 MVP

As a production manager, I want to view all production orders filtered by month and year so that I can focus on the current production period.

**Independent Test**: Load dashboard with month/year selector, verify orders are filtered correctly
**Inferred from**: `index.php:51-59` - month/year dropdown and query filtering

**Acceptance Criteria**:

- [x] WHEN the user accesses the dashboard, THE SYSTEM SHALL display orders for the current month by default
- [x] WHEN the user selects a different month from the dropdown, THE SYSTEM SHALL filter orders by that month and year
- [x] THE SYSTEM SHALL display available months based on existing order data (from `ngayin` field)
- [x] THE SYSTEM SHALL sort orders by factory (`xuong`), line number (`line1`), and entry date (`ngayin`) in ascending order

### User Story 2 - Search Production Orders (Priority: P1) 🎯 MVP

As a user, I want to search for production orders by multiple criteria so that I can quickly locate specific orders or groups of orders.

**Independent Test**: Use search form with different search types and values, verify correct filtering
**Inferred from**: `index.php:65-91` - search handling logic

**Acceptance Criteria**:

- [x] WHEN the user enters a search value and selects search type "xuong", THE SYSTEM SHALL filter orders by factory name using LIKE pattern matching
- [x] WHEN the user selects search type "line", THE SYSTEM SHALL filter orders by production line (`line1`) field
- [x] WHEN the user selects search type "po", THE SYSTEM SHALL filter orders by PO number
- [x] WHEN the user selects search type "style", THE SYSTEM SHALL filter orders by style code
- [x] WHEN the user selects search type "model", THE SYSTEM SHALL filter orders by model name
- [x] WHEN the user enters "all" (case-insensitive) as search value, THE SYSTEM SHALL ignore the search filter and display all orders
- [x] THE SYSTEM SHALL preserve month/year filter when applying search criteria

### User Story 3 - Import Orders from Excel (Priority: P1) 🎯 MVP

As a data entry operator, I want to import production orders from Excel files so that I can efficiently add multiple orders without manual entry.

**Independent Test**: Upload Excel file with order data, verify records are created
**Inferred from**: `import.php:506-676` - Excel upload and processing

**Acceptance Criteria**:

- [x] WHEN the user uploads an Excel file, THE SYSTEM SHALL parse the file using PhpSpreadsheet library
- [x] THE SYSTEM SHALL read the first row as column headers and support case-insensitive matching
- [x] THE SYSTEM SHALL validate required fields (LINE, xuong, PO, style, qty) before importing each row
- [x] IF any required field is empty, THEN THE SYSTEM SHALL skip that row and log an error message
- [x] THE SYSTEM SHALL convert date fields (ngayin, ngayout) from Excel serial format or d/m/Y format to Y-m-d format
- [x] IF date format is invalid, THEN THE SYSTEM SHALL skip that row and log a validation error
- [x] THE SYSTEM SHALL check for duplicate records based on matching PO and style before inserting
- [x] IF a duplicate is detected, THEN THE SYSTEM SHALL skip the row and log the duplicate with existing record ID
- [x] THE SYSTEM SHALL process records in batches of 50 rows to optimize performance
- [x] THE SYSTEM SHALL insert records with fields: line1, xuong, po, style, model, qty, ngayin, ngayout, ngay_tinh_han, so_ngay_xuly
- [x] THE SYSTEM SHALL set execution time limit to 300 seconds for large file imports
- [x] WHEN import completes, THE SYSTEM SHALL display summary of success count, error count, and duplicate count

### User Story 4 - Export Orders to Excel (Priority: P2)

As a production manager, I want to export production orders for a specific month to Excel so that I can share data with external stakeholders.

**Independent Test**: Click export button for a month, verify Excel file is generated
**Inferred from**: `export.php:187-212` - Excel export logic

**Acceptance Criteria**:

- [x] WHEN the user clicks export for a selected month/year, THE SYSTEM SHALL generate an Excel file using PhpSpreadsheet
- [x] THE SYSTEM SHALL include all order fields in the export (STT, xuong, line, PO, style, model, qty, ngayin, ngayout)
- [x] THE SYSTEM SHALL include department status columns for all 13 departments in the export
- [x] THE SYSTEM SHALL apply header styling to the Excel output
- [x] THE SYSTEM SHALL query orders filtered by the selected month and year
- [x] THE SYSTEM SHALL sort exported data by factory, line number, and entry date

### User Story 5 - Track Department Completion Status (Priority: P1) 🎯 MVP

As a production coordinator, I want to see completion status for each department on each order so that I can identify bottlenecks and delays.

**Independent Test**: View order dashboard, verify department completion indicators are displayed
**Inferred from**: `index.php:8-17`, `check_completion_status.php:4-30` - status checking functions

**Acceptance Criteria**:

- [x] THE SYSTEM SHALL display completion status for each order across all departments (kehoach, kho, cat, ep_keo, chuanbi_sanxuat_phong_kt, may, hoan_thanh, co_dien, kcs, ui_thanh_pham, chuyen_may, quan_ly_sx, quan_ly_cl)
- [x] WHEN all criteria for a department are marked complete (`da_thuchien = 1`), THE SYSTEM SHALL mark that department as completed in `dept_status` table
- [x] THE SYSTEM SHALL store completion date when a department completes all criteria
- [x] WHEN the user requests completion status check, THE SYSTEM SHALL verify all criteria completion and update `dept_status.completed` flag accordingly
- [x] IF previously completed criteria become incomplete, THEN THE SYSTEM SHALL reset completion status to 0 and clear completion date

### User Story 6 - Edit Order Dates and Recalculate Deadlines (Priority: P2)

As a production planner, I want to update order entry/exit dates and automatically recalculate all department deadlines so that deadlines stay synchronized with schedule changes.

**Independent Test**: Update ngayin or ngayout for an order, verify all department deadlines are recalculated
**Inferred from**: `edit_date.php:23-143` - deadline calculation and update logic

**Acceptance Criteria**:

- [x] WHEN the user updates the entry date (`ngayin`) or exit date (`ngayout`) for an order, THE SYSTEM SHALL recalculate all department deadlines based on the new dates
- [x] THE SYSTEM SHALL support four deadline calculation methods: `ngay_vao` (entry date minus processing days), `ngay_vao_cong` (entry date plus processing days), `ngay_ra` (exit date), `ngay_ra_tru` (exit date minus processing days)
- [x] THE SYSTEM SHALL retrieve each department's calculation method (`ngay_tinh_han`) and processing days (`so_ngay_xuly`) from the criteria evaluation records
- [x] THE SYSTEM SHALL update the `han_xuly` field in `danhgia_tieuchi` table for all criteria linked to the order
- [x] IF entry date is empty, THEN THE SYSTEM SHALL not calculate deadline for that criterion
- [x] IF exit date is empty and calculation method requires it, THEN THE SYSTEM SHALL use entry date plus 7 days as fallback

### User Story 7 - Display Order Details and Statistics (Priority: P2)

As a user, I want to view detailed order information and visual completion indicators so that I can quickly assess order status at a glance.

**Independent Test**: Load dashboard, verify order data and statistics are displayed correctly
**Inferred from**: `index.php:2584-2712` - table display and data rendering

**Acceptance Criteria**:

- [x] THE SYSTEM SHALL display a data table with columns: checkbox, STT, xuong, LINE, P/o no., Style, Model, Qty, In (ngayin), Out (ngayout), and department status columns
- [x] THE SYSTEM SHALL provide clickable links on STT to view detailed system evaluation
- [x] THE SYSTEM SHALL provide clickable links on xuong to view factory-specific templates
- [x] THE SYSTEM SHALL provide clickable links on style to view incomplete criteria for that style
- [x] THE SYSTEM SHALL highlight styles with incomplete criteria using special CSS class
- [x] THE SYSTEM SHALL support row selection via checkboxes for batch operations
- [x] THE SYSTEM SHALL display department column headers as clickable links to view department-specific statistics
- [x] THE SYSTEM SHALL provide mobile-responsive table display with scroll hints

## Database Tables

### khsanxuat

Primary table for production order tracking.

**Schema reference**: See `system_features_summary.md:83-95` and `khsanxuat_documentation.txt:57-66`

**Key fields**:
- `stt`: Auto-increment primary key
- `xuong`: Factory name
- `line1`: Production line identifier
- `po`: Purchase order number
- `style`: Product style code
- `model`: Product model name
- `qty`: Order quantity
- `ngayin`: Entry date (order start date)
- `ngayout`: Exit date (order completion date)
- `han_xuly`: Processing deadline
- `so_ngay_xuly`: Default processing days
- `ngay_tinh_han`: Deadline calculation method

### dept_status

Tracks completion status for each department per order.

**Schema reference**: See `khsanxuat_documentation.txt:82-88` and `system_features_summary.md:96-102`

**Key fields**:
- `id`: Auto-increment primary key
- `id_sanxuat`: Foreign key to `khsanxuat.stt`
- `dept`: Department code
- `completed`: Completion flag (0 = incomplete, 1 = complete)
- `completed_date`: Date when department completed all criteria

**Relationships**:
- `dept_status.id_sanxuat` → `khsanxuat.stt` (many-to-one)

### default_settings

Stores default deadline calculation settings per department and criteria.

**Schema reference**: See `indexdept.php:174-186` and `system_features_summary.md:144-151`

**Key fields**:
- `id`: Auto-increment primary key
- `dept`: Department code
- `xuong`: Factory name (can be empty for global settings)
- `id_tieuchi`: Foreign key to criteria table
- `ngay_tinh_han`: Deadline calculation method (ngay_vao, ngay_vao_cong, ngay_ra, ngay_ra_tru)
- `so_ngay_xuly`: Number of processing days
- `nguoi_chiu_trachnhiem_default`: Default responsible person ID
- `ngay_tao`: Creation timestamp
- `ngay_capnhat`: Last update timestamp

**Unique constraint**: `(dept, id_tieuchi, xuong)`

## Assumptions Made

| Decision | Chosen | Reasoning |
|----------|--------|-----------|
| Import batch size | 50 rows | Balances memory usage and performance based on `import.php:512` |
| Default month filter | Current month | Most users work with current period data (`index.php:51`) |
| Duplicate detection | PO + Style match | Prevents duplicate orders based on business key (`import.php:322-327`) |
| Date format support | Excel serial & d/m/Y | Supports both Excel numeric dates and manual entry format (`import.php:8-31`) |
| Search pattern | LIKE with wildcards | Enables partial matching for flexible search (`index.php:74-87`) |
| Deadline fallback | Entry date + 7 days | Reasonable default when exit date is unavailable (`edit_date.php:70-73`, `edit_date.php:82-85`) |
| Line sorting | Numeric cast | Sorts lines numerically (1, 2, 10) not alphabetically (1, 10, 2) (`index.php:94`) |
| Execution timeout | 300 seconds | Prevents timeout on large Excel imports (`import.php:516`) |

## Dashboard Department Visibility Configuration

**Current Status**: Temporarily showing only 4 departments on the main dashboard

### Visible Departments on Dashboard (4)

The main production tracking dashboard (`index.php`) currently displays only these departments:

| Department | Vietnamese Name | Code | Chart Color |
|------------|----------------|------|-------------|
| Kế Hoạch | Kế Hoạch | kehoach | #FF6384 |
| Kỹ Thuật | Chuẩn Bị SX - Phòng KT | chuanbi_sanxuat_phong_kt | #36A2EB |
| Kho | Kho Nguyên, Phụ Liệu | kho | #FFCE56 |
| Cắt | Cắt | cat | #4BC0C0 |

### Hidden Departments (6)

The following departments are **temporarily hidden** from the dashboard UI but remain fully functional in the backend and on department-specific pages (`indexdept.php`):

| Department | Vietnamese Name | Code | Status |
|------------|----------------|------|--------|
| Ép Keo | Ép Keo | ep_keo | Backend active |
| Cơ Điện | Cơ Điện | co_dien | Backend active |
| Chuyền May | Chuyền May | chuyen_may | Backend active |
| KCS | KCS | kcs | Backend active |
| Ủi TP | Ủi Thành Phẩm | ui_thanh_pham | Backend active |
| Hoàn Thành | Hoàn Thành | hoan_thanh | Backend active |

### Implementation Details

**Modified sections in `index.php`**:
- Lines 2392-2405: `$chart_departments` array (evaluation container) - reduced to 4 departments
- Lines 2621-2654: Table header columns - 6 `<th>` elements commented out
- Lines 2780-2790: `$departments` array (table body loop) - reduced to 1 department (cat)
- Lines 2871-2884: `$departments` array (Chart.js data) - reduced to 4 departments

All hidden sections are marked with `// HIDDEN TEMPORARILY` comments for easy restoration.

**Backend Impact**: 
- ✅ All 10 departments still work in department-specific evaluation pages
- ✅ Database tables (`dept_status`, `danhgia_tieuchi`) support all 10 departments
- ✅ Completion status tracking continues for all departments
- ⚠️ Main dashboard now shows fewer columns, improving mobile responsiveness
- ⚠️ Department evaluation chart shows only 4 departments instead of 10

**Restoration**: To restore hidden departments, uncomment the sections marked with `// HIDDEN TEMPORARILY` in the four locations listed above.

---

## Implementation Notes

**Status**: Synced from existing implementation
**Sync Date**: 2026-01-30 (Updated for department visibility changes)

All acceptance criteria marked complete [x] reflect the current state of the codebase as analyzed from:
- `index.php` (dashboard and search)
- `import.php` (Excel import)
- `export.php` (Excel export)
- `edit_date.php` (date editing and deadline recalculation)
- `check_completion_status.php` (completion status verification)
- Database schema from documentation files

**Recent Changes**:
- 2026-01-30: Updated spec to document temporary reduction of dashboard department columns from 10 to 4 for improved UI clarity
