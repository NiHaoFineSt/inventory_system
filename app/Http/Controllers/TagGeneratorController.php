<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerateTagsRequest;
use App\Models\User;
use App\Services\TagGeneratorService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TagGeneratorController extends Controller
{
    public function __construct(private readonly TagGeneratorService $tagGeneratorService) {}

    public function getNextNumber(Request $request): JsonResponse
    {
        $nextNumber = $this->tagGeneratorService->nextNumber((string) $request->query('prefix', ''));

        return response()->json(['success' => true, 'next_number' => $nextNumber]);
    }

    public function storeBulkTags(GenerateTagsRequest $request): JsonResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        try {
            $created = $this->tagGeneratorService->storeBulk($actor, $request->validated());
        } catch (UniqueConstraintViolationException) {
            return response()->json([
                'success' => false,
                'message' => 'One or more tracking numbers already exist. Refresh the next available number and try again.',
            ], 422);
        }

        return response()->json([
            'success' => true,
            'status' => 'success',
            'message' => "Successfully created {$created->count()} assets.",
            'data' => $created,
        ], 201);
    }
}
