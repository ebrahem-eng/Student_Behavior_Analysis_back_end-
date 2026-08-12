<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Alert;
use App\Http\Resources\AlertResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AlertController extends Controller
{
    public function index()
    {
        return AlertResource::collection(Alert::where('recipient_id', Auth::id())->get());
    }

    public function markAsRead(Alert $alert)
    {
        if ($alert->recipient_id !== Auth::id()) {
            abort(403);
        }
        
        $alert->update(['is_read' => true]);
        return new AlertResource($alert);
    }
}
