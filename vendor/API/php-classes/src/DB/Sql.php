<?php

namespace Hcode\DB;

class Sql
{
	const HOSTNAME = "127.0.0.1";
	const USERNAME = "dev";
	const PASSWORD = "MM@t@13192921";
	const DBNAME   = "prato_cheio";

	private $conn;

	public static function config(?string $key = null)
	{
		$config = array(
			'host' => self::env('DB_HOST', self::HOSTNAME),
			'port' => (int) self::env('DB_PORT', 3306),
			'username' => self::env('DB_USER', self::USERNAME),
			'password' => self::env('DB_PASSWORD', self::PASSWORD),
			'dbname' => self::env('DB_NAME', self::DBNAME),
			'charset' => self::env('DB_CHARSET', 'utf8mb4'),
		);

		return $key === null ? $config : ($config[$key] ?? null);
	}

	private static function env(string $key, $default = null)
	{
		if (function_exists('\\pc_env')) {
			return \pc_env($key, $default);
		}

		$value = getenv($key);
		return ($value === false || $value === '') ? $default : $value;
	}

	public function __construct()
	{
		$config = self::config();
		$port = !empty($config['port']) ? ";port=" . (int)$config['port'] : "";

		$this->conn = new \PDO(
			"mysql:dbname=" . $config['dbname'] . ";host=" . $config['host'] . $port . ";charset=" . $config['charset'],
			$config['username'],
			$config['password'],
			array(
				\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
				\PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
				\PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
			)
		);

		$this->conn->exec("SET NAMES utf8mb4");
		$this->conn->exec("SET CHARACTER SET utf8mb4");
		$this->conn->exec("SET SESSION collation_connection = utf8mb4_unicode_ci");
	}

	private function setParams($statement, $parameters = array())
	{
		foreach ($parameters as $key => $value) {
			$this->bindParam($statement, $key, $value);
		}
	}

	private function bindParam($statement, $key, $value)
	{
		$statement->bindValue($key, $value);
	}

	public function query($rawQuery, $params = array())
	{
		$stmt = $this->conn->prepare($rawQuery);
		$this->setParams($stmt, $params);
		$stmt->execute();

		return $stmt;
	}

	public function select($rawQuery, $params = array()): array
	{
		$stmt = $this->conn->prepare($rawQuery);
		$this->setParams($stmt, $params);
		$stmt->execute();

		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}
}
