<?php

namespace App\Http\Controllers;

use App\Models\MtnSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function show(): Response
    {
        return Inertia::render('Dashboard/Index');
    }

    /**
     * Return KPI summary for a given date.
     *
     * @return array{
     *   date: string,
     *   currency: string,
     *   summary: array{
     *     revenue: float,
     *     activeSubscribers: int,
     *     newSubscribers: int,
     *     churn: int,
     *     chargeSuccessRate: float,
     *     uniqueCharged: int
     *   },
     *   charging: array{
     *     uniqueCharged: int,
     *     successfulCharges: int,
     *     failedCharges: int
     *   }
     * }
     */
    public function kpi(Request $request): JsonResponse
    {
        $date = $request->input('date')
            ? Carbon::parse($request->input('date'))->toDateString()
            : Carbon::today()->toDateString();

        $endOfDay = $date.' 23:59:59';

        $billingSuccess = ['FSC-BL', 'RSC-BL'];
        $billingFail = ['FFL-BL', 'RFL-BL'];

        $revenue = (float) MtnSubscription::query()
            ->whereIn('status', $billingSuccess)
            ->whereDate('created_at', $date)
            ->sum('price');

        $totalSubscribed = MtnSubscription::query()
            ->where('status', 'ACT-SB')
            ->where('created_at', '<=', $endOfDay)
            ->count();

        $totalUnsubscribed = MtnSubscription::query()
            ->where('status', 'BLD-SB')
            ->where('created_at', '<=', $endOfDay)
            ->count();

        $activeSubscribers = max(0, $totalSubscribed - $totalUnsubscribed);

        $newSubscribers = MtnSubscription::query()
            ->where('status', 'ACT-SB')
            ->whereDate('created_at', $date)
            ->distinct('msisdn')
            ->count('msisdn');

        $churn = MtnSubscription::query()
            ->where('status', 'BLD-SB')
            ->whereDate('created_at', $date)
            ->distinct('msisdn')
            ->count('msisdn');

        $uniqueCharged = MtnSubscription::query()
            ->whereIn('status', $billingSuccess)
            ->whereDate('created_at', $date)
            ->distinct('msisdn')
            ->count('msisdn');

        $successfulCharges = MtnSubscription::query()
            ->whereIn('status', $billingSuccess)
            ->whereDate('created_at', $date)
            ->count();

        $failedCharges = MtnSubscription::query()
            ->whereIn('status', $billingFail)
            ->whereDate('created_at', $date)
            ->count();

        $chargeSuccessRate = $activeSubscribers > 0
            ? round($uniqueCharged / $activeSubscribers, 4)
            : 0.0;

        return response()->json([
            'date' => $date,
            'currency' => 'SDG',
            'summary' => [
                'revenue' => $revenue,
                'activeSubscribers' => $activeSubscribers,
                'newSubscribers' => $newSubscribers,
                'churn' => $churn,
                'chargeSuccessRate' => $chargeSuccessRate,
                'uniqueCharged' => $uniqueCharged,
            ],
            'charging' => [
                'uniqueCharged' => $uniqueCharged,
                'successfulCharges' => $successfulCharges,
                'failedCharges' => $failedCharges,
            ],
        ]);
    }

    /**
     * Return trend data points for a metric over the last N days.
     *
     * @return array{metric: string, points: list<array{date: string, value: float|int}>}
     */
    public function trend(Request $request): JsonResponse
    {
        $metric = $request->input('metric', '');
        $days = (int) $request->input('days', 30);
        $days = min(max($days, 1), 365);

        $validMetrics = ['revenue', 'churn', 'new_subscribers'];

        if (! in_array($metric, $validMetrics, true)) {
            return response()->json([
                'message' => 'Invalid metric. Allowed values: '.implode(', ', $validMetrics),
            ], 422);
        }

        $today = Carbon::today();
        $startDate = $today->copy()->subDays($days - 1)->startOfDay();
        $endDate = $today->copy()->endOfDay();

        // Build the query based on metric
        $query = MtnSubscription::query()
            ->selectRaw('DATE(created_at) as day')
            ->where('created_at', '>=', $startDate->toDateTimeString())
            ->where('created_at', '<=', $endDate->toDateTimeString())
            ->groupBy('day');

        if ($metric === 'revenue') {
            $query->addSelect(DB::raw('SUM(price) as value'))
                ->whereIn('status', ['FSC-BL', 'RSC-BL']);
        } elseif ($metric === 'churn') {
            $query->addSelect(DB::raw('COUNT(DISTINCT msisdn) as value'))
                ->where('status', 'BLD-SB');
        } else {
            // new_subscribers
            $query->addSelect(DB::raw('COUNT(DISTINCT msisdn) as value'))
                ->where('status', 'ACT-SB');
        }

        $rows = $query->get()->keyBy('day');

        // Build complete date range with zero-fill
        $points = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $startDate->copy()->addDays($i)->toDateString();
            $value = isset($rows[$day])
                ? ($metric === 'revenue' ? (float) $rows[$day]->value : (int) $rows[$day]->value)
                : ($metric === 'revenue' ? 0.0 : 0);

            $points[] = ['date' => $day, 'value' => $value];
        }

        return response()->json([
            'metric' => $metric,
            'points' => $points,
        ]);
    }
}
