<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()->with('role')
            ->when($request->filled('q'), fn ($query) => $query->where(function ($search) use ($request) {
                $keyword = '%'.$request->string('q')->trim().'%';
                $search->where('name', 'like', $keyword)->orWhere('email', 'like', $keyword)->orWhere('phone', 'like', $keyword);
            }))
            ->when($request->filled('role'), fn ($query) => $query->whereHas('role', fn ($role) => $role->where('slug', $request->string('role'))))
            ->when($request->filled('status'), fn ($query) => $query->where('is_active', $request->string('status') === 'active'))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.users.index', ['users' => $users, 'roles' => Role::orderBy('id')->get()]);
    }

    public function create()
    {
        return view('admin.users.form', ['user' => new User, 'roles' => Role::orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['password'] = Hash::make($data['password']);
        User::query()->create($data);

        return redirect()->route('admin.users.index')->with('success', 'Đã tạo tài khoản mới.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', ['user' => $user, 'roles' => Role::orderBy('id')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        if ($user->is($request->user()) && (! ($data['is_active'] ?? false) || (string) $data['role_id'] !== (string) $user->role_id)) {
            return back()->withErrors(['role_id' => 'Bạn không thể tự khóa tài khoản hoặc tự thay đổi vai trò của mình.'])->withInput();
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        } else {
            $data['password'] = Hash::make($data['password']);
        }
        $user->update($data);

        return redirect()->route('admin.users.index')->with('success', 'Đã cập nhật tài khoản.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:160', Rule::unique('users', 'email')->ignore($user?->id)],
            'phone' => ['nullable', 'string', 'max:20', Rule::unique('users', 'phone')->ignore($user?->id)],
            'role_id' => ['required', 'exists:roles,id'],
            'is_active' => ['required', 'boolean'],
            'password' => [$user ? 'nullable' : 'required', 'confirmed', 'min:8'],
        ]);
    }
}
