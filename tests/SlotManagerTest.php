<?php

use bybzmt\NumberGenerator\SlotManager;
use bybzmt\NumberGenerator\ToMysql;

require_once __DIR__ . "/../src/load.php";

class SlotManagerTest extends PHPUnit_Framework_TestCase
{
	private $_base = 16;
	private $_dep = 3;
	private $_obj;
	private $_pdo;

	public function setUp()
	{
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
		$this->_pdo = $pdo;

		$persistent = new ToMysql($pdo, $table, null);

		//实例化对像
		$this->_obj = new SlotManager($this->_base, $this->_dep, $persistent);
	}

	public function testInit()
	{
		$sql = "TRUNCATE TABLE `number_generator`";
		$this->_pdo->exec($sql);

		//增加10个slot
		$ids = $this->_obj->inrcSlot(10);

		$this->assertTrue(count(array_filter(array_map('intval', $ids))) == 10);

		return $this->_obj;
	}

    /**
     * @depends testInit
     */
	public function testRange($obj)
	{
		$max = pow($this->_base, $this->_dep);

		$start = mt_rand(0, $max*5);
		$lenght = $max / 8;

		$obj->setRange($start, $lenght, 1);

		$start += $lenght/2;
		$obj->setRange($start, $lenght, 0);

		$start += $lenght/2;
		$obj->setRange($start, $lenght, 1);

		$this->assertTrue(true);
	}

    /**
     * @depends testInit
     */
	public function testUnique($obj)
	{
		$max = pow($this->_base, $this->_dep);

		$ids = array();
		while (true) {
			$id = $obj->getByRand();
			if ($id === null) {
				break;
			}

			if (isset($ids[$id])) {
				$this->assertFalse(isset($ids[$id]), "duplicate id: $id");
			}

			$ids[$id] = 0;
		}

		$this->assertTrue(true);
	}

    /**
     * @depends testInit
     */
	public function testPoint($obj)
	{
		$max = pow($this->_base, $this->_dep);

		$number = mt_rand(0, $max * 10);

		//设为未用
		$obj->setPoint($number, 0);
		$po = $obj->checkPoint($number);
		$this->assertTrue($po == 0);

		//最小应该是上面的number
		$po = $obj->getByMin(false);
		//最大应该是上面的number
		$po = $obj->getByMax(false);

		$this->assertTrue(true);
	}
}


