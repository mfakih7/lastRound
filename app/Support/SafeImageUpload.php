<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class SafeImageUpload
{
    /** @var array<int, string> */
    public const EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public static function extension(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->guessExtension());

        if (! in_array($extension, self::EXTENSIONS, true)) {
            $extension = 'jpg';
        }

        return $extension === 'jpeg' ? 'jpg' : $extension;
    }

    public static function filename(UploadedFile $file): string
    {
        return Str::uuid()->toString().'.'.self::extension($file);
    }
}
