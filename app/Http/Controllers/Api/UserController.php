<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    //
    public function getUsers()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Người dùng không tồn tại'
            ], 404);
        }

        try {
            $userData = $request->only(['name', 'email', 'phone', 'address', 'avatar', 'date_of_birth']);

            // Kiểm tra xem request có chứa trường 'password' hay không
            if ($request->has('password')) {
                $userData['password'] = bcrypt($request->input('password'));
            }

            // Cập nhật thông tin người dùng
            $user->update($userData);

            return response()->json([
                'status' => true,
                'message' => 'Đã cập nhật thành công'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function getUser()
    {
        $user = Auth::user();
        if ($user) {
            return response()->json($user, 200);
        } else {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        try {
            // Kiểm tra xem request có chứa trường 'current_password', 'new_password' và 'confirm_new_password' hay không
            $request->validate([
                'current_password' => 'required',
                'new_password' => 'required|min:6',
                'confirm_new_password' => 'required|same:new_password'
            ]);

            // Kiểm tra xem mật khẩu hiện tại có khớp với mật khẩu trong database hay không
            if (!Hash::check($request->input('current_password'), $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Mật khẩu hiện tại không đúng'
                ], 400);
            }

            // Cập nhật mật khẩu mới
            $user->update([
                'password' => bcrypt($request->input('new_password'))
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Đã cập nhật thành công'
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Cập nhật thất bại',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function checkEmail(Request $request){
        $email = $request->input('email');
        $exists = User::where('email', $email)->exists();
        return response()->json(['exists' => $exists]);
    }

    public function checkPassword(Request $request){
       $current_password = $request->input('current_password');
       $user = Auth::user();

       if (Hash::check($current_password, $user->password)) {
           // Mật khẩu khớp
           return response()->json(['isAuthenticated' => true]);
       } else {
           // Mật khẩu không khớp
           return response()->json(['isAuthenticated' => false]);
       }
   }
   public function searchUser(Request $request){
       $search_stranger = $request->input('search_stranger');

       // Sử dụng phương thức where của Eloquent để tìm kiếm người dùng theo tên
       $result = User::where('name', 'LIKE', '%' . $search_stranger . '%')->get();

       if (count($result) > 0) {
           return response()->json($result, 200);
       } else {
           return response()->json(['Result' => 'Không tìm thấy kết quả nào'], 404);
       }
   }

}
