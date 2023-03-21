<?php

namespace UserStorage\Exception;

/**
 * Using common interface for all exceptions allow to catch Exceptions specific to the project, while not restricting
 * to have common base class for all exceptions (i.e. InvalidArgumentException can still be derived from PHP's
 * InvalidArgumentException)
 */
interface UserStorageException
{
}
