<?php

namespace App\Http\Controllers;

use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    public function index(): JsonResponse
    {
        $notifications = Auth::user()->notifications()
            ->paginate(8);

        return NotificationResource::collection($notifications)
            ->response();
    }

    public function unreadCount(): JsonResponse
    {
        $unreadCount = Auth::user()->notifications()
            ->where('read', false)
            ->count();

        return response()->json([
            'unread_count' => $unreadCount,
        ]);
    }

    public function read(string $notification): JsonResponse
    {
        Auth::user()->notifications()
            ->findOrFail($notification)
            ->update(['read' => true]);

        return response()->json([
            'message' => 'Notification marked as read',
        ]);
    }

    public function readAll(): JsonResponse
    {
        Auth::user()->notifications()
            ->where('read', false)
            ->update(['read' => true]);

        return response()->json([
            'message' => 'All notifications marked as read',
        ]);
    }
}
