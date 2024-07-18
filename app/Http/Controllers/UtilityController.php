<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class UtilityController extends Controller
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
        $utilities = $user->utilities;
        return response()->json($utilities, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'emergency' => 'numeric|min:0',
                'ahorro' => 'numeric|min:0'
            ]);

            $user = JWTAuth::parseToken()->authenticate();
            if (isset($user->account_email)) {
                $user = User::where('email', $user->account_email)->first();
            }
            $user->utilities()->create($validatedData);
            return response()->json(null, Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], 400);
        }
    }
}
