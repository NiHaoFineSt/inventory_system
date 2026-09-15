<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\View\View;

class LogHistoryController extends Controller
{
    public function index(): View
    {
        return view('log_history', ['logs' => AuditLog::latest()->get()]);
    }
}
