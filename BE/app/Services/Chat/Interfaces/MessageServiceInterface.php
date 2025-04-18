<?php

namespace App\Services\Chat\Interfaces;

interface MessageServiceInterface
{
    //
    public function sendMessage(array $data);
    public function getMessages(int $conversationId, int $limit = 50);
}