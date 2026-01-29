# Statistics & Reporting - Requirements

## Overview
Statistics and reporting feature provides performance analytics and visualization for production departments, enabling managers to track completion rates, scores, and system-wide evaluations across time periods.

## User Stories

### User Story 1 - View Department Performance Statistics (Priority: P1) 🎯 MVP

As a **production manager**, I want **to view department performance statistics by day and month** so that **I can monitor team productivity and completion rates**.

**Independent Test**: Navigate to dept_statistics.php with department/month/year parameters, verify statistics display correctly.

**Inferred from**: dept_statistics.php:62-135, dept_statistics_month.php:46-145

**Acceptance Criteria**:

- [x] **WHEN** the user accesses daily department statistics with dept, month, year parameters, **THE SYSTEM SHALL** display completed products for that department with scores and percentages.
- [x] **WHEN** the user accesses monthly aggregated statistics, **THE SYSTEM SHALL** calculate and display average scores across all completed products in that month.
- [x] **WHEN** displaying department statistics, **THE SYSTEM SHALL** calculate completion percentage as (total_score / max_score) * 100.
- [x] **WHEN** calculating department scores, **THE SYSTEM SHALL** sum evaluation points from danhgia_tieuchi joined with tieuchi_dept for each product.
- [x] **WHEN** displaying monthly statistics, **THE SYSTEM SHALL** show total completed products count, average score, and completion rate per department.

### User Story 2 - Compare Department Performance (Priority: P1) 🎯 MVP

As a **factory supervisor**, I want **to compare performance across multiple departments** so that **I can identify high-performing and underperforming areas**.

**Independent Test**: Load monthly statistics page, verify all departments display side-by-side with color-coded performance indicators.

**Inferred from**: dept_statistics_month.php:85-128, index.php:136-159

**Acceptance Criteria**:

- [x] **WHEN** viewing monthly department comparison, **THE SYSTEM SHALL** display each department with its assigned color from dept_colors array.
- [x] **WHEN** comparing departments, **THE SYSTEM SHALL** show required score (yeucau), actual average score, and percentage for each department.
- [x] **WHEN** a department's score is below requirement, **THE SYSTEM SHALL** highlight the row with warning background color (#ffebee).
- [x] **WHEN** displaying department summary, **THE SYSTEM SHALL** show completed product count vs total products for each department.
- [x] **WHEN** rendering comparison table, **THE SYSTEM SHALL** calculate overall percentage from total average scores across all departments.

### User Story 3 - Visualize Performance with Charts (Priority: P2)

As a **production manager**, I want **to see visual charts of department performance** so that **I can quickly understand trends and patterns**.

**Independent Test**: Load index.php with Chart.js, verify bar chart renders with correct department completion percentages.

**Inferred from**: index.php:2892-2987

**Acceptance Criteria**:

- [x] **WHEN** the dashboard loads, **THE SYSTEM SHALL** render a Chart.js bar chart showing department completion rates.
- [x] **WHEN** displaying chart data, **THE SYSTEM SHALL** use department-specific colors from dept_colors array for each bar.
- [x] **WHEN** rendering the chart, **THE SYSTEM SHALL** set y-axis range from 0 to 100 with percentage labels.
- [x] **WHEN** user hovers over a chart bar, **THE SYSTEM SHALL** display tooltip showing department name and completion percentage.
- [x] **WHEN** user clicks a chart bar, **THE SYSTEM SHALL** navigate to detailed statistics page for that department.
- [x] **WHEN** chart is displayed on mobile (width ≤ 428px), **THE SYSTEM SHALL** adjust font sizes and padding for responsive display.

### User Story 4 - View System-Wide Evaluation (Priority: P1) 🎯 MVP

As a **factory manager**, I want **to view system-wide production evaluation scores** so that **I can assess overall factory performance and risk levels**.

**Independent Test**: Access danhgia_hethong.php with product ID, verify all department scores display with total and risk assessment.

**Inferred from**: danhgia_hethong.php:1-259

**Acceptance Criteria**:

- [x] **WHEN** viewing system evaluation for a product, **THE SYSTEM SHALL** display scores for all 10 departments (Kế Hoạch, Chuẩn Bị SX, Kho, Cắt, Ép Keo, Cơ Điện, Chuyền May, KCS, Ủi TP, Hoàn Thành).
- [x] **WHEN** calculating department scores, **THE SYSTEM SHALL** sum diem_danhgia from danhgia_tieuchi joined with tieuchi_dept for the product.
- [x] **WHEN** displaying evaluation table, **THE SYSTEM SHALL** show required score (yeucau) and actual score for each department.
- [x] **WHEN** total score is below 267, **THE SYSTEM SHALL** classify risk as "High - requires immediate action".
- [x] **WHEN** total score is 267-338, **THE SYSTEM SHALL** classify risk as "Low - requires early correction".
- [x] **WHEN** total score is 339-430, **THE SYSTEM SHALL** classify risk as "Acceptable - needs improvement".
- [x] **WHEN** total score is 431 or above, **THE SYSTEM SHALL** classify risk as "Good - maintain performance".

### User Story 5 - Export Statistics to Excel (Priority: P2)

As a **production manager**, I want **to export statistics to Excel format** so that **I can share reports and perform offline analysis**.

**Independent Test**: Click export button with month/year selection, verify Excel file downloads with correct data and formatting.

**Inferred from**: export.php:109-346

**Acceptance Criteria**:

- [x] **WHEN** user selects month and year for export, **THE SYSTEM SHALL** generate Excel file filtered by that time period.
- [x] **WHEN** creating Excel export, **THE SYSTEM SHALL** add company header "CÔNG TY MAY HOÀ THỌ ĐIỆN BÀN" in merged cells A1:V1.
- [x] **WHEN** creating Excel export, **THE SYSTEM SHALL** add report title "ĐÁNH GIÁ HỆ THỐNG SẢN XUẤT TOÀN NHÀ MÁY THÁNG MM/YYYY" in merged cells A2:V2.
- [x] **WHEN** exporting data, **THE SYSTEM SHALL** include all department completion status columns (Kế Hoạch through Quản Lý CL).
- [x] **WHEN** generating Excel file, **THE SYSTEM SHALL** apply header styling with bold font size 22 and center alignment.
- [x] **WHEN** export is complete, **THE SYSTEM SHALL** download file named "KH_RaiChuyen_ThangMM_NamYYYY_YYYYMMDD_HHMMSS.xlsx".

### User Story 6 - Filter Statistics by Time Period (Priority: P2)

As a **production manager**, I want **to filter statistics by month and year** so that **I can analyze performance trends over different time periods**.

**Independent Test**: Use month/year selector dropdown, verify statistics refresh with selected period data.

**Inferred from**: dept_statistics_month.php:130-135, dept_statistics.php:129-134

**Acceptance Criteria**:

- [x] **WHEN** the statistics page loads, **THE SYSTEM SHALL** query distinct months and years from khsanxuat.ngayin.
- [x] **WHEN** displaying time period selector, **THE SYSTEM SHALL** show available months ordered by year DESC, month DESC.
- [x] **WHEN** user selects a different month/year, **THE SYSTEM SHALL** reload statistics filtered by MONTH(ngayin) and YEAR(ngayin).
- [x] **WHEN** no month/year is selected, **THE SYSTEM SHALL** default to current month and year.

## Data Models

See `dept_statistics_month.php:46-80` for calculateDeptScore function and score calculation logic.

See `danhgia_hethong.php:7-17` for departments array with yeucau thresholds.

See `export.php:190-204` for department code mappings.

## Assumptions

| Decision | Chosen | Reasoning |
|----------|--------|-----------|
| Maximum score per criterion | 3 points | Based on comment in calculateDeptScore: "Giả sử điểm tối đa cho mỗi tiêu chí là 3" |
| Chart library | Chart.js | Identified from index.php:2892 usage |
| Excel format | .xlsx | PhpSpreadsheet library used in export.php |
| Risk threshold levels | 267/338/430 | Hardcoded thresholds in danhgia_hethong.php:252-255 |
| Department color scheme | dept_colors array | Pre-defined colors for visual consistency |
| Default time period | Current month/year | Standard behavior when no selection made |

## Implementation Notes

**Status**: Synced from existing implementation

**Synced Date**: 2026-01-29

**Key Implementation Files**:
- dept_statistics.php - Daily department statistics
- dept_statistics_month.php - Monthly aggregated statistics
- danhgia_hethong.php - System-wide evaluation
- export.php - Excel export functionality
- index.php - Dashboard with Chart.js visualization

**Dependencies**:
- Chart.js library for visualization
- PhpSpreadsheet for Excel export
- MySQL database: tables khsanxuat, danhgia_tieuchi, tieuchi_dept, dept_status
