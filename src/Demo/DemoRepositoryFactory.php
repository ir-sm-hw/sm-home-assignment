<?php

namespace Demo;

use PDO;
use Exception;
use UserStorage\{
    Validator\RepositoryUserValidator, 
    User\UserHydratorInterface, 
    User\UserObjectHydrator, 
};
use UserStorage\Repository\{
    RepositoryFactoryInterface, 
    MysqlUserRepository
};


class DemoRepositoryFactory implements RepositoryFactoryInterface
{
    
    public $configFile = 'dbconfig.php';
    
    public $replace = false;
    
    public function createPDO(): PDO
    {
        require($this->configFile);
        
        if (!isset($config)) throw new Exception("\$config variable not defined in {$this->configFile}");
        
        $missingKeys = array_diff(['dsn', 'username', 'password', 'options'], array_keys($config));
        
        if ($missingKeys) throw new Exception("Missing key(s) in \$config array: "
            .implode(", ", $missingKeys));
        
        $pdo = new PDO($config['dsn'], $config['username'], $config['password'], $config['options']);
        
        return $pdo;
    }
    
    public function getUserRepository(): MysqlUserRepository
    {
        $pdo = $this->createPDO();
        
        $repository = new MysqlUserRepository($pdo, new UserObjectHydrator);
        
        $repository->updateOnInsert = $this->replace;
        
        $repository->setValidator(new RepositoryUserValidator($repository));
        
        return $repository;
    }

    public function getUserHydrator(): UserHydratorInterface {
        return new UserObjectHydrator;
    }

}
