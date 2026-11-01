<?php

namespace Modules\Storefront\Livewire;

use App\Support\ModuleAccess;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Modules\Storefront\Support\StorefrontSections;

#[Layout('components.layouts.panel')]
class StorefrontEditor extends Component
{
    use WithFileUploads;

    public string $selected = 'hero';

    public array $form = [];

    public $heroImage;

    public string $notice = '';

    public function mount(): void
    {
        $this->authorizeEditor();
        $this->selectSection('hero');
    }

    public function selectSection(string $key): void
    {
        $this->authorizeEditor();
        $this->selected = app(StorefrontSections::class)->find($key)['key'];
        $this->form = app(StorefrontSections::class)->find($key)['settings'];
        $this->heroImage = null;
        $this->notice = '';
    }

    public function save(): void
    {
        $this->authorizeEditor();
        $section = app(StorefrontSections::class)->find($this->selected);

        $rules = match ($this->selected) {
            'hero' => [
                'form.title' => ['required', 'string', 'max:120'],
                'form.description' => ['required', 'string', 'max:320'],
                'form.button' => ['required', 'string', 'max:60'],
                'form.href' => ['required', Rule::in(['#site-features', '#site-cta', '#site-top'])],
            ],
            'features' => [
                'form.first' => ['required', 'string', 'max:90'],
                'form.second' => ['required', 'string', 'max:90'],
                'form.third' => ['required', 'string', 'max:90'],
            ],
            'cta' => [
                'form.title' => ['required', 'string', 'max:120'],
                'form.description' => ['required', 'string', 'max:320'],
            ],
        };

        $validated = Validator::make(
            ['form' => $this->form, 'heroImage' => $this->heroImage],
            [...$rules, 'heroImage' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048']]
        )->validate();

        $section['settings'] = array_intersect_key($validated['form'], $section['settings']);
        $record = app(StorefrontSections::class)->persist($section);

        if ($this->selected === 'hero' && $this->heroImage) {
            $record->addMedia($this->heroImage->getRealPath())
                ->usingFileName($this->heroImage->hashName())
                ->toMediaCollection('hero', 'public');
            $this->heroImage = null;
        }

        $this->form = $section['settings'];
        $this->notice = 'تغییرات ذخیره و در سایت منتشر شد.';
    }

    public function toggleSection(string $key): void
    {
        $this->authorizeEditor();
        $section = app(StorefrontSections::class)->find($key);
        $section['is_visible'] = ! $section['is_visible'];
        app(StorefrontSections::class)->persist($section);
        $this->notice = 'وضعیت نمایش سکشن به‌روزرسانی شد.';
    }

    public function moveSection(string $key, string $direction): void
    {
        $this->authorizeEditor();
        app(StorefrontSections::class)->find($key);
        abort_unless(in_array($direction, ['up', 'down'], true), 422);

        $keys = array_column(app(StorefrontSections::class)->all(), 'key');
        $index = array_search($key, $keys, true);
        $target = $index + ($direction === 'up' ? -1 : 1);

        if ($target < 0 || $target >= count($keys)) {
            return;
        }

        [$keys[$index], $keys[$target]] = [$keys[$target], $keys[$index]];
        app(StorefrontSections::class)->reorder($keys);
        $this->notice = 'ترتیب سکشن‌ها ذخیره شد.';
    }

    private function authorizeEditor(): void
    {
        abort_unless(auth()->check() && auth()->user()->can('panel.access') && auth()->user()->can('storefront.manage'), 403);
        abort_unless(app(ModuleAccess::class)->available('Core') && app(ModuleAccess::class)->available('Storefront'), 404);
    }

    public function render()
    {
        return view('storefront::livewire.editor', [
            'sections' => app(StorefrontSections::class)->all(),
        ]);
    }
}
