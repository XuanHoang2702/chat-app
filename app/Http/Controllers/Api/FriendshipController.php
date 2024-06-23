<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Friendship;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Participant;

class FriendshipController extends Controller
{
    //

    //lấy danh sách bạn bè
    public function getFriends()
    {
        $user = Auth::user();

        $friends = $user->conversations()
            ->where('conversations.type', 'private')
            ->with(['participants.user', 'messages', 'participants.user.friendships'])
            ->get();
        return response()->json($friends);

    }

    // hủy kết bạn
    public function unFriend(Request $request)
    {
        $user = Auth::user();
        $friend_id  = $request->input('friend_id');
        $conversation_id = $request->input('conversation_id');
         $deletedConversation =  Conversation::find($conversation_id)->delete();
        // Truy vấn và xóa cả hai bản ghi friendship
        $deletedFriend = Friendship::where(function ($query) use ($user, $friend_id) {
            $query->where('user_id', $user->id)
                ->where('friend_id', $friend_id);
        })->orWhere(function ($query) use ($user, $friend_id) {
            $query->where('user_id', $friend_id)
                ->where('friend_id', $user->id);
        })->delete();
        // xóa bản ghi thành viên
        $deletedParticipant = Participant::where(function ($query) use ($user, $conversation_id,$friend_id) {
            $query->where('user_id', $user->id)
                ->where('conversation_id', $conversation_id);
        })->orWhere(function ($query) use ($user, $friend_id,$conversation_id) {
            $query->where('user_id',$friend_id )
                ->where('conversation_id', $conversation_id);
        })->delete();
        if ($deletedConversation && $deletedFriend && $deletedParticipant) {
            return response()->json([
                'status' => true,
                'message' => 'Đã hủy kết bạn thành công'
            ], 200);
        } else {
            return response()->json([
                'status' => false,
                'message' => 'Không thể thực hiện hủy kết bạn'
            ], 500);
        }
    }
    public function getStranger(){
        $loggedInUserId = Auth::id();
        $users = User::whereDoesntHave('friendships', function ($query) use ($loggedInUserId) {
            $query->where('friend_id', $loggedInUserId);
        })->get();
        return response()->json($users);
    }
   public  function sendFriend(Request $request){
        $user = Auth::user();
        $friendIds = json_decode($request->input('friendIds'), true);;
        try{
              foreach ($friendIds as $friendId){
                  $friendshipData = [
                      ['friend_id' =>$friendId, 'user_id' => $user->id, 'created_at' => Carbon::now(),'status'=>'pending'],
                      ['friend_id' => $user->id, 'user_id' =>$friendId, 'created_at' => Carbon::now(),'status'=>'pending']
                  ];
                  Friendship::insert($friendshipData);
              }

            return response()->json([
                'status' => true,
                'message' => 'Đã Gửi kết bạn thành công'
            ], 200);
        }catch (\Exception $e){
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
   }
    public function activeFriend(Request $request)
    {
        $user = Auth::user();
        $friendId = $request->input('friend_id');
        try {
            $friendships = Friendship::where(function ($query) use ($user, $friendId) {
                $query->where('user_id', $user->id)
                    ->where('friend_id', $friendId);
            })->orWhere(function ($query) use ($user, $friendId) {
                $query->where('user_id', $friendId)
                    ->where('friend_id', $user->id);
            })->update(['status' => 'active']);

            // Tạo cuộc trò chuyện 1-1 với tên là tên của người bạn
            $friendInformation = User::find($friendId);
            $conversation = Conversation::create([
                'name' => $friendInformation->name . '---' . $user->name,
                'avatar'=>$friendInformation->avatar,
                'type'=>'private'
            ]);

            // Thêm các bản ghi Participants cho người dùng và bạn bè vào cuộc trò chuyện
            $participantData = [
                ['conversation_id' => $conversation->id, 'user_id' => $user->id, 'created_at' => Carbon::now()],
                ['conversation_id' => $conversation->id, 'user_id' => $friendId, 'created_at' => Carbon::now()]
            ];
            Participant::insert($participantData);
            return response()->json([
                'status' => true,
                'message' => 'Đã chấp nhận lời kết bạn, hãy nhan tin tán tiỉnh đê'
            ], 200);
        }catch (\Exception $e){
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    public function getConfirmationFriends(){
        $user = Auth::user();
        $confirmationLists = Friendship::with('friend') // Eager load thông tin friend
        ->where('user_id', $user->id)
            ->where('status', 'pending')
            ->get();

        return response()->json($confirmationLists);
    }
    public function friendRelationship($friend_id){
        $user =  Auth::user();
        $friendSelected = Friendship::with('friend')->where('user_id', $user->id)
            ->where('friend_id', $friend_id)
            ->first();
        return response()->json($friendSelected);
    }
    public function blockFriend(Request $request){
        $user = Auth::user();
        $friend_id = $request->input('friend_id');
        $friendBlock = Friendship::with('friend') // Eager load thông tin friend
        ->where('user_id', $user->id)
            ->where('friend_id', $friend_id)
            ->first();
        if($friendBlock){
            $friendBlock->update([
                'status'=>"block"
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Đã block thành công , khong thể chat với người này khi chưa mở chặn'
            ], 200);
        }else{
            return response()->json([
                'status' => true,
                'message' => ' khong tìm thay người dùng này'
            ], 404);
        }

    }
    public function openBlock(Request $request){
        $user =  Auth::user();
        $frien_block_id = $request->input('friend_block_id');
        $friendBlock = Friendship::with('friend') // Eager load thông tin friend
        ->where('user_id', $user->id)
            ->where('friend_id', $frien_block_id)
            ->where('status','block')
            ->first();
        if($friendBlock){
            $friendBlock->update([
                'status'=>"active"
            ]);
            return response()->json([
                'status' => true,
                'message' => 'Đã mở block  người này, tán tỉnh nhau tiếp đi'
            ], 200);
        }else{
            return response()->json([
                'status' => true,
                'message' => ' khong tìm thay người dùng này'
            ], 404);
        }
    }

}
