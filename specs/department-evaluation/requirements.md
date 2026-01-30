# Department Evaluation System - Requirements Specification

## Feature Overview

The Department Evaluation System allows users to evaluate production processes across 10 departments using standardized criteria, track completion status, manage evidence through image uploads, and maintain comprehensive activity logs of all changes.

---

## User Stories

### User Story 1 - View Department Evaluation Criteria (Priority: P1) 🎯 MVP

As a **production evaluator**, I want to **view all evaluation criteria for a specific department and production order** so that **I can assess what needs to be evaluated**.

**Independent Test**: Navigate to indexdept.php?dept=kehoach&id=123 and verify all criteria for the Kế Hoạch department are displayed with current scores.

**Inferred from**: `indexdept.php:713-732` (criteria query with LEFT JOIN to evaluation data)

---

### User Story 2 - Score Evaluation Criteria (Priority: P1) 🎯 MVP

As a **department supervisor**, I want to **assign scores (0, 1, or 3 points) to evaluation criteria** so that **I can record the quality level of work performed**.

**Independent Test**: Select a score from dropdown for a criterion, submit the form, and verify the score is saved in danhgia_tieuchi table.

**Inferred from**: `save_danhgia_with_log.php:118-489` (score saving logic with transaction)

---

### User Story 3 - Track Department Completion Status (Priority: P1) 🎯 MVP

As a **production manager**, I want to **automatically track when a department completes all criteria** so that **I know when production can move to the next stage**.

**Independent Test**: Complete all criteria for a department (score all > 0) and verify dept_status.completed is set to 1 with completed_date.

**Inferred from**: `save_danhgia.php:118-133` (completion status calculation)

---

### User Story 4 - Upload Evidence Images for Criteria (Priority: P1) 🎯 MVP

As a **department staff member**, I want to **upload images as evidence for specific criteria** so that **I can provide visual proof of compliance**.

**Independent Test**: Upload an image for a criterion through image_handler.php and verify it appears in khsanxuat_images table linked to the criterion.

**Inferred from**: `image_handler.php:126-190` (image upload processing)

---

### User Story 5 - Enforce Image Requirements for Criteria (Priority: P2)

As a **quality control manager**, I want to **require image evidence for certain criteria before scoring** so that **evaluations are backed by documented proof**.

**Independent Test**: Mark a criterion as requiring images in required_images_criteria, attempt to score it without uploading image, verify validation error prevents submission.

**Inferred from**: `indexdept.php:259-284` (image requirement validation)

---

### User Story 6 - Assign Responsible Personnel (Priority: P2)

As a **department manager**, I want to **assign responsible personnel to each criterion** so that **accountability is clear**.

**Independent Test**: Select a staff member from dropdown for nguoi_thuchien field and verify assignment saves to danhgia_tieuchi table.

**Inferred from**: `indexdept.php:704` (person responsible column in form)

---

### User Story 7 - Add Notes to Criteria Evaluations (Priority: P2)

As an **evaluator**, I want to **add notes to criteria evaluations** so that **I can document special circumstances or clarifications**.

**Independent Test**: Enter text in ghichu field, save evaluation, and verify note appears in danhgia_tieuchi.ghichu.

**Inferred from**: `save_danhgia_with_log.php:258-286` (note handling in save logic)

---

### User Story 8 - View Incomplete Criteria Report (Priority: P2)

As a **production supervisor**, I want to **view all incomplete criteria across all departments for a production order** so that **I can identify bottlenecks**.

**Independent Test**: Open incomplete_criteria.php for a production order and verify only criteria with diem_danhgia = 0 or NULL are listed.

**Inferred from**: `incomplete_criteria.php:82-108` (incomplete criteria query)

---

### User Story 9 - Audit All Evaluation Changes (Priority: P2)

As a **quality auditor**, I want to **view complete activity logs of all evaluation changes** so that **I can track who changed what and when**.

**Independent Test**: Make score changes, view activity_logs table, verify entries contain user, timestamp, old/new values, and department info.

**Inferred from**: `save_danhgia_with_log.php:243-305` (activity logging integration)

---

### User Story 10 - Manage Department Staff (Priority: P3)

As a **department administrator**, I want to **add, update, and remove staff members** so that **the responsible personnel list stays current**.

**Independent Test**: Use manage_staff.php to add new staff, verify they appear in nhan_vien table and in personnel dropdown.

**Inferred from**: `manage_staff.php:12-105` (staff CRUD operations)

---

## Acceptance Criteria (EARS Format)

### Criteria Display

- [x] **WHEN** the user navigates to indexdept.php with valid dept and id parameters, **THE SYSTEM SHALL** display all criteria for that department ordered by nhom and thutu.
  - Reference: `indexdept.php:713-727`

- [x] **WHEN** criteria are displayed, **THE SYSTEM SHALL** show current evaluation scores, assigned personnel, notes, and deadline for each criterion.
  - Reference: `indexdept.php:692-707`

- [x] **WHILE** criteria are grouped by nhom field, **THE SYSTEM SHALL** display group headers to organize related criteria visually.
  - Reference: `indexdept.php:719-726` (ORDER BY nhom logic)

### Scoring System

- [x] **WHEN** the user selects a score from the dropdown, **THE SYSTEM SHALL** accept values of 0 (not completed), 1 (basic), or 3 (high quality).
  - Reference: `save_danhgia_with_log.php:148-160`

- [x] **IF** a score greater than 0 is submitted for a criterion, **THEN THE SYSTEM SHALL** automatically set da_thuchien to 1.
  - Reference: `save_danhgia.php:44`

- [x] **WHEN** a score is changed, **THE SYSTEM SHALL** record both old and new values in the activity log.
  - Reference: `save_danhgia_with_log.php:394-410`

### Alternative Scoring

- [x] **WHERE** special criteria exist (999 flag), **THE SYSTEM SHALL** support alternative scoring values (0, 0.5, 1.5).
  - Reference: `indexdept.php` score dropdown rendering logic (inferred from description)

### Department Completion

- [x] **WHEN** all criteria for a department have diem_danhgia > 0, **THE SYSTEM SHALL** set dept_status.completed = 1.
  - Reference: `save_danhgia.php:118-127`

- [x] **WHEN** a department becomes complete, **THE SYSTEM SHALL** record the completion timestamp in dept_status.completed_date.
  - Reference: `save_danhgia.php:123`

- [x] **IF** any criterion is set back to score 0, **THEN THE SYSTEM SHALL** set dept_status.completed = 0 and clear completed_date.
  - Reference: `save_danhgia.php:128-133`

### Image Upload

- [x] **WHEN** the user uploads image files, **THE SYSTEM SHALL** accept JPG, JPEG, PNG, and GIF formats up to 30MB each.
  - Reference: `image_handler.php:152-156`

- [x] **WHEN** an image is uploaded, **THE SYSTEM SHALL** store it in a criterion-specific folder at `/uploads/[style]/dept_[dept]/tieuchi_[id]/`.
  - Reference: `image_handler.php:162-169`

- [x] **WHEN** saving image metadata, **THE SYSTEM SHALL** record id_khsanxuat, dept, id_tieuchi, image_path, and upload_date in khsanxuat_images.
  - Reference: `image_handler.php:176-181`

### Image Requirements

- [x] **WHERE** a criterion is marked in required_images_criteria table, **THE SYSTEM SHALL** prevent scoring > 0 unless an image exists for that criterion.
  - Reference: `indexdept.php:259-272`

- [x] **IF** the user attempts to score a required-image criterion without uploading an image, **THEN THE SYSTEM SHALL** display a validation error and prevent form submission.
  - Reference: `indexdept.php:2622-2640` (JavaScript validation)

- [x] **WHEN** the page loads, **THE SYSTEM SHALL** check all required-image criteria and display warnings for any scored criteria missing images.
  - Reference: `indexdept.php:2575-2584`

### Personnel Assignment

- [x] **WHEN** assigning a responsible person, **THE SYSTEM SHALL** save the personnel ID in danhgia_tieuchi.nguoi_thuchien.
  - Reference: `save_danhgia.php:42`

- [x] **THE SYSTEM SHALL** load personnel list from nhan_vien table filtered by department (phong_ban).
  - Reference: Inferred from personnel dropdown implementation

### Notes and Comments

- [x] **WHEN** the user enters notes in the ghichu field, **THE SYSTEM SHALL** save them to danhgia_tieuchi.ghichu.
  - Reference: `save_danhgia.php:45`

- [x] **WHEN** saving multiple criteria with notes, **THE SYSTEM SHALL** include note entries in the activity log's additional_info.
  - Reference: `save_danhgia_with_log.php:258-286`

### Activity Logging

- [x] **WHEN** any evaluation field changes (score, person, note), **THE SYSTEM SHALL** log the action in activity_logs table.
  - Reference: `activity_logger.php:90-110` (table schema), `save_danhgia_with_log.php:288-304`

- [x] **WHEN** logging an activity, **THE SYSTEM SHALL** record user_name, user_full_name, action_type, target_type, target_id, id_khsanxuat, dept, old_value, new_value, and additional_info.
  - Reference: `activity_logger.php:91-105`

- [x] **WHEN** multiple criteria are updated in one request, **THE SYSTEM SHALL** consolidate changes into a single log entry with action_type = 'update_multiple'.
  - Reference: `save_danhgia_with_log.php:236-256`

- [x] **IF** logging fails, **THEN THE SYSTEM SHALL** log the error but not block the evaluation save operation.
  - Reference: `save_danhgia_with_log.php:298-304`

### Incomplete Criteria Report

- [x] **WHEN** viewing incomplete_criteria.php, **THE SYSTEM SHALL** display all criteria where diem_danhgia IS NULL OR diem_danhgia = 0.
  - Reference: `incomplete_criteria.php:107`

- [x] **WHEN** displaying incomplete criteria, **THE SYSTEM SHALL** group them by department in the standard department order (kehoach, chuanbi_sanxuat_phong_kt, kho, cat, ep_keo, co_dien, chuyen_may, kcs, ui_thanh_pham, hoan_thanh).
  - Reference: `incomplete_criteria.php:109-125`

- [x] **WHEN** the report is generated, **THE SYSTEM SHALL** show criterion content, assigned person, status, and notes.
  - Reference: `incomplete_criteria.php:666-672`

### Staff Management

- [x] **WHEN** adding a new staff member via manage_staff.php, **THE SYSTEM SHALL** require ten (name) and phong_ban (department) fields.
  - Reference: `manage_staff.php:14-16`

- [x] **IF** a staff member with the same name and department already exists, **THEN THE SYSTEM SHALL** reject the addition and return an error.
  - Reference: `manage_staff.php:23-31`

- [x] **WHEN** updating staff info, **THE SYSTEM SHALL** allow changing ten and chuc_vu but not phong_ban.
  - Reference: `manage_staff.php:50-93`

### Data Validation

- [x] **IF** required fields are missing during form submission, **THEN THE SYSTEM SHALL** display an error message and prevent saving.
  - Reference: Form validation logic (inferred)

- [x] **WHEN** processing evaluation saves, **THE SYSTEM SHALL** use database transactions to ensure atomicity.
  - Reference: `save_danhgia_with_log.php:113-329`

### User Interface

- [x] **WHEN** a form submission succeeds, **THE SYSTEM SHALL** redirect to indexdept.php with success=1 parameter to display a success message.
  - Reference: `save_danhgia_with_log.php:332`

- [x] **THE SYSTEM SHALL** display department names in Vietnamese using the dept_names mapping.
  - Reference: `indexdept.php:33-44`

---

## Department Codes and Names

**Note**: All 10 departments remain fully functional in the backend. The main dashboard (`index.php`) currently shows only 4 departments for improved UI clarity, but all departments can be accessed directly via department-specific evaluation pages.

### Visible on Main Dashboard (4)

| Code | Vietnamese Name | English | Dashboard Visibility |
|------|-----------------|---------|---------------------|
| kehoach | Kế Hoạch | Planning | ✅ Visible |
| chuanbi_sanxuat_phong_kt | Chuẩn Bị SX - Phòng KT | Production Prep - Engineering Dept | ✅ Visible |
| kho | Kho Nguyên, Phụ Liệu | Materials & Accessories Warehouse | ✅ Visible |
| cat | Cắt | Cutting | ✅ Visible |

### Hidden from Main Dashboard (6)

| Code | Vietnamese Name | English | Dashboard Visibility |
|------|-----------------|---------|---------------------|
| ep_keo | Ép Keo | Glue Pressing | ⚠️ Temporarily hidden (backend active) |
| co_dien | Cơ Điện | Mechanics & Electronics | ⚠️ Temporarily hidden (backend active) |
| chuyen_may | Chuyền May | Sewing Line | ⚠️ Temporarily hidden (backend active) |
| kcs | KCS | Quality Control | ⚠️ Temporarily hidden (backend active) |
| ui_thanh_pham | Ủi Thành Phẩm | Finished Product Ironing | ⚠️ Temporarily hidden (backend active) |
| hoan_thanh | Hoàn Thành | Completion | ⚠️ Temporarily hidden (backend active) |

**Access**: All departments remain accessible via:
- Direct URL: `indexdept.php?dept=[dept_code]&id=[order_id]`
- Department-specific statistics pages
- Backend evaluation system
- Database tables (`dept_status`, `danhgia_tieuchi`)

**Implementation Reference**: `index.php:2392-2884` (sections marked with `// HIDDEN TEMPORARILY` comments)

---

## Implementation Notes

**Status**: Synced from existing implementation  
**Sync Date**: 2026-01-30 (Updated for department visibility changes)
**Implementation Location**: `C:\xampp\htdocs\khsanxuat`

All acceptance criteria document EXISTING functionality. The system is fully operational with:
- 10 departments supported (4 visible on dashboard, 6 hidden temporarily)
- Scoring system with standard (0/1/3) and alternative (0/0.5/1.5) scales
- Image upload and validation
- Activity logging for audit trail
- Personnel management
- Incomplete criteria tracking

**Recent Changes**:
- 2026-01-30: Updated spec to document that only 4 departments are currently visible on the main production tracking dashboard, while all 10 departments remain fully functional in backend systems
