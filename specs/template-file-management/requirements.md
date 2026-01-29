# Template & File Management - Requirements

## Feature Overview

The Template & File Management system enables department users to upload, organize, view, download, and delete files associated with production orders. Each department maintains its own set of template categories, and files are uploaded against specific production orders (khsanxuat) within those templates. The system supports multiple file types with size constraints and provides both individual file operations and bulk downloads.

**Status**: ✅ Implemented and Synced

**Key Components**:
- Template management UI: `file_templates.php`
- Factory-wide template overview: `factory_templates.php`
- Bulk download functionality: `download_all_files.php`
- File deletion endpoint: `delete_template_file.php`

**Database Tables**:
- `dept_templates`: Template definitions per department
- `dept_template_files`: File records with metadata

**Storage Location**: `uploads/templates/[dept]/template_[id]/`

---

## User Stories

### User Story 1 - Create Template Categories (Priority: P1) 🎯 MVP

As a **department user**, I want to **create named template categories for my department** so that **I can organize uploaded files into logical groups**.

**Independent Test**: Add a new template for a department without any existing templates, verify it appears in the template list.

**Inferred from**: `file_templates.php:118-150` (template creation logic)

**Acceptance Criteria**:

- [x] WHEN a user submits the add template form with a valid template name, THE SYSTEM SHALL insert a new record into dept_templates table with dept, template_name, and template_description.
- [x] WHEN a user attempts to create a template with a name that already exists in the same department, THE SYSTEM SHALL reject the request with error message "Template '{name}' đã tồn tại trong phòng ban này."
- [x] IF the template name field is empty, THEN THE SYSTEM SHALL display error "Vui lòng nhập tên template."
- [x] WHEN a template is successfully created, THE SYSTEM SHALL display success message "Đã thêm template '{name}' thành công."
- [x] THE SYSTEM SHALL automatically set created_at and updated_at timestamps for new templates.

---

### User Story 2 - Upload Files to Templates (Priority: P1) 🎯 MVP

As a **department user**, I want to **upload multiple files to a template associated with a production order** so that **I can attach relevant documents and images to specific work items**.

**Independent Test**: Select a template and upload multiple files, verify all files are saved to the correct folder and database records are created.

**Inferred from**: `file_templates.php:154-226` (file upload logic)

**Acceptance Criteria**:

- [x] WHEN a user uploads files, THE SYSTEM SHALL support multiple file selection (multipart/form-data with array input).
- [x] WHEN processing uploaded files, THE SYSTEM SHALL validate file extensions against allowed list: jpg, jpeg, png, gif, bmp, tif, tiff, webp, pdf, xls, xlsx, doc, docx.
- [x] IF a file exceeds 30MB (30485760 bytes), THEN THE SYSTEM SHALL reject the file with error "File {name} quá lớn. Giới hạn 30MB!"
- [x] IF a file has disallowed extension, THEN THE SYSTEM SHALL reject with error "File {name} không đúng định dạng. Chỉ cho phép JPG, JPEG, PNG, GIF, PDF, Excel và Word!"
- [x] WHEN a file passes validation, THE SYSTEM SHALL create a unique filename using pattern: {safe_style}_{dept}_{YmdHis}_{index}.{ext}
- [x] WHEN saving files, THE SYSTEM SHALL store them in directory: template_files/{dept}/{id_khsanxuat}/template_{id_template}/
- [x] WHEN a file is successfully uploaded, THE SYSTEM SHALL insert a record into dept_template_files with: id_template, id_khsanxuat, dept, file_path, file_name, file_type, upload_date.
- [x] WHEN determining file type, THE SYSTEM SHALL categorize as: 'image' (jpg, jpeg, png, gif, bmp, tif, tiff, webp), 'pdf' (pdf), 'excel' (xls, xlsx), 'word' (doc, docx).
- [x] IF no template is selected (id_template <= 0), THEN THE SYSTEM SHALL display error "Vui lòng chọn template cho file."
- [x] WHEN upload completes, THE SYSTEM SHALL display success message with count: "Đã upload thành công {count} file(s)."
- [x] IF some files fail validation, THE SYSTEM SHALL display accumulated error messages for each failed file.

---

### User Story 3 - View Uploaded Files (Priority: P1) 🎯 MVP

As a **department user**, I want to **view a list of all files uploaded for a production order** so that **I can see what documents are available and access them**.

**Independent Test**: Navigate to file list page for a production order, verify all uploaded files are displayed with metadata.

**Inferred from**: `file_templates.php:251-264` (file listing query)

**Acceptance Criteria**:

- [x] WHEN displaying files, THE SYSTEM SHALL query dept_template_files joined with dept_templates to show template names.
- [x] WHEN listing files, THE SYSTEM SHALL filter by id_khsanxuat AND dept parameters.
- [x] WHEN displaying files, THE SYSTEM SHALL order results by upload_date DESC (newest first).
- [x] WHEN rendering file list, THE SYSTEM SHALL display: file name, template name, file type, upload date, and action buttons.
- [x] WHEN displaying file types, THE SYSTEM SHALL show appropriate icons/labels for image, pdf, excel, word file types.

---

### User Story 4 - Download All Files (Priority: P2)

As a **department user**, I want to **download all files for a production order as a single ZIP archive** so that **I can easily obtain all documents at once**.

**Independent Test**: Click download all button, verify a ZIP file is generated containing all files for the production order.

**Inferred from**: `download_all_files.php:40-155` (bulk download logic)

**Acceptance Criteria**:

- [x] WHEN initiating bulk download, THE SYSTEM SHALL query all files for the specified id_sanxuat and dept.
- [x] IF no files exist for download, THEN THE SYSTEM SHALL terminate with error "Không có file nào để tải xuống."
- [x] WHEN creating ZIP archive, THE SYSTEM SHALL create temporary directory: template_files/temp/{unique_id}/
- [x] WHEN processing files for ZIP, THE SYSTEM SHALL copy each file from original location to temp directory with sanitized filename.
- [x] WHEN sanitizing filenames, THE SYSTEM SHALL remove special characters that may cause issues.
- [x] WHEN ZIP creation succeeds using ZipArchive, THE SYSTEM SHALL add all copied files to archive with their sanitized names.
- [x] WHEN ZIP is ready, THE SYSTEM SHALL set appropriate headers: Content-Type: application/zip, Content-Disposition: attachment with filename pattern {style}_{dept}_{timestamp}.zip
- [x] WHEN download completes, THE SYSTEM SHALL clean up temporary directory and files.
- [x] IF the product (khsanxuat) is not found, THEN THE SYSTEM SHALL terminate with error "Không tìm thấy mã hàng."

---

### User Story 5 - Delete Individual Files (Priority: P2)

As a **department user**, I want to **delete individual files that were uploaded incorrectly or are no longer needed** so that **I can maintain clean and accurate file records**.

**Independent Test**: Delete a file from the list, verify it is removed from both the filesystem and database.

**Inferred from**: `delete_template_file.php:30-63` (file deletion logic)

**Acceptance Criteria**:

- [x] WHEN deleting a file, THE SYSTEM SHALL require three parameters: id (file ID), id_sanxuat, and dept.
- [x] IF any required parameter is missing or invalid (id <= 0, id_sanxuat <= 0, empty dept), THEN THE SYSTEM SHALL terminate with error "Thiếu thông tin cần thiết."
- [x] WHEN processing deletion, THE SYSTEM SHALL first query dept_template_files to retrieve the file_path for verification.
- [x] IF the file record is not found, THEN THE SYSTEM SHALL terminate with error "Không tìm thấy file cần xóa."
- [x] WHEN a file record exists, THE SYSTEM SHALL attempt to delete the physical file using unlink() if it exists.
- [x] IF physical file deletion fails, THE SYSTEM SHALL log the error but continue to delete the database record.
- [x] WHEN deleting from database, THE SYSTEM SHALL execute DELETE statement on dept_template_files where id matches.
- [x] WHEN deletion succeeds, THE SYSTEM SHALL redirect to file_templates.php with success parameter.
- [x] WHEN redirected with success=deleted, THE SYSTEM SHALL display message "Đã xóa file thành công."

---

### User Story 6 - View Factory-wide Template Overview (Priority: P2)

As a **factory manager**, I want to **view all templates and file counts across departments and production orders** so that **I can monitor document completion status across the factory**.

**Independent Test**: Navigate to factory templates page, verify templates are grouped by department with file counts displayed.

**Inferred from**: `factory_templates.php:88-148` (factory overview logic)

**Acceptance Criteria**:

- [x] WHEN loading factory overview, THE SYSTEM SHALL query distinct departments from dept_templates.
- [x] WHEN displaying department names, THE SYSTEM SHALL use friendly names from dept_names mapping array.
- [x] WHEN loading templates, THE SYSTEM SHALL retrieve all records from dept_templates ordered by dept, id.
- [x] WHEN a specific production order is selected (id_sanxuat > 0), THE SYSTEM SHALL count files per template using dept_template_files.
- [x] WHEN counting files, THE SYSTEM SHALL filter by id_template AND id_khsanxuat.
- [x] WHEN rendering templates, THE SYSTEM SHALL display: template ID, name, description, and file count.
- [x] WHEN organizing data, THE SYSTEM SHALL group templates by department code.

---

### User Story 7 - Auto-create Database Schema (Priority: P1) 🎯 MVP

As a **system administrator**, I want the **database tables to be automatically created if they don't exist** so that **deployment is simplified and errors are prevented**.

**Independent Test**: Drop the tables, access the feature page, verify tables are recreated automatically.

**Inferred from**: `file_templates.php:64-106` (schema creation logic)

**Acceptance Criteria**:

- [x] WHEN the application initializes, THE SYSTEM SHALL check for existence of dept_templates table using SHOW TABLES query.
- [x] IF dept_templates does not exist, THEN THE SYSTEM SHALL create it with schema: id (INT AUTO_INCREMENT), dept (VARCHAR 50), template_name (VARCHAR 100), template_description (TEXT), created_at (DATETIME), updated_at (DATETIME with ON UPDATE), PRIMARY KEY (id), KEY idx_dept (dept).
- [x] WHEN the application initializes, THE SYSTEM SHALL check for existence of dept_template_files table using SHOW TABLES query.
- [x] IF dept_template_files does not exist, THEN THE SYSTEM SHALL create it with schema: id (INT AUTO_INCREMENT), id_template (INT), id_khsanxuat (INT), dept (VARCHAR 50), file_path (VARCHAR 255), file_name (VARCHAR 255), file_type (VARCHAR 50), upload_date (DATETIME DEFAULT CURRENT_TIMESTAMP), PRIMARY KEY (id), KEY idx_id_template, KEY idx_id_khsanxuat, KEY idx_dept.
- [x] THE SYSTEM SHALL use ENGINE=InnoDB and CHARSET=utf8mb4 for both tables.

---

## Data Models

### dept_templates Table
See `file_templates.php:68-77` for schema definition.

**Columns**:
- `id`: Auto-increment primary key
- `dept`: Department code (VARCHAR 50)
- `template_name`: Template display name (VARCHAR 100)
- `template_description`: Optional description (TEXT)
- `created_at`: Creation timestamp
- `updated_at`: Last update timestamp

**Indexes**: idx_dept

---

### dept_template_files Table
See `file_templates.php:88-101` for schema definition.

**Columns**:
- `id`: Auto-increment primary key
- `id_template`: Foreign key to dept_templates
- `id_khsanxuat`: Foreign key to production order
- `dept`: Department code (VARCHAR 50)
- `file_path`: Server file path (VARCHAR 255)
- `file_name`: Original uploaded filename (VARCHAR 255)
- `file_type`: Categorized type (image/pdf/excel/word)
- `upload_date`: Upload timestamp

**Indexes**: idx_id_template, idx_id_khsanxuat, idx_dept

---

## File Type Constraints

### Allowed Extensions

**Images**: jpg, jpeg, png, gif, bmp, tif, tiff, webp  
**Documents**: pdf, xls, xlsx, doc, docx

**Validation Logic**: See `file_templates.php:184`

### Size Limits

- **Maximum file size**: 30MB (30,485,760 bytes)
- **Validation**: See `file_templates.php:182`

### Storage Pattern

**Directory structure**:
```
template_files/
  {dept}/
    {id_khsanxuat}/
      template_{id_template}/
        {safe_style}_{dept}_{timestamp}_{index}.{ext}
```

**Implementation**: See `file_templates.php:48-62, 164-191`

---

## Error Handling

### Validation Errors

| Condition | Error Message | Reference |
|-----------|---------------|-----------|
| Missing template selection | "Vui lòng chọn template cho file." | file_templates.php:158 |
| Empty template name | "Vui lòng nhập tên template." | file_templates.php:123 |
| Duplicate template name | "Template '{name}' đã tồn tại trong phòng ban này." | file_templates.php:134 |
| File too large | "File {name} quá lớn. Giới hạn 30MB!" | file_templates.php:221 |
| Invalid file extension | "File {name} không đúng định dạng..." | file_templates.php:218 |
| Missing required params | "Thiếu thông tin cần thiết" | delete_template_file.php:24 |
| File not found for deletion | "Không tìm thấy file cần xóa" | delete_template_file.php:39 |
| No files to download | "Không có file nào để tải xuống" | download_all_files.php:89 |
| Product not found | "Không tìm thấy mã hàng" | download_all_files.php:61 |

---

## Assumptions Made During Sync

| Decision | Chosen Approach | Reasoning |
|----------|-----------------|-----------|
| Feature Scope | Template & File Management (single workflow) | All components (upload, view, download, delete) are part of unified document management workflow for production orders |
| Priority Inference | User Stories 1, 2, 3, 7 are P1; Stories 4, 5, 6 are P2 | Core template creation and file upload are MVP; bulk download and deletion are enhancements |
| Spec Level | Level 1 (requirements.md only) | Implementation is straightforward CRUD with file handling; no complex architecture requiring design.md |
| File Size Limit | 30MB across all file types | Code applies single 30MB limit (line 182); UI message mentions contacting IT to increase |
| File Type Categories | 4 categories: image, pdf, excel, word | Inferred from categorization logic in file_templates.php:197-206 |
| Department Names | Use Vietnamese display names | Dept codes (kehoach, cat, may) mapped to friendly names in factory_templates.php:71-84 |
| Success Indicators | Message type 'success' or 'error' | Inferred from message_type variable usage throughout file_templates.php |

---

## Implementation Notes

**Status**: Synced from existing implementation on Thu Jan 29 2026

**Completeness**: All acceptance criteria marked complete [x] because they describe existing, deployed functionality.

**Files Referenced**:
- Main UI: `file_templates.php`
- Factory overview: `factory_templates.php`
- Bulk download: `download_all_files.php`
- File deletion: `delete_template_file.php`

**Integration Points**:
- Production order system: `khsanxuat` table (stt, style, po, dept)
- Department index pages: `indexdept1.php:651-661` (file count badges)

**Next Steps**: 
- Ready for `spec-validate` to verify implementation matches requirements
- Consider adding tests for file upload validation edge cases
- May need `design.md` if refactoring to extract reusable file handling components
