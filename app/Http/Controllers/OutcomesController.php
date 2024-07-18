<?php

namespace App\Http\Controllers;

use App\Enum\CategoryEnum;
use App\Enum\FrequencyEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\StatusEnum;
use App\Enum\TypeEnum;
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

class OutcomesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = JWTAuth::parseToken()->authenticate();
        if (isset($user->account_email)) {
            $user = User::where('email', $user->account_email)->first();
        }
        $outcomes = $user->outcomes()
            ->withCount(['items as total_amount' => function ($query) {
                // Sum the 'amount' column instead of counting rows
                $query->select(DB::raw('SUM(amount)'));
            }])
            ->get()
            ->map(function ($outcome) {
                // Set the status and payment_date from the last associated item
                $outcome->status = $outcome->items->last()->status;
                $outcome->payment_date = $outcome->items->last()->payment_date;

                // Convert to array and remove the 'items' relationship
                $newItem = $outcome->toArray();
                unset($newItem['items']);
                return $newItem;
            });
        $fixed = $outcomes->filter(function ($item) {
            return $item['type'] === TypeEnum::FIXED->value;
        })->values()->all();
        $dynamic = $outcomes->filter(function ($item) {
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
                'cuotas' => 'string|nullable',
                'note' => 'string|nullable',
                'category' => ['required', new EnumValue(CategoryEnum::class)],
                'type' => ['required', new EnumValue(TypeEnum::class)],
                'frequency' => ['required', new EnumValue(FrequencyEnum::class)],
                'payment_method' => ['required', new EnumValue(PaymentMethodEnum::class)],
                'start_date' => 'required|date_format:Y-m-d',
                'status' => ['required', new EnumValue(StatusEnum::class)],
            ]);

            $user = JWTAuth::parseToken()->authenticate();
            if (isset($user->account_email)) {
                $user = User::where('email', $user->account_email)->first();
            }
            $outcome = $user->outcomes()->create($validatedData);

            // Evaluate payment status
            $status = StatusEnum::PENDING;
            if (Carbon::now()->gt(Carbon::parse($request->start_date))) {
                $status = StatusEnum::FINISHED;
            }
            $outcome->status = $status->value;
            $outcome->save();

            if ($request->hasFile('attachment')) {
                $file = $request->file('attachment');
                $fileName = Carbon::now()->timestamp.'.'.$file->getClientOriginalExtension();
                $filePath = $file->storeAs('uploads', $fileName, 'public');
                $outcome->attachment = $filePath;
                $outcome->save();
            }

            $outcome->items()->create([
                'type'=> $request->type,
                'amount' => $request->amount,
                'payment_date' => $request->start_date,
                'status' => $status
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
                'cuotas' => 'string',
                'note' => 'string',
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

            if ($request->status === StatusEnum::PENDING->value) {
                Outcome::findOrFail($item->outcome_id)->items()->create([
                    "outcome_id" => $item->outcome_id,
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
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $outcome = Outcome::findOrFail($id);
        $outcome->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
