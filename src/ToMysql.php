<?php
namespace bybzmt\NumberGenerator;

/**
 * 保存到Mysql数据库中
 */
class ToMysql
{
	private $_pdo;
	private $_table;

	public function __construct(\PDO $pdo, $table)
	{
		$this->_pdo = $pdo;
		$this->_table = $table;
	}

	public function findIdByRand()
	{
		$sql = "SELECT id FROM `{$this->_table}` FORCE INDEX(PRI,isfull) WHERE isfull=0 ORDER BY RAND() LIMIT 1";
		$stmt = $this->_pdo->query($sql);
		if (!$stmt) {
			throw new Exception("db err:".var_export($this->_pdo->errorInfo(), true));
		}
		$stmt->execute(array($id));
		return $stmt->fetchColumn();
	}

	public function findIdByMin()
	{
		$sql = "SELECT MIN(id) FROM `{$this->_table}` FORCE INDEX(PRI,isfull) WHERE isfull=0";
		$stmt = $this->_pdo->query($sql);
		if (!$stmt) {
			throw new Exception("db err:".var_export($this->_pdo->errorInfo(), true));
		}
		$stmt->execute(array($id));
		return $stmt->fetchColumn();
	}

	public function findIdByMax()
	{
		$sql = "SELECT MAX(id) FROM `{$this->_table}` FORCE INDEX(PRI,isfull) WHERE isfull=0";
		$stmt = $this->_pdo->query($sql);
		if (!$stmt) {
			throw new Exception("db err:".var_export($this->_pdo->errorInfo(), true));
		}
		$stmt->execute(array($id));
		return $stmt->fetchColumn();
	}

	public function &get($id)
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
		$stmt->execute(array($data, $isfull));
		return $this->_pdo->lastInsertId();
	}
}
