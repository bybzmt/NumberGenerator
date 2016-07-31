<?php
namespace bybzmt\NumberGenerator;

/**
 * 按“槽”来包装壳，用来修正偏移
 */
class SlotWrapper
{
	private $_slot_id;
	private $_slot_size;
	private $_slot_obj;

	public function __construct($slot_id, $slot_size, SpaceManager $slot)
	{
		$this->_slot_id = $slot_id;
		$this->_slot_size = $slot_size;
		$this->_slot_obj = $slot;
	}

	public function getByRand($mark_used=1)
	{
		$ok = $this->_slot_obj->getByRand($mark_used);
		if ($ok !== null) {
			$ok += $this->_slot_id * $this->_slot_size;
		}
		return $ok;
	}

	public function getByMin($mark_used=1)
	{
		$ok = $this->_slot_obj->getByMin($mark_used);
		if ($ok !== null) {
			$ok += $this->_slot_id * $this->_slot_size;
		}
		return $ok;
	}

	public function getByMax($mark_used=1)
	{
		$ok = $this->_slot_obj->getByMax($mark_used);
		if ($ok !== null) {
			$ok += $this->_slot_id * $this->_slot_size;
		}
		return $ok;
	}

	public function checkPoint($number)
	{
		$slot_number = $number % $this->_slot_size;
		return $this->_slot_obj->checkPoint($slot_number);
	}

	public function setPoint($number, $is_used)
	{
		$slot_number = $number % $this->_slot_size;
		return $this->_slot_obj->setPoint($slot_number, $is_used);
	}

	public function setRange($start, $lenght, $is_used)
	{
		$slot_number = $start % $this->_slot_size;
		return $this->_slot_obj->setRange($slot_number, $lenght, $is_used);
	}

	public function isFull()
	{
		return $this->_slot_obj->isFull();
	}

	public function &getData()
	{
		return $this->_slot_obj->getData();
	}
}
