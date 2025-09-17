<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ActiveIngredient;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ActiveIngredientController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $query = ActiveIngredient::query();

            if ($request->has('search') && !empty($request->search)) {
                $searchTerm = $request->search;
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', "%{$searchTerm}%")
                      ->orWhere('description', 'LIKE', "%{$searchTerm}%");
                });
            }

            $sortBy = $request->input('sort_by', 'name');
            $sortOrder = $request->input('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 10);
            $activeIngredients = $query->paginate($perPage);

            return $this->success($activeIngredients, 'Active ingredients retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve active ingredients: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:active_ingredients,name',
                'description' => 'nullable|string|max:1000',
            ]);

            $activeIngredient = ActiveIngredient::create($validatedData);

            return $this->success($activeIngredient, 'Active ingredient created successfully', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create active ingredient: ' . $e->getMessage());
        }
    }

    public function update(Request $request, ActiveIngredient $activeIngredient)
    {
        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('active_ingredients', 'name')->ignore($activeIngredient->id)
                ],
                'description' => 'nullable|string|max:1000',
            ]);

            $activeIngredient->update($validatedData);

            return $this->success($activeIngredient, 'Active ingredient updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update active ingredient: ' . $e->getMessage());
        }
    }

    public function destroy(ActiveIngredient $activeIngredient)
    {
        try {
            if ($activeIngredient->trashed()) {
                return $this->error('Active ingredient is already deleted', 404);
            }

            $activeIngredient->delete();

            return $this->success(null, 'Active ingredient deleted successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to delete active ingredient: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        try {
            $activeIngredient = ActiveIngredient::withTrashed()->find($id);

            if (!$activeIngredient) {
                return $this->error('Active ingredient not found', 404);
            }

            if (!$activeIngredient->trashed()) {
                return $this->error('Active ingredient is not deleted', 400);
            }

            $activeIngredient->restore();

            return $this->success($activeIngredient, 'Active ingredient restored successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to restore active ingredient: ' . $e->getMessage());
        }
    }

    public function show(ActiveIngredient $activeIngredient)
    {
        $activeIngredient->load('medicines');

        return $this->success($activeIngredient, 'Active ingredient retrieved successfully');
    }
}
