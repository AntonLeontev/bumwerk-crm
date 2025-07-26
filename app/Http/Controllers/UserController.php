<?php

namespace App\Http\Controllers;

use App\Http\Requests\PasswordStoreRequest;
use App\Http\Requests\PasswordUpdateRequest;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UserCreateRequest;
use App\Models\User;
use App\Notifications\UserInvited;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $paginationSize = $request->get('items_per_page') == -1
            ? User::count()
            : $request->get('items_per_page');

        $usersIds = User::pluck('id')->toArray();

        $users = User::select(['name', 'email', 'id', 'email_verified_at', 'role'])
            ->whereIn('id', $usersIds)
            ->whereNot('email', 'aner-anton@yandex.ru')
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
                    $q->where('name', 'like', '%'.$request->get('search').'%')
                        ->orWhere('email', 'like', '%'.$request->get('search').'%');
                });
            })
            ->paginate($paginationSize);

        $customPaginator = $users->toArray();
        $customPaginator['data'] = $users->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->name(),
                'email_verified' => $user->email_verified_at !== null,
            ];
        });

        return response()->json($customPaginator);
    }

    public function destroy(int $id)
    {
        User::find($id)->delete();
    }

    public function create(UserCreateRequest $request)
    {
        $password = str()->random(16);
        $user = User::create([
            'password' => $password,
            ...$request->validated(),
        ]);

        $user->notify(new UserInvited($password));
    }

    public function sendInvite(int $id)
    {
        $user = User::find($id);
        if ($user->email_verified_at === null) {
            $password = str()->random(16);
            $user->password = $password;
            $user->save();
            $user->notify(new UserInvited($password));
        } else {
            $user->notify(new UserInvited(''));
        }
    }

    public function currentUser()
    {
        return response()->json(auth()->user()->only('id', 'name', 'email', 'role'));
    }

    public function updateProfile(ProfileUpdateRequest $request)
    {
        auth()->user()->update($request->validated());

        return response()->json(auth()->user()->only('id', 'name', 'email', 'role'));
    }

    public function updatePassword(PasswordUpdateRequest $request)
    {
        auth()->user()->update([
            'password' => $request->validated('password'),
        ]);
    }

    public function storePassword(PasswordStoreRequest $request)
    {
        $user = User::where('email', $request->email)->first();

        abort_if($user === null, Response::HTTP_BAD_REQUEST, 'Пароль для пользователя уже установлен');

        if (! Hash::check($request->get('token'), $user->password)) {
            abort(Response::HTTP_BAD_REQUEST, 'Пароль для пользователя уже установлен');
        }

        $user->password = $request->validated('password');
        $user->email_verified_at = now();
        $user->save();

        Auth::login($user);
    }
}
