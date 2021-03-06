<?php
/**
 * DBRecord class
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

class DBRecord extends Record implements DBObject {

	public $id;
	public $lifetime;
	public $lastAccessed;

	private $db;

	public function __construct($id, Database $db) {
		global $config;

		$this->db = $db;

		$sql = 'SELECT * FROM ' . $config['db']['tbl']['records'] . ' WHERE id = ' . (int) $id;

		$result = $this->db->query($sql, 1);
		$record = $result->first();

		$this->id = $record['id'];
		$this->lastAccessed = strtotime($record['last_accessed']);
		$this->lifetime = $record['lifetime'];
		$this->host = new DBHost($record['host_id'], $this->db);

		parent::__construct($this->host, (int) $record['ttl'], $record['class'], $record['type'], $record['rdata']);
	}

	public function update() {
		global $config;

		$sql = 'UPDATE ' . $config['db']['tbl']['records'] . '
				SET
					lifetime = ' . (int) $this->lifetime . ',
					last_accessed = \'' . date('Y-m-d H:i:s', $this->lastAccessed) . '\',
					host_id = \'' . $this->db->escape($this->host->id) . '\',
					ttl = ' . (int) $this->ttl . ',
					class = \'' . $this->db->escape($this->class) . '\',
					type = \'' . $this->db->escape($this->type) . '\',
					rdata = \'' . $this->db->escape( $this->rdata) . '\'
				WHERE id = ' . (int) $this->id;

		$this->db->execute($sql);

		return $this->db->affectedRows();
	}

	public function toXml(DOMDocument $doc) {
		$xmlRecord = parent::toXml($doc);

		$xmlRecord->setAttribute('id', $this->id);

		$xmlRecord->appendChild($doc->createElement('lifetime', $this->lifetime));
		$xmlRecord->appendChild($doc->createElement('lastaccessed', $this->lastAccessed));

		return $xmlRecord;
	}

	public function delete() {
		global $config;

		$sql = 'DELETE FROM ' . $config['db']['tbl']['records'] . ' WHERE id = ' . (int) $this->id;
		$this->db->execute($sql);
	}

	public static function get(Database $db, $filter = false, $order = array()) {
		global $config;

		$sql = 'SELECT r.id
				FROM ' .  $config['db']['tbl']['records'] . ' AS r
				LEFT JOIN ' .  $config['db']['tbl']['hosts'] . ' AS h
				ON h.id = r.host_id
				WHERE true';

				if (!empty($filter['id']))
					$sql .= ' && id = ' . (int) $filter['id'];
				if (!empty($filter['host']) && $filter['host'] instanceof Host)
					$sql .= ' && host_id = ' . (int) $filter['host']->isRegistred($db);
				if (!empty($filter['host']) && $filter['host'] instanceof DBHost)
					$sql .= ' && host_id = ' . (int) $filter['host']->id;
				if (!empty($filter['host']) && is_string($filter['host']))
					$sql .= ' && hostname = \'' . $db->escape($filter['host']) . '\'';
				if (!empty($filter['zone']) && $filter['zone'] instanceof Zone)
					$sql .= ' && zone = \'' . $db->escape($filter['zone']->name) . '\'';
				if (!empty($filter['zone']) && is_string($filter['zone']))
					$sql .= ' && zone = \'' . $db->escape($filter['zone']->name) . '\'';
				if (!empty($filter['type']))
					$sql .= ' && type = \'' . $db->escape($filter['type']) . '\'';
				if (!empty($filter['class']))
					$sql .= ' && class = \'' . $db->escape($filter['class']) . '\'';
				if (!empty($filter['rdata']))
					$sql .= ' && rdata = \'' . $db->escape($filter['rdata']) . '\'';
				if (!empty($filter['ttl']))
					$sql .= ' && ttl = ' . (int) $filter['ttl'];

                $sql .= ' ORDER BY';
		foreach ($order as $column => $dir) {
			$sql .= ' ' . $column . ' ' . $dir . ',';
		}
		$sql .= ' r.id ASC';

		$result = $db->query($sql);

		$records = array();
		foreach ($result as $record) {
			$records[] = new self($record['id'], $db);
		}
		return $records;
	}
}
