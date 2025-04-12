<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected $statisticsService;

    public function __construct(StatisticsService $statisticsService)
    {
        $this->statisticsService = $statisticsService;
    }

    public function dashboard(Request $request)
    {
        // Lấy tham số period từ request
        $period = $request->query('period');
        $startDate = $request->query('startDate');
        $endDate = $request->query('endDate');

        // Lấy thống kê cố định
        $fixedStats = $this->statisticsService->getFixedStatistics();

        // Lấy thống kê theo thời gian
        $timeBasedStats = $this->statisticsService->getStatisticsByPeriod($period, $startDate, $endDate);

        return response()->json([
            "status" => "success",
            "message" => "Lấy dữ liệu dashboard thành công!",
            "data" => array_merge($fixedStats, $timeBasedStats, [
                "period" => $period,
                "startDate" => $startDate,
                "endDate" => $endDate,
            ])
        ], 200);
    }

    // Lấy thống kê hôm nay
    public function todayStatistics()
    {
        $stats = $this->statisticsService->getTodayStatistics();
        return response()->json([
            "status" => "success",
            "message" => "Lấy thống kê hôm nay thành công!",
            "data" => $stats
        ], 200);
    }

    // Lấy thống kê hôm qua
    public function yesterdayStatistics()
    {
        $stats = $this->statisticsService->getYesterdayStatistics();
        return response()->json([
            "status" => "success",
            "message" => "Lấy thống kê hôm qua thành công!",
            "data" => $stats
        ], 200);
    }

    // Lấy thống kê tuần này
    public function thisWeekStatistics()
    {
        $stats = $this->statisticsService->getThisWeekStatistics();
        return response()->json([
            "status" => "success",
            "message" => "Lấy thống kê tuần này thành công!",
            "data" => $stats
        ], 200);
    }

    // Lấy thống kê tuần trước
    public function lastWeekStatistics()
    {
        $stats = $this->statisticsService->getLastWeekStatistics();
        return response()->json([
            "status" => "success",
            "message" => "Lấy thống kê tuần trước thành công!",
            "data" => $stats
        ], 200);
    }

    // Lấy thống kê tháng này
    public function thisMonthStatistics()
    {
        $stats = $this->statisticsService->getThisMonthStatistics();
        return response()->json([
            "status" => "success",
            "message" => "Lấy thống kê tháng này thành công!",
            "data" => $stats
        ], 200);
    }

    // Lấy thống kê tháng trước
    public function lastMonthStatistics()
    {
        $stats = $this->statisticsService->getLastMonthStatistics();
        return response()->json([
            "status" => "success",
            "message" => "Lấy thống kê tháng trước thành công!",
            "data" => $stats
        ], 200);
    }

    // Lấy thống kê quý này
    public function thisQuarterStatistics()
    {
        $stats = $this->statisticsService->getThisQuarterStatistics();
        return response()->json([
            "status" => "success",
            "message" => "Lấy thống kê quý này thành công!",
            "data" => $stats
        ], 200);
    }

    // Lấy thống kê quý trước
    public function lastQuarterStatistics()
    {
        $stats = $this->statisticsService->getLastQuarterStatistics();
        return response()->json([
            "status" => "success",
            "message" => "Lấy thống kê quý trước thành công!",
            "data" => $stats
        ], 200);
    }

    // Lấy thống kê năm này
    public function thisYearStatistics()
    {
        $stats = $this->statisticsService->getThisYearStatistics();
        return response()->json([
            "status" => "success",
            "message" => "Lấy thống kê năm này thành công!",
            "data" => $stats
        ], 200);
    }

    // Lấy thống kê năm trước
    public function lastYearStatistics()
    {
        $stats = $this->statisticsService->getLastYearStatistics();
        return response()->json([
            "status" => "success",
            "message" => "Lấy thống kê năm trước thành công!",
            "data" => $stats
        ], 200);
    }

    // Lấy tất cả thống kê theo các khoảng thời gian
    public function allTimePeriodStatistics()
    {
        $stats = $this->statisticsService->getAllTimePeriodStatistics();
        return response()->json([
            "status" => "success",
            "message" => "Lấy tất cả thống kê theo khoảng thời gian thành công!",
            "data" => $stats
        ], 200);
    }
}
