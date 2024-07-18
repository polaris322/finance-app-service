<?php

namespace App\Http\Controllers;

use App\Enum\FrequencyEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\StatusEnum;
use App\Enum\TypeEnum;
use App\Models\Income;
use App\Models\IncomeItem;
use App\Models\Outcome;
use App\Models\OutcomeItem;
use App\Models\User;
use App\Rules\EnumValue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }

        $incomes = $user->incomes()
            ->withCount(['items as total_amount' => function ($query) {
                // Sum the 'amount' column instead of counting rows
                $query->select(DB::raw('SUM(amount)'));
            }])
            ->get()
            ->map(function ($income) {
                // Set the status and payment_date from the last associated item
                $income->status = $income->items->last()->status;
                $income->payment_date = $income->items->last()->payment_date;

                // Convert to array and remove the 'items' relationship
                $newItem = $income->toArray();
                unset($newItem['items']);
                return $newItem;
            });
        $fixed = $incomes->filter(function ($item) {
            return $item['type'] === TypeEnum::FIXED->value;
        })->values()->all();
        $dynamic = $incomes->filter(function ($item) {
            return $item['type'] === TypeEnum::DYNAMIC->value;
        })->values()->all();
        return response()->json([
            "fixed" => $fixed, "dynamic" => $dynamic
        ], Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'type' => ['required', new EnumValue(TypeEnum::class)],
                'frequency' => ['required', new EnumValue(FrequencyEnum::class)],
                'payment_method' => ['required', new EnumValue(PaymentMethodEnum::class)],
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'date_format:Y-m-d'
            ]);

            $user = JWTAuth::parseToken()->authenticate();
            if (isset($user->account_email)) {
                $user = User::where('email', $user->account_email)->first();
            }
            $income = $user->incomes()->create($validatedData);
            // Evaluate payment status
            $status = StatusEnum::PENDING;
            if (Carbon::now()->gt(Carbon::parse($request->start_date))) {
                $status = StatusEnum::FINISHED;
            }
            $income->status = $status->value;
            $income->save();

            $income->items()->create([
                'type'=> $request->type,
                'amount' => $request->amount,
                'payment_date' => $request->start_date,
                'status' => $status
            ]);
            return response()->json($income, Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $id)
    {
        $income = Income::findOrFail($id);
        return response()->json($income, Response::HTTP_OK);
    }

    /**
     * Update the specified resource's status in storage.
     */
    public function updateStatus(Request $request, int $id)
    {
        try {
            $item = IncomeItem::where("income_id", $id)->orderBy('payment_date', 'desc')->get()->last();
            $request->validate([
                'status' => ['required', new EnumValue(StatusEnum::class)],
            ]);

            if ($request->status === StatusEnum::PENDING->value) {
                Income::findOrFail($item->outcome_id)->items()->create([
                    "income_id" => $item->outcome_id,
                    "amount" => $item->amount,
                    "payment_date" => $request->newDate,
                    "status" => $item->status,
                    "type" => $item->type
                ]);
            } else {
                $item->update([
                    'status' => $request->status,
                    'payment_date' => Carbon::now()->toDateTime()
                ]);
            }

            return response()->json(null, Response::HTTP_OK);
        }
        catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        try {
            $income = Income::findOrFail($id);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'type' => ['required', new EnumValue(TypeEnum::class)],
                'frequency' => ['required', new EnumValue(FrequencyEnum::class)],
                'payment_method' => ['required', new EnumValue(PaymentMethodEnum::class)],
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'date_format:Y-m-d'
            ]);

            $income->update($validatedData);
            return response()->json($income, Response::HTTP_OK);
        }
        catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $income = Income::findOrFail($id);
        $income->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
