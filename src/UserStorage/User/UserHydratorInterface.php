<?php

declare(strict_types=1);

namespace UserStorage\User;
use Traversable;

interface UserHydratorInterface
{
    public function hydrateUser($row): UserInterface;
    
    public function hydrateRows(Traversable|array $rows): Traversable;
}
