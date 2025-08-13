<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadSaveNewOrderRequest;
use App\Http\Requests\LeadStoreRequest;
use App\Http\Requests\LeadUpdateRequest;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LeadController extends Controller
{
    public function index(): JsonResponse
    {
        $leads = Lead::with('contact', 'user', 'status')
            ->orderBy('status_id')
            ->orderBy('position')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($leads);
    }

    public function store(LeadStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $nextPosition = (int) (Lead::where('status_id', $validated['status_id'])->min('position') ?? 0) - 1;

        $lead = Lead::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'amount' => $validated['amount'] ?? null,
            'contact_id' => $validated['contact_id'] ?? null,
            'status_id' => $validated['status_id'],
            'position' => $nextPosition,
            'user_id' => $validated['user_id'] ?? Auth::id(),
        ]);

        return response()->json($lead->load('contact', 'user', 'status'), 201);
    }

    public function show(Lead $lead): JsonResponse
    {
        $lead->load([
            'contact',
            'contact.phones',
            'contact.emails',
            'user',
            'status',
        ]);

        return response()->json($lead);
    }

    public function update(LeadUpdateRequest $request, Lead $lead): JsonResponse
    {
        $data = $request->validated();

        if (array_key_exists('status_id', $data) && (int) $data['status_id'] !== (int) $lead->status_id) {
            // Если меняется статус через форму — отправляем лид в начало нового статуса
            $nextPosition = (int) (Lead::where('status_id', $data['status_id'])->min('position') ?? 0) - 1;
            $lead->update(array_merge($data, ['position' => $nextPosition]));
        } else {
            $lead->update($data);
        }

        return response()->json($lead->load('contact', 'user', 'status'));
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json(['message' => 'Deleted']);
    }

    public function saveNewOrder(LeadSaveNewOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            foreach ($validated['columns'] as $column) {
                $position = 1;
                foreach (($column['ids'] ?? []) as $leadId) {
                    $lead = Lead::find($leadId);

                    $updateData = [
                        'status_id' => $column['status_id'],
                        'position' => $position,
                    ];

                    if (
                        $lead &&
                        $lead->user_id === null &&
                        (int) $lead->status_id === 1 &&
                        (int) $column['status_id'] !== (int) $lead->status_id
                    ) {
                        $updateData['user_id'] = Auth::id();
                    }

                    if ($lead) {
                        $lead->update($updateData);
                    }

                    $position++;
                }
            }
        });

        return response()->json(['status' => 'ok']);
    }
}
