<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();

        return view('users', compact('users'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|string|max:50|unique:users,user_id',
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'role' => 'required|string',
            'password' => 'required|min:6',
            'status' => 'required|boolean',
        ]);

        User::create([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'User added successfully');
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|string|max:50|unique:users,user_id,' . $id,
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $id,
            'role' => 'required|string',
            'status' => 'required|boolean',
        ]);

        $user = User::findOrFail($id);

        $user->update([
            'user_id' => $request->user_id,
            'name' => $request->name,
            'email' => $request->email,
            'role' => $request->role,
            'status' => $request->status,
        ]);

        return redirect()->back()->with('success', 'User updated successfully');
    }
    public function delete($id)
    {
        $user = User::findOrFail($id);

        $user->delete();

        return redirect()->back()->with('success', 'User deleted successfully');
    }
}