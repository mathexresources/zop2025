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

$form->addHidden('id');

$form->addText('name', 'Name:')
->setRequired('Please enter the item name.')
->setHtmlAttribute('placeholder', 'Item Name');

$form->addTextArea('description', 'Description:')
->setHtmlAttribute('placeholder', 'Item Description');

$form->addText('location', 'Location:')
->setRequired('Please enter the item location.')
->setHtmlAttribute('placeholder', 'Item Location');

$form->addSubmit('save', 'Save');

$form->onSuccess[] = [$this, 'editItemFormSucceeded'];

if (isset($this->template->selectedItem)) {
$form->setDefaults([
'id' => $this->template->selectedItem->id,
'name' => $this->template->selectedItem->name,
'description' => $this->template->selectedItem->description,
'location' => $this->template->selectedItem->location,
]);

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
$values['location']
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
'',              // empty description
''               // empty location
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