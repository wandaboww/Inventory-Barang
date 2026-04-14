<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Loan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class LoanService
{
    public function createLoan(User $user, Asset $asset, ?int $adminId = null, ?string $signaturePath = null): Loan
    {
        if (!$this->canUserBorrow($user)) {
            throw new RuntimeException('User masih memiliki pinjaman aktif atau status blacklist.');
        }

        if ($asset->status !== 'available') {
            throw new RuntimeException('Asset sedang tidak tersedia untuk dipinjam.');
        }

        $dueDate = $this->calculateDueDate($user);

        return DB::transaction(function () use ($user, $asset, $adminId, $signaturePath, $dueDate): Loan {
            $loan = Loan::create([
                'user_id' => $user->id,
                'asset_id' => $asset->id,
                'admin_id' => $adminId,
                'loan_date' => Carbon::now(),
                'due_date' => $dueDate,
                'status' => 'active',
                'digital_signature_path' => $signaturePath,
            ]);

            $asset->update(['status' => 'borrowed']);

            return $loan;
        });
    }

    public function returnLoan(Loan $loan, string $condition, ?string $notes = null, array $checklist = []): Loan
    {
        if (!in_array($condition, ['good', 'minor_damage', 'major_damage'], true)) {
            throw new RuntimeException('Kondisi pengembalian tidak valid.');
        }

        return DB::transaction(function () use ($loan, $condition, $notes, $checklist): Loan {
            $asset = $loan->asset()->lockForUpdate()->firstOrFail();

            $assetStatus = $condition === 'major_damage' ? 'maintenance' : 'available';
            $asset->update([
                'status' => $assetStatus,
                'condition' => $condition,
            ]);

            $loan->update([
                'status' => 'returned',
                'return_date' => Carbon::now(),
                'return_condition' => $condition,
                'return_notes' => $notes,
                'return_checklist' => $checklist,
            ]);

            return $loan->fresh(['user', 'asset']);
        });
    }

    public function canUserBorrow(User $user): bool
    {
        return !Loan::query()
            ->where('user_id', $user->id)
            ->whereIn('status', ['active', 'overdue'])
            ->exists();
    }

    public function calculateDueDate(User $user): Carbon
    {
        return $user->role === 'teacher'
            ? Carbon::now()->addDays(3)
            : Carbon::now()->addDay();
    }
}
