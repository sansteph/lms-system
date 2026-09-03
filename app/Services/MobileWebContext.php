<?php

namespace App\Services;

use App\Models\IndependentLearner;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/** Adapts explicit legacy web actions to token identity without persisting a web login. */
class MobileWebContext
{
    public function run(Request $request, callable $action)
    {
        $account = $request->user();
        abort_unless($account, 401);
        $store = app('session.store');
        $original = $store->all();
        $previous = $request->hasSession() ? $request->session() : null;
        try {
            $store->flush();
            $request->setLaravelSession($store);
            if ($account instanceof User) {
                $store->put([
                    'user_id' => $account->id, 'user_name' => $account->name,
                    'user_role' => $account->role === 'STEM Engineer' ? 'Teacher' : $account->role,
                    'user_institute' => $account->institute,
                ]);
            } elseif ($account instanceof Student) {
                $store->put(['student_id' => $account->id, 'student_name' => $account->name, 'student_code' => $account->student_id]);
            } elseif ($account instanceof IndependentLearner) {
                $store->put(['independent_learner_id' => $account->id]);
            } else {
                abort(403);
            }
            $response = $action();
            if ($response instanceof RedirectResponse) {
                if ($store->has('errors')) {
                    throw ValidationException::withMessages($store->get('errors')->getMessages());
                }
                if ($store->has('error')) {
                    throw ValidationException::withMessages(['action' => $store->get('error')]);
                }
                abort_unless($store->has('success'), 422, 'The action was not completed.');
                return response()->json(['success' => true, 'message' => $store->get('success'), 'details' => $store->get('deployment_messages', [])]);
            }
            return $response;
        } finally {
            $store->flush();
            $store->replace($original);
            // Request::setLaravelSession requires a store; an empty original store is safe in stateless API requests.
            $request->setLaravelSession($previous ?: $store);
        }
    }
}
