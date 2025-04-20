<?php

namespace App\Repositories;

use Prettus\Repository\Eloquent\BaseRepository;
use Prettus\Repository\Criteria\RequestCriteria;
use App\Repositories\StaffSessionRepository;
use App\Models\StaffSession;
use App\Validators\StaffSessionValidator;
use Illuminate\Support\Facades\Log;

/**
 * Class StaffSessionRepositoryEloquent.
 *
 * @package namespace App\Repositories;
 */
class StaffSessionRepositoryEloquent extends BaseRepository implements StaffSessionRepository
{
    /**
     * Specify Model class name
     *
     * @return string
     */
    public function model()
    {
        return StaffSession::class;
    }



    /**
     * Boot up the repository, pushing criteria
     */
    public function boot()
    {
        $this->pushCriteria(app(RequestCriteria::class));
    }

    public function isStaffOnline(int $staffId): bool
    {
        $check = StaffSession::where('staff_id', $staffId)
            ->where('last_seen_at', '>=', now()->subMinutes(50));
    
        Log::info('SQL:', [
            'sql' => $check->toSql(),
            'bindings' => $check->getBindings()
        ]);
    
        return $check->exists();
    }
}
