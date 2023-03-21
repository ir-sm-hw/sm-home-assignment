<?php

declare(strict_types=1);

namespace UserStorageTest;

use PHPUnit\Framework\TestCase;
use UserStorage\Validator\RepositoryUserValidator;
use UserStorage\Repository\UserRepositoryInterface;
use UserStorage\User\UserInterface;

final class RepositoryUserValidatorTest extends TestCase
{
        
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
    
    protected function createSampleRepostory(): UserRepositoryInterface {
        
        $res = $this->createMock(UserRepositoryInterface::class);
        
        return $res;
        
    }
    
    public function testValidateUsers(): void
    {
        
        $invalidEncodingText = mb_convert_encoding('Небагато українського текста', 'cp1251');
        
        $users = [
            'existingId' => $this->createSampleUser('idExists', 'the name'),
            'otherExistingId' => $this->createSampleUser('id2Exists', 'the name'),
            'tooLongId' => $this->createSampleUser(str_repeat('😀', 51), 'some name'),
            'tooLongName' => $this->createSampleUser('some id', str_repeat('😀', 205)),
            'invalidIdEncoding' => $this->createSampleUser($invalidEncodingText, 'name A'),
            'invalidNameEncoding' => $this->createSampleUser('otherKey', $invalidEncodingText),
            'okRecord' => $this->createSampleUser(str_repeat('😀', 50), str_repeat('😀', 200))
        ];
        
        $mockRepository = $this->createSampleRepostory();
        $mockRepository
            ->expects($this->any())
            ->method('getRequireNonExistingIds')
            ->willReturn(true);
        $mockRepository
            ->expects($this->once())
            ->method('listExistingUsers')
            ->with($this->identicalTo(['idExists', 'id2Exists', 'some id', 'otherKey', str_repeat('😀', 50)]))
            ->willReturn(
                (function() { yield from ['idExists', 'id2Exists']; }) ()
            );
        
        $validator = new RepositoryUserValidator($mockRepository);
        
        $problems = $validator->getValidationProblems($users);
               
        $this->assertEquals($problems['existingId'], [
            'id' => [RepositoryUserValidator::VALIDATION_ID_ALREADY_EXISTS],
        ]);
        
        $this->assertEquals($problems['otherExistingId'], [
            'id' => [RepositoryUserValidator::VALIDATION_ID_ALREADY_EXISTS],
        ]);
        
        $this->assertEquals($problems['tooLongId'], [
            'id' => [RepositoryUserValidator::VALIDATION_TOO_LONG],
        ]);
        
        $this->assertEquals($problems['tooLongName'], [
            'name' => [RepositoryUserValidator::VALIDATION_TOO_LONG],
        ]);
        
        $this->assertEquals($problems['invalidIdEncoding'], [
            'id' => [RepositoryUserValidator::VALIDATION_INVALID_ENCODING],
        ]);
        
        $this->assertEquals($problems['invalidNameEncoding'], [
            'name' => [RepositoryUserValidator::VALIDATION_INVALID_ENCODING],
        ]);
        
        $this->assertArrayNotHasKey('okRecord', $problems);
        
    }
    
}
