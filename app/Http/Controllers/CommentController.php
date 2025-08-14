<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentStoreRequest;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function storeForLead(Lead $lead, CommentStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // Создаем комментарий через связь
        $comment = $lead->comments()->create([
            'text' => $validated['text'],
            'user_id' => Auth::id(),
        ]);

        // Загружаем связанные данные для ответа
        $comment->load('user');

        return response()->json($comment, 201);
    }
}
