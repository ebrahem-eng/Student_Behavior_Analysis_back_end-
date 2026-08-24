<?php

namespace App\Http\Controllers\Api\Academic;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Http\Resources\AttendanceResource;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $query = Attendance::with(['student', 'section.course']);

        if ($user && !$user->hasRole('admin')) {
            if ($user->hasRole('student')) {
                $query->where('user_id', $user->id);
            } elseif ($user->hasRole('teacher')) {
                if ($request->boolean('my_sections')) {
                    $query->whereHas('section', function ($q) use ($user) {
                        $q->where('teacher_id', $user->id);
                    });
                } elseif ($user->institution_id) {
                    $query->whereHas('student', function ($q) use ($user) {
                        $q->where('institution_id', $user->institution_id);
                    });
                }
            } elseif ($user->hasRole('advisor') && $user->institution_id) {
                $query->whereHas('student', function ($q) use ($user) {
                    $q->where('institution_id', $user->institution_id);
                });
            }
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->filled('section_id')) {
            $query->where('section_id', $request->input('section_id'));
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->input('date'));
        }

        return AttendanceResource::collection($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'section_id' => 'required|exists:sections,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent,late',
            'notes' => 'nullable|string',
        ]);

        $attendance = Attendance::create($validated);
        return new AttendanceResource($attendance->load(['student', 'section.course']));
    }

    public function show(Attendance $attendance)
    {
        return new AttendanceResource($attendance->load(['student', 'section.course']));
    }

    public function update(Request $request, Attendance $attendance)
    {
        $validated = $request->validate([
            'status' => 'sometimes|in:present,absent,late',
            'notes' => 'nullable|string',
        ]);

        $attendance->update($validated);
        return new AttendanceResource($attendance->load(['student', 'section.course']));
    }

    public function destroy(Attendance $attendance)
    {
        $attendance->delete();
        return response()->noContent();
    }
}
