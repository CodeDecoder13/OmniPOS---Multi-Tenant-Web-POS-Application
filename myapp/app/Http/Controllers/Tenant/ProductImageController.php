<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductImageController extends Controller
{
    public function show(Request $request, string $tenantSlug, int $product, string $filename): StreamedResponse
    {
        $tenant = $request->attributes->get('current_tenant');

        $product = Product::forTenant($tenant)->findOrFail($product);

        abort_unless(
            $product->image_path && basename($product->image_path) === $filename,
            404
        );

        $disk = Storage::disk(config('filesystems.product_disk', 'local'));

        abort_unless($disk->exists($product->image_path), 404);

        $mimeType = $disk->mimeType($product->image_path) ?: 'application/octet-stream';
        $etag = md5($disk->lastModified($product->image_path) . $product->image_path);

        return $disk->response($product->image_path, $filename, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, max-age=86400',
            'ETag' => "\"{$etag}\"",
        ]);
    }
}
