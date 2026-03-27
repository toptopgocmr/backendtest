<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderByDesc('created_at')->paginate(20);

        return response()->json([
            'success'      => true,
            'data'         => $notifications->items(),
            'unread_count' => Notification::where('user_id', $request->user()->id)->where('is_read', false)->count(),
            'meta'         => ['total' => $notifications->total(), 'last_page' => $notifications->lastPage()],
        ]);
    }

    public function markRead(Request $request, string $id)
    {
        Notification::where('id', $id)->where('user_id', $request->user()->id)->update(['is_read' => true]);
        return response()->json(['success' => true]);
    }

    public function readAll(Request $request)
    {
        Notification::where('user_id', $request->user()->id)->where('is_read', false)->update(['is_read' => true]);
        return response()->json(['success' => true, 'message' => 'Toutes les notifications lues.']);
    }
}
