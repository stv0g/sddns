<?php
/**
 * Host class
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

class Host implements Object {

	public $punycode;
	public $zone;
	public $generated;

	/*
	 * Constructor & Factory
	 */
	public function __construct($hostname, Zone $zone, $generated = false) {
		$hostname = strtolower($hostname);
		if (self::isValid(idn_to_ascii($hostname))) {
			$this->punycode = idn_to_ascii($hostname);
			$this->zone = $zone;
			$this->generated = $generated;
		}
		else {
			if (strlen($hostname) > 63)
				throw new UserException('invalid hostname', 'too long');
			else
				throw new UserException('invalid hostname', idn_to_ascii($hostname));
		}
	}

	public static function unique(Zone $zone, Database $db) {
		global $config;

		$sql = 'SELECT hostname
				FROM ' . $config['db']['tbl']['hosts'] . '
				WHERE
					generated = TRUE  &&
					zone = \'' . $db->escape($zone->name) . '\'
				ORDER BY id DESC';

		$result = $db->query($sql, 1);
		$first = $result->first();
		$last_id = base_convert($first['hostname'], 36, 10);

		while ($result->count() > 0 || !Host::isValid(base_convert($last_id, 10, 36))) {

			$sql = 'SELECT hostname
					FROM ' .  $config['db']['tbl']['hosts'] . '
					WHERE
						hostname = \'' . base_convert(++$last_id, 10, 36) . '\' &&
						zone = \'' . $db->escape($zone->name) . '\'';

			$result = $db->query($sql, 1);
		}

		return new self(base_convert($last_id, 10, 36), $zone, true);
	}

	/*
	 * Checks
	 */
	public static function isValid($hostname) {
		$hostExpr = '[a-z0-9](?:[a-z0-9\-]*[a-z0-9])?';
		return preg_match('/^(?:\*|(\*\.)?(' . $hostExpr . '\.)*(' . $hostExpr . '))$/i', $hostname);
	}

	public function isRegistred(Database $db) {
		global $config;

		$sql = 'SELECT *
			FROM ' . $config['db']['tbl']['hosts'] . '
			WHERE hostname = \'' . $db->escape($this->toPunycode()) . '\' && zone = \'' . $db->escape($this->zone->name) . '\'';

		$result = $db->query($sql, 1);
		$host = $result->first();

		return ($result->count() > 0) ? $host['id'] : false;
	}

	/*
	 * Database
	 */
	public function add($pw, Database $db) {
		global $config;

		$sql = 'INSERT INTO ' .  $config['db']['tbl']['hosts'] . ' (hostname, zone, password, generated)
				VALUES (
					\'' . $db->escape($this->toPunycode()) . '\',
					\'' . $db->escape($this->zone->name) . '\',
					\'' . $db->escape(sha1($pw)) . '\',
					' . (int) $this->generated . ')';

		$db->execute($sql);

		return new DBHost($db->lastId(), $db);
	}

	/*
	 * Output
	 */
	public function toUnicode() {
		return idn_to_utf8($this->punycode);
	}

	public function toPunycode() {
		return $this->punycode;
	}

	public function __toString() {
		return $this->toPunycode() . '.' . $this->zone->name;
	}

	public function toXml(DOMDocument $doc) {
		$xmlHost = $doc->createElement('host');

		$xmlHost->appendChild($doc->createElement('hostname', $this->toPunycode()));
		$xmlHost->appendChild($doc->createElement('idn', $this->toUnicode()));
		$xmlHost->appendChild($this->zone->toXml($doc));

		return $xmlHost;
	}

	public function toHtml() {
		return '<a target="_blank" href="http://' . $this . '">' . $this->toUnicode() . '.' . $this->zone->name . '</a>';
	}
}

