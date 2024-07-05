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
    Route::get('statistics/get-balance', [Controllers\StatisticsController::class, 'getTotalBalance']);

    Route::apiResource('incomes', Controllers\IncomeController::class);

    Route::apiResource('outcomes', Controllers\OutcomesController::class);
    Route::put('outcomes/{id}/update-status', [Controllers\OutcomesController::class, 'updateStatus']);

    Route::apiResource('projects', Controllers\ProjectsController::class);
    Route::apiResource('projects.tasks', Controllers\ProjectTasksController::class);
    Route::put('projects/{project_id}/tasks/{id}/update-status', [Controllers\ProjectTasksController::class, 'updateStatus']);

    Route::apiResource('activities', Controllers\ActivitiesController::class);
    Route::apiResource('activities.tasks', Controllers\ActivitiesTasksController::class);
    Route::put('activities/{activity_id}/tasks/{id}/update-status', [Controllers\ActivitiesTasksController::class, 'updateStatus']);

    Route::get('utilities', [Controllers\UtilityController::class, 'index']);
    Route::post('utilities', [Controllers\UtilityController::class, 'store']);

    Route::get('configuration', [Controllers\ConfigurationController::class, 'index']);
    Route::put('configuration/update-emergency', [Controllers\ConfigurationController::class, 'updateEmergence']);
    Route::put('configuration/update-ahorro', [Controllers\ConfigurationController::class, 'updateAhorro']);
});

Route::get("cron-job/update-dynamics", [Controllers\CronJobController::class, 'updateDynamicPaymentStatus']);
