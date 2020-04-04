<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main\Application;
use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;

class RoleAccessTable extends DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_role_access';
	}

	/**
	 * @inheritdoc
	 */
	public static function getMap()
	{
		return [
			new IntegerField('ID', [
				'primary' => true,
				'autocomplete' => true,
			]),
			new IntegerField('ROLE_ID', [
				'required' => true,
			]),
			new StringField('ACCESS_CODE', [
				'required' => true,
			]),
			new Reference(
				'ROLE',
				'Bitrix\DocumentGenerator\Model\Role',
				['=this.ROLE_ID' => 'ref.ID'],
				['join_type' => 'INNER']
			)
		];
	}

	public static function truncate()
	{
		$connection = Application::getConnection();
		$connection->truncateTable(static::getTableName());
	}

	/**
	 * @param $roleId
	 * @return DeleteResult
	 * @throws \Bitrix\Main\ArgumentException
	 * @throws \Bitrix\Main\ObjectPropertyException
	 * @throws \Bitrix\Main\SystemException
	 */
	public static function deleteByRoleId($roleId)
	{
		$result = new DeleteResult();
		$roleId = (int)$roleId;
		if($roleId <= 0)
		{
			return $result->addError(new Error('roleId should be more than zero'));
		}

		$roleAccessList = static::getList(['select' => ['ID'], 'filter' => ['ROLE_ID' => $roleId]]);
		while($roleAccess = $roleAccessList->fetch())
		{
			$roleAccessDeleteResult = static::delete($roleAccess['ID']);
			if(!$roleAccessDeleteResult->isSuccess())
			{
				$result->addErrors($roleAccessDeleteResult->getErrors());
			}
		}

		return $result;
	}
}