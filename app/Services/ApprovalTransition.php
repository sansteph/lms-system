<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

class ApprovalTransition
{
    public static function apply(Model $record, string $column, array $values, bool $feature = false): void
    {
        $allowed = ['Pending', 'Pending Approval', 'pending_admin_approval'];
        if ($feature) $allowed[] = 'Approved';
        // Compare and update together so duplicate requests cannot repeat side effects.
        $changed = $record->newQuery()->whereKey($record->getKey())
            ->whereIn($column, $allowed)->update($values);
        abort_unless($changed === 1, 409, 'This item has already been reviewed. Refresh to see its current status.');
        $record->refresh();
    }
}
