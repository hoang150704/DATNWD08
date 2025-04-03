<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Contact\replyMailRequest;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Jobs\sendMailReplyContactJob;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $contacts = Contact::latest()->paginate(10);
            
            return response()->json($contacts, 200);
            
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $contact = Contact::find($id);

            if (!$contact) {
                return response()->json(['message' => 'Không tìm thấy liên hệ'], 404);
            }

            return response()->json($contact, 200);

        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {

        try {
            $contact = Contact::find($id);

            if (!$contact) {
                return response()->json(['message' => 'Không tìm thấy liên hệ'], 404);
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
                return response()->json(['message' => 'Không tìm thấy liên hệ'], 404);
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
                return response()->json(['message' => 'Không tìm thấy liên hệ'], 404);
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

    public function reply_mail(replyMailRequest $request)
    {
        try { 
            $contact = Contact::find($request->contact_id);
    
            // Kiểm tra nếu contact
            if (!$contact) {
                return response()->json(['message' => 'Liên hệ không tồn tại hoặc đã bị xóa'], 404);
            }
    
            // Chỉ cho phép gửi mail nếu trạng thái là 'in_progress'
            if ($contact->status !== 'in_progress') {
                return response()->json([
                    'message' => 'Chỉ được phép gửi mail khi trạng thái là "Đang xử lý"',
                    'current_status' => $contact->status
                ], 400);
            }
    
            // Gửi mail và cập nhật trạng thái thành 'resolved'
            sendMailReplyContactJob::dispatch($request->email, $request->name, $request->content);
            $contact->update(['status' => 'resolved']);
    
            return response()->json([
                'message' => 'Trả lời mail thành công',
            ]);
    
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function startProcessing(string $id)
    {
        try {
            $contact = Contact::find($id);

            if (!$contact) {
                return response()->json(['message' => 'Không tìm thấy liên hệ'], 404);
            }

            // Kiểm tra nếu trạng thái hiện tại là 'pending' mới cho phép cập nhật
            if ($contact->status !== 'pending') {
                return response()->json([
                    'message' => 'Chỉ có thể cập nhật trạng thái từ PENDING sang IN_PROGRESS',
                    'current_status' => $contact->status
                ], 400);
            }

            // Cập nhật trạng thái và lưu thời gian bắt đầu xử lý (nếu cần)
            $contact->update([
                'status' => 'in_progress',
            ]);

            return response()->json([
                'message' => 'Cập nhật trạng thái thành công: Đang xử lý',
                'contact' => $contact
            ], 200);

        } catch (\Exception $e) {
            Log::error($e);
            return response()->json([
                'message' => 'Lỗi hệ thống',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
