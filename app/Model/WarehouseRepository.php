<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;

//db schema for warehouses
//int id
//string name
//string description
//longtext location (will be used as [longtitude, latitude])
//datetime created_at
final class WarehouseRepository
{

    public function __construct(
        private readonly Explorer $db,
    )
    {
    }

    public function findAll(): array
    {
        return $this->db->table('warehouses')->fetchAll();
    }

    public function findById(int $id): ?\Nette\Database\Table\ActiveRow
    {
        return $this->db->table('warehouses')->get($id);
    }

    public function add(string $name, string $description, string $location): ?string
    {
        $this->db->table('warehouses')->insert([
            'name' => $name,
            'description' => $description,
            'location' => $location,
            'created_at' => new \DateTime(),
        ]);
        return $this->db->getInsertId();
    }

    public function update(int $id, string $name, string $description, string $location): void
    {
        $this->db->table('warehouses')->where('id', $id)->update([
            'name' => $name,
            'description' => $description,
            'location' => $location,
        ]);
    }

    public function delete(int $id): void
    {
        $this->db->table('warehouses')->where('id', $id)->delete();
    }
}
