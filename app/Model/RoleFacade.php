<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;
use Nette\Utils\ArrayHash;

final class RoleFacade
{
    private const PROTECTED_ROLES = ['admin', 'user', 'manager'];

    public function __construct(
        private readonly Explorer $db,
    ) {}

    public function getAll(): array
    {
        return $this->db->table('roles')->fetchAll();
    }

    public function getIdsByUserId(int $userId): array
    {
        return $this->db->table('user_roles')
            ->where('user_id', $userId)
            ->fetchPairs(null, 'role_id');
    }


    public function getById(int $id): ?\Nette\Database\Table\ActiveRow
    {
        return $this->db->table('roles')->get($id);
    }

    public function isProtected(string $roleName): bool
    {
        return in_array(strtolower($roleName), self::PROTECTED_ROLES, true);
    }

    public function add(string $name): void
    {
        if ($this->isProtected($name)) {
            throw new \Exception("Cannot add protected role.");
        }

        $this->db->table('roles')->insert(['name' => $name]);
    }

    public function update(int $id, string $name): void
    {
        $role = $this->getById($id);
        if (!$role) {
            throw new \Exception("Role not found.");
        }

        if ($this->isProtected($role->name)) {
            throw new \Exception("Cannot edit protected role.");
        }

        $role->update(['name' => $name]);
    }

    public function delete(int $id): void
    {
        $role = $this->getById($id);
        if (!$role) {
            throw new \Exception("Role not found.");
        }

        if ($this->isProtected($role->name)) {
            throw new \Exception("Cannot delete protected role.");
        }

        $role->delete();
    }
}
