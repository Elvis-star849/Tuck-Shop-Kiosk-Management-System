<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        $date = $request->input('date');
        $expenses = Expense::query()
            ->with('user')
            ->when($date, fn ($query) => $query->whereDate('expense_date', $date))
            ->latest('expense_date')
            ->paginate(15)
            ->withQueryString();

        return view('expenses.index', compact('expenses', 'date'));
    }

    public function create(): View
    {
        return view('expenses.create');
    }

    public function store(Request $request): RedirectResponse
    {
        Expense::query()->create($this->validated($request) + ['user_id' => $request->user()->id]);

        return redirect()->route('expenses.index')->with('success', 'Expense recorded.');
    }

    public function show(Expense $expense): RedirectResponse
    {
        return redirect()->route('expenses.index');
    }

    public function edit(Expense $expense): View
    {
        return view('expenses.edit', compact('expense'));
    }

    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $expense->update($this->validated($request));

        return redirect()->route('expenses.index')->with('success', 'Expense updated.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $expense->delete();

        return redirect()->route('expenses.index')->with('success', 'Expense deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'expense_date' => ['required', 'date'],
            'category' => ['required', 'in:'.implode(',', Expense::CATEGORIES)],
            'description' => ['required', 'string', 'max:200'],
            'amount' => ['required', 'numeric', 'min:0.01'],
        ]);
    }
}
