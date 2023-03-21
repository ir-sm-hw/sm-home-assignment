<?php

namespace Demo;
use \UserStorage\Repository\RepositoryFactoryInterface;
use \Exception;

class DemoSavingService 
{
    
    function __construct(protected string $jsonFilename, protected RepositoryFactoryInterface $repositoryFactory)
    {
    }
    
    function loadJson(): \Traversable
    {
        $hydrator = $this->repositoryFactory->getUserHydrator();
        $contents = file_get_contents($this->jsonFilename);
        if ($contents === false) throw new Exception("'{$this->jsonFilename}' does not exist or cannot be read");
        $users = json_decode(file_get_contents($this->jsonFilename), true);
        if (!is_array($users)) throw new \Exception("File '{$jsonFilename}' contains invalid JSON");
        return $hydrator->hydrateRows($users);
    }
    
    function loadUsers(): \Traversable
    {
        $repository = $this->repositoryFactory->getUserRepository();
        return $repository->loadUsers($repository->listUsers());
    }
    
    function saveUsers(array & $problems = []): bool
    {
        $repository = $this->repositoryFactory->getUserRepository();
        return $repository->saveUsers($this->loadJson(), $problems);
    }
    
    function findUsers(array $ids): \Traversable {
        $repository = $this->repositoryFactory->getUserRepository();
        return $repository->loadUsers($ids);
    }
    
    function deleteUsers(): void
    {
        $repository = $this->repositoryFactory->getUserRepository();
        $repository->deleteUsers($this->loadJson());
    }
    
}