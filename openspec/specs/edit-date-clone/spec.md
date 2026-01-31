## MODIFIED Requirements

### Requirement: Form page styling

The system SHALL use the standardized form-page component system for all styling instead of inline CSS.

#### Scenario: CSS stylesheet loading
- **WHEN** user loads edit_date_clone.php page
- **THEN** system SHALL load assets/css/form-page.css stylesheet
- **THEN** system SHALL NOT contain any inline <style> tag with custom CSS

#### Scenario: CSS isolation
- **WHEN** edit_date_clone.php renders
- **THEN** form content SHALL be wrapped in .form-page-component class for CSS isolation

### Requirement: Data display horizontal table layout

The system SHALL display product information in a horizontal table format consistent with indexdept.php layout.

#### Scenario: Table structure
- **WHEN** user views product information section
- **THEN** system SHALL render an HTML table with class `data-table`
- **THEN** table SHALL have 7 columns: Xưởng, Line, PO, Style, Số lượng, Ngày vào, Ngày ra
- **THEN** table header SHALL have background color #003366 (Navy Blue) from style.css

#### Scenario: Header row
- **WHEN** user views table header
- **THEN** system SHALL display column headers in `<thead>` with `<th>` elements
- **THEN** headers SHALL have white text color on navy blue background

#### Scenario: Data row
- **WHEN** user views table body
- **THEN** system SHALL display product data in single `<tr>` with `<td>` elements
- **THEN** all values SHALL be escaped using htmlspecialchars() for security

#### Scenario: Responsive behavior
- **WHEN** user views page on screen width <= 768px
- **THEN** table SHALL be horizontally scrollable (overflow-x: auto)

#### Scenario: Consistency with indexdept.php
- **WHEN** comparing with indexdept.php data table
- **THEN** both pages SHALL use same `.data-table` CSS class
- **THEN** both pages SHALL have matching header colors and typography

### Requirement: Theme color compliance

The system SHALL use the Cyan/Slate color theme defined in the form-page CSS variables.

#### Scenario: Primary button color
- **WHEN** user views submit button
- **THEN** button background color SHALL be #0891b2 (Cyan-600)
- **THEN** button SHALL NOT use #003366

#### Scenario: Focus state color
- **WHEN** user focuses on form input
- **THEN** input border color SHALL change to #0891b2
- **THEN** input SHALL have box-shadow using Cyan color

### Requirement: Button spacing

The system SHALL use standardized spacing between buttons.

#### Scenario: Form actions layout
- **WHEN** user views form action buttons
- **THEN** gap between buttons SHALL be 16px (using --form-page-spacing-md variable)

### Requirement: Form note component

The system SHALL use the render_form_note() PHP component for informational notes.

#### Scenario: Form note rendering
- **WHEN** edit_date_clone.php renders the note section
- **THEN** system SHALL call render_form_note() with type 'info'
- **THEN** system SHALL NOT render manual HTML for form note

### Requirement: Date picker icon

The system SHALL display a calendar icon for date input fields.

#### Scenario: Date input icon element
- **WHEN** user views date input field
- **THEN** system SHALL display a clickable calendar icon inside the input container
- **THEN** icon element SHALL have class .date-input-icon

#### Scenario: Date icon click behavior  
- **WHEN** user clicks on date input icon
- **THEN** system SHALL open the jQuery UI datepicker

### Requirement: Typography

The system SHALL use Fira Sans font family for all text.

#### Scenario: Font loading
- **WHEN** edit_date_clone.php loads
- **THEN** page SHALL use font-family from --form-page-font CSS variable
- **THEN** Fira Sans font SHALL be loaded via form-page.css @import

### Requirement: Page structure standardization

The system SHALL follow the standard page structure used across the project.

#### Scenario: Page component order
- **WHEN** edit_date_clone.php renders
- **THEN** page SHALL follow this structure order:
  1. Header component (components/header.php)
  2. Alert messages (success/error)
  3. Data table (product information)
  4. Form section (inputs and actions)
  5. Modal component (for success feedback)
  6. Scripts (jQuery, header.js)

#### Scenario: Header configuration
- **WHEN** page renders header
- **THEN** header SHALL use components/header.php with configuration array
- **THEN** header SHALL include title, logo, back button action

#### Scenario: Container layout
- **WHEN** page renders main content
- **THEN** content SHALL be wrapped in `.container` class
- **THEN** container SHALL have max-width and centered layout

#### Scenario: Modal feedback
- **WHEN** form submits successfully
- **THEN** system SHALL show success modal using render_modal() component
- **THEN** modal SHALL support auto-redirect with countdown

### Requirement: CSS dependencies

The system SHALL load CSS files in correct order for proper styling.

#### Scenario: Stylesheet order
- **WHEN** page loads stylesheets
- **THEN** system SHALL load in this order:
  1. style.css (base styles, .data-table)
  2. style2.css (additional base styles)
  3. Font Awesome (icons)
  4. assets/css/header.css (header component)
  5. jQuery UI CSS (datepicker)
  6. assets/css/form-page.css (form components)

#### Scenario: Script dependencies
- **WHEN** page loads scripts
- **THEN** system SHALL load jQuery before jQuery UI
- **THEN** system SHALL load header.js at end of body
