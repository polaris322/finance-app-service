<?php

namespace App\Http\Controllers;

use App\Enum\FrequencyEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\StatusEnum;
use App\Enum\TypeEnum;
use App\Models\Income;
use App\Models\IncomeItem;
use App\Rules\EnumValue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class IncomeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $user = JWTAuth::parseToken()->authenticate();
        $incomes = Income::whereYear('start_date', $year)
            ->whereMonth('start_date', $month)
            ->where("user_id", $user->id)
            ->get();

        $incomes->map(function($income){
            $income->total_amount = $income->items()->sum('amount');
            $income->amount = $income->items()->get()->last()->amount;
            return $income;
        });
        return response()->json($incomes, Response::HTTP_OK);
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
            $income = $user->incomes()->create($validatedData);
            $income->items()->create([
                'type'=> $request->type,
                'amount' => $request->amount,
                'payment_date' => $request->start_date,
                'status' => StatusEnum::FINISHED->value
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
