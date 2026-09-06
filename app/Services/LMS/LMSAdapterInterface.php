<?php

namespace App\Services\LMS;

interface LMSAdapterInterface
{
    /**
     * Authenticate or establish a connection with the LMS.
     */
    public function authenticate(): bool;

    /**
     * Fetch grades for a specific course/section.
     * 
     * @param string $courseId
     * @return array
     */
    public function fetchGrades(string $courseId): array;

    /**
     * Fetch attendance records for a specific course/section.
     * 
     * @param string $courseId
     * @return array
     */
    public function fetchAttendance(string $courseId): array;
}
