<?php

namespace App\Http\Controllers;

use App\Http\Requests\LeadApiStoreRequest;
use App\Models\Comment;
use App\Models\Contact;
use App\Models\Email;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\Phone;
use Illuminate\Support\Facades\DB;

class LeadApiController extends Controller
{
    public function index()
    {
        return Lead::orderBy('created_at', 'desc')->paginate(10);
    }

    public function store(LeadApiStoreRequest $request)
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();

            // Поиск контакта по phone, telegram или email
            $contact = $this->findContact($validated);

            if (! $contact) {
                // Создаем новый контакт и новый лид
                $contact = $this->createContact($validated);
                $lead = $this->createLead($contact, $validated);

                // Добавляем комментарий если есть
                if (! empty($validated['comment'])) {
                    $this->createComment($lead, $validated['comment']);
                }

                return response()->json([
                    'message' => 'Новый лид и контакт успешно созданы',
                    'lead' => $lead->load('contact', 'status'),
                ], 201);
            }

            // Контакт найден, ищем активные лиды
            $activeLead = $contact->leads()
                ->whereHas('status', function ($query) {
                    $query->where('is_final', false);
                })
                ->first();

            if ($activeLead) {
                // Есть активный лид, создаем комментарий
                $commentText = $this->buildCommentText($validated);
                $this->createComment($activeLead, $commentText);

                return response()->json([
                    'message' => 'Комментарий добавлен к существующему активному лиду',
                    'lead' => $activeLead->load('contact', 'status'),
                    'comment_added' => true,
                ], 200);
            }

            // Нет активных лидов, создаем новый лид
            $lead = $this->createLead($contact, $validated);

            // Добавляем комментарий если есть
            if (! empty($validated['comment'])) {
                $this->createComment($lead, $validated['comment']);
            }

            return response()->json([
                'message' => 'Новый лид создан для существующего контакта',
                'lead' => $lead->load('contact', 'status'),
            ], 201);
        });
    }

    private function findContact(array $data): ?Contact
    {
        // Поиск по phone
        if (! empty($data['phone'])) {
            $contact = Contact::whereHas('phones', function ($query) use ($data) {
                $query->where('number', $data['phone']);
            })->first();

            if ($contact) {
                return $contact;
            }
        }

        // Поиск по telegram
        if (! empty($data['telegram'])) {
            $contact = Contact::where('telegram', $data['telegram'])->first();

            if ($contact) {
                return $contact;
            }
        }

        // Поиск по email
        if (! empty($data['email'])) {
            $contact = Contact::whereHas('emails', function ($query) use ($data) {
                $query->where('address', $data['email']);
            })->first();

            if ($contact) {
                return $contact;
            }
        }

        return null;
    }

    private function createContact(array $data): Contact
    {
        $contact = Contact::create([
            'name' => $data['contact_name'] ?? null,
            'telegram' => $data['telegram'] ?? null,
        ]);

        // Создаем связанный телефон если есть
        if (! empty($data['phone'])) {
            Phone::create([
                'contact_id' => $contact->id,
                'number' => $data['phone'],
            ]);
        }

        // Создаем связанный email если есть
        if (! empty($data['email'])) {
            Email::create([
                'contact_id' => $contact->id,
                'address' => $data['email'],
            ]);
        }

        return $contact;
    }

    private function createLead(Contact $contact, array $data): Lead
    {
        // Находим стартовый статус
        $startStatus = LeadStatus::where('is_start', true)->first();

        if (! $startStatus) {
            throw new \Exception('Стартовый статус не найден в системе');
        }

        return Lead::create([
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'amount' => $data['amount'] ?? null,
            'contact_id' => $contact->id,
            'status_id' => $startStatus->id,
            'position' => 0,
        ]);
    }

    private function createComment($lead, string $text): Comment
    {
        return Comment::create([
            'text' => $text,
            'commentable_id' => $lead->id,
            'commentable_type' => 'lead',
        ]);
    }

    private function buildCommentText(array $data): string
    {
        $parts = [];

        if (! empty($data['title'])) {
            $parts[] = "Заголовок: {$data['title']}";
        }

        if (! empty($data['description'])) {
            $parts[] = "Описание: {$data['description']}";
        }

        if (! empty($data['amount'])) {
            $parts[] = "Сумма: {$data['amount']}";
        }

        if (! empty($data['comment'])) {
            $parts[] = "Комментарий: {$data['comment']}";
        }

        $contactParts = [];
        if (! empty($data['contact_name'])) {
            $contactParts[] = "Имя: {$data['contact_name']}";
        }
        if (! empty($data['phone'])) {
            $contactParts[] = "Телефон: {$data['phone']}";
        }
        if (! empty($data['email'])) {
            $contactParts[] = "Email: {$data['email']}";
        }
        if (! empty($data['telegram'])) {
            $contactParts[] = "Telegram: {$data['telegram']}";
        }

        if (! empty($contactParts)) {
            $parts[] = 'Контактные данные: '.implode(', ', $contactParts);
        }

        return "Повторное обращение:\n".implode("\n", $parts);
    }
}
