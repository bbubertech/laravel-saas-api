<?php

namespace App\Http\Controllers;

use App\Http\Requests\Users\UserRequest;
use App\Http\Resources\UserResource;
use App\Models\Role;
use App\Models\User;
use App\Support\Billing\Features;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\ValidationException;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('can:users.view', only: ['index', 'show']),
            new Middleware('can:users.manage', only: ['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $users = User::query()
            ->with('role')
            ->when($request->integer('role_id'), fn ($query, int $roleId) => $query->where('role_id', $roleId))
            ->orderBy('name')
            ->paginate(max(1, min($request->integer('per_page', 25), 100)));

        return UserResource::collection($users);
    }

    public function show(User $user): UserResource
    {
        return new UserResource($user->load('role'));
    }

    public function store(UserRequest $request, Features $features): UserResource
    {
        $features->ensureWithinLimit($request->user()->tenant, 'users', User::count());

        return new UserResource(User::create($request->validated())->load('role'));
    }

    public function update(UserRequest $request, User $user): UserResource
    {
        $data = $request->validated();

        if (isset($data['role_id']) && $user->isOwner() && $this->isLastOwner($user)) {
            $owner = Role::where('slug', 'owner')->value('id');

            if ((int) $data['role_id'] !== $owner) {
                throw ValidationException::withMessages(['role_id' => 'The company needs at least one owner.']);
            }
        }

        $user->update($data);

        return new UserResource($user->load('role'));
    }

    public function destroy(Request $request, User $user): Response
    {
        if ($user->is($request->user())) {
            throw ValidationException::withMessages(['user' => 'You cannot delete your own account.']);
        }

        if ($user->isOwner() && $this->isLastOwner($user)) {
            throw ValidationException::withMessages(['user' => 'The company needs at least one owner.']);
        }

        $user->delete();

        return response()->noContent();
    }

    private function isLastOwner(User $user): bool
    {
        return User::whereRelation('role', 'slug', 'owner')->whereKeyNot($user->id)->doesntExist();
    }
}
