<?php

declare(strict_types=1);

namespace UserStorage\User;
use Traversable;

abstract class AbstractUserHydrator implements UserHydratorInterface
{
    public function hydrateRows(Traversable|array $rows): Traversable
    {
        foreach ($rows as $key => $row) {
            yield $key => $this->hydrateUser($row);
        }
    }
}
