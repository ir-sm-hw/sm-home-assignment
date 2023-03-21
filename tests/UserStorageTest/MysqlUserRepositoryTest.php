<?php

declare(strict_types=1);

namespace UserStorageTest;

use PHPUnit\Framework\TestCase;
use \Demo\DemoRepositoryFactory;
use UserStorage\Validator\RepositoryUserValidator;
use UserStorage\Repository\MysqlUserRepository;
use UserStorage\User\{UserInterface, UserObjectHydrator, UserObject};
use PDO;

final class MysqlUserRepositoryTest extends TestCase
{
    private $testTableName = 'test_users';
    
    protected PDO $pdo;
    
    /**
     * Empties the test table and creates the fixture with five records
     */
    public function setUp(): void
    {
        $this->pdo = $this->createPdo();
        $this->pdo->exec("DELETE FROM {$this->testTableName}");
        $this->pdo->exec("INSERT INTO {$this->testTableName} (id, name) VALUES
            ('id-1', 'First'),
            ('id-2', 'Second'),
            ('id-3', 'Third'),
            ('id-4', 'Fourth'),
            ('id-5', 'Fifth')
        ");
    }
    
    protected function createPdo(): PDO
    {
        $factory = new DemoRepositoryFactory();
        // we can use different DB configuration for tests
        if (is_file($f = __DIR__.'/../test.dbconfig.php')) $factory->configFile = $f;
        else $factory->configFile = __DIR__.'/../../dbconfig.php';
        return $factory->createPDO();
    }
    
    protected function getRecords(): array
    {
        $stmt = $this->pdo->prepare("
            SELECT id, name 
            FROM {$this->testTableName} 
            ORDER BY id ASC
        ");
        $stmt->execute();
        $res = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) $res[$row['id']] = $row;
        return $res;
    }
    
    protected function toArray(UserInterface $user)
    {
        return ['id' => $user->getId(), 'name' => $user->getName()];
    }
    
    public function testRepositoryWithoutValidator()
    {
        $repo = new MysqlUserRepository($this->pdo, new UserObjectHydrator);
                
        $repo->usersTable = $this->testTableName;
        $list = $repo->listUsers();
        $this->assertIsIterable($list);
        $arrList = [...$list];
        sort($arrList);
        $this->assertEquals($arrList, ['id-1', 'id-2', 'id-3', 'id-4', 'id-5'], 'List users works');
        $this->assertEquals($repo->getRequireNonExistingIds(), true);
        
        $list = $repo->listExistingUsers(['id-1', 'nx-1', 'nx-2', 'id-3']);
        $this->assertIsIterable($list);
        $arrList = [...$list];
        sort($arrList);
        $this->assertEquals($arrList, ['id-1', 'id-3'],
            'List existing users: return only existing IDs');
        
        
        $someUsers = $repo->loadUsers(['id-1', 'id-2', 'id-3']);
        $this->assertIsIterable($someUsers);
        
        $rows = $this->getRecords();
        $arrList = [...$someUsers];
        
        $this->assertEquals($this->toArray($arrList['id-1']), $rows['id-1']);
        $this->assertEquals($this->toArray($arrList['id-2']), $rows['id-2']);
        $this->assertEquals($this->toArray($arrList['id-3']), $rows['id-3']);
        
        $repo->deleteUsers(['id-1', 'id-2']);
        $this->assertCount(0, [...$repo->listExistingUsers(['id-1', 'id-2'])]);
        $newRows = $this->getRecords();
        $this->assertArrayNotHasKey('id-1', $newRows);
        $this->assertArrayNotHasKey('id-2', $newRows);
        
        $newUsers = [
            new UserObject('id-6', 'Sixth'),
            new UserObject('id-7', 'Seventh'),
        ];
        $this->assertEquals($repo->saveUsers($newUsers), true, 'New users were successfully saved');
        
        $rows = $this->getRecords();
        $this->assertEquals($rows['id-6'], $this->toArray($newUsers[0]));
        $this->assertEquals($rows['id-7'], $this->toArray($newUsers[1]));
        
        $repo->updateOnInsert = true;
        $this->assertEquals($repo->getRequireNonExistingIds(), false);
        
        $overwriteUser = [
            new UserObject('id-3', 'Third-but-edited'),
            new UserObject('id-7', 'Seventh-but-edited'),
            new UserObject('id-8', 'Eighth'),
        ];
        $this->assertEquals($repo->saveUsers($overwriteUser), true, 'Overwrite users were successfully saved');
        
        $rows = $this->getRecords();
        $this->assertEquals($rows['id-3'], $this->toArray($overwriteUser[0]));
        $this->assertEquals($rows['id-7'], $this->toArray($overwriteUser[1]));
        $this->assertEquals($rows['id-8'], $this->toArray($overwriteUser[2]));
        
        
        // without validation AND overwrite-on-update, attempt to replace existing record causes the problem
        $repo->updateOnInsert = false;
        
        // We expect duplicate key exception
        $this->expectException(\PDOException::class);
        $overwriteUser = [
            new UserObject('id-3', 'Third-but-edited'),
        ];
        $repo->saveUsers($overwriteUser);
    }
    
    public function testRepositorySavingWithValidator(): void
    {
        $repo = new MysqlUserRepository($this->pdo, new UserObjectHydrator);
        $validator = new RepositoryUserValidator($repo);
        $repo->setValidator($validator);
        $repo->usersTable = $this->testTableName;
        
        $newUsers = [
            'a' => new UserObject('id-6', 'Sixth'),
            'b' => new UserObject('id-7', 'Seventh'),
        ];
        
        $this->assertEquals($repo->saveUsers($newUsers), true, 'New users were successfully saved');
        
        $rows = $this->getRecords();
        
        $this->assertEquals($rows['id-6'], $this->toArray($newUsers['a']));
        $this->assertEquals($rows['id-7'], $this->toArray($newUsers['b']));
        
        $newUsersAgain = [
            'a' => new UserObject('id-6', 'Sixth'),
            'b' => new UserObject('id-7', 'Seventh'),
            'c' => new UserObject('id-8', 'Eighth'),
        ];

        $problems = [];
        $this->assertEquals($repo->saveUsers($newUsersAgain, $problems), false, 'Duplicate records cannot be saved');
        $this->assertEquals($problems, [
            'a' => ['id' => [RepositoryUserValidator::VALIDATION_ID_ALREADY_EXISTS]],
            'b' => ['id' => [RepositoryUserValidator::VALIDATION_ID_ALREADY_EXISTS]],
        ]);
        
        $rows = $this->getRecords();
        $this->assertArrayNotHasKey('id-8', $rows, 'Record that was valid for insert was not inserted'
            .' since other records had problems');
        
        $repo->updateOnInsert = true;
        
        $overwriteUser = [
            new UserObject('id-3', 'Third-but-edited'),
            new UserObject('id-7', 'Seventh-but-edited'),
            new UserObject('id-8', 'Eighth'),
        ];
        $this->assertEquals($repo->saveUsers($overwriteUser), true, 'Overwrite users were successfully saved');
        
        $rows = $this->getRecords();
        $this->assertEquals($rows['id-3'], $this->toArray($overwriteUser[0]));
        $this->assertEquals($rows['id-7'], $this->toArray($overwriteUser[1]));
        $this->assertEquals($rows['id-8'], $this->toArray($overwriteUser[2]));
    }
        
    public function testInsertManyUsers()
    {
        $hydrator = new UserObjectHydrator;
        $repo = new MysqlUserRepository($this->pdo, $hydrator);
        $repo->usersTable = $this->testTableName;
        $repo->deleteUsers($repo->listUsers());
        $numUsers = 1350;
        
        $repo->saveUsers((function() use ($numUsers) {
            for ($i = 0; $i < $numUsers; $i++) {
                $s = str_pad(''.$i, 4, '0', STR_PAD_LEFT);
                yield new UserObject('id-'.$s, 'User #'.$s);
            }
        }) ($numUsers));
        
        $count = $this->pdo->query("SELECT COUNT(*) FROM {$this->testTableName}")->fetchColumn();
        
        $this->assertEquals($count, $numUsers, "{$numUsers} were created and saved");
        $this->assertEquals(count([...$repo->listUsers()]), $numUsers, "Repository reports same number of users");
    }
    
}
