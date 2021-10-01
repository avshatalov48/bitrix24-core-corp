<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

use Bitrix\Tasks\Util;
use Bitrix\Tasks\Util\Type;
use Bitrix\Tasks\Util\User;
use Bitrix\Tasks\Util\Error;
use Bitrix\Tasks\Integration\Bitrix24;
use Bitrix\Tasks\Integration\SocialNetwork;
use Bitrix\Tasks\Integration\SocialNetwork\Group;
use Bitrix\Tasks\Integration\Intranet\Department;
use Bitrix\Tasks\Item\Task\Template\Access;
use Bitrix\Tasks\Access\Permission\PermissionDictionary;

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksWidgetTemplateAccessComponent extends TasksBaseComponent
{
	protected function checkParameters()
	{
		static::tryParseStringParameter($this->arParams['ENTITY_CODE'], '');
		static::tryParseBooleanParameter($this->arParams['CAN_READ'], true);
		static::tryParseBooleanParameter($this->arParams['CAN_UPDATE'], true);
		static::tryParseBooleanParameter($this->arParams['EDIT_MODE'], false);
		static::tryParseIntegerParameter($this->arParams['TEMPLATE_ID']);
		static::tryParseArrayParameter($this->arParams['USER_DATA']);

		if ($this->arParams['ENTITY_CODE'] == '')
		{
			$this->errors->add('ILLEGAL_PARAMETER.ENTITY_CODE', 'Entity code not specified');
		}

		if (!$this->arParams['CAN_READ'])
		{
			$this->errors->add('NO_READ_ACCESS', Loc::getMessage('TASKS_COMPONENT_TWR_CAN_NOT_READ'), Error::TYPE_WARNING);
		}

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		$featureEnabled = true;

		if (!Bitrix24::checkFeatureEnabled(Bitrix24\FeatureDictionary::TASKS_TEMPLATES_ACCESS))
		{
			if (!$this->arParams['EDIT_MODE'])
			{
				$featureEnabled = false;
			}
			else
			{
				$lastAccessedTemplateId = Util::getOption('tasks_last_accessed_template');

				if ($this->arParams['TEMPLATE_ID'] > $lastAccessedTemplateId)
				{
					$featureEnabled = false;
				}
			}
		}

		$this->arResult['DATA']['FEATURE_ENABLED'] = $featureEnabled;
		$this->arResult['DATA']['MAIN_DEPARTMENT'] = Department::getMainDepartment();

		// get access levels
		$this->getAccessLevelData();
	}

	protected function getAuxData()
	{
		parent::getAuxData();

		$this->getMissingUserData();
		$this->getGroupData();
		$this->getDepartmentData();
	}

	protected function getAccessLevelData()
	{
		$levels = [
			PermissionDictionary::TEMPLATE_VIEW => [
				'ID' => PermissionDictionary::TEMPLATE_VIEW,
				'TITLE' => PermissionDictionary::getTitle(PermissionDictionary::TEMPLATE_VIEW)
			],
			PermissionDictionary::TEMPLATE_FULL => [
				'ID' => PermissionDictionary::TEMPLATE_FULL,
				'TITLE' => PermissionDictionary::getTitle(PermissionDictionary::TEMPLATE_FULL)
			]
		];

		if (!count($levels))
		{
			$this->errors->add('NO_ACCESS_LEVELS', Loc::getMessage('TASKS_COMPONENT_NO_ACCESS_LEVELS'));
		}

		$this->arResult['DATA']['LEVELS'] = $levels;
	}

	protected function getMissingUserData()
	{
		$users = [];

		foreach ($this->arParams['PERMISSIONS'] as $item)
		{
			/** @var \Bitrix\Tasks\Access\Permission\TasksTemplatePermission $item */
			if ($item->getMemberPrefix() === SocialNetwork::getUserEntityPrefix())
			{
				$users[] = $item->getMemberId();
			}
		}

		$knownUsers = $this->arParams['USER_DATA'];
		$knownUserIds = array_map(function($item){ return $item['ID']; }, $knownUsers);
		$unKnownIds = array_diff($users, $knownUserIds);

		if (count($unKnownIds))
		{
			$users = User::getData($unKnownIds);

			foreach ($users as $user)
			{
				$knownUsers[$user['ID']] = $user;
			}
		}

		$this->arResult['AUX_DATA']['USERS'] = $knownUsers;
	}

	protected function getGroupData()
	{
		$groupIds = [];

		foreach ($this->arParams['PERMISSIONS'] as $item)
		{
			/** @var \Bitrix\Tasks\Access\Permission\TasksTemplatePermission $item */
			if ($item->getMemberPrefix() === SocialNetwork::getGroupEntityPrefix())
			{
				$groupIds[] = $item->getMemberId();
			}
		}

		if (!empty($groupIds))
		{
			$this->arResult['AUX_DATA']['GROUPS'] = Group::getData($groupIds);
		}
	}

	protected function getDepartmentData()
	{
		$departmentIds = [];

		foreach ($this->arParams['PERMISSIONS'] as $item)
		{
			/** @var \Bitrix\Tasks\Access\Permission\TasksTemplatePermission $item */
			if ($item->getMemberPrefix() == SocialNetwork::getDepartmentEntityPrefix())
			{
				$departmentIds[] = $item->getMemberId();
			}
		}

		if (!empty($departmentIds))
		{
			$this->arResult['AUX_DATA']['DEPARTMENTS'] = Department::getData($departmentIds);
		}
	}
}