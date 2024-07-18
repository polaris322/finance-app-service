<?php

namespace App\Http\Controllers;

use App\Enum\PaymentMethodEnum;
use App\Enum\StatusEnum;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class StatisticsController extends Controller
{
    /**
     * Get balance by month
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBalanceByMonth(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $user = JWTAuth::parseToken()->authenticate();
        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }

        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }

        $income = $user->incomes()
            ->join('income_items', 'incomes.id', '=', 'income_items.income_id')
            ->whereMonth('income_items.payment_date', $month)
            ->whereYear('income_items.payment_date', $year)
            ->where('income_items.status', StatusEnum::FINISHED)
            ->sum('income_items.amount');
        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->where('outcome_items.status', StatusEnum::FINISHED)
            ->sum('outcome_items.amount');
        $projects = $user->projects()
            ->join('project_tasks', 'projects.id', '=', 'project_tasks.project_id')
            ->whereMonth('project_tasks.start_date', $month)
            ->whereYear('project_tasks.start_date', $year)
            ->where('project_tasks.status', StatusEnum::FINISHED)
            ->sum('project_tasks.amount');
        $activities = $user->activities()
            ->join('activity_tasks', 'activities.id', '=', 'activity_tasks.activity_id')
            ->whereMonth('activity_tasks.start_date', $month)
            ->whereYear('activity_tasks.start_date', $year)
            ->where('activity_tasks.status', StatusEnum::FINISHED)
            ->sum('activity_tasks.amount');

        return response()->json(["balance" => $income - $outcome - $projects - $activities]);
    }

    /**
     * Get total balance
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTotalBalance()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }
        $income = $user->incomes()
            ->join('income_items', 'incomes.id', '=', 'income_items.income_id')
            ->where('income_items.status', StatusEnum::FINISHED)
            ->sum('income_items.amount');
        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->where('outcome_items.status', StatusEnum::FINISHED)
            ->sum('outcome_items.amount');
        $projects = $user->projects()
            ->join('project_tasks', 'projects.id', '=', 'project_tasks.project_id')
            ->where('project_tasks.status', StatusEnum::FINISHED->value)
            ->sum('project_tasks.amount');
        $activities = $user->activities()
            ->join('activity_tasks', 'activities.id', '=', 'activity_tasks.activity_id')
            ->where('activity_tasks.status', StatusEnum::FINISHED->value)
            ->sum('activity_tasks.amount');

        return response()->json(["income" => $income, "outcome" => $outcome + $projects + $activities], Response::HTTP_OK);
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
        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }

        $income = $user->incomes()
            ->join('income_items', 'incomes.id', '=', 'income_items.income_id')
            ->whereMonth('income_items.payment_date', $month)
            ->whereYear('income_items.payment_date', $year)
            ->where('income_items.status', StatusEnum::FINISHED)
            ->sum('income_items.amount');
        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->where('outcome_items.status', StatusEnum::FINISHED)
            ->sum('outcome_items.amount');
        $ahorro = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->where('payment_method', PaymentMethodEnum::AHORRO)
            ->where('outcome_items.status', StatusEnum::FINISHED->value)
            ->sum('outcome_items.amount');
        $emergency = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->where('payment_method', PaymentMethodEnum::EMERGENCIA)
            ->where('outcome_items.status', StatusEnum::FINISHED->value)
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

        $result = array("income" => $income, "outcome" => $outcome + $projects + $activities, "ahorro" => $ahorro, "emergency" => $emergency);
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
        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }

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
        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }

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
        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }

        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->where('outcome_items.status', StatusEnum::PENDING)
            ->groupBy('outcomes.name')
            ->selectRaw('outcomes.name, SUM(outcome_items.amount) as total_amount')
            ->get();

        $projects = $user->projects()->join('project_tasks', 'projects.id', '=', 'project_tasks.project_id')
            ->whereDate('project_tasks.start_date', '<=', Carbon::today())
            ->whereDate('project_tasks.end_date', '>=', Carbon::today())
            ->where('project_tasks.status', StatusEnum::PENDING)
            ->groupBy('project_tasks.name')
            ->selectRaw('project_tasks.name, SUM(project_tasks.amount) as total_amount')
            ->get();

        $activities = $user->activities()->join('activity_tasks', 'activities.id', '=', 'activity_tasks.activity_id')
            ->whereDate('activity_tasks.start_date', '<=', Carbon::today())
            ->whereDate('activity_tasks.end_date', '>=', Carbon::today())
            ->where('activity_tasks.status', StatusEnum::PENDING)
            ->groupBy('activity_tasks.name')
            ->selectRaw('activity_tasks.name, SUM(activity_tasks.amount) as total_amount')
            ->get();

        return response()->json($outcome->concat($projects)->concat($activities), Response::HTTP_OK);
    }

    /**
     * Get paid outcomes by name
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPaidOutcome(Request $request): \Illuminate\Http\JsonResponse
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);
        $user = JWTAuth::parseToken()->authenticate();
        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }

        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->where('outcome_items.status', StatusEnum::FINISHED)
            ->groupBy('outcomes.name')
            ->selectRaw('outcomes.name, SUM(outcome_items.amount) as total_amount')
            ->get();

        $projects = $user->projects()->join('project_tasks', 'projects.id', '=', 'project_tasks.project_id')
            ->whereDate('project_tasks.start_date', '<=', Carbon::today())
            ->whereDate('project_tasks.end_date', '>=', Carbon::today())
            ->where('project_tasks.status', StatusEnum::FINISHED)
            ->groupBy('project_tasks.name')
            ->selectRaw('project_tasks.name, SUM(project_tasks.amount) as total_amount')
            ->get();

        $activities = $user->activities()->join('activity_tasks', 'activities.id', '=', 'activity_tasks.activity_id')
            ->whereDate('activity_tasks.start_date', '<=', Carbon::today())
            ->whereDate('activity_tasks.end_date', '>=', Carbon::today())
            ->where('activity_tasks.status', StatusEnum::FINISHED)
            ->groupBy('activity_tasks.name')
            ->selectRaw('activity_tasks.name, SUM(activity_tasks.amount) as total_amount')
            ->get();

        return response()->json($outcome->concat($projects)->concat($activities), Response::HTTP_OK);
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
        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }

        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereMonth('outcome_items.payment_date', $month)
            ->whereYear('outcome_items.payment_date', $year)
            ->groupBy('outcomes.name')
            ->selectRaw('outcomes.name, SUM(outcome_items.amount) as total_amount')
            ->orderBy('total_amount', 'desc')
            ->limit(7)
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
        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }

        $income = $user->incomes()
            ->join('income_items', 'incomes.id', '=', 'income_items.income_id')
            ->where('income_items.payment_date', '>=', Carbon::now()->subDays($days)->startOfDay())
            ->where('income_items.status', StatusEnum::FINISHED)
            ->selectRaw("DATE_FORMAT(income_items.payment_date, '%Y-%m-%d') as payment_day, SUM(income_items.amount) as total_amount")
            ->groupBy('payment_day')
            ->orderBy('payment_day', 'desc')
            ->get();
        $outcome = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->whereNotIn('outcomes.payment_method', [PaymentMethodEnum::AHORRO, PaymentMethodEnum::EMERGENCIA])
            ->where('outcome_items.payment_date', '>=', Carbon::now()->subDays($days)->startOfDay())
            ->where('outcome_items.status', StatusEnum::FINISHED)
            ->selectRaw("DATE_FORMAT(outcome_items.payment_date, '%Y-%m-%d') as payment_day, SUM(outcome_items.amount) as total_amount")
            ->groupBy('payment_day')
            ->orderBy('payment_day', 'desc')
            ->get();
        $ahorro = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->where('outcomes.payment_method', PaymentMethodEnum::AHORRO)
            ->where('outcome_items.payment_date', '>=', Carbon::now()->subDays($days)->startOfDay())
            ->where('outcome_items.status', StatusEnum::FINISHED)
            ->selectRaw("DATE_FORMAT(outcome_items.payment_date, '%Y-%m-%d') as payment_day, SUM(outcome_items.amount) as total_amount")
            ->groupBy('payment_day')
            ->orderBy('payment_day', 'desc')
            ->get();
        $emergency = $user->outcomes()
            ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
            ->where('outcomes.payment_method', PaymentMethodEnum::EMERGENCIA)
            ->where('outcome_items.payment_date', '>=', Carbon::now()->subDays($days)->startOfDay())
            ->where('outcome_items.status', StatusEnum::FINISHED)
            ->selectRaw("DATE_FORMAT(outcome_items.payment_date, '%Y-%m-%d') as payment_day, SUM(outcome_items.amount) as total_amount")
            ->groupBy('payment_day')
            ->orderBy('payment_day', 'desc')
            ->get();
        $projects = $user->projects()
            ->join('project_tasks', 'projects.id', '=', 'project_tasks.project_id')
            ->where('project_tasks.end_date', '>=', Carbon::now()->subDays($days)->startOfDay())
            ->where('project_tasks.status', StatusEnum::FINISHED)
            ->selectRaw("DATE_FORMAT(project_tasks.end_date, '%Y-%m-%d') as payment_day, SUM(project_tasks.amount) as total_amount")
            ->groupBy('payment_day')
            ->orderBy('payment_day', 'desc')
            ->get();
        $activities = $user->activities()
            ->join('activity_tasks', 'activities.id', '=', 'activity_tasks.activity_id')
            ->where('activity_tasks.end_date', '>=', Carbon::now()->subDays($days)->startOfDay())
            ->where('activity_tasks.status', StatusEnum::FINISHED)
            ->selectRaw("DATE_FORMAT(activity_tasks.end_date, '%Y-%m-%d') as payment_day, SUM(activity_tasks.amount) as total_amount")
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
        $ahorro = $ahorro->map(function ($item) {
            return [
                'date' => $item->payment_day,
                'amount' => $item->total_amount,
                'type' => 'ahorro'
            ];
        });
        $emergency = $emergency->map(function ($item) {
            return [
                'date' => $item->payment_day,
                'amount' => $item->total_amount,
                'type' => 'emergency'
            ];
        });

        $merged = $income->concat($outcome)->concat($projects)->concat($activities)->concat($ahorro)->concat($emergency)->sortBy('date');
        $merged = $merged->groupBy('date')->map(function ($group) {
            return [
                'date' => $group->first()['date'],
                'income' => $group->where('type', 'income')->sum('amount'),
                'outcome' => $group->where('type', 'outcome')->sum('amount'),
                'project' => $group->where('type', 'project')->sum('amount'),
                'activity' => $group->where('type', 'activity')->sum('amount'),
                'ahorro' => $group->where('type', 'ahorro')->sum('amount'),
                'emergency' => $group->where('type', 'emergency')->sum('amount'),
            ];
        });
        $merged = $merged->sortBy('date');

        $resArray = [];
        foreach ($merged as $item) {
            $dateString = $item['date'];
            $incomeTotal = $user->incomes()
                ->join('income_items', 'incomes.id', '=', 'income_items.income_id')
                ->where('income_items.payment_date', '<=', Carbon::parse($dateString))
                ->where('income_items.status', StatusEnum::FINISHED)
                ->selectRaw("SUM(income_items.amount) as total_amount")
                ->first()->total_amount;
            $outcomeTotal = $user->outcomes()
                ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
                ->whereNotIn('outcomes.payment_method', [PaymentMethodEnum::AHORRO, PaymentMethodEnum::EMERGENCIA])
                ->where('outcome_items.payment_date', '<=', Carbon::parse($dateString))
                ->where('outcome_items.status', StatusEnum::FINISHED)
                ->selectRaw("SUM(outcome_items.amount) as total_amount")
                ->first()->total_amount;
            $ahorroTotal = $user->outcomes()
                ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
                ->where('outcomes.payment_method', PaymentMethodEnum::AHORRO)
                ->where('outcome_items.payment_date', '<=', Carbon::parse($dateString))
                ->where('outcome_items.status', StatusEnum::FINISHED)
                ->selectRaw("SUM(outcome_items.amount) as total_amount")
                ->first()->total_amount;
            $emergencyTotal = $user->outcomes()
                ->join('outcome_items', 'outcomes.id', '=', 'outcome_items.outcome_id')
                ->where('outcomes.payment_method', PaymentMethodEnum::EMERGENCIA)
                ->where('outcome_items.payment_date', '<=', Carbon::parse($dateString))
                ->where('outcome_items.status', StatusEnum::FINISHED)
                ->selectRaw("SUM(outcome_items.amount) as total_amount")
                ->first()->total_amount;
            $projectTotal = $user->projects()
                ->join('project_tasks', 'projects.id', '=', 'project_tasks.project_id')
                ->where('project_tasks.end_date', '<=', Carbon::parse($dateString))
                ->where('project_tasks.status', StatusEnum::FINISHED)
                ->selectRaw("SUM(project_tasks.amount) as total_amount")
                ->first()->total_amount;
            $activityTotal = $user->activities()
                ->join('activity_tasks', 'activities.id', '=', 'activity_tasks.activity_id')
                ->where('activity_tasks.end_date', '<=', Carbon::parse($dateString))
                ->where('activity_tasks.status', StatusEnum::FINISHED)
                ->selectRaw("SUM(activity_tasks.amount) as total_amount")
                ->first()->total_amount;
            array_push($resArray, [
                'date' => $dateString,
                'income' => ['amount' => $item['income'], 'total' => $incomeTotal],
                'outcome' => ['amount' => $item['outcome'], 'total' => $outcomeTotal],
                'ahorro' =>  ['amount' => $item['ahorro'], 'total' => $ahorroTotal],
                'emergency' => ['amount' => $item['emergency'], 'total' => $emergencyTotal],
                'project' => ['amount' => $item['project'], 'total' => $projectTotal],
                'activity' => ['amount' => $item['activity'], 'total' => $activityTotal],
            ]);
        }

        return response()->json($resArray, Response::HTTP_OK);
    }
}
