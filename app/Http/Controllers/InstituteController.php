<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Institute;

class InstituteController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->search;

        $institutes = Institute::when($search, function ($query, $search) {
            return $query->where('institute_id', 'like', "%{$search}%")
                        ->orWhere('institute_name', 'like', "%{$search}%")
                        ->orWhere('location', 'like', "%{$search}%")
                        ->orWhere('contact_person', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
        })->get();

        return view('institutes', compact('institutes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'institute_id' => 'required|string|max:50|unique:institutes,institute_id',
            'institute_name' => 'required|string|max:150',
            'location' => 'required|string|max:100',
            'contact_person' => 'required|string|max:100',
            'email' => 'required|email|unique:institutes,email',
            'phone' => 'required|string|max:20',
            'status' => 'required|boolean',
        ]);

        Institute::create($request->all());

        return redirect()->back()->with('success', 'Institute added successfully');
    }
    public function update(Request $request, $id)
    {
        $request->validate([
            'institute_id' => 'required|string|max:50|unique:institutes,institute_id,' . $id,
            'institute_name' => 'required|string|max:150',
            'location' => 'required|string|max:100',
            'contact_person' => 'required|string|max:100',
            'email' => 'required|email|unique:institutes,email,' . $id,
            'phone' => 'required|string|max:20',
            'status' => 'required|boolean',
        ]);

        $institute = Institute::findOrFail($id);

        $institute->update($request->all());

        return redirect()->back()->with('success', 'Institute updated successfully');
    }
    public function delete($id)
    {
        $institute = Institute::findOrFail($id);

        $institute->delete();

        return redirect()->back()->with('success', 'Institute deleted successfully');
    }
}