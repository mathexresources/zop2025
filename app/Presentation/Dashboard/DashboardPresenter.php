<?php

declare(strict_types=1);

namespace App\Presentation\Dashboard;

use App\Presentation\Accessory\RequireLoggedUser;
use Nette\Application\AbortException;
use Nette;
use Nette\Application\UI\Form;
use App\Presentation\BasePresenter;
use App\Model\WarehouseRepository;
use App\Model\CategoryRepository;
use App\Model\InventoryRepository;
use App\Model\ItemRepository;
use Tracy\Debugger;
use Nette\Application\Attributes\Persistent;
use App\Model\UserFacade;

/**
 * Presenter for the dashboard view.
 * Ensures the user is logged in before access.
 */
final class DashboardPresenter extends BasePresenter
{
    use RequireLoggedUser;

    // Incorporates methods to check user login status

    public function __construct(
        private readonly UserFacade          $userFacade,
        private readonly WarehouseRepository $warehouseRepository,
        private readonly ItemRepository      $itemRepository,
        private readonly CategoryRepository  $categoryRepository,
        private readonly InventoryRepository $inventoryRepository,
    )
    {
    }

    private function checkAdminRole(): void
    {
        if (!$this->user->isInRole('admin')) {
            $this->flashMessage('You do not have permission to view this page.', 'error');
            $this->redirect('Dashboard:default');
        }
    }

    public function renderWarehouses(?int $id = null): void
    {
        $this->template->warehouses = $this->warehouseRepository->findAll();
        $this->template->canEdit = $this->user->isInRole('admin') || $this->user->isInRole('moderator');
        $this->template->canDelete = $this->user->isInRole('admin');

        if ($id !== null) {
            $this->template->selectedWarehouse = $this->warehouseRepository->findById($id);
        }
    }

    public function createComponentEditWarehouseForm(): Nette\Application\UI\Form
    {
        $form = new Form;

        $form->addHidden('id');

        $form->addText('name', 'Name:')
            ->setRequired('Please enter the warehouse name.')
            ->setHtmlAttribute('placeholder', 'Warehouse Name');

        $form->addTextArea('description', 'Description:')
            ->setHtmlAttribute('placeholder', 'Warehouse Description');

        $form->addText('location', 'Location:')
            ->setRequired('Please enter the warehouse location.')
            ->setHtmlAttribute('placeholder', 'Warehouse Location');

        $form->addSubmit('save', 'Save');

        $form->onSuccess[] = [$this, 'editWarehouseFormSucceeded'];

        if (isset($this->template->selectedWarehouse)) {
            $form->setDefaults([
                'id' => $this->template->selectedWarehouse->id,
                'name' => $this->template->selectedWarehouse->name,
                'description' => $this->template->selectedWarehouse->description,
                'location' => $this->template->selectedWarehouse->location,
            ]);

            if ($this->user->isInRole('admin')) {
                $form->addSubmit('delete', 'Delete');
            }
        }

        return $form;
    }


    public function editWarehouseFormSucceeded(Nette\Application\UI\Form $form, array $values): void
    {
        $warehouseId = $values['id'];

        try {
            // Handle update action
            $this->warehouseRepository->update(
                intval($warehouseId),
                $values['name'],
                $values['description'],
                $values['location']
            );

            // Flash the success message before redirecting
            $this->flashMessage('Warehouse updated successfully', 'success');

            // Redirect after success to prevent further execution
            $this->redirect('Dashboard:warehouses', $warehouseId);
        } catch (\Throwable $e) {
            if ($e instanceof AbortException) {
                $this->redirect('Dashboard:warehouses');
                return;
            }

            Debugger::log($e, Debugger::EXCEPTION);
            // If an error occurs, flash the error message
        }
    }

    public function actionAddWarehouse(): void
    {
        $this->checkAdminRole();

        // Insert a new warehouse with default values
        $newWarehouse = $this->warehouseRepository->add(
            'New warehouse', // default name
            '',              // empty description
            ''               // empty location
        );

        // Redirect to the warehouse details page after insertion
        $this->flashMessage('New warehouse added successfully', 'success');
        $this->redirect('Dashboard:warehouses', $newWarehouse);

    }

    public function actionDeleteWarehouse(int $id): void
    {
        $this->checkAdminRole();

        try {
            // Delete the warehouse by ID
            $this->warehouseRepository->delete($id);

            $this->flashMessage('Warehouse deleted successfully', 'success');
            $this->redirect('Dashboard:warehouses');
        } catch (\Throwable $e) {
            if ($e instanceof AbortException) {
                $this->redirect('Dashboard:warehouses');
                return;
            }
            $this->redirect('Dashboard:warehouses');
        }
    }

//    items

    public function renderItems(?int $id = null): void
    {
        $this->template->items = $this->itemRepository->findAll();
        $this->template->canEdit = $this->user->isInRole('admin') || $this->user->isInRole('moderator');
        $this->template->canDelete = $this->user->isInRole('admin');

        if ($id !== null) {
            $this->template->selectedItem = $this->itemRepository->findById($id);
        }
    }

    public function createComponentEditItemForm(): Nette\Application\UI\Form
    {
        $form = new Form;

        // Hidden ID field
        $form->addHidden('id');

        // Name field
        $form->addText('name', 'Name:')
            ->setRequired('Please enter the item name.')
            ->setHtmlAttribute('placeholder', 'Item Name');

        // Description field
        $form->addTextArea('description', 'Description:')
            ->setHtmlAttribute('placeholder', 'Item Description');

        // Weight field
        $form->addText('weight', 'Weight:')
            ->setRequired('Please enter the item weight.')
            ->setHtmlAttribute('placeholder', 'Item Weight');

        // Size (X, Y, Z) fields
        $form->addText('size_x', 'Size X:')
            ->setRequired('Please enter the item size in X dimension.')
            ->setHtmlAttribute('placeholder', 'Size X');

        $form->addText('size_y', 'Size Y:')
            ->setRequired('Please enter the item size in Y dimension.')
            ->setHtmlAttribute('placeholder', 'Size Y');

        $form->addText('size_z', 'Size Z:')
            ->setRequired('Please enter the item size in Z dimension.')
            ->setHtmlAttribute('placeholder', 'Size Z');

        // Category field (select dropdown)
        $form->addSelect('category_id', 'Category:')
            ->setRequired('Please select an item category.')
            ->setPrompt('Select a category') // Add placeholder option
            ->setItems([
                1 => 'Category 1', // Example options (replace with actual categories)
                2 => 'Category 2',
                3 => 'Category 3',
            ]);

        // Submit button
        $form->addSubmit('save', 'Save');

        // Success handler
        $form->onSuccess[] = [$this, 'editItemFormSucceeded'];

        // If there's a selected item, pre-fill the form fields
        if (isset($this->template->selectedItem)) {
            $form->setDefaults([
                'id' => $this->template->selectedItem->id,
                'name' => $this->template->selectedItem->name,
                'description' => $this->template->selectedItem->description,
                'weight' => $this->template->selectedItem->weight,
                'size_x' => $this->template->selectedItem->size_x,
                'size_y' => $this->template->selectedItem->size_y,
                'size_z' => $this->template->selectedItem->size_z,
                'category_id' => $this->template->selectedItem->category_id,
            ]);

            // Only admins can see the delete button
            if ($this->user->isInRole('admin')) {
                $form->addSubmit('delete', 'Delete');
            }
        }

        return $form;
    }

    public function editItemFormSucceeded(Nette\Application\UI\Form $form, array $values): void
    {
        $itemId = $values['id'];

        try {
// Handle update action
            $this->itemRepository->update(
                intval($itemId),
                $values['name'],
                $values['description'],
                $values['weight'],
                $values['size_x'],
                $values['size_y'],
                $values['size_z'],
                $values['category_id']
            );

// Flash the success message before redirecting
            $this->flashMessage('Item updated successfully', 'success');

// Redirect after success to prevent further execution
            $this->redirect('Dashboard:items', $itemId);
        } catch (\Throwable $e) {
            if ($e instanceof AbortException) {
                $this->redirect('Dashboard:items');
                return;
            }

            Debugger::log($e, Debugger::EXCEPTION);
// If an error occurs, flash the error message
        }
    }

    public function actionAddItem(): void
    {
        $this->checkAdminRole();

// Insert a new item with default values
        $newItem = $this->itemRepository->add(
            'New item', // default name
            '',         // empty description
            0,          // default weight
            0,          // default size_x
            0,          // default size_y
            0,          // default size_z
            1           // default category_id (replace with actual category ID)
        );

// Redirect to the item details page after insertion
        $this->flashMessage('New item added successfully', 'success');
        $this->redirect('Dashboard:items', $newItem);

    }

    public function actionDeleteItem(int $id): void
    {
        $this->checkAdminRole();

        try {
// Delete the item by ID
            $this->itemRepository->delete($id);

            $this->flashMessage('Item deleted successfully', 'success');
            $this->redirect('Dashboard:items');
        } catch (\Throwable $e) {
            if ($e instanceof AbortException) {
                $this->redirect('Dashboard:items');
                return;
            }
            $this->redirect('Dashboard:items');
        }
    }

//    categories
    public function renderCategories(?int $id = null): void
    {
        $this->template->categories = $this->categoryRepository->findAll();
        $this->template->canEdit = $this->user->isInRole('admin') || $this->user->isInRole('moderator');
        $this->template->canDelete = $this->user->isInRole('admin');

        if ($id !== null) {
            $this->template->selectedCategory = $this->categoryRepository->findById($id);
        }
    }

    public function createComponentEditCategoryForm(): Nette\Application\UI\Form
    {
        $form = new Form;

        $form->addHidden('id');

        $form->addText('name', 'Name:')
            ->setRequired('Please enter the category name.')
            ->setHtmlAttribute('placeholder', 'Category Name');

        $form->addTextArea('description', 'Description:')
            ->setHtmlAttribute('placeholder', 'Category Description');
        $categories = $this->categoryRepository->findAll();
        $categoriesSelect = [];
        foreach ($categories as $category) {
            $categoriesSelect[$category->id] = $category->name;
        }
        $form->addSelect('parent_id', 'Parrent:')
            ->setPrompt('Select a parrent') // Add placeholder option
            ->setItems([
                null => 'No parent', // Example options (replace with actual categories)
            ] + $categoriesSelect);

        $form->addSubmit('save', 'Save');

        $form->onSuccess[] = [$this, 'editCategoryFormSucceeded'];

        if (isset($this->template->selectedCategory)) {
            $form->setDefaults([
                'id' => $this->template->selectedCategory->id,
                'name' => $this->template->selectedCategory->name,
                'description' => $this->template->selectedCategory->description,
                'parent_id' => $this->template->selectedCategory->parent_id,
            ]);

            if ($this->user->isInRole('admin')) {
                $form->addSubmit('delete', 'Delete');
            }
        }

        return $form;
    }


    public function editCategoryFormSucceeded(Nette\Application\UI\Form $form, array $values): void
    {
        $categoryId = $values['id'];

        try {
            // Handle update action
            $this->categoryRepository->update(
                intval($categoryId),
                $values['name'],
                $values['description'],
                $values['parent_id']
            );

            // Flash the success message before redirecting
            $this->flashMessage('Category updated successfully', 'success');

            // Redirect after success to prevent further execution
            $this->redirect('Dashboard:categories', $categoryId);
        } catch (\Throwable $e) {
            if ($e instanceof AbortException) {
                $this->redirect('Dashboard:categories');
                return;
            }

            Debugger::log($e, Debugger::EXCEPTION);
            // If an error occurs, flash the error message
        }
    }

    public function actionAddCategory(): void
    {
        $this->checkAdminRole();

        // Insert a new category with default values
        $newCategory = $this->categoryRepository->add(
            'New category', // default name
            '',              // empty description
            null               // empty location
        );

        // Redirect to the category details page after insertion
        $this->flashMessage('New category added successfully', 'success');
        $this->redirect('Dashboard:categories', $newCategory);

    }

    public function actionDeleteCategory(int $id): void
    {
        $this->checkAdminRole();

        try {
            // Delete the category by ID
            $this->categoryRepository->delete($id);

            $this->flashMessage('Category deleted successfully', 'success');
            $this->redirect('Dashboard:categories');
        } catch (\Throwable $e) {
            if ($e instanceof AbortException) {
                $this->redirect('Dashboard:categories');
                return;
            }
            $this->redirect('Dashboard:categories');
        }
    }

//    inventory

    public function renderInventory(?string $filter = 'warehouses=all;items=all;categories=all'): void {

        $this->template->filter = $filter;
        $this->template->warehouses = $this->warehouseRepository->findAll();
        $this->template->item_types = $this->itemRepository->findAll();
        $this->template->items = $this->inventoryRepository->findFiltered($filter);
        $this->template->categories = $this->categoryRepository->findAll();
        $this->template->canEdit = $this->user->isInRole('admin') || $this->user->isInRole('moderator');
        $this->template->canDelete = $this->user->isInRole('admin');
    }

    public function createComponentFilter(): Nette\Application\UI\Form {
        $form = new Form;
        $warehouses = $this->warehouseRepository->findAll();
        $warehousesSelect = [];
        foreach ($warehouses as $warehouse) {
            $warehousesSelect[$warehouse->id] = $warehouse->name;
        }

        $form->addSelect('warehouse', 'Warehouses:')
            ->setPrompt('Select a warehouse') // Add placeholder option
            ->setItems([
                'all' => 'All', // Example options (replace with actual warehouses)
            ] + $warehousesSelect);

        $items = $this->itemRepository->findAll();
        $itemsSelect = [];
        foreach ($items as $item) {
            $itemsSelect[$item->id] = $item->name;
        }
        $form->addSelect('item', 'Items:')
            ->setPrompt('Select an item') // Add placeholder option
            ->setItems([
                'all' => 'All', // Example options (replace with actual items)
            ] + $itemsSelect);

        $categories = $this->categoryRepository->findAll();
        $categoriesSelect = [];
        foreach ($categories as $category) {
            $categoriesSelect[$category->id] = $category->name;
        }

        $form->addSelect('category', 'Categories:')
            ->setPrompt('Select a category') // Add placeholder option
            ->setItems([
                'all' => 'All', // Example options (replace with actual categories)
            ] + $categoriesSelect);


        $form->addSubmit('filter', 'Filter');

        $form->onSuccess[] = [$this, 'filterFormSucceeded'];

        return $form;
    }

    public function filterFormSucceeded(Nette\Application\UI\Form $form, array $values): void {
        if ($values['warehouse'] === '' || $values['warehouse'] === null) {
            $values['warehouse'] = 'all';
        }
        if ($values['item'] === '' || $values['item'] === null) {
            $values['item'] = 'all';
        }
        if ($values['category'] === '' || $values['category'] === null) {
            $values['category'] = 'all';
        }
        $filter = 'warehouses=' . $values['warehouse'] . ';items=' . $values['item'] . ';categories=' . $values['category'];
        $this->redirect('Dashboard:inventory', $filter);
    }

//    item detail

    public function renderDetail(int $id): void
    {
        $this->template->item = $this->itemRepository->findById($id);
        $this->template->canEdit = $this->user->isInRole('admin') || $this->user->isInRole('moderator');
        $this->template->canDelete = $this->user->isInRole('admin');
    }

//    item scanner

    public function createComponentScannerForm(): Nette\Application\UI\Form
    {
        $form = new Form;

        $form->addText('barcode', 'Barcode:')
            ->setRequired('Please enter the barcode.')
            ->setHtmlAttribute('placeholder', 'Barcode');

        $form->addSubmit('scan', 'Scan');

        $form->onSuccess[] = [$this, 'scannerFormSucceeded'];

        return $form;
    }

    public function scannerFormSucceeded(Form $form, $values): void
    {
        $barcode = $values->barcode;

        $barcodeParts = explode('|', $barcode);

        // Ensure the barcode contains exactly 7 parts (movement_type, item_type_id, from_warehouse_id, to_warehouse_id, amount_in_package, specific_item_id, attributes)
        if (count($barcodeParts) < 6) {
            $form->addError('Invalid barcode format. There must be at least 6 fields.');
            return;
        }

        // Parse the barcode fields into variables
        list(
            $movementType,
            $itemTypeId,
            $fromWarehouseId,
            $toWarehouseId,
            $amountInPackage,
            $specificItemId,
            $attributesString
            ) = array_pad($barcodeParts, 7, '');

        // Validate movement type (should be an integer 1-4)
        if (!in_array($movementType, [1, 2, 3, 4])) {
            $form->addError('Invalid movement type.');
            return;
        }

        // Validate the other fields: they should be numeric where required
        if (!is_numeric($itemTypeId) || !is_numeric($fromWarehouseId) || !is_numeric($toWarehouseId) || !is_numeric($amountInPackage) || !is_numeric($specificItemId)) {
            $form->addError('Fields item_type_id, from_warehouse_id, to_warehouse_id, amount_in_package, and specific_item_id must be numeric.');
            return;
        }

        // Parse the attributes string (optional)
        $attributes = [];
        if (!empty($attributesString)) {
            $attributesArray = explode(';', rtrim($attributesString, ';')); // Split by semicolons and remove trailing semicolon
            foreach ($attributesArray as $attribute) {
                list($key, $value) = explode(':', $attribute);
                $attributes[trim($key)] = trim($value); // Store attribute key-value pairs
            }
        }

        // Prepare the structured data in JSON format
        $barcodeData = [
            'movement_type' => (int)$movementType,
            'item_type_id' => (int)$itemTypeId,
            'from_warehouse_id' => (int)$fromWarehouseId,
            'to_warehouse_id' => (int)$toWarehouseId,
            'amount_in_package' => (int)$amountInPackage,
            'specific_item_id' => (int)$specificItemId,
            'attributes' => $attributes,
        ];

        // Optional: Output the barcode data as JSON
        echo json_encode($barcodeData, JSON_PRETTY_PRINT);

        // You can proceed with further processing, such as storing this data in the database
    }



}
