<?php

declare(strict_types=1);

namespace UserStorageTest;

use PHPUnit\Framework\TestCase;
use UserStorage\User\UserObjectHydrator;
use UserStorage\User\UserObject;
use UserStorage\Exception\InvalidArrayMemberException;
use UserStorage\Exception\MissingArrayMemberException;
use UserStorage\Exception\UserStorageException;

final class UserHydratorTest extends TestCase
{
    public function testMissingKeys(): void 
    {
        $hyd = new UserObjectHydrator;
        $this->expectException(MissingArrayMemberException::class);
        $this->expectExceptionMessageMatches('/\\bid\\b/');
        $hyd->hydrateUser([]);
    }
    
    public function testInvalidValues(): void 
    {
        $hyd = new UserObjectHydrator;
        $this->expectException(InvalidArrayMemberException::class);
        $this->expectExceptionMessageMatches('/\\bname\\b/');
        $hyd->hydrateUser(['id' => 'something', 'name' => 10]);
    }
    
    public function testHydrateOne(): void
    {
        $hyd = new UserObjectHydrator;
        $row = ['id' => 'something', 'name' => 'John Doe'];
        $user = $hyd->hydrateUser($row);
        $this->assertEquals($user::class, UserObject::class, 'UserObjectHydrator returns UserObject instance');
        $this->assertEquals($user->getId(), 'something', 'Hydrated instance has provided name');
        $this->assertEquals($user->getName(), 'John Doe');
    }
    
    public function testHydrateMany(): void
    {
        $hyd = new UserObjectHydrator;
        $rows = [
            'x' => ['id' => 'something', 'name' => 'John Doe'],
            'y' => ['id' => 'something else', 'name' => 'Jane Doe']
        ];
        $users = $hyd->hydrateRows($rows);
        $this->assertIsIterable($users);
        $resUsers = [...$users];
        $this->assertSame(array_keys($resUsers), array_keys($rows), 
            'Keys of hydrated iterator are the same as keys of source rows');
        $this->assertEquals($resUsers['x']->getId(), $rows['x']['id']);
        $this->assertEquals($resUsers['x']->getName(), $rows['x']['name']);  
        $this->assertEquals($resUsers['y']->getId(), $rows['y']['id']);
        $this->assertEquals($resUsers['y']->getName(), $rows['y']['name']);  
    }
    
    public function testHydrateZero(): void
    {
        $hyd = new UserObjectHydrator;
        $rows = [];
        $users = $hyd->hydrateRows($rows);
        $this->assertIsIterable($users);
        $this->assertEquals([...$users], []);  
    }

}
