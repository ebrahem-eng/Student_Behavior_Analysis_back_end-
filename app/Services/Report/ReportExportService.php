<?php

namespace App\Services\Report;

use App\Models\Grade;
use App\Models\Attendance;
use App\Models\Alert;
use App\Models\User;
use App\Models\Enrollment;
use Illuminate\Support\Collection;

class ReportExportService
{
    /**
     * Generate CSV content from an array or collection of records.
     */
    public function generateCsv(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }

    /**
     * Export Grades as CSV or structured array.
     */
    public function exportGrades(?int $sectionId = null): array
    {
        $query = Grade::with(['enrollment.student', 'enrollment.section.course']);

        if ($sectionId) {
            $query->whereHas('enrollment', function ($q) use ($sectionId) {
                $q->where('section_id', $sectionId);
            });
        }

        $grades = $query->get();
        $headers = ['ID', 'Student Name', 'Student Email', 'Course', 'Exam Name', 'Score', 'Weight', 'Date'];
        $rows = [];

        foreach ($grades as $g) {
            $rows[] = [
                $g->id,
                $g->enrollment->student->name ?? 'N/A',
                $g->enrollment->student->email ?? 'N/A',
                $g->enrollment->section->course->name ?? 'N/A',
                $g->exam_name,
                $g->score,
                $g->weight,
                $g->created_at->format('Y-m-d H:i'),
            ];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'csv' => $this->generateCsv($headers, $rows),
        ];
    }

    /**
     * Export Attendance as CSV or structured array.
     */
    public function exportAttendance(?int $sectionId = null): array
    {
        $query = Attendance::with(['student', 'section.course']);

        if ($sectionId) {
            $query->where('section_id', $sectionId);
        }

        $attendances = $query->latest('date')->get();
        $headers = ['ID', 'Student Name', 'Student Email', 'Course', 'Date', 'Status', 'Notes'];
        $rows = [];

        foreach ($attendances as $a) {
            $rows[] = [
                $a->id,
                $a->student->name ?? 'N/A',
                $a->student->email ?? 'N/A',
                $a->section->course->name ?? 'N/A',
                $a->date->format('Y-m-d'),
                $a->status,
                $a->notes ?? '',
            ];
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
            'csv' => $this->generateCsv($headers, $rows),
        ];
    }

    /**
     * Export Student Risk Profile report.
     */
    public function exportRiskProfile(int $studentId): array
    {
        $student = User::with(['enrollments.section.course', 'behaviorLogs'])->findOrFail($studentId);
        $alerts = Alert::where('student_id', $studentId)->latest()->get();

        $headers = ['Student ID', 'Student Name', 'Email', 'Active Courses', 'Alert Count', 'High Risk Alerts'];
        $rows = [[
            $student->id,
            $student->name,
            $student->email,
            $student->enrollments->count(),
            $alerts->count(),
            $alerts->where('level', 'high')->count(),
        ]];

        return [
            'student' => $student,
            'alerts' => $alerts,
            'headers' => $headers,
            'rows' => $rows,
            'csv' => $this->generateCsv($headers, $rows),
        ];
    }
}
