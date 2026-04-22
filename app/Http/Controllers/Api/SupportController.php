<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SupportMessage;
use Illuminate\Http\Request;

class SupportController extends Controller
{
    /**
     * Send support message
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        $supportMessage = SupportMessage::create([
            'user_id' => $request->user()->id,
            'subject' => $request->subject,
            'message' => $request->message,
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'Your message has been sent successfully. We will get back to you soon!',
            'support_message' => $supportMessage,
        ], 201);
    }

    /**
     * Get user's support messages
     */
    public function getMessages(Request $request)
    {
        $messages = SupportMessage::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Get single support message
     */
    public function getMessage(Request $request, $id)
    {
        $message = SupportMessage::where('user_id', $request->user()->id)
            ->findOrFail($id);

        return response()->json($message);
    }
}
