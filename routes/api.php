<?php

use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/


Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

Route::middleware('auth:api')->group(function () {
    Route::get('user', [AuthController::class, 'user']);
    Route::post('logout', [AuthController::class, 'logout']);

    Route::get('statistics/gross', [Controllers\StatisticsController::class, 'getGrossStatistics']);
    Route::get('statistics/gross-outcome', [Controllers\StatisticsController::class, 'getGrossOutcome']);
    Route::get('statistics/outcome-by-category', [Controllers\StatisticsController::class, 'getOutcomeByCategory']);
    Route::get('statistics/pending-outcome', [Controllers\StatisticsController::class, 'getPendingOutcome']);
    Route::get('statistics/major-outcome', [Controllers\StatisticsController::class, 'getMajorOutcome']);
    Route::get('statistics/gross-daily', [Controllers\StatisticsController::class, 'getDailyStatistics']);
});

Route::get("cron-job/update-dynamics", [Controllers\CronJobController::class, 'updateDynamicPaymentStatus']);

Route::apiResource('incomes', Controllers\IncomeController::class)->middleware('auth:api');

Route::apiResource('outcomes', Controllers\OutcomesController::class)->middleware('auth:api');
Route::put('outcomes/{id}/update-status', [Controllers\OutcomesController::class, 'updateStatus']);

Route::apiResource('projects', Controllers\ProjectsController::class)->middleware('auth:api');
Route::apiResource('projects.tasks', Controllers\ProjectTasksController::class)->middleware('auth:api');
Route::put('projects/{project_id}/tasks/{id}/update-status', [Controllers\ProjectTasksController::class, 'updateStatus']);

Route::apiResource('activities', Controllers\ActivitiesController::class)->middleware('auth:api');
Route::apiResource('activities.tasks', Controllers\ActivitiesTasksController::class)->middleware('auth:api');
Route::put('activities/{activity_id}/tasks/{id}/update-status', [Controllers\ActivitiesTasksController::class, 'updateStatus']);