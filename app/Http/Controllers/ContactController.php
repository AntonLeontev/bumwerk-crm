<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactStoreRequest;
use App\Models\Contact;
use App\Models\Email;
use App\Models\Phone;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ContactController extends Controller
{
    public function index(Request $request)
    {
        $paginationSize = $request->get('items_per_page') == -1
            ? Contact::count()
            : $request->get('items_per_page');

        $contacts = Contact::select(['id', 'telegram', 'name', 'surname', 'patronymic'])
            ->with([
                'phone' => fn ($q) => $q->select('number', 'contact_id'),
                'email' => fn ($q) => $q->select('address', 'contact_id'),
            ])
            ->when($request->has('sort'), function ($query) use ($request) {
                foreach ($request->get('sort') as $sort) {
                    $query->orderBy($sort['key'], $sort['order']);
                }
            })
            ->when(! $request->has('sort'), function ($query) {
                $query->orderBy('id', 'desc');
            })
            ->when($request->has('search'), function ($query) use ($request) {
                $query->search($request->get('search'));
            })
            ->paginate($paginationSize);

        $customPaginator = $contacts->toArray();
        $customPaginator['data'] = $contacts->getCollection()->map(function (Contact $contact) {
            return [
                'id' => $contact->id,
                'title' => implode(' ', array_filter([$contact->surname, $contact->name, $contact->patronymic])),
                'telegram' => $contact->telegram,
                'phone' => $contact->phone?->number,
                'email' => $contact->email?->address,
            ];
        });

        return response()->json($customPaginator);
    }

    public function store(ContactStoreRequest $request)
    {
        $validated = $request->validated();
        $contact = Contact::create(Arr::except($validated, ['phone', 'email']));

        if (array_key_exists('phone', $validated) && $validated['phone'] !== null) {
            Phone::create([
                'number' => $validated['phone'],
                'contact_id' => $contact->id,
            ]);
        }

        if (array_key_exists('email', $validated) && $validated['email'] !== null) {
            Email::create([
                'address' => $validated['email'],
                'contact_id' => $contact->id,
            ]);
        }

        return response()->json($contact);
    }

    public function show(Contact $contact)
    {
        return response()->json($contact->load('phone', 'email'));
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy(Contact $contact)
    {
        if ($contact->leads()->exists()) {
            return response()->json(['message' => 'Нельзя удалить контакт, у которого есть лиды'], 400);
        }

        $contact->delete();
    }
}
