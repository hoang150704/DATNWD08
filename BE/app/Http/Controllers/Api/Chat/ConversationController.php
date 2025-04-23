<?php

namespace App\Http\Controllers\Api\Chat;

use App\Enums\SystemEnum;
use App\Events\ConversationAssignedEvent;
use App\Events\ConversationClaimedEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateConversationRequest;
use App\Http\Resources\ConversationResource;
use App\Http\Resources\ConvesationTransfer;
use App\Models\Conversation;
use App\Services\Chat\ConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    protected $conversationService;

    public function __construct(ConversationService $conversationService)
    {
        $this->conversationService = $conversationService;
    }
    //
    public function createAndAssign(CreateConversationRequest $request)
    {
        try {
            //code...
            DB::beginTransaction();
            $data = $request->validated();
            $conversation = $this->conversationService->createAndAssign($data);
            DB::commit();
            return response()->json([
                'message' => 'Tạo cuộc trò chuyện thành công',
                'data' => $conversation,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi tạo hội thoại',
                'errors' => $e->getMessage()
            ], 403);
        }
    }

    public function getActiveConversation(Request $request)
    {
        $user_id = auth('sanctum')->user()?->id ?? null;
        $guest_id = $request->input('guest_id');
        if ($user_id) {
            $conversation = $this->conversationService->findOpenByCustomer($user_id);
        } elseif ($guest_id) {
            $conversation = $this->conversationService->findOpenByGuest($guest_id);
        } else {
            return response()->json([
                'conversation' => null,
                'message' => 'Không xác định được người dùng',
            ], 200);
        }
        return response()->json([
            'conversation' => $conversation,
        ]);
    }

    public function myConversations()
    {
        try {
            //code...
            $paginate = 20;
            $user = auth('sanctum')->user();

            if (!$user || !in_array($user?->role, [SystemEnum::ADMIN, SystemEnum::STAFF])) {
                return response()->json([
                    'message' => 'Bạn không có quyền truy cập danh sách này.'
                ], 403);
            }

            $conversations = $this->conversationService->myConversations($user->id, $paginate);
            $tranferPending = $this->conversationService->getPendingTransferByStaff($user->id);
            return response()->json([
                'conversations' => ConversationResource::collection($conversations),
                'pagination' => [
                    'total' => $conversations->total(),
                    'per_page' => $conversations->perPage(),
                    'current_page' => $conversations->currentPage(),
                    'last_page' => $conversations->lastPage(),
                ],
                'transfer'=>ConvesationTransfer::collection($tranferPending)
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                'message' => $th->getMessage(),

            ]);
        }
    }

    public function adminConversations(Request $request)
    {
        $paginate = 30;
        $user = auth('sanctum')->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Không có quyền truy cập'], 403);
        }
        $filters = $request->only(['status', 'staff_id']);

        $conversations = $this->conversationService->adminConversations($paginate, $filters);

        return response()->json([
            'conversations' => ConversationResource::collection($conversations),
            'pagination' => [
                'total' => $conversations->total(),
                'per_page' => $conversations->perPage(),
                'current_page' => $conversations->currentPage(),
                'last_page' => $conversations->lastPage(),
            ]
        ]);
    }

    public function claim(int $id)
    {
        $user = auth('sanctum')->user();

        if (!$user || !in_array($user->role, [SystemEnum::ADMIN, SystemEnum::STAFF])) {
            return response()->json(['message' => 'Không có quyền nhận cuộc trò chuyện'], 403);
        }

        try {
            $success = $this->conversationService->claim($id, $user->id);

            if (!$success) {
                return response()->json([
                    'message' => 'Cuộc trò chuyện đã có người nhận hoặc không khả dụng'
                ], 400);
            } else {
                $conversation = Conversation::with('staff')->find($id);
                event(new ConversationClaimedEvent($conversation));
            }
            return response()->json(['message' => 'Bạn đã nhận hỗ trợ cuộc trò chuyện thành công']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Lỗi khi nhận hội thoại',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assignToStaff(Request $request, int $id)
    {
        
        $user = auth('sanctum')->user();

        if (!$user || $user->role !== 'admin') {
            return response()->json(['message' => 'Không có quyền truy cập'], 403);
        }

        // Validate nhanh ngay tại controller
        $validated = $request->validate([
            'staff_id' => 'required|integer|exists:users,id',
        ]);

        try {
            DB::beginTransaction();
            $success = $this->conversationService->assignToStaff($id, $validated['staff_id']);
            
            if (!$success) {
                DB::rollBack();
                return response()->json([
                    'message' => 'Cuộc trò chuyện không khả dụng hoặc nhân viên không online',
                    'data'=>$success
                ], 400);
            } else {
                $conversation = Conversation::with('staff')->find($id);
                event(new ConversationAssignedEvent($conversation));
            }
            DB::commit();
            return response()->json(['message' => 'Gán nhân viên thành công']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi khi gán nhân viên',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function close(Request $request, int $id)
    {
        $user = auth('sanctum')->user();

        if (!$user || !in_array($user->role, [SystemEnum::ADMIN, SystemEnum::STAFF])) {
            return response()->json(['message' => 'Bạn không có quyền đóng cuộc trò chuyện'], 403);
        }

        $validated = $request->validate([
            'note' => 'nullable|string|max:1000',
        ]);

        try {
            $success = $this->conversationService->close($id, $user, $validated['note'] ?? null);

            if (!$success) {
                return response()->json(['message' => 'Không thể đóng cuộc trò chuyện'], 400);
            }

            return response()->json(['message' => 'Cuộc trò chuyện đã được đóng thành công']);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Lỗi khi đóng hội thoại',
                'error'   => $e->getMessage()
            ], 500);
        }
    }
    //
    public function requestTransfer(Request $request, int $conversationId)
    {
        $user = auth('sanctum')->user();

        $validated = $request->validate([
            'to_staff_id' => 'required|exists:users,id',
            'note' => 'nullable|string|max:255',
        ]);

        try {
            $conversation = $this->conversationService->requestTransfer(
                $conversationId,
                $user->id,
                $validated['to_staff_id'],
                $validated['note'] ?? null
            );

            if (!$conversation) {
                return response()->json([
                    'message' => 'Không thể tạo yêu cầu chuyển',
                    'data'=>[
                        'conversationId'=>$conversationId,
                        'from'=>$user->id,
                        'to_staff_id'=>$validated['to_staff_id'],
                        'note'=>$validated['note']
                    ]
            ], 400);
            }

            return response()->json([
                'message' => 'Đã gửi yêu cầu chuyển cuộc trò chuyện',
                'conversation' => $conversation
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Lỗi xử lý', 'error' => $e->getMessage()], 500);
        }
    }

    public function acceptTransfer(Request $request, int $transferId)
    {
        try {
            $conversation = $this->conversationService->acceptTransfer($transferId);

            if (!$conversation) {
                return response()->json(['message' => 'Không thể chấp nhận yêu cầu chuyển'], 400);
            }

            return response()->json([
                'message' => 'Đã tiếp nhận cuộc trò chuyện',
                'conversation' => $conversation
            ]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Lỗi xử lý', 'error' => $e->getMessage()], 500);
        }
    }

    public function rejectTransfer(Request $request, int $transferId)
    {
        try {
            $success = $this->conversationService->rejectTransfer($transferId);

            if (!$success) {
                return response()->json(['message' => 'Không thể từ chối yêu cầu chuyển'], 400);
            }

            return response()->json(['message' => 'Đã từ chối nhận cuộc trò chuyện']);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Lỗi xử lý', 'error' => $e->getMessage()], 500);
        }
    }


    //
    public function unassignedConversations(Request $request)
    {
        $paginate = 50;
        $user = auth('sanctum')->user();

        if (!$user || !in_array($user->role, ['admin', 'staff'])) {
            return response()->json(['message' => 'Không có quyền truy cập'], 403);
        }


        try {
            $conversations = $this->conversationService->unassignedConversations($paginate);

            return response()->json([
                'conversations' => ConversationResource::collection($conversations),
                'pagination' => [
                    'total' => $conversations->total(),
                    'per_page' => $conversations->perPage(),
                    'current_page' => $conversations->currentPage(),
                    'last_page' => $conversations->lastPage(),
                ]
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Lỗi khi lấy danh sách',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
