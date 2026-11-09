<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\Outlet;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MenuController extends Controller
{
    public function index(Request $request): View
    {
        $outlets = Outlet::where('is_active', true)->orderBy('name')->get();
        $outlet = $outlets->firstWhere('outlets_id', (int) $request->query('outlet')) ?? $outlets->first();
        abort_if(! $outlet, 404, 'Belum ada outlet.');

        $categories = $outlet->menuCategories()->where('is_active', true)->orderBy('name')->get();
        $itemsQuery = $outlet->menuItems()->with('category')->orderBy('name');
        if ($categoryId = $request->integer('category')) {
            $itemsQuery->where('menu_categories_id', $categoryId);
        }
        if ($search = trim((string) $request->query('q'))) {
            $itemsQuery->where('name', 'like', '%' . addcslashes($search, '%_\\') . '%');
        }
        $menuItems = $itemsQuery->get();

        return view('manager.menu.index', compact('outlets', 'outlet', 'categories', 'menuItems'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $imagePath = $request->file('image')?->store('', 'menu_uploads');
        $data['image_path'] = $imagePath ? 'uploads/menu-items/' . $imagePath : null;
        $item = MenuItem::create($data);

        $this->audit($request, 'menu_item_created', 'menu_item', $item->menu_items_id, null, $this->snapshot($item));

        return $this->backTo($item->outlets_id)->with('success', 'Menu berhasil ditambahkan.');
    }

    public function update(Request $request, MenuItem $menuItem): RedirectResponse
    {
        $oldValues = $this->snapshot($menuItem);
        $data = $this->validated($request, $menuItem);
        if ($request->hasFile('image')) {
            $data['image_path'] = 'uploads/menu-items/' . $request->file('image')->store('', 'menu_uploads');
        }
        $menuItem->update($data);

        $this->audit($request, 'menu_item_updated', 'menu_item', $menuItem->menu_items_id, $oldValues, $this->snapshot($menuItem));

        return $this->backTo($menuItem->outlets_id)->with('success', 'Menu berhasil diperbarui.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $outletId = (int) $request->input('outlets_id');
        $data = $request->validate([
            'outlets_id' => ['required', 'integer', Rule::exists('outlets', 'outlets_id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:50', Rule::unique('menu_categories', 'name')->where('outlets_id', $outletId)],
        ], [
            'name.unique' => 'Nama kategori tersebut sudah ada di outlet ini.',
        ]);

        $category = MenuCategory::create($data + ['is_active' => true]);
        $this->audit($request, 'menu_category_created', 'menu_category', $category->menu_categories_id, null, $category->only(['outlets_id', 'name', 'is_active']));

        return $this->backTo($category->outlets_id)->with('success', 'Kategori berhasil ditambahkan.');
    }

    private function validated(Request $request, ?MenuItem $menuItem = null): array
    {
        $outletId = (int) $request->input('outlets_id');
        $price = preg_replace('/\D+/', '', (string) $request->input('price', ''));
        $request->merge(['price' => $price]);
        $categoryRule = Rule::exists('menu_categories', 'menu_categories_id')
            ->where('outlets_id', $outletId)
            ->where('is_active', true);

        $data = $request->validate([
            'outlets_id' => ['required', 'integer', Rule::exists('outlets', 'outlets_id')->where('is_active', true)],
            'menu_categories_id' => ['required', 'integer', $categoryRule],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'gt:0', 'max:9999999999.99'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'menu_categories_id.exists' => 'Kategori harus berasal dari outlet yang dipilih dan masih aktif.',
            'image.max' => 'Ukuran gambar maksimal 5 MB.',
            'image.mimes' => 'Format gambar harus JPG, PNG, atau WebP.',
        ]);

        $data['is_available'] = $request->boolean('is_available');
        unset($data['image']);

        if ($menuItem) {
            unset($data['outlets_id']);
        }

        return $data;
    }

    private function backTo(int $outletId): RedirectResponse
    {
        return redirect()->route('manager.menu.index', ['outlet' => $outletId]);
    }

    private function snapshot(MenuItem $item): array
    {
        return $item->only(['outlets_id', 'menu_categories_id', 'name', 'description', 'price', 'image_path', 'is_available']);
    }

    private function audit(Request $request, string $action, string $entityType, int $entityId, ?array $oldValues, ?array $newValues): void
    {
        AuditLog::create([
            'users_id' => $request->user()?->users_id,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
