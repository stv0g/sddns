<?php
/**
 * DBHost class
 *
 * @copyright	2013 Steffen Vogel
 * @license	http://www.gnu.org/licenses/gpl.txt GNU Public License
 * @author	Steffen Vogel <post@steffenvogel.de>
 * @link	http://www.steffenvogel.de
 */
/*
 * This file is part of sddns
 *
 * sddns is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * sddns is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sddns. If not, see <http://www.gnu.org/licenses/>.
 */

class DBHost extends Host implements DBObject {
	public $id;
	public $generated;
	private $password;

	private $db;

	public function __construct($id, Database $db) {
		global $config;

		$this->db = $db;

		$sql = 'SELECT * FROM ' . $config['db']['tbl']['hosts'] . ' WHERE id = ' . (int) $id;
		$result = $this->db->query($sql, 1);

		if ($result->count() == 1) {
			$host = $result->first();
			$this->id = $host['id'];
			parent::__construct($host['hostname'], $config['sddns']['zones'][$host['zone']], $host['generated']);
		}
		else {
			throw new CustomException('host not found by id', $id);
		}
	}

	public function update() {
		global $config;

		$sql = 'UPDATE ' . $config['db']['tbl']['hosts'] . '
				SET
					hostname = \'' . $this->db->escape($this->toPunycode()) . '\',
					zone = \'' . $this->db->escape($this->zone->name) . '\',
					password = \'' . $this->db->escape(sha1($this->password)) . '\',
					generated = \'' .$this->db->escape( $this->generated) . '\'
				WHERE id = ' . (int) $this->id;

		$this->db->execute($sql);
	}

	public function delete() {
		global $config;

		if ($this->getRecordsFromDB() > 0) {
			throw new UserException('host has records');
		}
		elseif ($this->getUrisFromDB() > 0) {
			throw new UserException('host has uris');
		}
		else {
			$sql = 'DELETE FROM ' .  $config['db']['tbl']['hosts'] . '
					WHERE id = ' . (int) $this->id;
			$this->db->execute($sql);
		}
	}

	public function checkPassword($pw) {
		global $config;

		$sql = 'SELECT password
			     FROM ' .  $config['db']['tbl']['hosts'] . '
			     WHERE hostname = \'' . $this->db->escape($this->toPunycode()) . '\' && zone = \'' . $this->db->escape($this->zone->name) . '\'';

		$result = $this->db->query($sql, 1);
		$entry = $result->first();

		return ($entry['password'] === sha1($pw)) && !empty($pw);
	}

	public function getRecordsFromDB() {
		return DBRecord::get($this->db, array('host' => $this));
	}

	public function getUrisFromDB() {
		return DBRUri::get($this->db, array('host' => $this));
	}

	public static function get(Database $db, $filter = false, $order = array()) {
		global $config;

		$sql = 'SELECT id
				FROM ' .  $config['db']['tbl']['hosts'] . '
				WHERE true';

				if (!empty($filter['id']))
					$sql .= ' && id = ' . (int) $filter['id'];
				if (!empty($filter['host']) && is_string($filter['host']))
					$sql .= ' && hostname = \'' . $db->escape($filter['host']) . '\'';
				if (!empty($filter['zone']) && $filter['zone'] instanceof Zone)
					$sql .= ' && zone = \'' . $db->escape($filter['zone']->name) . '\'';
				if (!empty($filter['zone']) && is_string($filter['zone']))
					$sql .= ' && zone = \'' . $db->escape($filter['zone']->name) . '\'';
				if (!empty($filter['generated']))
					$sql .= ' && generated = ' . ($filter['generated']) ? '1' : '0';

		$sql .= ' ORDER BY';
		foreach ($order as $column => $dir) {
			$sql .= ' ' . $column . ' ' . $dir . ',';
		}
		$sql .= ' id ASC';


		$result = $db->query($sql);

		$hosts = array();
		foreach ($result as $host) {
			$hosts[] = new self($host['id'], $db);
		}
		return $hosts;
	}

	/*
	 * Output
	 */
	public function toXml(DOMDocument $doc) {
		$xmlRecord = parent::toXml($doc);

		$xmlRecord->setAttribute('id', $this->id);

		return $xmlRecord;
	}
}
