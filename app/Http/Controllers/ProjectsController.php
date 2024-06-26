<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Facades\JWTAuth;

class ProjectsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = JWTAuth::parseToken()->authenticate();
        $items = $user->projects();
        $items->map(function ($item){
            return $item->tasks = $item->tasks;
        });
        return response()->json($items, Response::HTTP_OK);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255'
            ]);

            $user = JWTAuth::parseToken()->authenticate();
            $newItem = $user->projects()->create($validatedData);
            return response()->json($newItem, Response::HTTP_CREATED);
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
        $item = Project::findOrFail($id);
        return response()->json($item, Response::HTTP_OK);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $id)
    {
        $item = Project::findOrFail($id);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255'
        ]);

        $item->update($validatedData);
        return response()->json($item, Response::HTTP_OK);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(int $id)
    {
        $item = Project::findOrFail($id);
        $item->delete();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
