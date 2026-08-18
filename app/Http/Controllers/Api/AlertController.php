<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Http\Resources\AlertResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    /**
     * Get all alerts for the user or system broadcast alerts
     */
    public function index()
    {
        $userId = Auth::id();
        $alerts = Alert::where('recipient_id', $userId)
            ->orWhereNull('recipient_id')
            ->orderBy('created_at', 'desc')
            ->get();

        return AlertResource::collection($alerts);
    }

    /**
     * Mark a single alert as read
     */
    public function markAsRead(Alert $alert)
    {
        $alert->update(['is_read' => true]);
        return new AlertResource($alert);
    }

    /**
     * Mark all alerts as read
     */
    public function markAllAsRead(Request $request)
    {
        $userId = Auth::id();
        Alert::where('recipient_id', $userId)
            ->orWhereNull('recipient_id')
            ->update(['is_read' => true]);

        return response()->json([
            'message' => 'All alerts marked as read.',
        ]);
    }
}
