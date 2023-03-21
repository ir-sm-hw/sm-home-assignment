<?php

declare(strict_types=1);

namespace UserStorage\Exception;
use \Exception;

class InvalidDependencyException extends Exception implements UserStorageException
{
}
