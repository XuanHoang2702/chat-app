<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    //
    public function getMessages($conversation_id)
    {
        $conversation = Conversation::with(['participants.user', 'messages'])->find($conversation_id);
        return response()->json($conversation);
    }

    public function addMessage(Request $request)
    {
        $user = Auth::user();
        if ($request->has('content') || $request->has('conversation_id')) {
           $message = Message::create([
                'conversation_id' => $request->conversation_id,
                'user_id' => $user->id,
                'content' => $request->message_content,
            ]);
            // Gọi event SendMessageEvent
         // broadcast(new SendMessageEvent($user->id,$request->conversation_id,$request->message_content));
            return response()->json([
                'status' => true,
                'message' => 'Gửi tin nhắn thành công'
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Gửi tin nhắn không thành công'
            ], 500);
        }
    }

    public function replyMessage(Request $request)
    {
        $user = Auth::user();

        $message = Message::create([
            'parent_id' => $request->message_id,
            'conversation_id' => $request->conversation_id,
            'user_id' => $user->id,
            'content' => $request->message_content
        ]);
        if ($message) {
            return response()->json([
                'status' => false,
                'message' => 'trả lời tin nhắn người khác thành công'
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'trả lời tin nhắn người khác thất bại'
            ], 500);
        }
    }

    public function deleteMessage(Request $request)
    {
        $user = Auth::user();
        $message_id = $request->input('message_id');
        $message = Message::find($message_id);
        if ($message) {
            if ($user->id == $message->user_id) {
                $message->delete();
            }
            return response()->json([
                'status' => true,
                'message' => 'Đã xóa thành công'
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => ' xóa thất bại'
            ], 500);
        }

    }

    public function editMessage(Request $request, $message_id)
    {
        $message = Message::find($message_id);
        $edit_message = $message->update([
            'content' => $request->message_content,
        ]);
        if ($edit_message) {
            return response()->json([
                'status' => true,
                'message' => 'chỉnh sửa tin nhắn thành công'
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'chỉnh sửa tin nhắn thất bại'
            ], 500);
        }
    }
    // lấy ra các tin nhắn chưa đọc.
    public function getUnreadMessages($conversationId)
    {
        $messages = Message::where('conversation_id', $conversationId)
            ->whereNotIn('user_id', [Auth::user()->id])
            ->where('status', 'unread')
            ->get();

        return response()->json($messages);
    }
    // cập nhật tin nhắn status
    public function markMessagesAsRead(Request $request)
    {
        $conversation_id  = $request->input('conversation_id');
        $conversation = Conversation::find($conversation_id);

        $conversation->messages()
            ->where('status', 'unread')
            ->update(['status' => 'read']);
        return response()->json([
            'message' => "Đã cập nhật tất cả tin nhắn thành đã đọc"
        ]);
    }
    // lấy tin nhắn mới nhất chưa đọc
    public function getLatestUnreadMessage($conversationId)
    {
        $latestMessage = Message::where('conversation_id', $conversationId)
            ->latest('created_at')
            ->first();

        return response()->json([
            'latestMessage' => $latestMessage,
        ]);
    }
    public function recallMessage(Request $request)
    {
        $message_id = $request->input('message_id');

        $message = Message::find($message_id);

        if (!$message) {
            return response()->json([
                'status' => false,
                'message' => 'Tin nhắn không tồn tại'
            ], 404);
        }

        $message->fill(['recall' => true]);
        $message->save();

        return response()->json([
            'status' => true,
            'message' => 'Đã thu hồi tin nhắn',
            'messageData' => $message
        ], 200);
    }

}
