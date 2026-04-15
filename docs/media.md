# 📦 UltraLean File Uploader (v2)

A minimal, high-performance, and secure file upload system built with pure PHP.

Designed for:
- ⚡ Maximum speed (near native PHP performance)
- 🧠 Low overhead (no frameworks, no unnecessary abstractions)
- 🔒 Strong security (real MIME validation, sanitized filenames)
- 🧩 Clean extensibility (modular classes)

---

# 🚀 Features

- ✅ Single & multiple file uploads
- ✅ Real MIME type detection (`finfo`)
- ✅ Upload error handling (`UPLOAD_ERR_*`)
- ✅ Secure filename sanitization
- ✅ Random unique filenames (no collision checks needed)
- ✅ Optional image processing (resize + EXIF auto-rotation)
- ✅ Old file cleanup
- ✅ Minimal syscalls for maximum performance

---

# 📁 Installation

Just include the classes in your project:


UltraLean/Core/Media/


No dependencies required.

---

# ⚙️ Basic Usage

```php
use UltraLean\Core\Media\Uploader;
use UltraLean\Core\Media\FileNamer;
use UltraLean\Core\Media\UploadValidator;
use UltraLean\Core\Media\FileManager;
use UltraLean\Core\Media\ImageProcessor;

$result = Uploader::upload('file_input_name')
    ->to(__DIR__ . '/uploads')
    ->deleteOld($oldFile ?? null)
    ->save(
        new FileNamer(prefix: 'user', useDate: true),
        new UploadValidator(
            maxBytes: 5 * 1024 * 1024,
            allowed: ['image/jpeg', 'image/png', 'image/webp']
        ),
        new FileManager(),
        new ImageProcessor() // optional
    );

if (!$result['success']) {
    echo $result['error'];
} else {
    print_r($result['files']);
}
📥 HTML Example
<form method="POST" enctype="multipart/form-data">
    <input type="file" name="file_input_name[]" multiple>
    <button type="submit">Upload</button>
</form>
📊 Response Format
✅ Success
[
  'success' => true,
  'files' => [
    [
      'name' => 'img_user_20260411_file_ab12cd34ef.jpg',
      'size' => 245678,
      'mime' => 'image/jpeg'
    ]
  ]
]
❌ Failure
[
  'success' => false,
  'error' => 'File too large'
]
🔒 Security

This uploader includes:

✔ Real MIME detection via finfo (not browser spoofable)
✔ Upload error validation (UPLOAD_ERR_*)
✔ Sanitized filenames (no special characters)
✔ Safe file handling (move_uploaded_file)
✔ Optional MIME whitelist
🖼️ Image Processing (Optional)

When ImageProcessor is provided:

📸 Auto-resizes large images (max 1200x1200)
🔄 Fixes EXIF orientation (mobile photos)
🧠 Skips processing if not needed

Supports:

JPEG
PNG
WebP
🧩 Components
Uploader

Main entry point. Handles:

File iteration
Validation
Moving files
Processing
Cleanup
UploadValidator

Validates:

File size
MIME type
FileNamer

Generates unique filenames using:

Sanitized base name
Random suffix
Optional prefix and date
FileManager

Handles:

Directory creation
Safe file deletion
ImageProcessor

Handles:

EXIF orientation fix
Image resizing
⚡ Performance Notes
No collision checks (random naming is sufficient)
Minimal filesystem calls
Single finfo instance reused
Early exits on failure
No unnecessary loops or abstractions
🧠 Best Practices
Always set a MIME whitelist
Keep max file size reasonable
Use image processing only when needed
Store uploads outside public root (if possible)
Serve files via controlled endpoints
📁 Suggested Directory Structure
uploads/
  └── 2026/
      └── 04/

(You can extend FileNamer or Uploader to support this)

🔮 Possible Extensions
Chunked uploads (large files)
Cloud storage (S3, etc.)
Async image processing
File hashing (deduplication)
Upload progress tracking
🏁 Summary

UltraLean Uploader v2 provides:

⚡ Maximum performance
🔒 Strong security
🧼 Clean architecture
🧩 Easy extensibility