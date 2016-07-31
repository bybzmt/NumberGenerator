<?php

use bybzmt\NumberGenerator\SpaceManager;

require_once __DIR__ . "/../src/load.php";

class SpaceManagerTest extends PHPUnit_Framework_TestCase
{
	private $_base = 16;
	private $_dep = 5;

	public function testInit()
	{
		$max = pow($this->_base, $this->_dep);

		$obj = new SpaceManager($this->_base, $this->_dep);

		$this->assertTrue(true);

		return $obj;
	}

    /**
     * @depends testInit
     */
	public function testRange($obj)
	{
		$max = pow($this->_base, $this->_dep);

		$start = mt_rand(0, $max / 2);
		$lenght = $max / 8;

		$obj->setRange($start, $lenght, 1);

		$start += $lenght/2;
		$obj->setRange($start, $lenght, 0);

		$start += $lenght/2;
		$obj->setRange($start, $lenght, 1);

		$close_num = $lenght + ($lenght / 2);

		$this->assertTrue(true);

		return $close_num;
	}

    /**
     * @depends testInit
     * @depends testRange
     */
	public function testUnique($obj, $close_num)
	{
		$max = pow($this->_base, $this->_dep);

		$ids = array();
		for ($i=0; $i<$max; $i++) {
			$id = $obj->getByRand();
			if ($id === null) {
				break;
			}

			if (isset($ids[$id])) {
				$this->assertFalse(isset($ids[$id]), "duplicate id: $id");
			}

			$ids[$id] = 0;
		}

		$num = count($ids);

		$a1 = $num + $close_num;

		$this->assertTrue($a1 == $max, "id number not eq $a1 != $max");
	}

    /**
     * @depends testInit
     */
	public function testPoint($obj)
	{
		$max = pow($this->_base, $this->_dep);

		$number = mt_rand(0, $max);

		//当前应该己占用
		$po = $obj->checkPoint($number);
		$this->assertTrue($po == 1);

		//设为未用
		$obj->setPoint($number, 0);
		$po = $obj->checkPoint($number);
		$this->assertTrue($po == 0);

		//最小应该是上面的number
		$po = $obj->getByMin(false);
		$this->assertTrue($po == $number);

		//最大应该是上面的number
		$po = $obj->getByMax(false);
		$this->assertTrue($po == $number);
	}
}


