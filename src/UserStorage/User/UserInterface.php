<?php

namespace UserStorage\User;

interface UserInterface 
{
    
    public function getId(): string;
    
    public function getName(): string;
    
}