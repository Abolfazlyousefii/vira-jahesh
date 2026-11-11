<?php

namespace Modules\Catalog\Support;

use Illuminate\Support\Facades\Storage;
use Spatie\MediaLibrary\HasMedia;

final class ProductImageUrl
{
    public function for(HasMedia $record): string
    {
        $media = $record->getFirstMedia('product-image');

        if ($media === null) {
            return '';
        }

        // Public files must follow the browser's current origin, not APP_URL.
        // This keeps local images working when Artisan is served on another port.
        if ($media->disk === 'public' && config('filesystems.disks.public.driver') === 'local') {
            $path = ltrim($media->getPathRelativeToRoot(), '/');

            if ($path === '' || ! Storage::disk('public')->exists($path)) {
                return '';
            }

            $encodedPath = implode('/', array_map('rawurlencode', explode('/', $path)));

            return '/storage/'.$encodedPath;
        }

        // Preserve the provider's own URL for future S3 / remote disks.
        return $media->getUrl();
    }
}
