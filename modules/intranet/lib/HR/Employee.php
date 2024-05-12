<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage intranet
 * @copyright 2001-2023 Bitrix
 */
namespace Bitrix\Intranet\HR;

use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\UserFieldTable;
use Bitrix\Main\UserTable;
use Bitrix\Tasks\Integration\Intranet\Internals\Runtime\UtmUserTable;

class Employee
{
	protected static Employee $instance;

	/**
	 * @param int $departmentId
	 * @return array
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public function getListByDepartmentId(int $departmentId): array
	{
		$userIds =
			UtmUserTable::query()
				->setSelect(['VALUE_ID'])
				->registerRuntimeField(
					new Reference(
						'UF',
						UserFieldTable::class,
						Join::on('this.FIELD_ID', 'ref.ID'),
						['join_type' => Join::TYPE_LEFT]
					)
				)
				->registerRuntimeField(
					new Reference(
						'USER',
						UserTable::class,
						Join::on('this.VALUE_ID', 'ref.ID'),
						['join_type' => Join::TYPE_LEFT]
					)
				)
				->where('VALUE_INT', $departmentId)
				->where('USER.ACTIVE', true)
				->where('UF.FIELD_NAME', 'UF_DEPARTMENT')
				->exec()
				->fetchAll()
		;

		return array_column($userIds, 'VALUE_ID');
	}

	final public static function getInstance(): self
	{
		if (!isset(self::$instance))
		{
			self::$instance = new self();
		}

		return self::$instance;
	}
}
