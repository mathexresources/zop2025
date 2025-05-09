<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;

//db schema for categories
//int id
//string name
//string description
//longtext location (will be used as [longtitude, latitude])
//datetime created_at
final class CategoryRepository
{

    public function __construct(
        private readonly Explorer $db,
    )
    {
    }

    public function findAll(): array
    {
        return $this->db->table('categories')->fetchAll();
    }

    public function findById(int $id): ?\Nette\Database\Table\ActiveRow
    {
        return $this->db->table('categories')->get($id);
    }

    public function add(string $name, string $description, ?int $parent_id): ?string
    {
        $this->db->table('categories')->insert([
            'name' => $name,
            'description' => $description,
            'parent_id' => $parent_id,
            'created_at' => new \DateTime(),
        ]);
        return $this->db->getInsertId();
    }

    public function update(int $id, string $name, string $description, ?int $parent_id): void
    {
        $this->db->table('categories')->where('id', $id)->update([
            'name' => $name,
            'description' => $description,
            'parent_id' => $parent_id,
        ]);
    }

    public function delete(int $id): void
    {
        $this->db->table('categories')->where('id', $id)->delete();
    }
}
