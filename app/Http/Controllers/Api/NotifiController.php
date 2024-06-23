<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotifiController extends Controller
{
    public function getNotification(){
        $user = Auth::user();
        $list_notify = Friendship::where('user_id',$user->id)
            ->where('status','pending')
            ->get();
        return response()->json($list_notify );
    }
}
