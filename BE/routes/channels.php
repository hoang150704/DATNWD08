<?php

use App\Models\StaffSession;
use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('voucher-channel', function () {
    return true; // Cho phép tất cả truy cập kênh
});
Broadcast::channel('staff.{id}', function ($user, $id) {
    if ($user->id == $id && in_array($user->role, ['staff', 'admin'])) {
        StaffSession::updateOrCreate(
            ['staff_id' => $user->id],
            ['last_seen_at' => now()]
        );
        return true;
    }

    return false;
});

Broadcast::channel('admin', function ($user) {
    if ($user->role === 'admin') {
        StaffSession::updateOrCreate(
            ['staff_id' => $user->id],
            ['last_seen_at' => now()]
        );
        return true;
    }

    return false;
});

Broadcast::channel('conversation.{id}', function () {
    return true; // hoặc kiểm tra quyền nếu cần
});
