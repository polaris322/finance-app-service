<?php

namespace App\Http\Controllers;

use App\Enum\FrequencyEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\StatusEnum;
use App\Enum\TypeEnum;
use App\Models\Activity;
use App\Models\ActivityTask;
use App\Models\Project;
use App\Models\ProjectTask;
use App\Rules\EnumValue;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;

class ActivitiesTasksController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(int $activity_id)
    {
        $activity = Activity::findOrFail($activity_id);
        $tasks = $activity->tasks;
        return response()->json($tasks, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, int $activity_id)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'payment_method' => ['required', new EnumValue(PaymentMethodEnum::class)],
                'status' => ['required', new EnumValue(StatusEnum::class)],
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d',
            ]);

            $activity = Activity::findOrFail($activity_id);
            $newTask = $activity->tasks()->create($validatedData);
            return response()->json($newTask, Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(int $activity_id, int $id)
    {
        $task = ActivityTask::findOrFail($id);
        return response()->json($task, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $activity_id, int $id)
    {
        try {
            $task = ActivityTask::findOrFail($id);
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'amount' => 'required|numeric|min:0',
                'payment_method' => ['required', new EnumValue(PaymentMethodEnum::class)],
                'status' => ['required', new EnumValue(StatusEnum::class)],
                'start_date' => 'required|date_format:Y-m-d',
                'end_date' => 'required|date_format:Y-m-d',
            ]);

            $task->update($validatedData);
            return response()->json($task, Response::HTTP_OK);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Update the specified resource's status in storage.
     */
    public function updateStatus(Request $request, int $activity_id, int $id): \Illuminate\Http\JsonResponse
    {
        try {
            $item = ActivityTask::findOrFail($id);
            $request->validate([
                'status' => ['required', new EnumValue(StatusEnum::class)],
            ]);
            $item->update([
                'status' => $request->status,
                'start_date' => Carbon::now()->toDateTime()
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
        $task = ActivityTask::findOrFail($id);
        $task->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
