<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Where user uploads (chat screenshots, receipt logos) live.
 *
 * Railway's local disk is wiped on every deploy, so with S3 configured these go to the same bucket as
 * product images (private, served through short-lived signed links). Without S3 they use the public
 * disk as before. Override with UPLOADS_DISK.
 */
final class UploadStorage
{
    public static function disk(): string
    {
        return config('filesystems.uploads_disk', 'public');
    }

    /** A URL the browser can load: a signed one-hour link on S3, the normal storage URL otherwise. */
    public static function url(string $path): string
    {
        $disk = self::disk();

        if ($disk === 's3') {
            return Storage::disk('s3')->temporaryUrl($path, now()->addMinutes(60));
        }

        return Storage::disk($disk)->url($path);
    }

    public static function delete(?string $path): void
    {
        if ($path) {
            Storage::disk(self::disk())->delete($path);
        }
    }
}
