<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessImport;
use App\Models\Import;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ImportController extends Controller
{
    public function index() { return view('admin.imports.index', ['imports' => Import::with('user')->latest()->paginate(25)]); }
    public function show(Import $import) { return view('admin.imports.show', compact('import')); }
    public function store(Request $request)
    {
        $request->validate(['type' => 'required|in:sup,stfc,smp', 'file' => 'required|file|max:102400|mimes:txt,csv']);
        $file = $request->file('file');
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, ['txt', 'csv'], true)) throw ValidationException::withMessages(['file' => 'Envie TXT ou CSV.']);
        $path = $file->store('imports/'.$request->input('type'), 'local');
        $import = Import::create([
            'user_id' => $request->user()->id, 'type' => $request->input('type'),
            'original_name' => basename($file->getClientOriginalName()), 'private_path' => $path,
            'status' => 'queued',
        ]);
        ProcessImport::dispatch($import->id);
        return redirect()->route('admin.imports.show', $import)->with('status', 'Importação agendada.');
    }
}
