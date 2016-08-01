<?php
namespace bybzmt\NumberGenerator;

/**
 * 保存到Mysql数据库中
 *
 * 表格构:
 * CREATE TABLE `number_generator` (
 *     `id` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
 *     `data` LONGBLOB NOT NULL ,
 *     `isfull` TINYINT UNSIGNED NOT NULL ,
 *     `ver` INT UNSIGNED NOT NULL ,
 *     PRIMARY KEY (`id`),
 *     KEY (`isfull`)
 * ) ENGINE = InnoDB COMMENT='数字生成器';
 */
class ToMysql implements Persistent
{
	/**
	 * 锁Key前缀
	 */
	static public $_locker_key_prefix = 'bybzmt_number_generator:';

	private $_pdo;
	private $_table;
	private $_locker;
	private $_lock;

	/**
	 * $locker是一个回调函数func($key)返回一个有lock(),unlock()两个方法的锁实例
	 */
	public function __construct(\PDO $pdo, $table, callable $locker=null)
	{
		$this->_pdo = $pdo;
		$this->_table = $table;
		$this->_locker = $locker;
	}

	public function findIdByRand()
	{
		$sql = "SELECT id FROM `{$this->_table}` FORCE INDEX(PRI,isfull) WHERE isfull=0 ORDER BY RAND() LIMIT 1";
		$stmt = $this->_pdo->query($sql);
		if (!$stmt) {
			throw new Exception("db err:".var_export($this->_pdo->errorInfo(), true));
		}
		return $stmt->fetchColumn();
	}

	public function findIdByMin()
	{
		$sql = "SELECT MIN(id) FROM `{$this->_table}` FORCE INDEX(PRI,isfull) WHERE isfull=0";
		$stmt = $this->_pdo->query($sql);
		if (!$stmt) {
			throw new Exception("db err:".var_export($this->_pdo->errorInfo(), true));
		}
		return $stmt->fetchColumn();
	}

	public function findIdByMax()
	{
		$sql = "SELECT MAX(id) FROM `{$this->_table}` FORCE INDEX(PRI,isfull) WHERE isfull=0";
		$stmt = $this->_pdo->query($sql);
		if (!$stmt) {
			throw new Exception("db err:".var_export($this->_pdo->errorInfo(), true));
		}
		return $stmt->fetchColumn();
	}

	public function get($id)
	{
		$sql = "SELECT data, ver FROM `{$this->_table}` WHERE id = ?";
		$stmt = $this->_pdo->prepare($sql);
		if (!$stmt) {
			throw new Exception("db err:".var_export($this->_pdo->errorInfo(), true));
		}
		$stmt->execute(array($id));
		return $stmt->fetch(\PDO::FETCH_NUM);
	}

	public function update($id, &$data, $isfull, $ver)
	{
		$sql = "UPDATE `{$this->_table}` SET data = ?, isfull=?, ver = ver + 1 WHERE id = ? AND ver = ?";
		$stmt = $this->_pdo->prepare($sql);
		if (!$stmt) {
			throw new Exception("db err:".var_export($this->_pdo->errorInfo(), true));
		}
		$stmt->execute(array($data, $isfull, $id, $ver));
		return $stmt->rowCount();
	}

	public function set($id, &$data, $isfull)
	{
		$sql = "REPLACE INTO `{$this->_table}` (id, data, isfull, ver) VALUES(?, ?, ?, 0)";
		$stmt = $this->_pdo->prepare($sql);
		if (!$stmt) {
			throw new Exception("db err:".var_export($this->_pdo->errorInfo(), true));
		}
		return $stmt->execute(array($id, $data, $isfull));
	}

	public function add(&$data, $isfull)
	{
		$sql = "INSERT INTO `{$this->_table}` (data, isfull, ver) VALUES(?, ?, 0)";
		$stmt = $this->_pdo->prepare($sql);
		if (!$stmt) {
			throw new Exception("db err:".var_export($this->_pdo->errorInfo(), true));
		}
		$ok = $stmt->execute(array($data, $isfull));
		return $this->_pdo->lastInsertId();
	}

	public function lock($id)
	{
		if ($this->_locker) {
			$key = self::$_locker_key_prefix . $id;

			$this->_lock[$id] = call_user_func($this->_locker, $key);
			$this->_lock[$id]->lock();
		}
	}

	public function unlock($id)
	{
		if (isset($this->_lock[$id])) {
			$this->_lock[$id]->unlock();
		}
	}
}
