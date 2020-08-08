<?php

namespace Bitrix\Rpa\Scenario;

use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Result;
use Bitrix\Rpa\Components\Base;
use Bitrix\Rpa\Model\FieldTable;
use Bitrix\Rpa\Model\PermissionTable;
use Bitrix\Rpa\Model\TypeTable;
use Bitrix\Rpa\Scenario;
use Bitrix\Rpa\UserPermissions;

class DraftType extends Scenario
{
	public function play(): Result
	{
		Base::loadBaseLanguageMessages();
		$type = TypeTable::createObject();
		$type->setTitle(Loc::getMessage('RPA_COMMON_NEW_PROCESS'));
		$name = TypeTable::generateName();
		if(!$name)
		{
			$result = new Result();
			return $result->addError(new Error(Loc::getMessage('RPA_SCENARIO_DRAFT_TYPE')));
		}
		$type->setName($name);
		$result = $type->save();
		if($result->isSuccess())
		{
			$result->setData([
				'type' => $type,
			]);

			PermissionTable::add([
				'ENTITY' => UserPermissions::ENTITY_TYPE,
				'ENTITY_ID' => $type->getId(),
				'ACCESS_CODE' => 'UA',
				'ACTION' => UserPermissions::ACTION_ITEMS_CREATE,
				'PERMISSION' => UserPermissions::PERMISSION_ANY,
			]);

			PermissionTable::add([
				'ENTITY' => UserPermissions::ENTITY_TYPE,
				'ENTITY_ID' => $type->getId(),
				'ACCESS_CODE' => 'UA',
				'ACTION' => UserPermissions::ACTION_MODIFY,
				'PERMISSION' => UserPermissions::PERMISSION_ANY,
			]);

			PermissionTable::add([
				'ENTITY' => UserPermissions::ENTITY_TYPE,
				'ENTITY_ID' => $type->getId(),
				'ACCESS_CODE' => 'UA',
				'ACTION' => UserPermissions::ACTION_VIEW,
				'PERMISSION' => UserPermissions::PERMISSION_ANY,
			]);

			$fieldName = $type->getItemUfNameFieldName();
			$ufEntity = new \CAllUserTypeEntity();
			$id = $ufEntity->Add([
				'ENTITY_ID' => $type->getItemUserFieldsEntityId(),
				'FIELD_NAME' => $fieldName,
				'USER_TYPE_ID' => \CUserTypeManager::BASE_TYPE_STRING,
				'SORT' => 10,
				'MULTIPLE' => 'N',
				'MANDATORY' => 'N',
				'SHOW_FILTER' => 'S',
				'SHOW_IN_LIST' => 'Y',
				'EDIT_IN_LIST' => 'Y',
				'IS_SEARCHABLE' => 'Y',
				'EDIT_FORM_LABEL' => [
					Loc::getCurrentLang() => Loc::getMessage('RPA_COMMON_TITLE'),
				],
				'LIST_COLUMN_LABEL' => [
					Loc::getCurrentLang() => Loc::getMessage('RPA_COMMON_TITLE'),
				],
				'LIST_FILTER_LABEL' => [
					Loc::getCurrentLang() => Loc::getMessage('RPA_COMMON_TITLE'),
				],
			]);

			if($id > 0)
			{
				FieldTable::add([
					'TYPE_ID' => $type->getId(),
					'STAGE_ID' => 0,
					'FIELD' => $fieldName,
					'VISIBILITY' => FieldTable::VISIBILITY_CREATE,
				]);
			}
		}

		return $result;
	}
}