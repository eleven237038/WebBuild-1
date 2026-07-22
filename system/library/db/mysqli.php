<?php
namespace DB;
final class MySQLi {
	private $connection;

	public function __construct($hostname, $username, $password, $database, $port = '3306') {
		// Persistent connection (p: prefix) - avoids a fresh TCP+auth handshake per request.
		$this->connection = new \mysqli('p:' . $hostname, $username, $password, $database, $port);

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
