<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroyCategoryRequest;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Requests\UpdateCategoryRequest;
use App\Models\User;
use App\Services\AssetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CategoryController extends Controller
{
    public function __construct(private readonly AssetService $assetService) {}

    public function store(StoreCategoryRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        $this->assetService->createCategory($actor, trim($request->string('category_name')->value()));

        return response()->json(['success' => true, 'message' => 'Category added successfully.']);
    }

    public function update(UpdateCategoryRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        $this->assetService->renameCategory(
            $actor,
            $request->string('old_name')->value(),
            trim($request->string('new_name')->value()),
        );

        return response()->json(['success' => true, 'message' => 'Category updated successfully.']);
    }

    public function destroy(DestroyCategoryRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        $this->assetService->deleteCategory($actor, $request->string('category_name')->value());

        return response()->json(['success' => true, 'message' => 'Category deleted successfully.']);
    }
}
