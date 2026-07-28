# Automated Employee ID Maker

A PHP/MySQL application for XAMPP that manages employee records and generates separate front and back employee ID images based on the supplied red Mitsubishi Motors General Santos template.

## Included

- Secure administrator login
- Employee create, edit, view, search, and active/inactive status
- Seven departments: Accounting, Sales, Service, Information Technology, Human Resource, CNC, and BRP
- Photo and signature uploads
- Live SVG front/back ID preview
- Company filters for MGSC, MKC, FUSO, and NGSC employee records
- Selectable Mitsubishi General Santos, Mitsubishi Kidapawan, FUSO General Santos, and NTRprising/Hyundai General Santos ID templates
- Draggable front-photo placement with size, zoom, and crop controls
- Separate 600 × 960 PNG downloads for front and back, matching the supplied PSD
- Separate front/back browser printing
- Record-monitoring audit trail for:
  - Sign-ins and sign-outs
  - Employee creation
  - Employee edits, including before/after JSON snapshots
  - Employee status changes
  - Front/back ID downloads and printing
- Pending/done workflow on Employee Records, including completion user and time
- Dashboard totals and department summaries

The project intentionally does **not** include a three-ID print sheet.

## Photoshop source assets

The exact front/back artwork extracted from the supplied Photoshop document is in
`assets/id-template`. The accompanying `manifest.json` records the original card
origins, layer coordinates, dimensions, and type assignments. The MMC Office
Regular, Medium, and Bold fonts used by the PSD are stored in `assets/fonts` and
embedded into generated SVG/PNG output.

The Kidapawan variant uses the supplied Kidapawan logo artwork and prints
`Prk. Mangga` / `Brgy. Paco 115, Kidapawan City` as its company address.

The FUSO variant is exported from the supplied layered `id fuso(remake).psd`.
Its front and back artwork, FUSO logos, gradients, address, labels, and
Photoshop coordinates are preserved while employee-specific fields remain live.

The NGSC variant is exported from the supplied layered
`ID MICEI HYUNDAI filtered(remake).psd`, including the Hyundai and NTRprising
logos, original blue artwork, Hyundai fonts, information panel, and placements.

## Installation in XAMPP

1. Copy the `automated_id_maker` folder to:

   `C:\xampp\htdocs\automated_id_maker`

2. Start **Apache** and **MySQL** in XAMPP.
3. Open phpMyAdmin and import `setup.sql`.
4. Confirm the database settings in `config/config.php`:

   - Database: `automated_id_maker`
   - User: `root`
   - Password: blank by default in XAMPP

5. Open:

   `http://localhost/automated_id_maker/`

For an existing installation, import `migrate_add_employee_done_status.sql` once
in phpMyAdmin to enable the Employee Records completion workflow.
Import `migrate_add_employee_released_status.sql` once to add release tracking
after an employee ID is completed.
Also import `migrate_add_employee_company_code.sql` once to enable MGSC, MKC,
FUSO, and NGSC company assignment and filtering.

## Initial login

- Username: `admin`
- Password: `admin123`

Change this password before production use by replacing the password hash in the database with a new PHP `password_hash()` value or by adding a user-management screen.

## Server requirements

- PHP 8.1 or newer
- MySQL 5.7+ or MariaDB 10.4+
- PHP extensions normally included with XAMPP: PDO MySQL and Fileinfo
- Writable `uploads/photos` and `uploads/signatures` folders

## Data and privacy

The database contains government identification numbers, birth dates, and emergency-contact information. Use HTTPS on a live server, restrict access to authorized staff, and include the database and upload folders in your backup plan.
