<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $contacts = Contact::latest()->paginate(10);
        return response()->json($contacts, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $contact = Contact::create($request->validated());
        return response()->json($contact, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $contact = Contact::find($id);

        if (!$contact) {
            return response()->json(['message' => 'Contact not found'], 404);
        }

        return response()->json($contact, 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        try {
            $contact = Contact::find($id);

            if (!$contact) {
                return response()->json(['message' => 'Contact not found'], 404);
            }

            $contact->delete(); // Xóa mềm (chỉ cập nhật deleted_at)

            return response()->json(['message' => 'Contact deleted successfully'], 204);
            
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function restore(string $id)
    {
        try {
            $contact = Contact::withTrashed()->find($id);

            if (!$contact) {
                return response()->json(['message' => 'Contact not found'], 404);
            }

            $contact->restore(); // Khôi phục contact

            return response()->json(['message' => 'Contact restored successfully'], 200);
            
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function forceDelete(string $id)
    {
        try {
            $contact = Contact::withTrashed()->find($id);

            if (!$contact) {
                return response()->json(['message' => 'Contact not found'], 404);
            }

            $contact->forceDelete(); // Xóa vĩnh viễn

            return response()->json(['message' => 'Contact permanently deleted'], 204);
            
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
