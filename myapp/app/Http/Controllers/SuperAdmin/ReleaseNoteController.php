<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\ReleaseNote;
use App\Services\Central\AdminActivityLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReleaseNoteController extends Controller
{
    public function __construct(
        private AdminActivityLogService $activityLog,
    ) {}

    public function index(): Response
    {
        $releaseNotes = ReleaseNote::latest()
            ->paginate(15);

        return Inertia::render('admin/release-notes/Index', [
            'releaseNotes' => $releaseNotes,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('admin/release-notes/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        if ($validated['is_published'] && ! $validated['published_at']) {
            $validated['published_at'] = now();
        }

        $releaseNote = ReleaseNote::create($validated);

        $this->activityLog->log(
            $request->user('admin'),
            'created_release_note',
            $releaseNote,
            ['title' => $releaseNote->title],
            $request->ip(),
        );

        return redirect()->route('admin.release-notes.index')->with('success', 'Release note created successfully.');
    }

    public function edit(int $id): Response
    {
        $releaseNote = ReleaseNote::findOrFail($id);

        return Inertia::render('admin/release-notes/Edit', [
            'releaseNote' => $releaseNote,
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $releaseNote = ReleaseNote::findOrFail($id);

        $validated = $request->validate($this->rules());

        if ($validated['is_published'] && ! $validated['published_at'] && ! $releaseNote->published_at) {
            $validated['published_at'] = now();
        }

        $releaseNote->update($validated);

        $this->activityLog->log(
            $request->user('admin'),
            'updated_release_note',
            $releaseNote,
            ['title' => $releaseNote->title],
            $request->ip(),
        );

        return redirect()->route('admin.release-notes.index')->with('success', 'Release note updated successfully.');
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        $releaseNote = ReleaseNote::findOrFail($id);
        $title = $releaseNote->title;

        $releaseNote->delete();

        $this->activityLog->log(
            $request->user('admin'),
            'deleted_release_note',
            null,
            ['title' => $title],
            $request->ip(),
        );

        return back()->with('success', 'Release note deleted successfully.');
    }

    private function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'version' => ['required', 'string', 'max:50'],
            'summary' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.type' => ['required', 'string', 'in:feature,fix,improvement'],
            'items.*.description' => ['required', 'string', 'max:500'],
            'is_published' => ['boolean'],
            'published_at' => ['nullable', 'date'],
        ];
    }
}
