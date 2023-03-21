<?php

declare(strict_types=1);

namespace UserStorage\Repository;

use UserStorage\User\{UserInterface, UserHydratorInterface};
use UserStorage\Validator\UserValidatorInterface;
use UserStorage\Exception\InvalidDependencyException;
use PDO;
use Traversable;
use Exception;

class MysqlUserRepository implements UserRepositoryInterface
{
    /**
     * Name of the DB table
     */
    public string $usersTable = 'users';
    
    /**
     * How many user records are inserted in one batch
     */
    public int $maxUsersPerInsert = 500;
    
    /**
     * When set to true, repository will overwrite names of users with duplicate IDs
     * by using INSERT ... ON DUPLICATE KEY UPDATE
     */
    public bool $updateOnInsert = false;
    
    protected ?UserValidatorInterface $validator = null;
    
    private $isTransaction = 0;
    
    /**
     * Check that PDO type is mysql because SQL dialect uses Mysql features.
     * Issues warning if PDO is not configured to throw exceptions. 
     * 
     * @param PDO $pdo Interface to Mysql database
     * @param UserHydratorInterface $hydrator Hydrator to create users
     * @throws InvalidDependencyException
     */
    public function __construct(protected PDO $pdo, protected UserHydratorInterface $hydrator) 
    {
        $driverName = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driverName !== 'mysql') throw new InvalidDependencyException(
            __CLASS__." can use only mysql PDO driver; supplied: ".$driverName
        );
        if ($this->pdo->getAttribute(PDO::ATTR_ERRMODE) !== PDO::ERRMODE_EXCEPTION) {
            trigger_error("PDO::ATTR_ERRMODE in \$pdo argument to ".__METHOD__."()"
                ." isn't set to PDO::ERRMODE_EXCEPTION, while this repository implementation"
                ." does not provide own error handling for PDO errrors", 
                E_USER_WARNING);
        }
    }
    
    public function setValidator(UserValidatorInterface $validator): void
    {
        $this->validator = $validator;
    }        
    
    public function saveUsers(Traversable|array $users, array & $problems = []): bool
    {
        $success = true;
        $this->pdo->beginTransaction();
        $this->isTransaction++;
        $problems = [];
        try {
            if ($this->validator) {
                $users = [...$users];
                $problems = $this->validator->getValidationProblems($users);
            }
            if ($problems) {
                $success = false;
            } else {
                $this->saveManyUsers($users);
            }
        } catch (Exception $e) {
            $this->isTransaction--;
            $this->pdo->rollBack();
            throw $e;
        }
        
        if ($success) $this->pdo->commit();
        else $this->pdo->rollBack();
        $this->isTransaction--;
        
        return $success;
    }
    
    protected function saveManyUsers(Traversable|array $users): void
    {
        $queue = [];
        foreach ($users as $user) {
            $queue[] = $user;
            if (count($queue) >= $this->maxUsersPerInsert) {
                $this->saveBatch($queue);
                $queue = [];
            }
        }
        if ($queue) $this->saveBatch($queue);
        
    }
    
    protected function saveBatch(array $queue): void
    {
        $recordPlaceholders = [];
        $values = [];
        
        foreach ($queue as $user) {
            $placeholders = [];
            foreach ([$user->getId(), $user->getName()] as $item) {
                $placeholders[] = '?';
                $values[] = $item;
            }
            $recordPlaceholders[] = "(".implode(", ", $placeholders).")";
        }
        
        if ($this->updateOnInsert) {
            $updateClause = "\n    ON DUPLICATE KEY UPDATE name=VALUES(name)";
        } else {
            $updateClause = "";
        }
        
        $stmt = $this->pdo->prepare($s = "INSERT INTO $this->usersTable (id, name) VALUES "
            .implode(", ", $recordPlaceholders).$updateClause);
        $stmt->execute($values);
    }
    
    public function listExistingUsers(Traversable|array $ids): Traversable 
    {
        list ($placeholders, $vals) = $this->constructPreparedParams($ids);
        
        // this will prevent concurrent provcesses to use IDs that we want to save until the end of transaction
        if ($this->isTransaction) $intent = "FOR UPDATE";
        else $intent = "";
        
        $stmt = $this->pdo->prepare($s = "
            SELECT id FROM {$this->usersTable} WHERE id IN ({$placeholders})
            $intent
        ");
        $stmt->execute($vals);
        while (($id = $stmt->fetch(PDO::FETCH_COLUMN)) !== false) {
            yield $id;
        }
   }
    
    public function loadUsers(Traversable|array $ids): Traversable 
    {
        list ($placeholders, $vals) = $this->constructPreparedParams($ids);
        $stmt = $this->pdo->prepare("
            SELECT * FROM {$this->usersTable} WHERE id IN ({$placeholders})
        ");
        $stmt->execute($vals);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $user = $this->hydrator->hydrateUser($row);
            yield $user->getId() => $user;
        }
    }
    
    public function deleteUsers(Traversable|array $ids): void
    {
        list ($placeholders, $vals) = $this->constructPreparedParams($ids);
        $stmt = $this->pdo->prepare("
            DELETE FROM {$this->usersTable} WHERE id IN ({$placeholders})
        ");
        $stmt->execute($vals);
    }
    
    public function listUsers(): Traversable 
    {
        $stmt = $this->pdo->prepare("
            SELECT id FROM {$this->usersTable}
        ");
        $stmt->execute();
        while (($id = $stmt->fetch(PDO::FETCH_COLUMN)) !== false) {
            yield $id;
        }
    }
    
    protected function constructPreparedParams(Traversable|array $scalars): array
    {
        $placeholders = [];
        $values = [];
        foreach ($scalars as $v) {
            if ($v instanceof UserInterface) $v = $v->getId();
            $placeholders[] = '?';
            $values[] = $v;
        }
        $strPlaceholders = implode(', ', $placeholders);
        return [$strPlaceholders, $values];
    }
    
    public function getRequireNonExistingIds(): bool {
        return !$this->updateOnInsert;
    }
    
}
