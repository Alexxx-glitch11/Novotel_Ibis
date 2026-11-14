<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Outlet;
use App\Services\AdminAuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $outlets = Outlet::where('is_active', true)->ordered()->get();
        $outlet = $outlets->firstWhere('outlets_id', $request->integer('outlet')) ?? $outlets->first();
        $categories = $outlet ? $outlet->menuCategories()->where('is_active', true)->orderBy('name')->get() : collect();
        $query = $outlet ? $outlet->menuItems()->with('category')->orderBy('name') : MenuItem::query()->whereRaw('1 = 0');
        if ($request->integer('category') > 0) {
            $query->where('menu_categories_id', $request->integer('category'));
        }
        if ($search = trim((string) $request->query('q'))) {
            $query->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%');
        }

        return view('admin.menu', ['outlets' => $outlets, 'outlet' => $outlet, 'categories' => $categories, 'items' => $query->get(), 'editing' => $request->integer('edit') ? MenuItem::with('category')->find($request->integer('edit')) : null]);
    }

    public function store(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $data = $this->validated($request);
        $path = $request->file('image')?->store('', 'menu_uploads');
        $data['image_path'] = $path ? 'uploads/menu-items/' . $path : null;
        unset($data['image']);
        $item = DB::transaction(function () use ($data, $request, $audit): MenuItem {
            $item = MenuItem::create($data);
            $audit->record($request, 'admin_menu_item_created', 'menu_item', $item->menu_items_id, null, $this->snapshot($item));
            return $item;
        });

        return redirect()->route('admin.menu.index', ['outlet' => $item->outlets_id])->with('success', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, MenuItem $menuItem, AdminAuditLogger $audit): RedirectResponse
    {
        $old = $this->snapshot($menuItem);
        $data = $this->validated($request, $menuItem);
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('', 'menu_uploads');
            $data['image_path'] = $path ? 'uploads/menu-items/' . $path : $menuItem->image_path;
        }
        unset($data['image'], $data['outlets_id']);
        DB::transaction(function () use ($menuItem, $data, $request, $audit, $old): void {
            $menuItem->update($data);
            $audit->record($request, 'admin_menu_item_updated', 'menu_item', $menuItem->menu_items_id, $old, $this->snapshot($menuItem->fresh()));
        });

        return redirect()->route('admin.menu.index', ['outlet' => $menuItem->outlets_id])->with('success', 'Menu berhasil diperbarui. Reservasi lama tetap menggunakan snapshot harga.');
    }

    public function storeCategory(Request $request, AdminAuditLogger $audit): RedirectResponse
    {
        $outletId = (int) $request->input('outlets_id');
        $data = $request->validate([
            'outlets_id' => ['required', 'integer', Rule::exists('outlets', 'outlets_id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:50', Rule::unique('menu_categories', 'name')->where('outlets_id', $outletId)],
        ]);
        $category = DB::transaction(function () use ($data, $request, $audit): MenuCategory {
            $category = MenuCategory::create($data + ['is_active' => true]);
            $audit->record($request, 'admin_menu_category_created', 'menu_category', $category->menu_categories_id, null, $category->only(['outlets_id', 'name', 'is_active']));
            return $category;
        });

        return redirect()->route('admin.menu.index', ['outlet' => $outletId])->with('success', 'Kategori berhasil ditambahkan.');
    }

    private function validated(Request $request, ?MenuItem $item = null): array
    {
        $outletId = (int) ($item?->outlets_id ?? $request->input('outlets_id'));
        $request->merge(['price' => (string) $request->input('price', '')]);
        $categoryRule = Rule::exists('menu_categories', 'menu_categories_id')->where('outlets_id', $outletId)->where('is_active', true);

        $data = $request->validate([
            'outlets_id' => ['required', 'integer', Rule::exists('outlets', 'outlets_id')->where('is_active', true), ...($item ? [Rule::in([$item->outlets_id])] : [])],
            'menu_categories_id' => ['required', 'integer', $categoryRule],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'regex:/^\d+$/', 'numeric', 'gt:0', 'max:9999999999.99'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_available' => ['nullable', 'boolean'],
        ]);
        unset($data['image']);
        $data['is_available'] = $request->boolean('is_available');
        $data['image_path'] = $item?->image_path;

        return $data;
    }

    private function snapshot(MenuItem $item): array
    {
        return $item->only(['outlets_id', 'menu_categories_id', 'name', 'description', 'price', 'image_path', 'is_available']);
    }
}
