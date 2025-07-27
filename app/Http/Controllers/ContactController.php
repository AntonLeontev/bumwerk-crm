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

        // $usersIds = Contact::pluck('id')->toArray();

        $contacts = Contact::select(['id', 'telegram', 'name', 'surname', 'patronymic'])
            // ->whereIn('id', $usersIds)
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
                $query->where(function ($q) use ($request) {
                    $phoneIds = Phone::where('number', 'like', '%'.$request->get('search').'%')->pluck('contact_id')->toArray();
                    $emailIds = Email::where('address', 'like', '%'.$request->get('search').'%')->pluck('contact_id')->toArray();
                    $nameIds = Contact::where('name', 'like', '%'.$request->get('search').'%')
                        ->orWhere('surname', 'like', '%'.$request->get('search').'%')
                        ->orWhere('patronymic', 'like', '%'.$request->get('search').'%')
                        ->pluck('id')->toArray();

                    $q->whereIn('id', array_merge($phoneIds, $emailIds, $nameIds));
                });
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
        $contact = Contact::create(Arr::except($request->validated(), ['phone', 'email']));

        if ($request->has('phone') && $request->get('phone') !== null) {
            Phone::create([
                'number' => $request->validated('phone'),
                'contact_id' => $contact->id,
            ]);
        }

        if ($request->has('email') && $request->get('email') !== null) {
            Email::create([
                'address' => $request->validated('email'),
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
        $contact->delete();
    }
}
