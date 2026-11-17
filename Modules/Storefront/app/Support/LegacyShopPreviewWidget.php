<?php

namespace Modules\Storefront\Support;

final class LegacyShopPreviewWidget
{
    public function __construct(public readonly string $key) {}

    public function option(string $key, mixed $default = null): mixed
    {
        return $default;
    }
}
