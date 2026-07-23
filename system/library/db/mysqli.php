<?php
namespace DB;
final class MySQLi {
	private $connection;

	public function __construct($hostname, $username, $password, $database, $port = '3306') {
		// 非持久连接。曾用 'p:' 持久连接省去每请求 TCP+auth 握手, 但 PHP-FPM 持久连接池会复用被
		// MySQL wait_timeout / 服务重启 / 上一请求中途异常 弄死的失效连接, 导致 "Packets out of order"
		// 警告, 该警告在 framework.php:44 被 echo 出来 -> 响应头已发 -> session cookie (framework.php:136)
		// 无法设置 -> 后端无法进入。改为每请求新建连接 (handshake ~1-2ms 可忽略), 彻底避免复用失效连接。
		$this->connection = new \mysqli($hostname, $username, $password, $database, $port);

		if ($this->connection->connect_errno) {
			throw new \Exception('Error: ' . $this->connection->connect_error . '<br />Error No: ' . $this->connection->connect_errno);
		}

		// Use utf8mb4 when defined, otherwise fall back to utf8
		$charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8';
		$this->connection->set_charset($charset);
		$this->connection->query("SET NAMES '" . $this->connection->real_escape_string($charset) . "'");
		$this->connection->query("SET SQL_MODE = ''");
	}

	public function query($sql) {
		$query = $this->connection->query($sql);

		if (!$this->connection->errno) {
			if ($query instanceof \mysqli_result) {
				$data = array();

				while ($row = $query->fetch_assoc()) {
					$data[] = $row;
				}

				$result = new \stdClass();
				$result->num_rows = $query->num_rows;
				$result->row = isset($data[0]) ? $data[0] : array();
				$result->rows = $data;

				$query->close();

				return $result;
			} else {
				return true;
			}
		} else {
			throw new \Exception('Error: ' . $this->connection->error  . '<br />Error No: ' . $this->connection->errno . '<br />' . $sql);
		}
	}

	public function escape($value) {
		return $this->connection->real_escape_string($value);
	}
	
	public function countAffected() {
		return $this->connection->affected_rows;
	}

	public function getLastId() {
		return $this->connection->insert_id;
	}
	
	public function connected() {
		return $this->connection->ping();
	}
	
	public function __destruct() {
		$this->connection->close();
	}
}
