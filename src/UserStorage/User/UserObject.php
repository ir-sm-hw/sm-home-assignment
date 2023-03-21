<?php

declare(strict_types=1);

namespace UserStorage\User;

class UserObject implements UserInterface, \JsonSerializable
{
    
    public function __construct(protected string $id, protected string $name) 
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
    
    public function setId(string $id): void
    {
        $this->id = $id;
    }
    
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    
    public function __toString(): string
    {
        return json_encode($this);
    }

    public function jsonSerialize(): mixed 
    {
        return ["id" => $this->id, "name" => $this->name];
    }

}
