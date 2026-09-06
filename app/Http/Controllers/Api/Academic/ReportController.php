<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\ScheduledReport;
use App\Services\Report\ReportExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    protected $exportService;

    public function __construct(ReportExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Export grades report as CSV download or JSON.
     */
    public function exportGrades(Request $request)
    {
        $sectionId = $request->integer('section_id') ?: null;
        $format = $request->query('format', 'json');
        $data = $this->exportService->exportGrades($sectionId);

        if ($format === 'csv') {
            return response($data['csv'], 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="grades-report-' . now()->format('Y-m-d') . '.csv"',
            ]);
        }

        return response()->json([
            'headers' => $data['headers'],
            'rows' => $data['rows'],
            'total' => count($data['rows']),
        ]);
    }

    /**
     * Export attendance report as CSV download or JSON.
     */
    public function exportAttendance(Request $request)
    {
        $sectionId = $request->integer('section_id') ?: null;
        $format = $request->query('format', 'json');
        $data = $this->exportService->exportAttendance($sectionId);

        if ($format === 'csv') {
            return response($data['csv'], 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="attendance-report-' . now()->format('Y-m-d') . '.csv"',
            ]);
        }

        return response()->json([
            'headers' => $data['headers'],
            'rows' => $data['rows'],
            'total' => count($data['rows']),
        ]);
    }

    /**
     * Export student risk profile.
     */
    public function exportStudentRiskProfile(Request $request, int $studentId)
    {
        $format = $request->query('format', 'json');
        $data = $this->exportService->exportRiskProfile($studentId);

        if ($format === 'csv') {
            return response($data['csv'], 200, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="student-risk-profile-' . $studentId . '.csv"',
            ]);
        }

        return response()->json([
            'student' => $data['student'],
            'alerts' => $data['alerts'],
            'summary' => $data['rows'][0] ?? [],
        ]);
    }

    /**
     * List all scheduled reports for the user/institution.
     */
    public function listScheduled(Request $request)
    {
        $scheduled = ScheduledReport::where('user_id', Auth::id())->latest()->get();
        return response()->json(['data' => $scheduled]);
    }

    /**
     * Create a new scheduled report.
     */
    public function schedule(Request $request)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:grades,attendance,risk_profiles',
            'frequency' => 'required|in:daily,weekly,monthly',
            'recipients' => 'required|array|min:1',
            'recipients.*' => 'email',
            'filters' => 'nullable|array',
        ]);

        $scheduled = ScheduledReport::create([
            'user_id' => Auth::id(),
            'report_type' => $validated['report_type'],
            'frequency' => $validated['frequency'],
            'recipients' => $validated['recipients'],
            'filters' => $validated['filters'] ?? null,
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Report schedule created successfully.',
            'data' => $scheduled,
        ], 201);
    }

    /**
     * Delete a scheduled report.
     */
    public function deleteScheduled(Request $request, ScheduledReport $scheduledReport)
    {
        if ($scheduledReport->user_id !== Auth::id()) {
            abort(403);
        }

        $scheduledReport->delete();
        return response()->noContent();
    }
}
