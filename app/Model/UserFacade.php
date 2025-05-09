<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Database\Table\ActiveRow;
use Nette\Security\Passwords;


/**
 * Manages user-related operations such as authentication and adding new users.
 */
final class UserFacade implements Nette\Security\Authenticator
{
	// Minimum password length requirement for users
	public const PasswordMinLength = 7;

	// Database table and column names
	private const
		TableName = 'users',
		ColumnId = 'id',
		ColumnName = 'username',
		ColumnPasswordHash = 'password',
		ColumnEmail = 'email',
		ColumnRole = 'role';

	// Dependency injection of database explorer and password utilities
	public function __construct(
		private Nette\Database\Explorer $database,
		private Passwords $passwords,
	) {
	}


	/**
	 * Authenticate a user based on provided credentials.
	 * Throws an AuthenticationException if authentication fails.
	 */
	public function authenticate(string $username, string $password): Nette\Security\SimpleIdentity
	{
		// Fetch the user details from the database by username
		$user = $this->database->table(self::TableName)
			->where(self::ColumnName, $username)
			->fetch();

		// Authentication checks
		if (!$user) {
			throw new Nette\Security\AuthenticationException('The username is incorrect.', self::IdentityNotFound);

		} elseif (!$this->verifyPassword($user, $password)) {
			throw new Nette\Security\AuthenticationException('The password is incorrect.', self::InvalidCredential);
		}

		return $this->createIdentity($user);
	}


	public function verifyPassword(ActiveRow $user, string $password): bool
	{
		if (!$this->passwords->verify($password, $user[self::ColumnPasswordHash])) {
			return false;
		}

		if ($this->passwords->needsRehash($user[self::ColumnPasswordHash])) {
			$user->update([
				self::ColumnPasswordHash => $this->passwords->hash($password),
			]);
		}

		return true;
	}


    public function createIdentity(ActiveRow $user): Nette\Security\IIdentity
    {
        // Get user roles via many-to-many relationship
        $roles = $this->database->table('user_roles')
            ->where('user_id', $user->id)
            ->select('role.name')
            ->fetchPairs(null, 'name');

        // Remove password hash from user data
        $data = $user->toArray();
        unset($data[self::ColumnPasswordHash]);

        // Return SimpleIdentity with multiple roles
        return new Nette\Security\SimpleIdentity($user[self::ColumnId], $roles, $data);
    }



    /**
	 * Add a new user to the database.
	 * Throws a DuplicateNameException if the username is already taken.
	 */
    public function add(string $username, string $email, string $password, array $roleIds = []): ActiveRow
    {
        Nette\Utils\Validators::assert($email, 'email');

        $this->database->beginTransaction();

        try {
            $user = $this->database->table(self::TableName)->insert([
                self::ColumnName => $username,
                self::ColumnPasswordHash => $this->passwords->hash($password),
                self::ColumnEmail => $email,
            ]);

            foreach ($roleIds as $roleId) {
                $this->database->table('user_roles')->insert([
                    'user_id' => $user->id,
                    'role_id' => $roleId,
                ]);
            }

            $this->database->commit();
            return $user;
        } catch (\Throwable $e) {
            $this->database->rollBack();
            throw new DuplicateNameException;
        }
    }

    public function update(int $id, string $username, string $email, string $first_name, string $last_name, string $phone, array $roleIds = []): void
    {
        Nette\Utils\Validators::assert($email, 'email');

        $this->database->beginTransaction();

        try {
            $user = $this->database->table(self::TableName)->get($id);
            if (!$user) {
                throw new Nette\Database\UniqueConstraintViolationException('User not found');
            }

            $user->update([
                self::ColumnName => $username,
                self::ColumnEmail => $email,
                'first_name' => $first_name,
                'last_name' => $last_name,
                'phone' => $phone,
            ]);

            // Update user roles
            $this->database->table('user_roles')->where('user_id', $id)->delete();
            foreach ($roleIds as $roleId) {
                $this->database->table('user_roles')->insert([
                    'user_id' => $id,
                    'role_id' => $roleId,
                ]);
            }

            $this->database->commit();
        } catch (\Throwable $e) {
            $this->database->rollBack();
            throw new DuplicateNameException;
        }
    }

    public function updatePassword(int $id, string $password): void
    {
        $this->database->beginTransaction();

        try {
            $user = $this->database->table(self::TableName)->get($id);
            if (!$user) {
                throw new Nette\Database\UniqueConstraintViolationException('User not found');
            }

            $user->update([
                self::ColumnPasswordHash => $this->passwords->hash($password),
            ]);

            $this->database->commit();
        } catch (\Throwable $e) {
            $this->database->rollBack();
            throw new DuplicateNameException;
        }
    }

    public function delete(int $id): void
    {
        $this->database->beginTransaction();

        try {
            $this->database->table('user_roles')->where('user_id', $id)->delete();
            $this->database->table(self::TableName)->where(self::ColumnId, $id)->delete();
            $this->database->commit();
        } catch (\Throwable $e) {
            $this->database->rollBack();
            throw new DuplicateNameException;
        }
    }

    public function getById(int $id): ?Nette\Security\SimpleIdentity
    {
        $user = $this->database->table(self::TableName)
            ->where('id', $id)
            ->fetch();

        if (!$user) {
            return null;
        }

        $roles = $this->database->table('user_roles')
            ->where('user_id', $user->id)
            ->select('role.name')
            ->fetchPairs(null, 'name');

        $data = $user->toArray();

        return new Nette\Security\SimpleIdentity($user[self::ColumnId], $roles, $data);
    }

    public function getByUsername(string $username): ?Nette\Security\SimpleIdentity
    {
        $user = $this->database->table(self::TableName)
            ->where(self::ColumnName, $username)
            ->fetch();

        if (!$user) {
            return null;
        }

        $roles = $this->database->table('user_roles')
            ->where('user_id', $user->id)
            ->select('role.name')
            ->fetchPairs(null, 'name');

        $data = $user->toArray();
        unset($data[self::ColumnPasswordHash]);

        return new Nette\Security\SimpleIdentity($user[self::ColumnId], $roles, $data);
    }


    public function getAll(): Nette\Database\Table\Selection
    {
        return $this->database->table(self::TableName);
    }

    public function createPasswordResetToken(int $userId): string
    {
        $token = bin2hex(random_bytes(32)); // 64-char token
        $expiresAt = new \DateTime('+3 hour');

        // Store token
        $this->database->table('password_resets')->insert([
            'user_id' => $userId,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        return $token;
    }

    public function getLastValidResetToken(int $userId): ?string
    {
        $row = $this->database->table('password_resets')
            ->where('user_id', $userId)
            ->where('expires_at > NOW()')
            ->order('created_at DESC')
            ->fetch();
        return $row?->token;
    }


}


/**
 * Custom exception for duplicate usernames.
 */
class DuplicateNameException extends \Exception
{
}
