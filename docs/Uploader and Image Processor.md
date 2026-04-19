📦 UltraLean Uploader & Image Processor

A lightweight, high-performance file upload and image processing system designed for near raw PHP speed with a clean, fluent API.

✅ No dependencies
✅ No containers / no reflection
✅ Secure by default
✅ Fluent (Laravel-like)
✅ Fully decoupled image processing
🚀 Installation

Just include the classes:

use UltraLean\Core\Media\Uploader;
use UltraLean\Core\Media\ImageProcessor;
⚙️ Configuration

Uploader automatically uses:

config('uploads_path')

Example:

'uploads_path' => $webroot . '/uploads',

👉 You can override this using ->to().

🧠 Basic Usage
Simple upload
Uploader::file('avatar')->save();
Upload image only
Uploader::file('avatar')
    ->image()
    ->save();
Upload with custom path
Uploader::file('avatar')
    ->to('/var/www/uploads/avatars')
    ->save();
🎯 Fluent API Methods
📁 File Source
file(string|array $input)

Initialize uploader.

Uploader::file('avatar');
📂 Destination
to(string $path)

Set upload directory.

->to('/uploads/users')
📏 File Size
max(int $mb)

Set max file size in MB.

->max(5) // 5MB
📄 File Type Filters
image()

Allow only images:

->image()
video()

Allow only videos:

->video()
document()

Allow documents:

->document()
allow(array $mimeTypes) (if added later)

Custom MIME types.

🏷️ File Naming
prefix(string $value)
->prefix('user')
postfix(string $value)
->postfix('profile')
autoPrefix(bool $state = true)

Adds automatic type-based prefix:

img_ for images
vid_ for videos
doc_ for documents
->autoPrefix()
autoPostfix(bool $state = true)

Adds timestamp postfix.

->autoPostfix()
date(string $format = 'Ymd')

Adds date into filename.

->date()            // 20260419
->date('Y-m-d')     // 2026-04-19
🧾 Example filename
img_user_20260419_avatar_a1b2c3d4e5_1713520000_profile.jpg
🧹 File Management
deleteOld(?string $file)

Deletes old file after upload.

->deleteOld($oldFile)
🖼️ Image Processing (Optional)
🔌 Attach processor
->processWith($processor)

👉 Processing only runs for images, automatically ignored for others.

🧩 ImageProcessor
Create processor
$processor = new ImageProcessor();
📏 Resize
$processor->resize(1200, 1200);
🖼️ Thumbnail
$processor->thumbnail(300, 300);

Creates:

image.jpg
image_thumb.jpg
🎚️ Quality
$processor->quality(80);
🔄 Orientation Fix
$processor->orient();

✔ Fixes mobile rotated images (EXIF)

🔁 Full Processing Example
$processor = (new ImageProcessor())
    ->resize(1200, 1200)
    ->thumbnail(300, 300)
    ->quality(80)
    ->orient();
🔗 Full Upload Example
$processor = (new ImageProcessor())
    ->resize(1200, 1200)
    ->thumbnail(300, 300)
    ->quality(80)
    ->orient();

$result = Uploader::file('photo')
    ->image()
    ->max(3)
    ->prefix('user')
    ->postfix('profile')
    ->date()
    ->autoPostfix()
    ->to('/uploads/users')
    ->deleteOld($oldFile)
    ->processWith($processor)
    ->save();
📤 Return Response
Success
[
    'success' => true,
    'files' => [
        'img_user_...jpg'
    ]
]
Error
[
    'success' => false,
    'error' => 'File too large'
]
🔒 Security Features
✅ Uses finfo (real MIME detection)
✅ Blocks invalid uploads
✅ Prevents direct file spoofing
✅ Safe filename generation
✅ Randomized file names
✅ Upload validation enforced
⚡ Performance Notes
No dependency injection
No service container
No reflection
Single-pass processing
Minimal function calls

👉 Designed for maximum speed

🚫 Automatic Safeguards

Even if developer does:

->processWith($processor)

Uploader will:

if (!str_starts_with($mime, 'image/')) {
    // skip processing
}

✔ Prevents misuse
✔ No crashes
✔ No wasted CPU

🧠 Best Practices
Store uploads outside public root if possible
Always use ->image() or ->document() filters
Limit file size using ->max()
Use thumbnails for large images
Use prefix/postfix for organization
📌 Minimal Example (Fastest)
Uploader::file('file')->save();
📌 Production Example
Uploader::file('avatar')
    ->image()
    ->max(2)
    ->prefix('user')
    ->date()
    ->save();
🧱 Architecture
Uploader          → upload + validation + naming
ImageProcessor    → image-only processing

✔ Fully decoupled
✔ Reusable
✔ Extendable