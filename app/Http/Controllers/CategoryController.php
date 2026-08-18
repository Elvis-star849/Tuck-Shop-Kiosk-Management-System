<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    public function index(): View
    {
        $categories = Category::query()->withCount('products')->orderBy('name')->paginate(15);

        return view('categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', 'unique:categories,name']]);
        $category = Category::query()->create($data);
        AuditLog::record('category.created', 'Admin added category "'.$category->name.'"', $category);

        return redirect()->route('categories.index')->with('success', 'Category saved.');
    }

    public function show(Category $category): RedirectResponse
    {
        return redirect()->route('categories.edit', $category);
    }

    public function edit(Category $category): View
    {
        return view('categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80', 'unique:categories,name,'.$category->id]]);
        $old = $category->name;
        $category->update($data);
        AuditLog::record('category.updated', 'Category renamed from "'.$old.'" to "'.$category->name.'"', $category, 'name', $old, $category->name);

        return redirect()->route('categories.index')->with('success', 'Category updated.');
    }

    public function destroy(Category $category): RedirectResponse
    {
        if ($category->products()->exists()) {
            return back()->with('error', 'This category is in use and cannot be deleted.');
        }

        $name = $category->name;
        $category->delete();
        AuditLog::record('category.deleted', 'Admin deleted category "'.$name.'"');

        return redirect()->route('categories.index')->with('success', 'Category deleted.');
    }
}
