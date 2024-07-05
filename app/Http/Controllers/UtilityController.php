<?php

namespace App\Http\Controllers;

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
            $user->utilities()->create($validatedData);
            return response()->json(null, Response::HTTP_CREATED);
        } catch (ValidationException $e) {
            return response()->json([
                'errors' => $e->errors(),
            ], 400);
        }
    }
}
