<?php

namespace App\Presentation\User;

use App\Model\RoleFacade;
use App\Model\UserFacade;
use App\Presentation\BasePresenter;
use Nette;
use Nette\Application\UI\Form;

final class UserPresenter extends BasePresenter
{
    public function __construct(
        private readonly RoleFacade $roleFacade,
        private readonly UserFacade $userFacade,
        private readonly Nette\Database\Explorer $database,
    ) {}

    public function actionProfile(?string $username = null): void
    {
        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('Please sign in to view this page.', 'error');
            $this->redirect('Sign:in');
        }

        // If no username is provided, use the logged-in user's username
        $username = $username ?: $this->getUser()->getIdentity()->username;

        $profile = $this->userFacade->getByUsername($username);

        if (!$profile) {
            $this->error('User not found');
        }

        $this->template->profile = $profile;
    }


    private function checkAdminRole(): void
    {
        if (!$this->user->isInRole('admin')) {
            $this->flashMessage('You do not have permission to view this page.', 'error');
            $this->redirect('Dashboard:default');
        }
    }

    public function actionRoles(): void
    {
        $this->checkAdminRole();
    }

    public function actionAddRole(): void
    {
        $this->checkAdminRole();
    }

    public function renderProfile(?string $username = null): void
    {
        // Use the logged-in user's username if no username is provided
        $username = $username ?: $this->getUser()->getIdentity()->username;

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Sign:in');
        }

        $profile = $this->userFacade->getByUsername($username);

        if (!$profile) {
            $this->error('User not found');
        }

        $this->template->isAdmin = $this->user->isInRole('admin');
        $this->template->isSelf = $this->getUser()->getIdentity()->username === $username;

        $this->template->profile = $profile;
    }

    public function renderRoles(): void
    {
        $this->template->roles = $this->roleFacade->getAll();
    }

    public function actionEditRole(int $id): void
    {
        $this->checkAdminRole();

        $role = $this->roleFacade->getById($id);
        if (!$role) {
            $this->error('Role not found');
        }

        if ($this->roleFacade->isProtected($role->name)) {
            $this->flashMessage('You cannot edit this role.', 'error');
            $this->redirect('User:roles');
        }

        $this['roleForm']->setDefaults(['name' => $role->name]);
    }

    public function actionDeleteRole(int $id): void
    {
        try {
            $this->roleFacade->delete($id);
            $this->flashMessage('Role deleted successfully.', 'success');
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this->redirect('User:roles');
    }

    public function createComponentRoleForm(): Form
    {
        $form = new Form;
        $form->addText('name', 'Role name:')
            ->setRequired()
            ->addRule($form::MAX_LENGTH, 'Max 50 characters', 50);

        $form->addSubmit('save', 'Save');

        $form->onSuccess[] = function (Form $form, \stdClass $values): void {
            $this->handleRoleFormSubmit($values->name);
        };

        return $form;
    }

    public function handleRoleFormSubmit(string $roleName): void
    {
        $id = $this->getParameter('id');

        try {
            if ($id) {
                $this->roleFacade->update((int)$id, $roleName);
                $this->flashMessage('Role updated.', 'success');
            } else {
                $this->roleFacade->add($roleName);
                $this->flashMessage('Role added.', 'success');
            }
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this->redirect('User:roles');
    }

    public function renderUsers(): void
    {
        $this->checkAdminRole();
        $this->template->users = $this->userFacade->getAll();
    }

    public function actionDelete(int $id): void
    {
        $this->checkAdminRole();
        $user = $this->userFacade->getById($id);
        if (!$user) {
            $this->flashMessage('User not found', 'error');
            $this->redirect('User:users');
        }
        if ($user->username === $this->getUser()->getIdentity()->username) {
            $this->flashMessage('You cannot delete your own account.', 'error');
            $this->redirect('User:users');
        }
        if (in_array('admin', $user->roles)) {
            $this->flashMessage('You cannot delete an admin account.', 'error');
            $this->redirect('User:users');
        }

        try {
            $this->userFacade->delete($id);
            $this->flashMessage('User deleted successfully.', 'success');
        } catch (\Exception $e) {
            $this->flashMessage($e->getMessage(), 'error');
        }

        $this->redirect('User:users');
    }

    public function actionEdit(int $id): void
    {
        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('Please sign in to view this page.', 'error');
            $this->redirect('Sign:in');
        }

        if (!$this->user->isInRole('admin') && $this->user->getId() !== $id) {
            $this->flashMessage('You do not have permission to view this page.', 'error');
            $this->redirect('Dashboard:default');
        }

        $user = $this->userFacade->getById($id);
        if (!$user) {
            $this->error('User not found');
        }

        $this['editUserForm']->setDefaults([
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'phone' => $user->phone,
            'roles' => $this->roleFacade->getIdsByUserId($id), // assuming this exists
        ]);
    }

    public function createComponentEditUserForm(): Form
    {
        $form = new Form;

        $form->addText('username', 'Username:')
            ->setRequired();

        $form->addEmail('email', 'Email:')
            ->setRequired();

        $form->addText('first_name', 'First Name:')
            ->setRequired();

        $form->addText('last_name', 'Last Name:')
            ->setRequired();

        $form->addText('phone', 'Phone:')
            ->setRequired();

        $roles = $this->roleFacade->getAll();
        $form->addCheckboxList('roles', 'Roles:', $roles);

        $form->addSubmit('save', 'Save');

        $form->onSuccess[] = function (Form $form, \stdClass $values): void {
            $id = $this->getParameter('id');

            try {
                $this->userFacade->update(
                    (int) $id,
                    $values->username,
                    $values->email,
                    $values->first_name,
                    $values->last_name,
                    $values->phone,
                    $values->roles ?? []
                );

                $this->flashMessage('User updated successfully.', 'success');
            } catch (\Exception $e) {
                $this->flashMessage($e->getMessage(), 'error');
            }

            $this->redirect('User:profile', $values->username);
        };

        return $form;
    }

    public function renderEdit(int $id): void
    {
        $this->template->isAdmin = $this->user->isInRole('admin');
    }

    public function actionChangePassword(): void
    {
        if (!$this->user->isLoggedIn()) {
            $this->flashMessage('You must be signed in to change your password.', 'error');
            $this->redirect('Sign:in');
        }
    }

    protected function createComponentChangePasswordForm(): Form
    {
        $form = new Form;

        $form->addPassword('old', 'Current Password:')
            ->setRequired('Please enter your current password.');

        $form->addPassword('new', 'New Password:')
            ->setRequired('Please enter a new password.')
            ->addRule($form::MIN_LENGTH, 'Password must be at least %d characters.', 6)
            ->addRule($form::PATTERN, 'Password must contain at least one digit.', '.*\d+.*');

        $form->addPassword('verify', 'Repeat New Password:')
            ->setRequired('Please repeat the new password.')
            ->addRule($form::EQUAL, 'Passwords do not match.', $form['new']);

        $form->addSubmit('save', 'Change Password');

        $form->onSuccess[] = function (Form $form, \stdClass $values): void {
            $identity = $this->getUser()->getIdentity();
            $userId   = $identity->getId();
            $userRow  = $this->userFacade->getById($userId);

            if (!password_verify($values->old, $userRow->password)) {
                $form->addError('Current password is incorrect.');
                return;
            }

            try {
                $this->userFacade->updatePassword(
                    $userId,
                    $values->new
                );
                $this->flashMessage('Your password has been changed.', 'success');
                $this->user->logout(true);
                $this->redirect('Sign:out');

            } catch (\Exception $e) {
                $form->addError($e->getMessage());
            }
        };

        return $form;
    }

    public function actionResetPassword(int $id): void
    {
        $this->checkAdminRole();

        $token = $this->userFacade->createPasswordResetToken($id);
        $resetLink = $this->link('//User:recover', ['token' => $token]);

//        $this->flashMessage("Password reset link: $resetLink", 'info');
    }

    public function renderResetPassword(int $id): void
    {
        $this->checkAdminRole();

        $token = $this->userFacade->getLastValidResetToken($id);
        if (!$token) {
            $this->error('No valid reset token exists for this user.');
        }

        $this->template->resetLink = $this->link('//User:recover', ['token' => $token]);
        $this->template->usr = $this->userFacade->getById($id);
    }


    public function actionRecover(string $token): void
    {
        $row = $this->database->table('password_resets')
            ->where('token', $token)
            ->where('expires_at > ?', new \DateTime())
            ->fetch();

        if (!$row) {
            $this->flashMessage('This reset link is invalid or expired.', 'danger');
            $this->redirect('Homepage:default');
        }

        $this->template->resetToken = $token;
    }

    protected function createComponentRecoverForm(): Form
    {
        $form = new Form;

        $form->addPassword('password', 'New Password:')
            ->setRequired()
            ->addRule(Form::MIN_LENGTH, 'Password must be at least %d characters', 6)
            ->addRule(Form::PATTERN, 'Password must include a number.', '.*[0-9].*');

        $form->addPassword('password2', 'Confirm Password:')
            ->setRequired()
            ->addRule(Form::EQUAL, 'Passwords must match.', $form['password']);

        $form->addHidden('token');
        $form->addSubmit('save', 'Reset Password');

        $form->onSuccess[] = [$this, 'recoverFormSucceeded'];

        return $form;
    }

    public function recoverFormSucceeded(Form $form, \stdClass $values): void
    {
        $row = $this->database->table('password_resets')
            ->where('token', $values->token)
            ->where('expires_at > ?', new \DateTime())
            ->fetch();

        if (!$row) {
            $this->flashMessage('This token is invalid or expired.', 'danger');
            $this->redirect('Homepage:default');
            return;
        }

        $this->userFacade->updatePassword($row->user_id, $values->password);

        // Delete used token
        $this->database->table('password_resets')
            ->where('token', $values->token)
            ->delete();

        $this->flashMessage('Password has been reset. You may now log in.', 'success');
        $this->redirect('Sign:in');
    }

}
