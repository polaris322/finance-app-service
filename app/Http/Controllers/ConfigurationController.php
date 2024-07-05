<?php

namespace App\Http\Controllers;

use App\Enum\FrequencyEnum;
use App\Enum\PaymentMethodEnum;
use App\Enum\TypeEnum;
use App\Models\Income;
use App\Rules\EnumValue;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ConfigurationController extends Controller
{
    public function index(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $config = $user->configuration;
        return response()->json($config, Response::HTTP_OK);
    }

    public function updateEmergence(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $config = $user->configuration;
        $config->update([
            'emergency' => $request->emergency
        ]);
        return response()->json(null, Response::HTTP_OK);
    }

    public function updateAhorro(Request $request)
    {
        $user = JWTAuth::parseToken()->authenticate();
        $config = $user->configuration;
        $config->update([
            'ahorro' => $request->ahorro
        ]);
        return response()->json(null, Response::HTTP_OK);
    }
}
