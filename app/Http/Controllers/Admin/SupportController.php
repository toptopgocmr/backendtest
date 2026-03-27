<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    public function index()
    {
        $tickets = SupportTicket::with('user')->latest()->paginate(15);
        return view('admin.support.index', compact('tickets'));
    }

    public function show(string $id)
    {
        $ticket = SupportTicket::with('user')->findOrFail($id);
        return view('admin.support.show', compact('ticket'));
    }

    public function reply(Request $request, string $id)
    {
        $request->validate(['reply' => 'required|string']);
        SupportTicket::findOrFail($id)->update(['admin_reply'=>$request->reply,'replied_at'=>now(),'status'=>'en_cours']);
        return back()->with('success','Réponse envoyée.');
    }

    public function close(string $id)
    {
        SupportTicket::findOrFail($id)->update(['status'=>'résolu','closed_at'=>now()]);
        return back()->with('success','Ticket résolu.');
    }
}