<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Friendship;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Participant;
use App\Models\User;

class ConversationController extends Controller
{
    //
    public function getConversations()
    {
        $user = Auth::user();
        $conversations = $user->conversations()
            ->has('messages')
            ->with(['participants.user', 'messages'])
            ->get();

        // Thêm đường dẫn ảnh vào từng cuộc trò chuyện
        foreach ($conversations as $conversation) {
            if ($conversation->avatar) {
                $conversation->avatar = asset('storage/images/' . $conversation->avatar);
            }
            // Lấy tin nhắn mới nhất của cuộc trò chuyện
            $latestMessage = $conversation->messages()->latest()->first();
            $conversation->latest_message = $latestMessage;
        }

        return response()->json($conversations);

    }
    public function getGroup()

    {
        $user = Auth::user();
        $getGroups = $user->conversations->where('type', 'group');
        $getGroups->load('participants');
        return response()->json($getGroups);
    }

    // Tạo group nhóm chat, với $list_user
    public function addGroup(Request $request)
    {
        $name = $request->input('name');
        $userIds = json_decode($request->input('user_ids'), true);
        $user = Auth::user();
        // Kiểm tra trường "name"
        if (empty($name)) {
            return response()->json([
                'status' => false,
                'message' => 'Vui lòng nhập tên nhóm chat.',
            ], 400);
        }

        // Xử lý tệp tin ảnh
        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '.' . $image->getClientOriginalExtension(); // Tạo tên tệp tin bằng ngày giờ hiện tại + phần mở rộng của tệp tin gốc
            $imagePath = $image->storeAs('public/images', $imageName); // Lưu ảnh vào thư mục 'storage/app/public/images' với tên tệp tin mới
        } else {
            $imageName = null; // Nếu không có tệp tin ảnh, đặt giá trị là null
        }
        // Tiến hành xử lý tạo nhóm chat và tạo bản ghi participant tương ứng
        try {

            if (empty($userIds)) {
                $group = Conversation::create([
                    'name' => $name,
                    'type' => "group",
                    'owner_id' => $user->id,
                    'avatar'  => $imageName
                ]);
                Participant::create([
                    'conversation_id' => $group->id,
                    'user_id' => $user->id,
                ]);
                return response()->json([
                    'status' => true,
                    'message' => 'bạn đã tạo thành công nhóm chat , hãy mời thêm thành viên.',
                    'avatar' => asset('storage/app/public/images/' . $imageName)
                ], 400);
            } else {
                // Tạo nhóm chat
                $group = Conversation::create([
                    'name' => $name,
                    'type' => "group",
                    'owner_id' => $user->id,
                    'avatar'  => $imageName
                ]);
                Participant::create([
                    'conversation_id' => $group->id,
                    'user_id' => $user->id,
                ]);
                // Tạo các bản ghi participant
                foreach ($userIds as $userId) {
                    Participant::create([
                        'conversation_id' => $group->id,
                        'user_id' => $userId,
                    ]);
                }

                // Trả về phản hồi thành công
                return response()->json([
                    'status' => true,
                    'message' => 'Tạo nhóm chat thành công, hãy trò chuyện với các thành viên trong nhóm',
                    'group' => $group
                ], 200);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Tạo nhóm chat thất bại',
            ], 500);
        }
    }

    public function conversationStranger($Stranger_id)
    {

        $user = Auth::user();
        // Tạo cuộc trò chuyện 1-1 với tên là tên của người bạn
        $stranger = User::find($Stranger_id);
        $conversation = Conversation::create([
            'name' => $stranger->name,
            'type' => "stranger"
        ]);
        if ($conversation) {
            // Thêm các bản ghi Participants cho người dùng và bạn bè vào cuộc trò chuyện
            $participantData = [
                ['conversation_id' => $conversation->id, 'user_id' => $user->id, 'created_at' => Carbon::now()],
                ['conversation_id' => $conversation->id, 'user_id' => $Stranger_id, 'created_at' => Carbon::now()]
            ];
            Participant::insert($participantData);

            return response()->json([
                'status' => true,
                'message' => 'Bạn có thể trò chuyện với người lạ này'
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Không thể chat với người lạ này '
            ], 500);
        }

    }

    // chức năng rời nhóm
    public function leaveGroup($conversation_id)
    {
        $user = Auth::user();

        $participant = $user->conversations->where('conversation_id', $conversation_id)->first();
        if ($participant) {
            Participant::delete($participant);
            return response()->json([
                'status' => true,
                'message' => 'Bạn đã roi nhóm thành công'
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Rời nhóm chat thất bại'
            ], 500);
        }

    }

    // đổi trò chuyện 2 người thành nhiều người bằng cách add người vào
    public function updateNameGroup(Request $request, $conversation_id)
    {
        $conversation = Conversation::find($conversation_id);
        if ($conversation) {
            $conversation->update([
                'name' => $request->name,
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Đã đổi tên cuộc trò chuyện thành công'
            ], 200);

        } else {
            return response()->json([
                'status' => false,
                'message' => 'Không tìm thấy cuộc trò chuyện'
            ], 500);
        }
    }

    public function addMember(Request $request, $conversation_id)
    {
        $userIds = $request->input('user_ids');
        if ($conversation_id) {
            // Tạo các bản ghi participant
            foreach ($userIds as $userId) {
                Participant::create([
                    'conversation_id' => $conversation_id,
                    'user_id' => $userId,
                ]);
            }
            return response()->json([
                'status' => true,
                'message' => 'Đã thêm thành viên thành công'
            ], 200);

        } else {
            return response()->json([
                'status' => false,
                'message' => 'Thêm thành viên thất bại'
            ], 500);
        }
    }

    public function searchConversation(Request $request)
    {
        $user = Auth::user();
        $search_value = $request->input('search_value');
        $result = $user->conversations()->where('name', 'LIKE', '%' . $search_value . '%')->get();
        if (count($result) > 0) {
            return response()->json($result, 200);
        } else {
            return response()->json(['Result' => 'Khong tìm thấy ket quả nào'], 404);
        }
    }

   public function deleteConversationPrivate(Request $request){
        $conversation_id = $request->input('conversation_id');

       $conversation = Conversation::findOrFail($conversation_id);
      if($conversation){
          // Xóa tất cả tin nhắn trong cuộc trò chuyện
          $conversation->messages()->delete();

          // Trả về thông báo thành công
          return response()->json([
              'status' => true,
              'message' => 'Đã thêm thành viên thành công'
          ], 200);
      }else{
          return response()->json([
              'status' => false,
              'message' => 'Không tìm thấy cuộc trò chuyện muốn xóa'
          ], 404);
      }

   }

}
