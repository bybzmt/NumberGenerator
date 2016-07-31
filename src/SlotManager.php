<?php
namespace bybzmt\NumberGenerator;

/**
 * 按“槽”来管理空间
 *
 * NOTE: 注意一旦存到了某个地方base和dep就不能再改了
 * NOTE: 数据库中的id总是比slot_id多1
 */
class SlotManager
{
	private $_base;

	private $_dep;

	private $_persistent;

	private $_slot_size;

	//当前用的slot对像
	private $_slot_obj;
	private $_slot_id;
	private $_slot_ver;

	/**
	 * 初始化 生成数字的范围是 base ^ dep
	 *
	 * @param int $base 基数,必需要8的倍数 eg: 8,16,32,64,128,256,512,1024,2048
	 * @param int $dep 空间数据的层数
	 * @param Persistent $persistent 持久化对像
	 * @param int $auto_incr_slot 自动增加槽位的数量,0则不自动增加
	 */
	public function __construct($base, $dep, Persistent $persistent)
	{
		$this->_base = $base;
		$this->_dep = $dep;
		$this->_persistent = $persistent;

		$this->_slot_size = pow($this->_base, $this->_dep);
	}

	/**
	 * 随机选取一个什值
	 */
	public function getByRand($mark_used=1)
	{
		$slot_id = $this->_persistent->findIdByRand();
		if ($slot_id === false) {
			return null;
		}

		$slot_id--;

		$this->lock($slot_id);

		$slot = $this->_getSlot($slot_id);
		if (!$slot) {
			$this->unlock($slot_id);
			return null;
		}

		$ok = $slot->getByRand($mark_used);
		if ($mark_used) {
			$this->_saveSlot();
		}

		$this->unlock($slot_id);

		if ($ok !== null) {
			$ok += $slot_id * $this->_slot_size;
		}

		return $ok;
	}

	/**
	 * 得到可用的最小值
	 */
	public function getByMin($mark_used=1)
	{
		$slot_id = $this->_persistent->findIdByMin();
		if ($slot_id === false) {
			return null;
		}

		$slot_id--;

		$this->lock($slot_id);

		$slot = $this->_getSlot($slot_id);
		if (!$slot) {
			$this->unlock($slot_id);
			return null;
		}

		$ok = $slot->getByMin($mark_used);
		if ($mark_used) {
			$this->_saveSlot();
		}

		$this->unlock($slot_id);

		if ($ok !== null) {
			$ok += $slot_id * $this->_slot_size;
		}

		return $ok;
	}

	/**
	 * 得到可用的最大值
	 */
	public function getByMax($mark_used=1)
	{
		$slot_id = $this->_persistent->findIdByMax();
		if ($slot_id === false) {
			return null;
		}

		$slot_id--;

		$this->lock($slot_id);

		$ok = null;
		$slot = $this->_getSlot($slot_id);
		if (!$slot) {
			$this->unlock($slot_id);
			return null;
		}

		$ok = $slot->getByMax($mark_used);
		if ($mark_used) {
			$this->_saveSlot();
		}

		$this->unlock($slot_id);

		if ($ok !== null) {
			$ok += $slot_id * $this->_slot_size;
		}

		return $ok;
	}

	/**
	 * 检查指定的值是否可用
	 */
	public function checkPoint($number)
	{
		$slot_id = intval($number / $this->_slot_size);
		$slot_number = $number % $this->_slot_size;

		$this->lock($slot_id);

		$ok = null;
		$slot = $this->_getSlot($slot_id);
		if ($slot) {
			$ok = $slot->checkPoint($slot_number);
		}

		$this->unlock($slot_id);

		return $ok;
	}

	/**
	 * 设置指定值的状态
	 */
	public function setPoint($number, $is_used)
	{
		$slot_id = intval($number / $this->_slot_size);
		$slot_number = $number % $this->_slot_size;

		$this->lock($slot_id);

		$slot = $this->_getSlot($slot_id);
		$ok = 0;
		if ($slot) {
			$slot->setPoint($slot_number, $is_used);
			$ok = $this->_saveSlot();
		}

		$this->unlock($slot_id);

		return (bool)$ok;
	}

	/**
	 * 设定连续的区域的状态
	 */
	public function setRange($start, $lenght, $is_used)
	{
		$ok = 0;

		while ($lenght > 0) {
			$slot_id = intval($start / $this->_slot_size);
			$slot_number = $start % $this->_slot_size;
			$slot_lenght = $this->_slot_size - $slot_number;
			if ($slot_lenght > $lenght) {
				$slot_lenght = $lenght;
			}

			$this->lock($slot_id);

			$slot = $this->_getSlot($slot_id);
			if ($slot) {
				$slot->setRange($slot_number, $slot_lenght, $is_used);
				$ok |= ~(int)(bool)$this->_saveSlot();
			}

			$this->unlock($slot_id);

			$start += $slot_lenght;
			$lenght -= $slot_lenght;
		}

		return (bool)$ok;
	}

	/**
	 * 增加新Slot
	 */
	public function inrcSlot($num=1)
	{
		$ids = array();

		while ($num-- > 0) {
			$slot = new SpaceManager($this->_base, $this->_dep);
			$ids[] = $this->_persistent->add($slot->getData(), $slot->isFull());
		}

		return $ids;
	}

	/**
	 * 强制取到一个Slot数据，没有时自动初始
	 */
	public function getSlot($slot_id, $auto_init=true)
	{
		$slot = $this->_getSlot($slot_id);
		if ($auto_init) {
			$slot = new SpaceManager($this->_base, $this->_dep);
		}

		return new SlotWrapper($slot_id, $this->_slot_size, $slot);
	}

	/**
	 * 强制设置一个Slot的数据
	 */
	public function setSlot($slot_id, SpaceManager $slot)
	{
		$this->_persistent->set($slot_id+1, $slot->getData(), $slot->isFull());
	}

	private function _getSlot($slot_id)
	{
		list($data, $ver) = $this->_persistent->get($slot_id + 1);
		if (!$data) {
			return null;
		}

		$slot = new SpaceManager($this->_base, $this->_dep, $data);

		$this->_slot_id = $slot_id;
		$this->_slot_obj = $slot;
		$this->_slot_ver = $ver;

		return $slot;
	}

	private function _saveSlot()
	{
		return $this->_persistent->update(
			$this->_slot_id+1,
			$this->_slot_obj->getData(),
			$this->_slot_obj->isFull(),
			$this->_slot_ver
		);
	}

	public function lock($slot_id)
	{
		$this->_persistent->lock($slot_id);
	}

	public function unlock($slot_id)
	{
		$this->_persistent->unlock($slot_id);
	}
}
