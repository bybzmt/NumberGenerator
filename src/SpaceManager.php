<?php
namespace bybzmt\NumberGenerator;

/**
 * 空间管理器
 *
 * 算法思想来自内存MMU和硬盘空间管理的算法
 * 基础思想如下:
 * 1. 需要管理的是以(基数N ^ 指数Y)大小的空间
 * 2. 将这个空间以N为分片，Y为维度
 * 3. 上一维度位置i如果标记成己用，则表示下一维度分片i*N整个为己用
 *
 * 特别说明:为了方便第1个字节用于标记整个空间是否全己用
 */
class SpaceManager
{
	/*
	 * 空间基数
	 */
	private $_base;

	/*
	 * 空间维度数
	 */
	private $_dep;

	//方便计算,一个基数占用的字节数
	private $_str_base;
	//方便计算,字符0xFF
	private $_str_full;
	//方便计算,维持维度需要额外占用的空间
	private $_dimension_size;

	/**
	 * 初始化 生成数字的范围是 base ^ dep
	 *
	 * @param int $base 基数,必需要8的倍数 eg: 8,16,32,64,128,256,512,1024,2048
	 * @param int $dep 空间数据的层数
	 * @param string $data bit级的空间使用数据
	 */
	public function __construct($base, $dep, &$data=null)
	{
		$this->_base = $base;
		$this->_dep = $dep;
		$this->_data = $data;
		$this->_str_base = $this->_base / 8;
		$this->_str_full = pack('C', 255);

		//维持维度需要额外占用的空间
		$this->_dimension_size[0] = 0;
		$dimension_size = 8;
		for ($i=1; $i < $this->_dep+1; $i++) {
			$this->_dimension_size[$i] = $dimension_size;
			$dimension_size += pow($this->_base, $i);
		}
		$this->_dimension_size[$i] = $dimension_size;

		if ($this->_data === null) {
			$this->_initData();
		}
	}

	/**
	 * 得到原始数据
	 */
	public function &getData()
	{
		return $this->_data;
	}

	/**
	 * 查看是否全满
	 */
	public function isFull()
	{
		if ($this->_data[0] == $this->_str_full) {
			return 1;
		}
		return 0;
	}

	/**
	 * 随机选取一个什值
	 *
	 * @param bool $close 是否关闭取到的那个值
	 */
	public function getByRand($mark_used=1)
	{
		if ($this->_data[0] == $this->_str_full) {
			return null;
		}

		$poit = $this->_findRandNumber(1, 0);

		//标记当前位置为己用
		if ($poit!== null && $mark_used) {
			$this->_setPoint($this->_dep, $poit, 1);
		}

		return $poit;
	}

	/**
	 * 得到可用的最小值
	 *
	 * @param bool $close 是否关闭取到的那个值
	 */
	public function getByMin($mark_used=1)
	{
		if ($this->_data[0] == $this->_str_full) {
			return null;
		}

		$poit = $this->_findMinNumber(1, 0);

		//标记当前位置为己用
		if ($poit!== null && $mark_used) {
			$this->_setPoint($this->_dep, $poit, 1);
		}

		return $poit;
	}

	/**
	 * 得到可用的最大值
	 *
	 * @param bool $close 是否关闭取到的那个值
	 */
	public function getByMax($mark_used=1)
	{
		if ($this->_data[0] == $this->_str_full) {
			return null;
		}

		$poit = $this->_findMaxNumber(1, 0);

		//标记当前位置为己用
		if ($poit!== null && $mark_used) {
			$this->_setPoint($this->_dep, $poit, 1);
		}

		return $poit;
	}

	/**
	 * 检查指定的值是否可用
	 */
	public function checkPoint($number)
	{
		if ($this->_data[0] == $this->_str_full) {
			return 1;
		}

		//最大长度
		$max_lenght = $this->_dimension_size[$this->_dep+1] - $this->_dimension_size[$this->_dep];

		//修正开始偏移
		if ($number < 0 || $number >= $max_lenght) {
			$number = 0;
		}

		return $this->_checkPoint($this->_dep, $number);
	}

	/**
	 * 设置指定的值为可用
	 */
	public function setPoint($number, $is_used)
	{
		//最大长度
		$max_lenght = $this->_dimension_size[$this->_dep+1] - $this->_dimension_size[$this->_dep];

		//修正开始偏移
		if ($number < 0 || $number >= $max_lenght) {
			$number = 0;
		}

		$this->_setPoint($this->_dep, $number, $is_used);
	}

	/**
	 * 设定连续的区域的状态
	 */
	public function setRange($start, $lenght, $is_used)
	{
		//最大长度
		$max_lenght = $this->_dimension_size[$this->_dep+1] - $this->_dimension_size[$this->_dep];

		//修正开始偏移
		if ($start < 0 || $start >= $max_lenght) {
			$start = 0;
		}

		//修正lenght让它不过界
		if ($start + $lenght >= $max_lenght) {
			$lenght -= $start + $lenght - $max_lenght;
		}

		$this->_setRange($this->_dep, $start, $lenght, $is_used);
	}

	/*
	 * 随机找到一个空闲位置
	 */
	private function _findRandNumber($dimension, $number)
	{
		//层次维持用数据占用的空间
		$dimension_size = $this->_dimension_size[$dimension];

		//总数据中的偏移(字节)
		$str_offset = intval(($dimension_size + $number)/8);

		//找到所有空的位置
		$positions = array();
		for ($i=0; $i < $this->_str_base; $i++) {
			//在整个字符串中的偏移,位定到字符
			$char = $this->_data[$str_offset + $i];
			if ($char !== $this->_str_full) {
				$char = unpack('C', $char)[1];
				//一个字节内部查找
				for ($y=0; $y<8; $y++) {
					$k = 1 << $y;
					if (!($k & $char)) {
						$positions[] = ($i * 8) + $y;
					}
				}
			}
		}

		//整个片都是满的
		if (empty($positions)) {
			return null;
		}

		//从出空的位置中随机出１个
		$position = $positions[array_rand($positions)];

		//在当前维度中的位置
		$now_offset = $number + $position;

		//不是最底层,递归
		if ($dimension < $this->_dep) {
			//当前维度中的位置 * 当前基数 = 下一维度的偏移值
			$next_dimension_size = $now_offset * $this->_base;

			//递归到下一维度
			return $this->_findRandNumber($dimension+1, $next_dimension_size);
		} else {
			return $now_offset;
		}
	}

	/*
	 * 找到最小的可用位置
	 */
	private function _findMinNumber($dimension, $number)
	{
		//层次维持用数据占用的空间
		$dimension_size = $this->_dimension_size[$dimension];

		//总数据中的偏移(字节)
		$str_offset = intval(($dimension_size + $number)/8);

		$position = null;
		for ($i=0; $i < $this->_str_base; $i++) {
			//在整个字符串中的偏移,位定到字符
			$char = $this->_data[$str_offset + $i];
			if ($char !== $this->_str_full) {
				$char = unpack('C', $char)[1];
				//一个字节内部查找
				for ($y=0; $y<8; $y++) {
					$k = 1 << $y;
					if (!($k & $char)) {
						$position = ($i * 8) + $y;
						break 2;
					}
				}
			}
		}

		//没有空的
		if ($position === null) {
			return null;
		}

		//在当前维度中的位置
		$now_offset = $number + $position;

		if ($dimension < $this->_dep) {
			$next_number = $now_offset * $this->_base;

			return $this->_findMinNumber($dimension+1, $next_number);
		} else {
			return $now_offset;
		}
	}

	/*
	 * 找到最大的可用位置
	 */
	private function _findMaxNumber($dimension, $number)
	{
		//层次维持用数据占用的空间
		$dimension_size = $this->_dimension_size[$dimension];

		//总数据中的偏移(字节)
		$str_offset = intval(($dimension_size + $number)/8);

		$position = null;

		$i=$this->_str_base;
		while (--$i >= 0) {
			//在整个字符串中的偏移,位定到字符
			$char = $this->_data[$str_offset + $i];
			if ($char !== $this->_str_full) {
				$char = unpack('C', $char)[1];
				//一个字节内部查找
				for ($y=7; $y>=0; $y--) {
					$k = 1 << $y;
					if (!($k & $char)) {
						$position = ($i * 8) + $y;
						break 2;
					}
				}
			}
		}

		//没有空的
		if ($position === null) {
			return null;
		}

		//在当前维度中的位置
		$now_offset = $number + $position;

		if ($dimension < $this->_dep) {
			$next_number = $now_offset * $this->_base;

			return $this->_findMaxNumber($dimension+1, $next_number);
		} else {
			return $now_offset;
		}
	}

	/*
	 * 检查某个点是否可用
	 */
	private function _checkPoint($dimension, $number)
	{
		//层次维持用数据占用的空间
		$dimension_size = $this->_dimension_size[$dimension];

		$str_offset = intval(($number + $dimension_size) / 8);
		$char_offset = ($number + $dimension_size) % 8;
		$char = unpack('C', $this->_data[$str_offset])[1];

		$check = 1 << $char_offset;
		return $check & $char ? 1 : 0;
	}

	/*
	 * 标记一个位置为己占用
	 */
	private function _setPoint($dimension, $number, $is_used)
	{
		//第0层特殊处理
		if ($dimension < 1) {
			$this->_data[0] = $is_used ? $this->_str_full : "\0";
			return;
		}

		//层次维持用数据占用的空间
		$dimension_size = $this->_dimension_size[$dimension];

		$str_offset = intval(($number + $dimension_size) / 8);
		$char_offset = ($number + $dimension_size) % 8;
		$char = unpack('C', $this->_data[$str_offset])[1];
		if ($is_used) {
			$new_char = $char | (1 << $char_offset);
		} else {
			$new_char = $char & (~(1 << $char_offset));
		}

		if ($char == $new_char) {
			return;
		}

		$this->_data[$str_offset] = pack('C', $new_char);

		if ($is_used) {
			if ($new_char == 255) {
				$this->_secition_fixup($dimension, $number, $is_used);
			}
		} else {
			$this->_secition_fixup($dimension, $number, $is_used);
		}
	}

	/*
	 * 设置连续区域状态
	 */
	private function _setRange($dimension, $start, $lenght, $is_used)
	{
		$start_pos = $start % $this->_base;
		if ($start_pos == 0) {
			$start_fix = $start;
			$lenght_fix = $lenght;
		} else {
			$start_fix = $start + ($this->_base - $start_pos);
			$lenght_fix = $lenght - ($this->_base - $start_pos);
		}

		$next_lenght = intval($lenght_fix / $this->_base);
		if ($next_lenght > 0) {
			$next_start = intval($start_fix / $this->_base);
			$this->_setRange($dimension-1, $next_start, $next_lenght, $is_used);
		}

		$this->_setRangeByChar($dimension, $start, $lenght, $is_used);

		//检查开头和未尾边界是否有分片全满
		$stop = $start + $lenght;
		$this->_secition_fixup($dimension, $start, $is_used);
		$this->_secition_fixup($dimension, $stop-1, $is_used);
	}

	/*
	 * 数据结构(分片性质)维护
	 */
	private function _secition_fixup($dimension, $number, $is_used)
	{
		$dimension_size = $this->_dimension_size[$dimension];

		//当前维度片开头的偏移
		$offset = $number - ($number % $this->_base);

		//在整个字符中的偏移
		$str_offset = intval(($offset + $dimension_size) / 8);

		if ($is_used) {
			for ($i=0; $i<$this->_str_base; $i++) {
				if ($this->_data[$str_offset + $i] != $this->_str_full) {
					//只要有一个不满则不满
					return;
				}
			}
		}

		$prev_number = intval($offset / $this->_base);
		$this->_setPoint($dimension-1, $prev_number, $is_used);
	}

	/*
	 * 设置指定维度的指定区域状态
	 */
	private function _setRangeByChar($dimension, $start, $lenght, $is_used)
	{
		$dimension_size = $this->_dimension_size[$dimension];

		$str_pos = (int)(($dimension_size + $start) / 8);
		$pos = $start % 8;

		//开头的非整字节偏移
		if ($pos > 0) {
			$char = unpack('C', $this->_data[$str_pos])[1];

			for ($i=$pos; $i<8 && $lenght > 0; $i++, $lenght--) {
				if ($is_used) {
					$char |= 1 << $i;
				} else {
					$char &= ~(1 << $i);
				}
			}

			$this->_data[$str_pos] = pack('C', $char);
			$str_pos++;
		}

		$set_char = $is_used ? $this->_str_full : "\0";

		//中间的整字节部分
		$str_len = (int)($lenght / 8);
		for ($i=0; $i<$str_len; $i++) {
			$this->_data[$str_pos] = $set_char;
			$str_pos++;
			$lenght-=8;
		}

		if ($lenght > 0) {
			return;
		}

		//未尾的非整字节偏移
		$char = unpack('C', $this->_data[$str_pos])[1];
		for ($i=0; $i<8 && $lenght > 0; $i++, $lenght--) {
			if ($is_used) {
				$char |= 1 << $i;
			} else {
				$char &= ~(1 << $i);
			}
		}
		$this->_data[$str_pos] = pack('C', $char);
	}

	/*
	 * 初始化多维数据
	 */
	private function _initData()
	{
		$num = $this->_dimension_size[$this->_dep+1];

		//按字符处理
		$num = $num / 8;
		$this->_data = str_repeat("\0", $num);
	}
}
