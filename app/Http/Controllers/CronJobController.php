<?php

namespace App\Http\Controllers;

use App\Enum\FrequencyEnum;
use App\Enum\StatusEnum;
use App\Enum\TypeEnum;
use App\Models\Income;
use App\Models\Outcome;
use Carbon\Carbon;
use Illuminate\Http\Response;

class CronJobController extends Controller
{
    public function updateDynamicPaymentStatus(): \Illuminate\Http\JsonResponse
    {
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // add new income item
        $incomes = Income::where('end_date', ">=", Carbon::now())->where('type', TypeEnum::DYNAMIC)->where(function ($query) use ($startOfMonth, $endOfMonth) {
            $query->doesntHave('items')
                ->orWhereDoesntHave('items', function ($subQuery) use ($startOfMonth, $endOfMonth) {
                    $subQuery->whereBetween('payment_date', [$startOfMonth, $endOfMonth]);
                });
            })
            ->with(['items' => function ($query) {
                $query->orderBy('payment_date', 'desc');
            }])
            ->get();
        $filtered_incomes = $incomes->filter(function ($income) use ($startOfMonth, $endOfMonth) {
            if ($income->items->isEmpty()) {
                return true;
            }
            $lastPaymentDate = $income->items->first()->payment_date;
            $nextPaymentDate = Carbon::parse($lastPaymentDate)->addMonths(FrequencyEnum::getMonthlyDifference($income->frequency));

            return $nextPaymentDate->between($startOfMonth, $endOfMonth) &&
                $income->items->whereBetween('payment_date', [$startOfMonth, $endOfMonth])->count() == 0;
        });
        $filtered_incomes->map(function ($income){
            $income->items()->create([
                'type'=> $income->type,
                'amount' => $income->amount,
                'payment_date' => Carbon::now()->toDateTime(),
                'status' => StatusEnum::FINISHED
            ]);
            return $income;
        });

        // add new outcome item
        $outcomes = Outcome::where('type', TypeEnum::DYNAMIC)->where(function ($query) use ($startOfMonth, $endOfMonth) {
            $query->doesntHave('items')
                ->orWhereDoesntHave('items', function ($subQuery) use ($startOfMonth, $endOfMonth) {
                    $subQuery->whereBetween('payment_date', [$startOfMonth, $endOfMonth]);
                });
        })
            ->with(['items' => function ($query) {
                $query->orderBy('payment_date', 'desc');
            }])
            ->get();
        $filtered_outcomes = $outcomes->filter(function ($outcome) use ($startOfMonth, $endOfMonth) {
            if ($outcome->items->isEmpty()) {
                return true;
            }
            $lastPaymentDate = $outcome->items->first()->payment_date;
            $nextPaymentDate = Carbon::parse($lastPaymentDate)->addMonths(FrequencyEnum::getMonthlyDifference($outcome->frequency));

            return $nextPaymentDate->between($startOfMonth, $endOfMonth) &&
                $outcome->items->whereBetween('payment_date', [$startOfMonth, $endOfMonth])->count() == 0;
        });
        $filtered_outcomes->map(function ($outcome){
            $outcome->items()->create([
                'type'=> $outcome->type,
                'amount' => $outcome->amount,
                'payment_date' => Carbon::now()->toDateTime(),
                'status' => StatusEnum::PENDING
            ]);
            return $outcome;
        });

        return response()->json(null, Response::HTTP_OK);
    }
}
