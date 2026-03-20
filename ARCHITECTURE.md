# Architecture: tFPDF

## Purpose

A fork of FPDF that adds UTF-8 support and TrueType/OpenType font embedding for generating PDF documents in PHP. Extends FPDF's Latin-1 baseline with multi-byte character handling.

## Directory Structure

```
tfpdf.php          - Single-file library: the tFPDF class (extends FPDF)
font/              - Bundled TrueType/OpenType font files and font metrics cache
ex.php             - Example script producing ex.pdf
```

## Key Design Decisions

- **Single-file distribution**: The entire library is one PHP file (`tfpdf.php`), matching FPDF's distribution model. No autoloading or namespaces are required.
- **UTF-8 input**: Accepts UTF-8 strings and converts them to the glyph IDs needed by embedded TrueType fonts, whereas stock FPDF only handles ISO-8859-1.
- **TrueType embedding**: Embeds font subset data directly in the PDF binary so the output is self-contained and renders correctly on any viewer without the reader having the font installed.
- **FPDF compatibility**: Retains FPDF's full API (`AddPage`, `SetFont`, `Cell`, `MultiCell`, `Output`, etc.), so existing FPDF code only needs to swap the class name.

## Extension Points

- Drop additional TrueType font files into the `font/` directory and call `AddFont()` to register them.
- Subclass `tFPDF` to override layout methods exactly as you would with FPDF.

## Dependency Flow

```
new tFPDF('P', 'mm', 'A4')
  └─> AddFont('DejaVu', '', 'DejaVuSansCondensed.ttf') — load TrueType metrics
  └─> AddPage() / SetFont() / Cell() / MultiCell()     — build page content
  └─> Output('file.pdf', 'F')                          — write PDF binary
```
