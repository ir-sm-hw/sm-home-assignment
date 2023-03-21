<?php

namespace UserStorage\Repository;

use UserStorage\User\UserInterface;
use UserStorage\Validator\UserValidatorInterface;
use Traversable;

interface UserRepositoryInterface 
{
    /**
     * Optionally validates, and saves array of users.
     * If validator dependency is not provided by setValidator(), won't do any validation.
     * If validator dependency is provided, records will be validated before they are saved, and
     * saved only if all records are deemed valid.
     * 
     * @param Traversable|UserInterface[] $users Users to save
     * @array $problems Return-value parameter that contains description of validation problems. 
     *      If non-empty, return value will FALSE
     * 
     * @return bool TRUE if operation completed successfully, FALSE is one of records didn't pass validation
     * @see UserValidatorInterface::getValidationProblems()
     */
    public function saveUsers(Traversable|array $users, array & $problems = []): bool;
    
    /**
     * Filters IDs and returns only ones that exist in the repository.
     * 
     * @param Traversable|array $ids List of IDs to check for presence in the repository
     * @return \Traversable list of ids that exist in the repository
     */
    public function listExistingUsers(Traversable|array $ids): \Traversable;
    
    /**
     * Loads and returns Traversable of users with given IDs
     * 
     * @param Traversable|array $ids IDs of users to load
     * @return Traversable Loaded users
     */
    public function loadUsers(Traversable|array $ids): Traversable;
    
    /**
     * Returns Traversable with IDs of all users in the repository
     * 
     * @return Traversable
     */
    public function listUsers(): Traversable;
    
    /**
     * Deletes users with given IDs
     */
    public function deleteUsers(Traversable|array $ids): void;
    
    /**
     * Returns TRUE, if this repository requires saved records to have non-existing IDs
     */
    public function getRequireNonExistingIds(): bool;
    
    /**
     * Injects optional Validator dependency that is used to validate users 
     * before they are saved into the repository.
     */
    public function setValidator(UserValidatorInterface $validator): void;
    
}
