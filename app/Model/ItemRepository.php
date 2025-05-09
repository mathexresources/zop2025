<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;

//db schema for items
//int id
//string name
//string description
//longtext location (will be used as [longtitude, latitude])
//datetime created_at
final class ItemRepository
{

    public function __construct(
        private readonly Explorer $db,
    )
    {
    }

    public function findAll(): array
    {
        return $this->db->table('item_types')->fetchAll();
    }

    public function findById(int $id): ?\Nette\Database\Table\ActiveRow
    {
        return $this->db->table('item_types')->get($id);
    }

    public function add(string $name, string $description, $weight, $size_x, $size_y, $size_z, $category_id): ?string
    {
        $this->db->table('item_types')->insert([
            'name' => $name,
            'description' => $description,
            'weight' => $weight,
            'size_x' => $size_x,
            'size_y' => $size_y,
            'size_z' => $size_z,
            'category_id' => $category_id,
            'created_at' => new \DateTimeImmutable(),
        ]);
        return $this->db->getInsertId();
    }

    public function update(int $id, string $name, string $description, $weight, $size_x, $size_y, $size_z, $category_id): void
    {
        $this->db->table('item_types')->where('id', $id)->update([
            'name' => $name,
            'description' => $description,
            'weight' => $weight,
            'size_x' => $size_x,
            'size_y' => $size_y,
            'size_z' => $size_z,
            'category_id' => $category_id,
            'created_at' => new \DateTimeImmutable(),
        ]);
    }

    public function delete(int $id): void
    {
        $this->db->table('item_types')->where('id', $id)->delete();
    }
}
