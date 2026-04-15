<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class UsersExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping
{
    private int $rowNumber = 0;

    public function __construct(
        private readonly Collection $users,
    ) {
    }

    public function collection(): Collection
    {
        return $this->users;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'No',
            'Identity',
            'Nama',
            'Kelas',
            'No. HP',
            'Role',
        ];
    }

    /**
     * @param mixed $user
     * @return list<string|int>
     */
    public function map($user): array
    {
        if (!$user instanceof User) {
            return [];
        }

        $this->rowNumber++;

        return [
            $this->rowNumber,
            (string) $user->identity_number,
            (string) $user->name,
            (string) $user->kelas,
            (string) ($user->phone ?? ''),
            (string) $user->role,
        ];
    }
}
