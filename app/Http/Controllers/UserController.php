<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $users = User::where('role', 'Teacher')
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('user_id', 'like', "%{$search}%")
                      ->orWhere('name', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->get();

        return view('users', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string|max:50|unique:users,user_id',
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'status' => 'required|boolean',
        ]);

        User::create([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'Teacher',
            'password' => Hash::make($request->password),
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Teacher added successfully');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|string|max:50|unique:users,user_id,' . $id,
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $id,
            'status' => 'required|boolean',
        ]);

        $user = User::where('role', 'Teacher')->findOrFail($id);

        $user->update([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'email' => $request->email,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'Teacher updated successfully');
    }

    public function delete($id)
    {
        $user = User::where('role', 'Teacher')->findOrFail($id);

        $user->delete();

        return redirect()->back()->with('success', 'Teacher deleted successfully');
    }

    public function adminLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
                    ->where('role', 'Admin')
                    ->where('status', 1)
                    ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            session([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
            ]);

            return redirect()->route('admin.dashboard');
        }

        return redirect()->back()->with('error', 'Invalid admin login details');
    }

    public function teacherLogin(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)
                    ->where('role', 'Teacher')
                    ->where('status', 1)
                    ->first();

        if ($user && Hash::check($request->password, $user->password)) {
            session([
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role,
            ]);

            return redirect()->route('teacher.dashboard');
        }

        return redirect()->back()->with('error', 'Invalid teacher login details');
    }

    public function logout()
    {
        session()->flush();

        return redirect()->route('home');
    }
}