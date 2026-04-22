<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FatSecretService;
use Illuminate\Http\Request;

class FoodSearchController extends Controller
{
    protected $fatSecretService;

    public function __construct(FatSecretService $fatSecretService)
    {
        $this->fatSecretService = $fatSecretService;
    }

    /**
     * Search food by name
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'page' => 'nullable|integer|min:0',
        ]);

        try {
            $result = $this->fatSecretService->searchFood(
                $request->query,
                $request->page ?? 0
            );

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to search food',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get food details by ID
     */
    public function show($foodId)
    {
        try {
            $result = $this->fatSecretService->getFoodById($foodId);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get food details',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Search by barcode
     */
    public function barcode(Request $request)
    {
        $request->validate([
            'barcode' => 'required|string',
        ]);

        try {
            $result = $this->fatSecretService->searchByBarcode($request->barcode);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to search by barcode',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Autocomplete suggestions
     */
    public function autocomplete(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1',
        ]);

        try {
            $result = $this->fatSecretService->autocomplete($request->query);
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get autocomplete',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
