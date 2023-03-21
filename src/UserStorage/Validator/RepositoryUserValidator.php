<?php

declare(strict_types=1);

namespace UserStorage\Validator;

use UserStorage\{User\UserInterface, Repository\UserRepositoryInterface};
use Traversable;

/**
 * Subclass of UserValidator that validates constraints imposed by a repository
 * (max length of id and name, validity of character encoding, and absence of records
 * with provided IDs in the repository - only if repository dependency is provided, 
 * and repository reports getRequireNonExistingIds() === true)
 */
class RepositoryUserValidator extends UserValidator
{
    public int $userNameMaxLength = 200;
    
    public int $idMaxLength = 50;
    
    public bool $requireNonExistingUsers = true;
    
    public $charset = 'utf-8'; 
    
    const VALIDATION_INVALID_ENCODING = 'Invalid encoding';
    
    const VALIDATION_TOO_LONG = 'Value is too long';
    
    const VALIDATION_ID_ALREADY_EXISTS = 'ID already exists';
    
    function __construct(protected ?UserRepositoryInterface $repository = null)
    {
    }
    
    /**
     * 
     * @param Traversable|array $users
     * @throws InvalidArrayMemberException
     * @return array
     */
    public function getValidationProblems(array $users): array 
    {
        $res = parent::getValidationProblems($users);
        $this->validateNonExistingIdsIfNeeded($users, $res);
        return $res;
    }
    
    protected function validateNonExistingIdsIfNeeded(array $users, array & $problems): void
    {
        if (!$this->repository) return;
        if (!$this->repository->getRequireNonExistingIds()) return;
        $ids = [];
        foreach ($users as $key => $user) {
            $id = $user->getId();
            // we validate DB presence only of valid ids - ones with proper encoding, length
            if (strlen($id) && !isset($problems[$key]['id'])) $ids[$id][] = $key;
        }
        $existing = $this->repository->listExistingUsers(array_keys($ids));
        foreach ($existing as $existingId) {
            foreach($ids[$existingId] as $key) $problems[$key]['id'][] = self::VALIDATION_ID_ALREADY_EXISTS;
        }
    }
    
    protected function validateUser(UserInterface $user): array
    {
        $res = parent::validateUser($user);
        
        if (!mb_check_encoding($user->getId(), $this->charset)) {
            $res['id'][] = self::VALIDATION_INVALID_ENCODING;
        } elseif (mb_strlen($user->getName(), $this->charset) > $this->userNameMaxLength) {
            $res['name'][] = self::VALIDATION_TOO_LONG;
        }
        
        if (!mb_check_encoding($user->getName(), $this->charset)) {
            $res['name'][] = self::VALIDATION_INVALID_ENCODING;
        } elseif (mb_strlen($user->getId(), $this->charset) > $this->idMaxLength) {
            $res['id'][] = self::VALIDATION_TOO_LONG;
        }
        return $res;
    }
    
}
