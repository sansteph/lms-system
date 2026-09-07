<?php

namespace App\Http\Controllers;

use App\Models\Institute;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class PrincipalController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search'));
        $selectedInstitute = trim((string) $request->input('institute'));
        $institutes = Institute::where('status', 1)
            ->orderBy('institute_name')
            ->pluck('institute_name');

        $principals = User::where('role', 'Principal')
            ->when($selectedInstitute !== '', fn ($query) => $query->where('institute', $selectedInstitute))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('institute', 'like', "%{$search}%");
                });
            })
            ->orderBy('institute')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('principals', compact('principals', 'institutes', 'search', 'selectedInstitute'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'institute' => 'required|string|max:255|exists:institutes,institute_name',
            'phone' => 'required|string|max:30',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|max:255',
        ]);

        $exists = User::where('role', 'Principal')
            ->where('institute', $validated['institute'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This institute already has a principal assigned.');
        }

        $principal = DB::transaction(function () use ($validated) {
            return User::create([
                'user_id' => $this->nextPrincipalUserId(),
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'institute' => $validated['institute'],
                'role' => 'Principal',
                'password' => Hash::make($validated['password']),
                'status' => 1,
            ]);
        });

        return redirect()->back()
            ->with('success', 'Principal added successfully. Login email: ' . $principal->email);
    }

    public function update(Request $request, int $id)
    {
        $principal = User::where('role', 'Principal')->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'institute' => 'required|string|max:255|exists:institutes,institute_name',
            'phone' => 'required|string|max:30',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($principal->id),
            ],
            'password' => 'nullable|string|min:8|max:255',
            'status' => 'required|boolean',
        ]);

        $exists = User::where('role', 'Principal')
            ->where('institute', $validated['institute'])
            ->where('id', '!=', $principal->id)
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'This institute already has a principal assigned.');
        }

        $updates = [
            'name' => $validated['name'],
            'institute' => $validated['institute'],
            'phone' => $validated['phone'],
            'email' => $validated['email'],
            'status' => $validated['status'],
        ];

        if (!empty($validated['password'])) {
            $updates['password'] = Hash::make($validated['password']);
        }

        $principal->update($updates);

        return redirect()->back()
            ->with('success', 'Principal updated successfully.');
    }

    public function delete(int $id)
    {
        User::where('role', 'Principal')->findOrFail($id)->delete();

        return redirect()->back()
            ->with('success', 'Principal deleted successfully.');
    }

    private function nextPrincipalUserId(): string
    {
        $last = User::where('role', 'Principal')
            ->where('user_id', 'like', 'PR%')
            ->orderByDesc('id')
            ->value('user_id');

        $next = $last && preg_match('/PR(\d+)/', $last, $matches)
            ? ((int) $matches[1]) + 1
            : 1;

        do {
            $candidate = 'PR' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
            $next++;
        } while (User::where('user_id', $candidate)->exists());

        return $candidate;
    }
}
