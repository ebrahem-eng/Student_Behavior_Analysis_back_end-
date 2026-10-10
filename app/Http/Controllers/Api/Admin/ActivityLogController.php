<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ActivityLogResource;
use App\Models\User;
use App\Support\AdminScope;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Display a paginated listing of system audit logs.
     */
    public function index(Request $request)
    {
        $query = Activity::with('causer')->latest();
        $managedIds = AdminScope::managedIds($request->user());

        if (is_array($managedIds)) {
            $scope = $managedIds !== [] ? $managedIds : [0];
            $userIds = User::query()
                ->where(function ($q) use ($scope) {
                    $q->whereIn('institution_id', $scope)
                        ->orWhereHas('managedInstitutions', function ($mq) use ($scope) {
                            $mq->whereIn('institutions.id', $scope);
                        });
                })
                ->pluck('id');

            $query->where('causer_type', User::class)->whereIn('causer_id', $userIds);
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->string('log_name'));
        }

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->integer('causer_id'));
        }

        if ($request->filled('subject_type')) {
            $query->where('subject_type', 'like', '%' . $request->string('subject_type') . '%');
        }

        $logs = $query->paginate($request->integer('per_page', 25));

        return ActivityLogResource::collection($logs);
    }

    /**
     * Display the specified audit log entry.
     */
    public function show(Activity $activityLog)
    {
        $activityLog->load('causer');
        return new ActivityLogResource($activityLog);
    }
}
