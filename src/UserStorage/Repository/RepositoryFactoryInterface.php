<?php

namespace UserStorage\Repository;

use UserStorage\User\UserHydratorInterface;

interface RepositoryFactoryInterface
{
    
    public function getUserRepository(): UserRepositoryInterface;
    
    public function getUserHydrator(): UserHydratorInterface;
    
}
