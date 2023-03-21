<?php

declare(strict_types=1);

namespace UserStorage\User;

use UserStorage\Exception\{MissingArrayMemberException, InvalidArrayMemberException};

class UserObjectHydrator extends AbstractUserHydrator
{

    public function hydrateUser($row): UserObject
    {
        if (!is_array($row)) {
            throw new InvalidArgumentException(__METHOD__." expects array \$row as an argument");
        }
        
        if (!array_key_exists('id', $row)) {
            throw MissingArrayMemberException::create('row', 'id');
        }
        if (!is_string($row['id'])) {
            throw InvalidArrayMemberException::create('row', 'id', 'string', $row['id']);
        }
        if (!array_key_exists('name', $row)) {
            throw MissingArrayMemberException::create('row', 'name');
        }
        if (!is_string($row['name'])) {
            throw InvalidArrayMemberException::create('row', 'name', 'string', $row['name']);
        }
        return new UserObject($row['id'], $row['name']);
    }
    
}
