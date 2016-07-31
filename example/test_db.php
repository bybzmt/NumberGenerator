<?php

//创建一个临时用的数据库

$dsn = 'mysql:dbname=test;host=127.0.0.1';
$user = 'root';
$password = '123456';

$table = 'number_generator';


$pdo = new PDO($dsn, $user, $password);

$sql = "CREATE DATABASE test IF NOT EXISTS";
$pdo->exec($sql);

$sql = "USE test";
$pdo->exec($sql);

$sql = "CREATE TABLE `number_generator` IF NOT EXISTS (
	`id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
	`data` LONGBLOB NOT NULL ,
	`isfull` TINYINT UNSIGNED NOT NULL ,
	`ver` INT UNSIGNED NOT NULL ,
	PRIMARY KEY (`id`),
	KEY (`isfull`)
) ENGINE = InnoDB COMMENT='数字生成器';";

$pdo->exec($sql);

$sql = "TRUNCATE TABLE `number_generator`";
$pdo->exec($sql);


