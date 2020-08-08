<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

namespace Bitrix\Tasks\Access\Install;


use Bitrix\Tasks\Access\Permission\PermissionDictionary;

class Migration
{
	private const LEVEL_FULL = 'full';

	private $db;

	public static function migrateAgent()
	{
		global $DB;
		(new self($DB))->migrateTemplateRights();
		return '';
	}

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function migrateTemplateRights()
	{
		$levelFull = $this->getLegacyAccessLevelId(self::LEVEL_FULL);

		$sql = '
			INSERT INTO b_tasks_template_permission
				(TEMPLATE_ID, ACCESS_CODE, PERMISSION_ID, VALUE)
			SELECT
				ENTITY_ID AS TEMPLATE_ID,
				GROUP_CODE AS ACCESS_CODE,
				IF(TASK_ID='. $levelFull .', '. PermissionDictionary::TEMPLATE_FULL .', '. PermissionDictionary::TEMPLATE_VIEW .') AS PERMISSION_ID,
				1 AS VALUE
			FROM b_tasks_task_template_access
		';

		$this->db->Query($sql);
	}

	/**
	 * @param string $level
	 * @return mixed
	 *
	 * full|read
	 */
	private function getLegacyAccessLevelId(string $level)
	{
		$level = \Bitrix\Tasks\Util\User::getAccessLevel('task_template', $level);
		return $level['ID'];
	}
}