<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Admin\MessageController
 *
 * Gère les conversations côté admin panel (web).
 * Permet à l'admin de voir et répondre aux messages des clients Flutter.
 *
 * Routes à ajouter dans routes/admin.php :
 *   Route::get('messages', [MessageController::class, 'index'])->name('admin.messages.index');
 *   Route::get('messages/{id}', [MessageController::class, 'show'])->name('admin.messages.show');
 *   Route::post('messages/{id}/reply', [MessageController::class, 'reply'])->name('admin.messages.reply');
 */
class MessageController extends Controller
{
    public function index(Request $request)
    {
        // Récupérer toutes les conversations, triées par dernier message
        $conversations = Conversation::with(['user1', 'user2'])
            ->orderByDesc('last_message_at')
            ->paginate(30);

        return view('admin.messages.index', compact('conversations'));
    }

    public function show(string $id)
    {
        $activeConv = Conversation::with(['user1', 'user2'])->findOrFail($id);

        // Récupérer tous les messages de cette conversation
        $messages = Message::where('conversation_id', $id)
            ->with('sender:id,name,role,avatar')
            ->orderBy('created_at')
            ->get();

        // Marquer les messages du client comme lus
        $adminUser = $this->getAdminUser($activeConv);
        if ($adminUser) {
            // Réinitialiser le compteur unread côté admin
            if ($activeConv->user1_id === $adminUser->id) {
                $activeConv->update(['user1_unread' => 0]);
            } else {
                $activeConv->update(['user2_unread' => 0]);
            }
            // Marquer les messages non lus envoyés au admin comme lus
            Message::where('conversation_id', $id)
                ->where('sender_id', '!=', $adminUser->id)
                ->where('is_read', false)
                ->update(['is_read' => true, 'read_at' => now()]);
        }

        // Toutes les conversations pour la sidebar
        $conversations = Conversation::with(['user1', 'user2'])
            ->orderByDesc('last_message_at')
            ->paginate(30);

        return view('admin.messages.index', compact('conversations', 'activeConv', 'messages'));
    }

    public function reply(Request $request, string $id)
    {
        $request->validate(['content' => 'required|string|max:2000']);

        $conv = Conversation::findOrFail($id);

        // Identifier le compte admin dans cette conversation
        $adminUser = $this->getAdminUser($conv);

        if (!$adminUser) {
            // Fallback : utiliser le premier admin du système
            $adminUser = User::where('role', 'admin')->first();
        }

        if (!$adminUser) {
            return back()->with('error', 'Compte admin introuvable.');
        }

        // Identifier le destinataire (le client)
        $clientId = $conv->user1_id === $adminUser->id
            ? $conv->user2_id
            : $conv->user1_id;

        // Créer le message
        Message::create([
            'conversation_id' => $conv->id,
            'sender_id'       => $adminUser->id,
            'content'         => $request->content,
            'type'            => 'text',
        ]);

        // Incrémenter unread pour le client
        if ($conv->user1_id === $clientId) {
            $conv->increment('user1_unread');
        } else {
            $conv->increment('user2_unread');
        }

        $conv->update([
            'last_message'    => $request->content,
            'last_message_at' => now(),
        ]);

        return redirect()->route('admin.messages.show', $id)
            ->with('success', 'Message envoyé.');
    }

    /**
     * Retourne l'utilisateur admin dans la conversation.
     */
    private function getAdminUser(Conversation $conv): ?User
    {
        if ($conv->user1 && $conv->user1->role === 'admin') {
            return $conv->user1;
        }
        if ($conv->user2 && $conv->user2->role === 'admin') {
            return $conv->user2;
        }
        return null;
    }
}
