<?php

namespace Bitrix\DocumentGenerator\Model;

use Bitrix\Main\Error;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Data\DeleteResult;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Fields\StringField;

class RolePermissionTable extends DataManager
{
	/**
	 * @inheritdoc
	 */
	public static function getTableName()
	{
		return 'b_documentgenerator_role_permission';
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
			new StringField('ENTITY', [
				'required' => true,
			]),
			new StringField('ACTION', [
				'required' => true,
			]),
			new StringField('PERMISSION'),
			new Reference(
				'ROLE_ACCESS',
				'Bitrix\DocumentGenerator\Model\RoleAccess',
				['=this.ROLE_ID' => 'ref.ROLE_ID'],
				['join_type' => 'INNER']
			),
			new Reference(
				'ROLE',
				'Bitrix\ImOpenLines\Model\Role',
				['=this.ROLE_ID' => 'ref.ID'],
				['join_type' => 'INNER']
			),
		];
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

		$rolePermissions = static::getList(['select' => ['ID'], 'filter' => ['ROLE_ID' => $roleId]]);
		while($rolePermission = $rolePermissions->fetch())
		{
			$rolePermissionDeleteResult = static::delete($rolePermission['ID']);
			if(!$rolePermissionDeleteResult->isSuccess())
			{
				$result->addErrors($rolePermissionDeleteResult->getErrors());
			}
		}

		return $result;
	}
}