<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dataset;
use App\Models\Import;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', ['datasets' => Dataset::all()->keyBy('type'), 'lastImports' => Import::latest()->get()->unique('type')->keyBy('type'), 'imports' => Import::with('user')->latest()->limit(10)->get()]);
    }
}
