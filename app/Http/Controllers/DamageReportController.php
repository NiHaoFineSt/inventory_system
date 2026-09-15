<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDamageReportRequest;
use App\Http\Requests\UpdateDamageReportStatusRequest;
use App\Models\DamageReport;
use App\Models\User;
use App\Services\DamageReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DamageReportController extends Controller
{
    public function __construct(private readonly DamageReportService $damageReportService) {}

    public function index(): View
    {
        $reports = DamageReport::latest()->get();

        return view('damage_queue', ['damagedAssets' => $reports, 'reports' => $reports]);
    }

    public function store(StoreDamageReportRequest $request): RedirectResponse
    {
        /** @var User $reporter */
        $reporter = Auth::user();

        $this->damageReportService->submit(
            $reporter,
            $request->validated(),
            $request->file('image'),
        );

        return back()->with('success', 'Damage report submitted successfully.');
    }

    public function updateStatus(UpdateDamageReportStatusRequest $request, DamageReport $report): JsonResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        $this->damageReportService->updateStatus($actor, $report, $request->enum('status', \App\Enums\DamageReportStatus::class));

        return response()->json(['status' => 'success', 'report' => $report->fresh()]);
    }
}
