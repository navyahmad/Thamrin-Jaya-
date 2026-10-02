<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Inquiry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', Inquiry::class);
        $filters = $request->validate(['company_id' => ['nullable', 'integer', 'exists:companies,id'], 'status' => ['nullable', Rule::in(['new', 'in_progress', 'resolved'])], 'q' => ['nullable', 'string', 'max:100']]);
        $query = Inquiry::with('company')->latest();
        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['q'])) {
            $query->where(function ($query) use ($filters): void {
                $query->where('name', 'like', '%'.$filters['q'].'%')->orWhere('email', 'like', '%'.$filters['q'].'%')->orWhere('subject', 'like', '%'.$filters['q'].'%');
            });
        }

        return view('admin.inbox', ['inquiries' => $query->paginate(15)->withQueryString(), 'companies' => Company::orderBy('sort_order')->get()]);
    }

    public function show(Inquiry $inquiry): View
    {
        Gate::authorize('view', $inquiry);
        Gate::authorize('update', $inquiry);
        if (! $inquiry->read_at) {
            $inquiry->update(['read_at' => now()]);
        }

        return view('admin.inquiry', ['inquiry' => $inquiry->load('company')]);
    }

    public function update(Request $request, Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('update', $inquiry);
        $inquiry->update($request->validate(['status' => ['required', Rule::in(['new', 'in_progress', 'resolved'])]]));

        return back()->with('success', 'Status pesan diperbarui.');
    }

    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('delete', $inquiry);
        $inquiry->delete();

        return redirect()->route('admin.inbox.index')->with('success', 'Pesan dihapus.');
    }
}
