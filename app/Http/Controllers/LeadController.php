<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadStoreRequest;
use App\Http\Requests\LeadUpdateRequest;
use App\Models\Lead;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class LeadController extends Controller
{
    public function index(): JsonResponse
    {
        $leads = Lead::with('contact', 'user', 'status')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($leads);
    }

    public function store(LeadStoreRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $lead = Lead::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'amount' => $validated['amount'] ?? null,
            'contact_id' => $validated['contact_id'] ?? null,
            'status_id' => $validated['status_id'],
            'user_id' => Auth::id(),
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
        $lead->update($request->validated());

        return response()->json($lead->load('contact', 'user', 'status'));
    }

    public function destroy(Lead $lead): JsonResponse
    {
        $lead->delete();

        return response()->json(['message' => 'Deleted']);
    }
}
