<?php

declare(strict_types=1);

namespace App\Model;

use Nette\Database\Explorer;

final class inventoryRepository
{
    public function __construct(
        private readonly Explorer $db,
    )
    {
    }

    public function findAll(): array
    {
        return $this->db->table('inventory')->fetchAll();
    }

    public function findFiltered(string $filterString)
    {
        $filters = [];
        foreach (explode(';', $filterString) as $part) {
            list($key, $value) = explode('=', $part);
            $filters[$key] = $value;
        }

        $query = 'SELECT inventory.*, item_types.name AS item_name, categories.name AS category_name 
                  FROM inventory
                  JOIN item_types ON inventory.item_id = item_types.id
                  JOIN categories ON item_types.category_id = categories.id';

        if (!empty($filters['warehouses']) && $filters['warehouses'] !== 'all') {
            $query .= ' AND inventory.warehouse_id = '. $filters['warehouses'];
        }

        if (!empty($filters['items']) && $filters['items'] !== 'all') {
            $query .= ' AND inventory.item_id = '. $filters['items'];
        }

        if (!empty($filters['categories']) && $filters['categories'] !== 'all') {
            $query .= ' AND item_types.category_id = '. $filters['categories'];
        }

        $query .= ' ORDER BY inventory.id;';

        $stmt = $this->db->query($query);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?\Nette\Database\Table\ActiveRow
    {
        return $this->db->table('inventory')->get($id);
    }

    public function add(int $item_id, int $warehouse_id, int $quantity): void
    {
        $this->db->table('inventory')->insert([
            'item_id' => $item_id,
            'warehouse_id' => $warehouse_id,
            'quantity' => $quantity,
        ]);
    }

    public function remove(int $id): void
    {
        $this->db->table('inventory')->where('id', $id)->delete();
    }

    public function move(int $id, int $warehouse_id): void
    {
        $this->db->table('inventory')->where('id', $id)->update([
            'warehouse_id' => $warehouse_id,
        ]);
    }

    /**
     * Parse the barcode string into a structured array
     *
     * @param string $barcode
     * @return array|null Returns parsed data as an associative array or null if invalid.
     */
    public function parseBarcode(string $barcode): ?array
    {
        $barcodeParts = explode('|', $barcode);

        // We expect 7 fields: movement_type, item_type_id, from_warehouse_id, to_warehouse_id, amount_in_package, specific_item_id, attributes
        if (count($barcodeParts) < 6) {
            return null;  // Invalid barcode format
        }

        list(
            $movementType,
            $itemTypeId,
            $fromWarehouseId,
            $toWarehouseId,
            $amountInPackage,
            $specificItemId,
            $attributesString
            ) = array_pad($barcodeParts, 7, '');

        // Validate movement type
        if (!in_array($movementType, [1, 2, 3, 4])) {
            return null;  // Invalid movement type
        }

        // Validate other fields
        if (!is_numeric($itemTypeId) || !is_numeric($fromWarehouseId) || !is_numeric($toWarehouseId) ||
            !is_numeric($amountInPackage) || !is_numeric($specificItemId)) {
            return null;  // Invalid numeric fields
        }

        // Parse attributes if any
        $attributes = [];
        if (!empty($attributesString)) {
            $attributesArray = explode(';', rtrim($attributesString, ';'));
            foreach ($attributesArray as $attribute) {
                list($key, $value) = explode(':', $attribute);
                $attributes[trim($key)] = trim($value);
            }
        }

        return [
            'movement_type' => (int)$movementType,
            'item_type_id' => (int)$itemTypeId,
            'from_warehouse_id' => (int)$fromWarehouseId,
            'to_warehouse_id' => (int)$toWarehouseId,
            'amount_in_package' => (int)$amountInPackage,
            'specific_item_id' => (int)$specificItemId,
            'attributes' => $attributes,
        ];
    }

    /**
     * Add an item to the inventory from a barcode
     *
     * @param string $barcode
     * @return void
     */
    public function addFromBarcode(string $barcode): void
    {
        $data = $this->parseBarcode($barcode);

        if ($data === null) {
            throw new \InvalidArgumentException('Invalid barcode data.');
        }

        // Add the item to the inventory
        $this->add($data['item_type_id'], $data['to_warehouse_id'], $data['amount_in_package']);
    }

    /**
     * Move an item to another warehouse from barcode
     *
     * @param string $barcode
     * @return void
     */
    public function moveFromBarcode(string $barcode): void
    {
        $data = $this->parseBarcode($barcode);

        if ($data === null) {
            throw new \InvalidArgumentException('Invalid barcode data.');
        }

        // Update the warehouse of the item
        $this->move($data['specific_item_id'], $data['to_warehouse_id']);
    }
}
