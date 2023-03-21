<?php

declare(strict_types=1);

namespace UserStorage\Exception;
use \InvalidArgumentException as BaseInvalidArgumentException;

class InvalidArgumentException extends BaseInvalidArgumentException implements UserStorageException 
{
}
