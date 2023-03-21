<?php

declare(strict_types=1);

namespace UserStorageTest;

use PHPUnit\Framework\TestCase;
use UserStorage\Validator\UserValidator;
use UserStorage\User\UserInterface;
use UserStorage\Exception\InvalidArrayMemberException;

final class UserValidatorTest extends TestCase
{
    
    public function testValidatorInvalidArg(): void
    {
        $users = [
            'itemKey' => 'whateverThatsNotUser'
        ];
        
        $validator = new UserValidator;
        
        $this->expectExceptionMessageMatches('/.*users.*itemKey.*UserInterface.*string/');
        $this->expectException(InvalidArrayMemberException::class);
        $validator->getValidationProblems($users);
    }
        
    protected function createSampleUser($id, $name): UserInterface {
        
        return new class ($id, $name) implements UserInterface {
            
           public function __construct(private $id, private $name)
           {
           }
            
           public function getId(): string 
           {
               return $this->id; 
           }
               
           public function getName(): string 
           {
               return $this->name;
           }
            
        };
    }


    public function testValidateUser(): void
    {
        
        $users = [
            'both fields empty' => $this->createSampleUser('', ''),
            'id empty' => $this->createSampleUser('', 'some name'),
            'name empty' => $this->createSampleUser('some id', ''),
            'repeatingId1' => $this->createSampleUser('same-id-1', 'name A'),
            'repeatingId2' => $this->createSampleUser('same-id-1', 'name B'),
            'okRecord' => $this->createSampleUser('uniqId', 'The Name')
        ];
        
        $validator = new UserValidator;
        
        //var_dump(mb_check_encoding(mb_convert_encoding('Небагато українського текста', 'cp1251'), 'utf-8'));
        
        $problems = $validator->getValidationProblems($users);
        
        $this->assertEquals($problems['both fields empty'], [
            'id' => [UserValidator::VALIDATION_FIELD_IS_EMPTY],
            'name' => [UserValidator::VALIDATION_FIELD_IS_EMPTY],
        ]);
        
        $this->assertEquals($problems['id empty'], [
            'id' => [UserValidator::VALIDATION_FIELD_IS_EMPTY],
        ]);
        
        $this->assertEquals($problems['name empty'], [
            'name' => [UserValidator::VALIDATION_FIELD_IS_EMPTY],
        ]);
        
        $this->assertEquals($problems['repeatingId1'], [
            'id' => [UserValidator::VALIDATION_DUPLICATE_ID],
        ]);
        
        $this->assertEquals($problems['repeatingId2'], [
            'id' => [UserValidator::VALIDATION_DUPLICATE_ID],
        ]);
        
        $this->assertArrayNotHasKey('okRecord', $problems);
        
    }
    
}
