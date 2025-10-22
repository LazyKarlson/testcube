<?php

namespace App\Http\Controllers\Api;

use App\Contracts\CacheServiceInterface;
use App\Http\Controllers\Controller;
use App\Services\StatisticsService;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __construct(
        private StatisticsService $statsService,
        private CacheServiceInterface $cache
    ) {}

    /**
     * Get post statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function posts(Request $request)
    {
        // Validate date range parameters
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        // Create cache key based on parameters
        $cacheKey = 'api:stats:posts';
        if ($dateFrom || $dateTo) {
            $cacheKey .= ':'.($dateFrom ?? 'null').':'.($dateTo ?? 'null');
        }

        // Cache for 15 minutes (900 seconds)
        return $this->cache->remember($cacheKey, 900, function () use ($dateFrom, $dateTo) {
            $stats = $this->statsService->getPostStatistics($dateFrom, $dateTo);

            return response()->json($stats);
        });
    }

    /**
     * Get comment statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function comments(Request $request)
    {
        // Validate date range parameters
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        // Create cache key based on parameters
        $cacheKey = 'api:stats:comments';
        if ($dateFrom || $dateTo) {
            $cacheKey .= ':'.($dateFrom ?? 'null').':'.($dateTo ?? 'null');
        }

        // Cache for 15 minutes (900 seconds)
        return $this->cache->remember($cacheKey, 900, function () use ($dateFrom, $dateTo) {
            $stats = $this->statsService->getCommentStatistics($dateFrom, $dateTo);

            return response()->json($stats);
        });
    }

    /**
     * Get user statistics.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function users(Request $request)
    {
        // Validate date range parameters
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        // Create cache key based on parameters
        $cacheKey = 'api:stats:users';
        if ($dateFrom || $dateTo) {
            $cacheKey .= ':'.($dateFrom ?? 'null').':'.($dateTo ?? 'null');
        }

        // Cache for 15 minutes (900 seconds)
        return $this->cache->remember($cacheKey, 900, function () use ($dateFrom, $dateTo) {
            $stats = $this->statsService->getUserStatistics($dateFrom, $dateTo);

            return response()->json($stats);
        });
    }
}
