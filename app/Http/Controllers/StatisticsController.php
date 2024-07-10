<?php

namespace App\Http\Controllers;

use App\Enum\PaymentMethodEnum;
use App\Enum\StatusEnum;
use App\Models\IncomeItem;
use App\Models\OutcomeItem;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tymon\JWTAuth\Facades\JWTAuth;

class StatisticsController extends Controller
{
    /**
     * Get total balance
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTotalBalance()
    {
        $income = IncomeItem::all()
            ->sum('amount');
        $outcome = OutcomeItem::all()
            ->sum('amount');

        return response()->json(["income" => $income, "outcome" => $outcome], Response::HTTP_OK);
    }

    /**
     * Get gross income and outcome by month
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGrossStatistics(Request $request): \Illuminate\Http\JsonResponse
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $user = JWTAuth::parseToken()->authenticate();

        $income = $user->incomes()
            ->join('income_items', 'incomes.id', '=', 'income_items.income_id')
            ->whereMonth('income_items.payment_date', $month)
            ->whereYear('income_items.payment_date', $year)
            ->sum('income_items.amount');
        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->sum('outcome_items.amount');
        $ahorro = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->where('payment_method', PaymentMethodEnum::AHORRO)
            ->sum('outcome_items.amount');
        $projects = $user->projects()
            ->join('project_tasks', 'projects.id', '=', 'project_tasks.project_id')
            ->whereMonth('project_tasks.start_date', $month)
            ->whereYear('project_tasks.start_date', $year)
            ->where('project_tasks.status', StatusEnum::FINISHED->value)
            ->sum('project_tasks.amount');
        $activities = $user->activities()
            ->join('activity_tasks', 'activities.id', '=', 'activity_tasks.activity_id')
            ->whereMonth('activity_tasks.start_date', $month)
            ->whereYear('activity_tasks.start_date', $year)
            ->where('activity_tasks.status', StatusEnum::FINISHED->value)
            ->sum('activity_tasks.amount');

        $result = array("income" => $income, "outcome" => $outcome + $projects + $activities, "ahorro" => $ahorro);
        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * Get overall outcome analysis, Paid vs Pending
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getGrossOutcome(Request $request): \Illuminate\Http\JsonResponse
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $user = JWTAuth::parseToken()->authenticate();

        $outcome_pending = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->where('outcome_items.status', StatusEnum::PENDING->value)
            ->sum('outcome_items.amount');
        $outcome_paid = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->where('outcome_items.status', StatusEnum::FINISHED->value)
            ->sum('outcome_items.amount');
        $projects_pending = $user->projects()
            ->join('project_tasks', 'projects.id', '=', 'project_tasks.project_id')
            ->whereMonth('project_tasks.start_date', $month)
            ->whereYear('project_tasks.start_date', $year)
            ->where('project_tasks.status', StatusEnum::PENDING->value)
            ->sum('project_tasks.amount');
        $projects_paid = $user->projects()
            ->join('project_tasks', 'projects.id', '=', 'project_tasks.project_id')
            ->whereMonth('project_tasks.start_date', $month)
            ->whereYear('project_tasks.start_date', $year)
            ->where('project_tasks.status', StatusEnum::FINISHED->value)
            ->sum('project_tasks.amount');
        $activities_paid = $user->activities()
            ->join('activity_tasks', 'activities.id', '=', 'activity_tasks.activity_id')
            ->whereMonth('activity_tasks.start_date', $month)
            ->whereYear('activity_tasks.start_date', $year)
            ->where('activity_tasks.status', StatusEnum::FINISHED->value)
            ->sum('activity_tasks.amount');
        $activities_pending = $user->activities()
            ->join('activity_tasks', 'activities.id', '=', 'activity_tasks.activity_id')
            ->whereMonth('activity_tasks.start_date', $month)
            ->whereYear('activity_tasks.start_date', $year)
            ->where('activity_tasks.status', StatusEnum::PENDING->value)
            ->sum('activity_tasks.amount');

        $result = array(
            "paid" => $outcome_paid + $projects_paid + $activities_paid,
            "pending" => $outcome_pending + $projects_pending + $activities_pending
        );
        return response()->json($result, Response::HTTP_OK);
    }

    /**
     * Get monthly outcomes by category
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOutcomeByCategory(Request $request): \Illuminate\Http\JsonResponse
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        $user = JWTAuth::parseToken()->authenticate();

        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->groupBy('outcomes.category')
            ->selectRaw('outcomes.category, SUM(outcome_items.amount) as total_amount')
            ->get();

        $outcome->map(function ($item){
            return $item->category_text = $item->category->name;
        });

        return response()->json($outcome, Response::HTTP_OK);
    }

    /**
     * Get pending outcomes by name
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPendingOutcome(Request $request): \Illuminate\Http\JsonResponse
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        $user = JWTAuth::parseToken()->authenticate();

        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->where('outcome_items.status', StatusEnum::PENDING)
            ->groupBy('outcomes.name')
            ->selectRaw('outcomes.name, SUM(outcome_items.amount) as total_amount')
            ->get();

        return response()->json($outcome, Response::HTTP_OK);
    }

    /**
     * Get 5 major outcomes
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMajorOutcome(Request $request): \Illuminate\Http\JsonResponse
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        $user = JWTAuth::parseToken()->authenticate();

        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->groupBy('outcomes.name')
            ->selectRaw('outcomes.name, SUM(outcome_items.amount) as total_amount')
            ->orderBy('total_amount', 'desc')
            ->limit(5)
            ->get();

        return response()->json($outcome, Response::HTTP_OK);
    }

    /**
     * Get statistics by day
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDailyStatistics(Request $request): \Illuminate\Http\JsonResponse
    {
        $days = $request->days;
        $user = JWTAuth::parseToken()->authenticate();
        $income = $user->incomes()
            ->join('income_items', 'incomes.id', '=', 'income_items.income_id')
            ->where('income_items.payment_date', '>=', Carbon::now()->subDays($days)->startOfDay())
            ->selectRaw("DATE_FORMAT(income_items.payment_date, '%Y-%m-%d') as payment_day, SUM(income_items.amount) as total_amount")
            ->groupBy('payment_day')
            ->orderBy('payment_day', 'desc')
            ->get();
        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->where('outcome_items.payment_date', '>=', Carbon::now()->subDays($days)->startOfDay())
            ->selectRaw("DATE_FORMAT(outcome_items.payment_date, '%Y-%m-%d') as payment_day, SUM(outcome_items.amount) as total_amount")
            ->groupBy('payment_day')
            ->orderBy('payment_day', 'desc')
            ->get();
        $projects = $user->projects()
            ->join('project_tasks', 'projects.id', '=', 'project_tasks.project_id')
            ->where('project_tasks.start_date', '>=', Carbon::now()->subDays($days)->startOfDay())
            ->where('project_tasks.status', StatusEnum::FINISHED)
            ->selectRaw("DATE_FORMAT(project_tasks.start_date, '%Y-%m-%d') as payment_day, SUM(project_tasks.amount) as total_amount")
            ->groupBy('payment_day')
            ->orderBy('payment_day', 'desc')
            ->get();
        $activities = $user->activities()
            ->join('activity_tasks', 'activities.id', '=', 'activity_tasks.activity_id')
            ->where('activity_tasks.start_date', '>=', Carbon::now()->subDays($days)->startOfDay())
            ->where('activity_tasks.status', StatusEnum::FINISHED)
            ->selectRaw("DATE_FORMAT(activity_tasks.start_date, '%Y-%m-%d') as payment_day, SUM(activity_tasks.amount) as total_amount")
            ->groupBy('payment_day')
            ->orderBy('payment_day', 'desc')
            ->get();

        $income = $income->map(function ($item) {
            return [
                'date' => $item->payment_day,
                'amount' => $item->total_amount,
                'type' => 'income'
            ];
        });
        $outcome = $outcome->map(function ($item) {
            return [
                'date' => $item->payment_day,
                'amount' => $item->total_amount,
                'type' => 'outcome'
            ];
        });
        $projects = $projects->map(function ($item) {
            return [
                'date' => $item->payment_day,
                'amount' => $item->total_amount,
                'type' => 'project'
            ];
        });
        $activities = $activities->map(function ($item) {
            return [
                'date' => $item->payment_day,
                'amount' => $item->total_amount,
                'type' => 'activity'
            ];
        });
        $merged = $income->concat($outcome)->concat($projects)->concat($activities)->sortBy('date');
        $merged = $merged->groupBy('date')->map(function ($group) {
            return [
                'date' => $group->first()['date'],
                'income' => $group->where('type', 'income')->sum('amount'),
                'outcome' => $group->where('type', 'outcome')->sum('amount'),
                'project' => $group->where('type', 'project')->sum('amount'),
                'activity' => $group->where('type', 'activity')->sum('amount')
            ];
        });
        $merged = $merged->sortBy('date')->values();

        return response()->json($merged, Response::HTTP_OK);
    }
}
