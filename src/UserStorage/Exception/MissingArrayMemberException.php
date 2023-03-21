<?php

declare(strict_types=1);

namespace UserStorage\Exception;
use \Exception;

class MissingArrayMemberException extends Exception implements UserStorageException
{
    
    public static function create($name, $key): MissingArrayMemberException
    {
        return new MissingArrayMemberException("Missing array item {$name}['{$key}']");
    }
    
}
