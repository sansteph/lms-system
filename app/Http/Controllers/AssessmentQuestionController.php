<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AssessmentQuestionController extends Controller
{
    public function index()
    {
        abort(410, 'Manual question builder has been removed. Assessments now use uploaded, admin-approved question papers.');
    }

    public function store(Request $request)
    {
        abort(410, 'Manual question creation has been removed.');
    }

    public function update(Request $request, $id)
    {
        abort(410, 'Manual question editing has been removed.');
    }

    public function delete($id)
    {
        abort(410, 'Manual question deletion has been removed.');
    }
}