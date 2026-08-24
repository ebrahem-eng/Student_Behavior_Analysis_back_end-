<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $studentId = $request->query('student_id');
        $otherUserId = $request->query('user_id') ?: $request->query('recipient_id');

        $query = Message::with(['sender', 'recipient', 'student']);

        if ($studentId && $otherUserId) {
            $query->where('student_id', $studentId)
                  ->where(function ($q) use ($userId, $otherUserId) {
                      $q->where(function ($inner) use ($userId, $otherUserId) {
                          $inner->where('sender_id', $userId)->where('recipient_id', $otherUserId);
                      })->orWhere(function ($inner) use ($userId, $otherUserId) {
                          $inner->where('sender_id', $otherUserId)->where('recipient_id', $userId);
                      });
                  });
        } elseif ($studentId) {
            $query->where('student_id', $studentId);
        } elseif ($otherUserId) {
            $query->where(function ($q) use ($userId, $otherUserId) {
                $q->where(function ($inner) use ($userId, $otherUserId) {
                    $inner->where('sender_id', $userId)->where('recipient_id', $otherUserId);
                })->orWhere(function ($inner) use ($userId, $otherUserId) {
                    $inner->where('sender_id', $otherUserId)->where('recipient_id', $userId);
                });
            });
        } else {
            $query->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)->orWhere('recipient_id', $userId);
            });
        }

        $messages = $query->orderBy('created_at', 'asc')->get();

        return response()->json(['data' => $messages]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:5000',
            'recipient_id' => 'nullable|exists:users,id',
            'student_id' => 'nullable|exists:users,id',
        ]);

        $senderId = $request->user()->id;
        $recipientId = $validated['recipient_id'] ?? null;
        $studentId = $validated['student_id'] ?? null;

        // If recipient_id is not specified, try to find an advisor or staff member
        if (!$recipientId) {
            $advisor = User::whereHas('roles', function ($q) {
                $q->where('name', 'Advisor')->orWhere('name', 'advisor');
            })->first();

            if ($advisor && $advisor->id !== $senderId) {
                $recipientId = $advisor->id;
            }
        }

        $msg = Message::create([
            'sender_id' => $senderId,
            'recipient_id' => $recipientId,
            'student_id' => $studentId,
            'message' => $validated['message'],
            'is_read' => false,
        ]);

        $msg->load(['sender', 'recipient', 'student']);

        return response()->json(['data' => $msg], 201);
    }

    public function markAsRead($id)
    {
        $msg = Message::findOrFail($id);
        $msg->update(['is_read' => true]);

        return response()->json(['data' => $msg]);
    }
}
