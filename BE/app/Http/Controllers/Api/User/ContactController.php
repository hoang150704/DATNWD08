<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ContactRequest $request)
    {
        try {
            $contact = Contact::create($request->all());
            return response()->json($contact, 201);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function history(Request $request)
    {
        try {
            $user = User::find($request->user_id);
            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }
            // Lấy danh sách contact theo user_id
            $contactsByUserId = Contact::where('user_id', $request->user_id);
            // Lấy danh sách contact theo email (loại trừ những contact đã lấy bằng user_id để tránh trùng lặp)
            $contactsByEmail = Contact::where('email', $user->email)
                ->whereNotIn('id', $contactsByUserId->pluck('id'));
            // Kết hợp hai truy vấn lại
            $contacts = $contactsByUserId->union($contactsByEmail)->paginate(10);
            
            return response()->json($contacts, 200);
            
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'message' => 'Lỗi hệ thống',
            ], 500);
        }
    }

}
