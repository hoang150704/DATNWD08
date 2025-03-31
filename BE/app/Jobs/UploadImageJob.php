<?php

namespace App\Jobs;

use App\Models\Library;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class UploadImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $imagePath;
    protected $originalName;

    /**
     * Create a new job instance.
     */
    public function __construct($imagePath, $originalName)
    {
        $this->imagePath = $imagePath;
        $this->originalName = $originalName;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Kiểm tra file có tồn tại trước khi upload
            if (!file_exists($this->imagePath)) {
                throw new \Exception("File không tồn tại: {$this->imagePath}");
            }

            // Upload lên Cloudinary
            $result = cloudinary()->upload($this->imagePath);

            Log::info("Cloudinary Response for {$this->originalName}: " . json_encode($result));

            if (!$result) {
                throw new \Exception("Không nhận được phản hồi từ Cloudinary");
            }

            // Lấy đường dẫn URL và Public ID
            $url = $result->getSecurePath() ?? null;
            $publicId = $result->getPublicId() ?? null;

            if (!$url || !$publicId) {
                throw new \Exception("Cloudinary trả về dữ liệu không hợp lệ. Kết quả: " . json_encode($result));
            }

            // Lưu vào database
            Library::create([
                'public_id' => $publicId,
                'url' => $url
            ]);

            Log::info("Upload thành công: {$this->originalName} - URL: $url");

            // **Xóa ảnh sau khi upload thành công**
            if (file_exists($this->imagePath)) {
                unlink($this->imagePath);
                Log::info("Đã xóa file: {$this->imagePath}");
            }

        } catch (\Exception $e) {
            Log::error("Upload thất bại: {$this->originalName} - Lỗi: " . $e->getMessage());
        }
    }
}
