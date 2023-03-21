<?php

namespace UserStorage\Validator;
use Traversable;

interface UserValidatorInterface
{
    
    /**
     * Validates users. Returns array with zero or more validation problems.
     * 
     * Problems are described in the format $key => [$field => ['problem', 'problem...']]
     * where $field is 'id' or 'name', and $key is key of the record in $users argument array.
     * 
     * @param array $users
     * @return array
     */
    public function getValidationProblems(array $users): array;
    
}
