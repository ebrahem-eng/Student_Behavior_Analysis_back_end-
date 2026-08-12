<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ScheduledReport;
use App\Services\Report\ReportExportService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class GenerateScheduledReports extends Command
{
    protected $signature = 'reports:generate-scheduled';
    protected $description = 'Generate and dispatch scheduled reports to configured recipients';

    public function handle(ReportExportService $exportService): int
    {
        $this->info('Starting scheduled report generation...');
        $reports = ScheduledReport::where('is_active', true)->get();

        foreach ($reports as $report) {
            $this->info("Processing {$report->report_type} report ID: {$report->id}");

            try {
                $exportData = match ($report->report_type) {
                    'grades' => $exportService->exportGrades($report->filters['section_id'] ?? null),
                    'attendance' => $exportService->exportAttendance($report->filters['section_id'] ?? null),
                    default => null,
                };

                if ($exportData) {
                    // Send to recipients (mock/logged for local environment)
                    foreach ($report->recipients as $email) {
                        Log::info("Dispatched scheduled {$report->report_type} report to {$email}");
                    }
                }

                $report->update(['last_run_at' => now()]);
            } catch (\Exception $e) {
                $this->error("Failed to generate report {$report->id}: {$e->getMessage()}");
                Log::error("Scheduled report error: " . $e->getMessage());
            }
        }

        $this->info('Scheduled reports processed successfully.');
        return Command::SUCCESS;
    }
}
