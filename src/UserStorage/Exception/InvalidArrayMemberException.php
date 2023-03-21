<?php

declare(strict_types=1);

namespace UserStorage\Exception;
use \Exception;

class InvalidArrayMemberException extends Exception implements UserStorageException
{
    public static function create($name, $key, $expectedType, $value): InvalidArrayMemberException
    {
        $descr = get_debug_type($value);
        return new InvalidArrayMemberException("Invalid type of item {$name}['{$key}']:"
            ." expected {$expectedType}, provided: {$descr}");
    }
    
}
