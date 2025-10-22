<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommentStatsController extends Controller
{
    /**
     * Get comment statistics.
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Validate date range parameters
        $validated = $request->validate([
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        $dateFrom = $validated['date_from'] ?? null;
        $dateTo = $validated['date_to'] ?? null;

        // 1. Total number of comments
        $totalComments = Comment::count();

        // 2. Number of comments by date range (if provided)
        $commentsByDateRange = null;
        if ($dateFrom || $dateTo) {
            $query = Comment::query();
            
            if ($dateFrom) {
                $query->where('created_at', '>=', $dateFrom);
            }
            
            if ($dateTo) {
                $query->where('created_at', '<=', $dateTo . ' 23:59:59');
            }
            
            $commentsByDateRange = [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'total' => $query->count(),
            ];
        }

        // 3. Comments activity by hour (0-23)
        $commentsByHour = Comment::select(
                DB::raw('EXTRACT(HOUR FROM created_at) as hour'),
                DB::raw('count(*) as count')
            )
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->pluck('count', 'hour')
            ->toArray();

        // Fill missing hours with 0
        $commentsByHourFilled = [];
        for ($i = 0; $i < 24; $i++) {
            $commentsByHourFilled[$i] = $commentsByHour[$i] ?? 0;
        }

        // 4. Comments activity by weekday (0=Sunday, 6=Saturday)
        $commentsByWeekday = Comment::select(
                DB::raw('EXTRACT(DOW FROM created_at) as weekday'),
                DB::raw('count(*) as count')
            )
            ->groupBy('weekday')
            ->orderBy('weekday')
            ->get()
            ->pluck('count', 'weekday')
            ->toArray();

        // Fill missing weekdays with 0 and add day names
        $weekdayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        $commentsByWeekdayFormatted = [];
        for ($i = 0; $i < 7; $i++) {
            $commentsByWeekdayFormatted[] = [
                'day' => $weekdayNames[$i],
                'day_number' => $i,
                'count' => $commentsByWeekday[$i] ?? 0,
            ];
        }

        // 5. Comments activity by date (last 30 days)
        $commentsByDate = Comment::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('count(*) as count')
            )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'count' => $item->count,
                ];
            });

        return response()->json([
            'total_comments' => $totalComments,
            'comments_by_date_range' => $commentsByDateRange,
            'activity_by_hour' => $commentsByHourFilled,
            'activity_by_weekday' => $commentsByWeekdayFormatted,
            'activity_by_date_last_30_days' => $commentsByDate,
        ]);
    }
}

