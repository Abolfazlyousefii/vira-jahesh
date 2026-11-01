<?php

namespace Modules\Storefront\Support;

use Illuminate\Support\Facades\DB;
use Modules\Storefront\Models\StorefrontSection;

final class StorefrontSections
{
    public const DEFAULTS = [
        'hero' => [
            'type' => 'hero',
            'settings' => [
                'title' => 'از اولین ارتباط، تا یک خرید ماندگار.',
                'description' => 'فروش حضوری، فروشگاه اینترنتی و ارتباط با مشتریان؛ کنار هم در یک تجربهٔ یکپارچه.',
                'button' => 'آشنایی با امکانات',
                'href' => '#site-features',
            ],
        ],
        'features' => [
            'type' => 'features',
            'settings' => [
                'first' => 'مشتریان یکپارچه',
                'second' => 'انبار و فروش مشترک',
                'third' => 'گسترش ماژولار',
            ],
        ],
        'cta' => [
            'type' => 'cta',
            'settings' => [
                'title' => 'برای رشد فروشگاه، آمادهٔ یکپارچگی هستید؟',
                'description' => 'ویرا مرحله‌به‌مرحله به یک ابزار جامع و قابل اعتماد تبدیل می‌شود.',
            ],
        ],
    ];

    public function all(): array
    {
        $records = StorefrontSection::query()->get()->keyBy('section_key');

        return collect(self::DEFAULTS)->map(function (array $definition, string $key) use ($records): array {
            $record = $records->get($key);
            $index = array_search($key, array_keys(self::DEFAULTS), true);

            return [
                'key' => $key,
                'type' => $definition['type'],
                'settings' => array_replace($definition['settings'], $record?->settings ?? []),
                'position' => $record?->position ?? $index,
                'is_visible' => $record?->is_visible ?? true,
                'image_url' => $key === 'hero' ? $record?->getFirstMediaUrl('hero') : null,
            ];
        })->sortBy([['position', 'asc']])->values()->all();
    }

    public function find(string $key): array
    {
        abort_unless(array_key_exists($key, self::DEFAULTS), 404);

        return collect($this->all())->firstWhere('key', $key);
    }

    public function persist(array $section): StorefrontSection
    {
        abort_unless(array_key_exists($section['key'], self::DEFAULTS), 404);

        return StorefrontSection::query()->updateOrCreate(
            ['section_key' => $section['key']],
            [
                'type' => self::DEFAULTS[$section['key']]['type'],
                'settings' => $section['settings'],
                'position' => $section['position'],
                'is_visible' => $section['is_visible'],
            ]
        );
    }

    public function reorder(array $keys): void
    {
        DB::transaction(function () use ($keys): void {
            foreach ($keys as $position => $key) {
                $section = $this->find($key);
                $section['position'] = $position;
                $this->persist($section);
            }
        });
    }
}
