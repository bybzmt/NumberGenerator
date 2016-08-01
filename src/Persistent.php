<?php
namespace bybzmt\NumberGenerator;

/**
 * 持久化接口
 */
interface Persistent
{
	/**
	 * 随机得到一个可用的id
	 */
	public function findIdByRand();

	/**
	 * 得到最小可用的id
	 */
	public function findIdByMin();

	/**
	 * 得到最大可用的id
	 */
	public function findIdByMax();

	/**
	 * 得到一行记录
	 * @return array(data, ver)
	 */
	public function get($id);

	/**
	 * 更新一行记行录
	 * @return 影响记录行数
	 */
	public function update($id, &$data, $isfull, $ver);

	/**
	 * 强制设置一行记录
	 * @return 影响记录行数
	 */
	public function set($id, &$data, $isfull);

	/**
	 * 通过自增添加一行新记录
	 * @return 自增id
	 */
	public function add(&$data, $isfull);

	/**
	 * 加锁
	 */
	public function lock($id);

	/**
	 * 解锁
	 */
	public function unlock($id);
}
