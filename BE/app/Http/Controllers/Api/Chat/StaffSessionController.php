<?php

namespace App\Http\Controllers\Api\Chat;

use App\Entities\StaffSession;
use App\Http\Controllers\Controller;
use App\Http\Resources\StaffOnlineResource;
use App\Models\StaffSession as ModelsStaffSession;
use Illuminate\Http\Request;

class StaffSessionController extends Controller
{
    //
    public function getOnlineStaff(Request $request)
{
    $user = auth('sanctum')->user();

    if (!$user || !in_array($user->role, ['admin','staff'])) {
        return response()->json(['message' => 'Không có quyền truy cập'], 403);
    }

    $onlineStaff = ModelsStaffSession::where('last_seen_at', '>=', now()->subMinutes(50))
        ->with('staff:id,name,avatar,email,role')
        ->get();

    return response()->json([
        'message' => 'Danh sách nhân viên đang online',
        'data' => StaffOnlineResource::collection($onlineStaff)
    ]);
}

}
