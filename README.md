# Php home assignment

## Installation

Create database, i.e. "sm_users", and run sql/users.sql in it to create the tables
(users is the 'live' table, test_users is test table used in the tests).

    mysql -e "create database sm_users"
    mysql sm_users < sql/users.sql
    composer install

Copy the example configuration file and change the defaults with your database access data:

    cp dbconfig.example.php dbconfig.php
    nano dbconfig.php

## Demo usage

    Usage: php bin/demo.php [--load|--save|--replace|--delete] [jsonFileName]

    jsonFilename: name of file with JSON data (array of objects with keys "id" and "name", and string values)

    --load      Load data from repository and dump it in JSON format
    --save      Validate and save records (don't save any if some IDs already exist)
    --replace   Validate and save records (replace if some IDs already exist)
    --delete    Delete records with matching IDs

    If filename is not provided, by default will use 'data/demo.json'.

## Tests

    composer run test

(to use different test database, copy dbconfig.php into tests/test.dbconfig.php and change DB name)