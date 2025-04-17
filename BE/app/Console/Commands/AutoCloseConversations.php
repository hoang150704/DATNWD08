<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AutoCloseConversations extends Command
{
    protected $signature = 'chat:auto-close';

    protected $description = 'Tự động đóng các cuộc trò chuyện không hoạt động trong 30 phút';

    public function handle()
    {
        $cutoff = now()->subMinutes(30);

        // Lấy các cuộc trò chuyện đang mở
        $conversations = Conversation::where('status', 'open')->get();

        $closedCount = 0;

        foreach ($conversations as $conversation) {
            $latestMessage = $conversation->messages()->latest()->first();

            if (!$latestMessage || $latestMessage->created_at < $cutoff) {
                $conversation->update([
                    'status'      => 'closed',
                    'closed_at'   => now(),
                    'close_note'  => 'Tự động đóng do không có phản hồi',
                ]);

          

                $closedCount++;
            }
        }

        $this->info("Đã tự động đóng {$closedCount} cuộc trò chuyện.");
    }
}

