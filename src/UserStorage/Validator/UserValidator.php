<?php

declare(strict_types=1);

namespace UserStorage\Validator;

use UserStorage\{
    User\UserInterface,
    Exception\InvalidArrayMemberException,
    Repository\UserRepositoryInterface
};

class UserValidator implements UserValidatorInterface
{
    
    const VALIDATION_FIELD_IS_EMPTY = 'Field is empty';
    
    const VALIDATION_DUPLICATE_ID = 'Duplicate ID';
    
    /**
     * @param array $users
     * @throws InvalidArrayMemberException
     * @return array
     */
    public function getValidationProblems(array $users): array
    {
        $res = [];
        
        // [$id => [$key1, $key2...]] where $key1 is key in \$users
        $keysOfIds = [];
        foreach ($users as $key => $user) {
            if (!$user instanceof UserInterface) {
                throw InvalidArrayMemberException::create('$users', $key, UserInterface::class, $user);
            }
            $id = $user->getId();
            $problems = $this->validateUser($user);
            if (strlen($id)) {
                if (isset($keysOfIds[$id])) {
                    $problems['id'][] = self::VALIDATION_DUPLICATE_ID;
                    if (count($keysOfIds[$id]) == 1) $res[$keysOfIds[$id][0]]['id'][] = self::VALIDATION_DUPLICATE_ID;
                }
                $keysOfIds[$id][] = $key;
            }
            if ($problems) $res[$key] = $problems;
        }
        return $res;
    }
    
    protected function validateUser(UserInterface $user): array
    {
        $res = [];
        if (!strlen($user->getId())) $res['id'][] = self::VALIDATION_FIELD_IS_EMPTY;
        if (!strlen($user->getName())) $res['name'][] = self::VALIDATION_FIELD_IS_EMPTY;
        return $res;
    }
    
}
