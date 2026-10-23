<?php

namespace App\Services\Personnel;

use App\Support\DepartmentColor;

/**
 * Personel API'sinden gelen tek bir personel kaydı.
 */
final readonly class EmployeeData
{
    public function __construct(
        public int $id,
        public string $firstName,
        public string $lastName,
        public string $email,
        public string $department,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            firstName: (string) $data['first_name'],
            lastName: (string) $data['last_name'],
            email: (string) $data['email'],
            department: (string) $data['department'],
        );
    }

    public function fullName(): string
    {
        return "{$this->firstName} {$this->lastName}";
    }

    public function initials(): string
    {
        return mb_substr($this->firstName, 0, 1).mb_substr($this->lastName, 0, 1);
    }

    public function departmentColor(): string
    {
        return DepartmentColor::for($this->department);
    }
}
