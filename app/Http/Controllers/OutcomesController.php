<?php

namespace App\Http\Controllers;

use App\Enum\CategoryEnum;
use App\Enum\FrequencyEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\StatusEnum;
use App\Enum\TypeEnum;
use App\Models\Income;
use App\Models\Outcome;
use App\Models\OutcomeItem;
use App\Rules\EnumValue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class OutcomesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $month = $request->input('month', Carbon::now()->month);

        $user = JWTAuth::parseToken()->authenticate();
        $outcomes = $user->outcomes()
            ->withCount(['items as total_amount' => function ($query) use ($month, $year) {
                $query->select(DB::raw('SUM(amount)'))
                    ->whereMonth('payment_date', $month)
                    ->whereYear('payment_date', $year);
            }])
            ->get()
            ->map(function ($outcome) {
                $outcome->status = $outcome->items->last()->status;
                $newItem = $outcome->toArray();
                unset($newItem['items']);
                return $newItem;
            });
        return response()->json($outcomes, Response::HTTP_OK);
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
                'cuotas' => 'required|string',
                'note' => 'string',
                'attachment' => 'string',
                'category' => ['required', new EnumValue(CategoryEnum::class)],
                'type' => ['required', new EnumValue(TypeEnum::class)],
                'frequency' => ['required', new EnumValue(FrequencyEnum::class)],
                'payment_method' => ['required', new EnumValue(PaymentMethodEnum::class)],
                'start_date' => 'required|date_format:Y-m-d',
                'status' => ['required', new EnumValue(StatusEnum::class)],
            ]);

            $user = JWTAuth::parseToken()->authenticate();
            $outcome = $user->outcomes()->create($validatedData);
            $outcome->items()->create([
                'type'=> $request->type,
                'amount' => $request->amount,
                'payment_date' => $request->start_date,
                'status' => $request->status
            ]);
            return response()->json($outcome, Response::HTTP_CREATED);
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
        $outcome = Outcome::findOrFail($id);
        return response()->json($outcome, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        try {
            $outcome = Outcome::findOrFail($id);

            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'cuotas' => 'required|string',
                'note' => 'string',
                'attachment' => 'string',
                'category' => ['required', new EnumValue(CategoryEnum::class)],
                'type' => ['required', new EnumValue(TypeEnum::class)],
                'frequency' => ['required', new EnumValue(FrequencyEnum::class)],
                'payment_method' => ['required', new EnumValue(PaymentMethodEnum::class)],
                'start_date' => 'required|date_format:Y-m-d',
                'status' => ['required', new EnumValue(StatusEnum::class)],
            ]);

            $outcome->update($validatedData);
            return response()->json($outcome, Response::HTTP_OK);
        }
        catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Update the specified resource's status in storage.
     */
    public function updateStatus(Request $request, int $id)
    {
        try {
            $item = OutcomeItem::where("outcome_id", $id)->orderBy('payment_date', 'desc')->get()->last();
            $request->validate([
                'status' => ['required', new EnumValue(StatusEnum::class)],
            ]);
            $item->update([
                'status' => $request->status,
                'payment_date' => Carbon::now()->toDateTime()
            ]);

            return response()->json(null, Response::HTTP_OK);
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
        $outcome = Outcome::findOrFail($id);
        $outcome->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
