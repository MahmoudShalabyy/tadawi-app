<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\TherapeuticClass;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TherapeuticClassController extends Controller
{
    use ApiResponse;

    public function index(Request $request)
    {
        try {
            $query = TherapeuticClass::query();

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
            $therapeuticClasses = $query->paginate($perPage);

            return $this->success($therapeuticClasses, 'Therapeutic classes retrieved successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to retrieve therapeutic classes: ' . $e->getMessage());
        }
    }

    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255|unique:therapeutic_classes,name',
                'description' => 'nullable|string|max:1000',
            ]);

            $therapeuticClass = TherapeuticClass::create($validatedData);

            return $this->success($therapeuticClass, 'Therapeutic class created successfully', 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to create therapeutic class: ' . $e->getMessage());
        }
    }

    public function update(Request $request, TherapeuticClass $therapeuticClass)
    {
        try {
            $validatedData = $request->validate([
                'name' => [
                    'required',
                    'string',
                    'max:255',
                    Rule::unique('therapeutic_classes', 'name')->ignore($therapeuticClass->id)
                ],
                'description' => 'nullable|string|max:1000',
            ]);

            $therapeuticClass->update($validatedData);

            return $this->success($therapeuticClass, 'Therapeutic class updated successfully');

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->error('Validation failed', 422, $e->errors());
        } catch (\Exception $e) {
            return $this->serverError('Failed to update therapeutic class: ' . $e->getMessage());
        }
    }

    public function destroy(TherapeuticClass $therapeuticClass)
    {
        try {
            if ($therapeuticClass->trashed()) {
                return $this->error('Therapeutic class is already deleted', 404);
            }

            $therapeuticClass->delete();

            return $this->success(null, 'Therapeutic class deleted successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to delete therapeutic class: ' . $e->getMessage());
        }
    }

    public function restore($id)
    {
        try {
            $therapeuticClass = TherapeuticClass::withTrashed()->find($id);

            if (!$therapeuticClass) {
                return $this->error('Therapeutic class not found', 404);
            }

            if (!$therapeuticClass->trashed()) {
                return $this->error('Therapeutic class is not deleted', 400);
            }

            $therapeuticClass->restore();

            return $this->success($therapeuticClass, 'Therapeutic class restored successfully');

        } catch (\Exception $e) {
            return $this->serverError('Failed to restore therapeutic class: ' . $e->getMessage());
        }
    }

    public function show(TherapeuticClass $therapeuticClass)
    {
        $therapeuticClass->load('medicines');

        return $this->success($therapeuticClass, 'Therapeutic class retrieved successfully');
    }
}
