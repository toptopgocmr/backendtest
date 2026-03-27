<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function conversations(Request $request)
    {
        $userId = $request->user()->id;

        $conversations = Conversation::with(['user1', 'user2'])
            ->where('user1_id', $userId)
            ->orWhere('user2_id', $userId)
            ->orderByDesc('last_message_at')
            ->get()
            ->map(function ($conv) use ($userId) {
                $partner = $conv->user1_id === $userId ? $conv->user2 : $conv->user1;
                $unread  = $conv->user1_id === $userId ? $conv->user1_unread : $conv->user2_unread;

                return [
                    'conversation_id' => $conv->id,
                    'partner'         => ['id' => $partner?->id, 'name' => $partner?->name, 'avatar' => $partner?->avatar_url],
                    'last_message'    => $conv->last_message,
                    'last_message_at' => $conv->last_message_at,
                    'unread_count'    => $unread,
                ];
            });

        return response()->json(['success' => true, 'data' => $conversations]);
    }

    public function thread(Request $request, string $userId)
    {
        $me   = $request->user()->id;
        $conv = Conversation::where(function ($q) use ($me, $userId) {
            $q->where('user1_id', $me)->where('user2_id', $userId);
        })->orWhere(function ($q) use ($me, $userId) {
            $q->where('user1_id', $userId)->where('user2_id', $me);
        })->first();

        if (!$conv) return response()->json(['success' => true, 'data' => []]);

        $messages = Message::where('conversation_id', $conv->id)
            ->with('sender:id,name,avatar')
            ->orderBy('created_at')
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id,
                'content'    => $m->content,
                'type'       => $m->type,
                'is_mine'    => $m->sender_id === $me,
                'is_read'    => $m->is_read,
                'created_at' => $m->created_at,
            ]);

        // Marquer comme lu
        if ($conv->user1_id === $me) {
            $conv->update(['user1_unread' => 0]);
        } else {
            $conv->update(['user2_unread' => 0]);
        }

        return response()->json(['success' => true, 'data' => $messages]);
    }

    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content'     => 'required|string|max:2000',
            'booking_id'  => 'nullable|exists:bookings,id',
        ]);

        $me         = $request->user()->id;
        $receiverId = $request->receiver_id;

        // Trouver ou créer la conversation
        $conv = Conversation::where(function ($q) use ($me, $receiverId) {
            $q->where('user1_id', $me)->where('user2_id', $receiverId);
        })->orWhere(function ($q) use ($me, $receiverId) {
            $q->where('user1_id', $receiverId)->where('user2_id', $me);
        })->first();

        if (!$conv) {
            $conv = Conversation::create([
                'user1_id'   => $me,
                'user2_id'   => $receiverId,
                'booking_id' => $request->booking_id,
            ]);
        }

        $message = Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $me,
            'content'         => $request->content,
            'type'            => 'text',
        ]);

        // Incrémenter unread pour le destinataire
        if ($conv->user1_id === $receiverId) {
            $conv->increment('user1_unread');
        } else {
            $conv->increment('user2_unread');
        }

        $conv->update(['last_message' => $request->content, 'last_message_at' => now()]);

        return response()->json(['success' => true, 'data' => $message], 201);
    }

    public function markRead(Request $request, string $id)
    {
        Message::where('id', $id)->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['success' => true]);
    }
}
