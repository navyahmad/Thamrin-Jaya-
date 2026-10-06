<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\InboxFilterRequest;
use App\Http\Requests\UpdateInquiryRequest;
use App\Models\Company;
use App\Models\Inquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class InboxController extends Controller
{
    public function index(InboxFilterRequest $request): View
    {
        Gate::authorize('viewAny', Inquiry::class);
        $filters = $request->validated();
        $query = Inquiry::with('company')->latest()->orderByDesc('id');
        if (! empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['read'])) {
            $filters['read'] === 'read' ? $query->whereNotNull('read_at') : $query->whereNull('read_at');
        }
        if (! empty($filters['q'])) {
            $query->where(function (Builder $query) use ($filters): void {
                $query->where('name', 'like', '%'.$filters['q'].'%')->orWhere('email', 'like', '%'.$filters['q'].'%')->orWhere('subject', 'like', '%'.$filters['q'].'%');
            });
        }

        return view('admin.inbox', ['inquiries' => $query->paginate(15)->withQueryString(), 'companies' => Company::orderBy('sort_order')->get()]);
    }

    public function show(Inquiry $inquiry): View
    {
        Gate::authorize('view', $inquiry);

        return view('admin.inquiry', ['inquiry' => $inquiry->load('company')]);
    }

    public function update(UpdateInquiryRequest $request, Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('update', $inquiry);
        $data = $request->safe()->only(['status']);
        if ($request->has('read')) {
            $data['read_at'] = $request->input('read') === 'read' ? ($inquiry->read_at ?? now()) : null;
        }
        $inquiry->update($data);

        return back()->with('success', 'Status pesan diperbarui.');
    }

    public function destroy(Inquiry $inquiry): RedirectResponse
    {
        Gate::authorize('delete', $inquiry);
        $inquiry->delete();

        return redirect()->route('admin.inbox.index')->with('success', 'Pesan dihapus.');
    }
}
