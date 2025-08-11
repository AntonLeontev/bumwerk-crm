<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadStatusController extends Controller
{
    public function index(): JsonResponse
    {
        $leadStatuses = LeadStatus::orderBy('position')->get();

        return response()->json($leadStatuses);
    }

    public function store(Request $request): JsonResponse
    {
        $colors = [
            '#22c55e3a',
            '#3b82f63a',
            '#ef44443a',
            '#f59e0b3a',
            '#10b9813a',
        ];
        $name = (string) ($request->get('name') ?? 'Новый статус');
        $color = (string) ($request->get('color') ?? $colors[array_rand($colors)]);
        $newPosition = $request->get('position');

        $leadStatus = DB::transaction(function () use ($name, $color, $newPosition) {
            // Сдвигаем позиции существующих не финальных статусов, чтобы освободить место
            $statusesToShift = LeadStatus::where('is_final', false)
                ->where('position', '>=', $newPosition)
                ->where('position', '<', 255)
                ->where('position', '>', 0)
                ->orderByDesc('position')
                ->get(['id', 'position']);

            foreach ($statusesToShift as $status) {
                $status->update(['position' => $status->position + 1]);
            }

            $leadStatus = LeadStatus::create([
                'name' => $name,
                'color' => $color,
                'position' => $newPosition,
                'is_final' => false,
                'is_system' => false,
            ]);

            return $leadStatus;
        });

        return response()->json($leadStatus, 201);
    }

    public function saveNewOrder(Request $request)
    {
        $position = 1;
        foreach ($request->json('statuses') as $status) {
            if ($status['position'] == 0) {
                continue;
            }

            LeadStatus::where('id', $status['id'])->update(['position' => $position]);
            $position++;
        }
    }

    public function update(Request $request, LeadStatus $status): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'color' => ['required', 'string', 'regex:/^#([A-Fa-f0-9]{6,8})$/'],
        ]);

        $status->update([
            'name' => $validated['name'],
            'color' => $validated['color'],
        ]);

        return response()->json($status->fresh());
    }

    public function destroy(LeadStatus $status)
    {
        if ($status->is_system) {
            abort(403, 'Нельзя удалить системный статус');
        }

        if (Lead::where('status_id', $status->id)->exists()) {
            abort(403, 'Нельзя удалить статус с лидами');
        }

        $status->delete();
    }
}
