<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\HomepageConfig;
use App\Models\HomepageSection;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class HomepageConfigController extends Controller
{
    public function index()
    {
        $configs = HomepageConfig::withCount('sections')->latest()->get();

        return Inertia::render('admin/homepage/index', [
            'configs' => $configs,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/homepage/create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_key' => 'required|string|max:64|unique:homepage_configs,client_key|regex:/^[a-z0-9\-_]+$/',
            'label'      => 'required|string|max:255',
            'is_active'  => 'boolean',
        ]);

        $config = HomepageConfig::create($data);

        return redirect()->route('admin.homepage.edit', $config)->with('success', 'Client config created.');
    }

    public function edit(HomepageConfig $config)
    {
        $config->load('sections');

        return Inertia::render('admin/homepage/edit', [
            'config'     => $config,
            'categories' => Category::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
            'brands'     => Brand::select('id', 'name')->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, HomepageConfig $config)
    {
        $data = $request->validate([
            'client_key' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9\-_]+$/', Rule::unique('homepage_configs')->ignore($config->id)],
            'label'      => 'required|string|max:255',
            'is_active'  => 'boolean',
        ]);

        $config->update($data);

        return back()->with('success', 'Config updated.');
    }

    public function destroy(HomepageConfig $config)
    {
        $config->delete();

        return redirect()->route('admin.homepage.index')->with('success', 'Config deleted.');
    }

    public function storeSection(Request $request, HomepageConfig $config)
    {
        $base = $request->validate([
            'type'      => 'required|in:featured_category,banner,hero,product_carousel,brand_showcase',
            'title'     => 'required|string|max:255',
            'is_active' => 'boolean',
            'settings'  => 'array',
        ]);

        $settings = $this->validateSettings($request, $base['type']);

        $maxOrder = $config->sections()->max('sort_order') ?? -1;

        $config->sections()->create([
            'type'       => $base['type'],
            'title'      => $base['title'],
            'is_active'  => $base['is_active'] ?? true,
            'settings'   => $settings,
            'sort_order' => $maxOrder + 1,
        ]);

        return back()->with('success', 'Section added.');
    }

    public function updateSection(Request $request, HomepageConfig $config, HomepageSection $section)
    {
        abort_if($section->homepage_config_id !== $config->id, 404);

        $base = $request->validate([
            'type'      => 'required|in:featured_category,banner,hero,product_carousel,brand_showcase',
            'title'     => 'required|string|max:255',
            'is_active' => 'boolean',
            'settings'  => 'array',
        ]);

        $settings = $this->validateSettings($request, $base['type']);

        $section->update([
            'type'      => $base['type'],
            'title'     => $base['title'],
            'is_active' => $base['is_active'] ?? $section->is_active,
            'settings'  => $settings,
        ]);

        return back()->with('success', 'Section updated.');
    }

    public function destroySection(HomepageConfig $config, HomepageSection $section)
    {
        abort_if($section->homepage_config_id !== $config->id, 404);

        $section->delete();

        return back()->with('success', 'Section removed.');
    }

    public function reorderSections(Request $request, HomepageConfig $config)
    {
        $request->validate([
            'sections'             => 'required|array',
            'sections.*.id'        => 'required|integer',
            'sections.*.sort_order' => 'required|integer|min:0',
        ]);

        $idsForConfig = $config->sections()->pluck('id')->all();

        foreach ($request->sections as $item) {
            if (in_array($item['id'], $idsForConfig)) {
                HomepageSection::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        }

        return back()->with('success', 'Order saved.');
    }

    private function validateSettings(Request $request, string $type): array
    {
        return match ($type) {
            'featured_category' => $request->validate([
                'settings.category_id'    => 'required|integer|exists:categories,id',
                'settings.product_limit'  => 'integer|min:1|max:50',
            ])['settings'] ?? [],

            'banner' => $request->validate([
                'settings.image_url'  => 'required|string|max:2048',
                'settings.link_url'   => 'nullable|string|max:2048',
                'settings.subtitle'   => 'nullable|string|max:255',
            ])['settings'] ?? [],

            'hero' => $request->validate([
                'settings.subtitle'  => 'nullable|string|max:500',
                'settings.cta_text'  => 'nullable|string|max:100',
                'settings.cta_url'   => 'nullable|string|max:2048',
                'settings.image_url' => 'nullable|string|max:2048',
            ])['settings'] ?? [],

            'product_carousel' => $request->validate([
                'settings.filter' => 'required|in:new,sale,featured',
                'settings.limit'  => 'integer|min:1|max:50',
            ])['settings'] ?? [],

            'brand_showcase' => $request->validate([
                'settings.brand_ids'   => 'required|array|min:1',
                'settings.brand_ids.*' => 'integer|exists:brands,id',
            ])['settings'] ?? [],

            default => [],
        };
    }
}
