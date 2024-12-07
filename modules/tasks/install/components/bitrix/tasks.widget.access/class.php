<?php
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

Loc::loadMessages(__FILE__);

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

/**
 * @Deprecated since tasks 22.500.0 and will be removed
 */
class TasksWidgetAccessComponent extends TasksBaseComponent
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
		else
		{
			if (!Type::isIterable($this->arParams['DATA']))
			{
				$this->arParams['DATA'] = [];
			}
		}

		$legalPrefixes = ['U', 'SG', 'DR']; // U - users, SG - groups, DR - departments

		foreach ($this->arParams['DATA'] as $k => $item)
		{
			// currently only "user" and "group" types are supported
			$legal = $item instanceof Access && in_array($item->getGroupPrefix(), $legalPrefixes);

			if (!$legal)
			{
				unset($this->arParams['DATA'][$k]);
			}
		}

		return $this->errors->checkNoFatals();
	}

	protected function getData()
	{
		$featureEnabled = true;

		if (!Bitrix24::checkFeatureEnabled(Bitrix24\FeatureDictionary::TASK_TEMPLATE_ACCESS_PERMISSIONS))
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
		$levels = User::getAccessLevelsForEntity($this->arParams['ENTITY_CODE']);

		if (!count($levels))
		{
			$this->errors->add('NO_ACCESS_LEVELS', Loc::getMessage('TASKS_COMPONENT_NO_ACCESS_LEVELS'));
		}

		$this->arResult['DATA']['LEVELS'] = $levels;
	}

	protected function getMissingUserData()
	{
		$users = [];

		foreach ($this->arParams['DATA'] as $item)
		{
			/** @var Access $item */
			if ($item->getGroupPrefix() == SocialNetwork::getUserEntityPrefix())
			{
				$users[] = $item->getGroupId();
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

		foreach ($this->arParams['DATA'] as $item)
		{
			/** @var Access $item */
			if ($item->getGroupPrefix() == SocialNetwork::getGroupEntityPrefix())
			{
				$groupIds[] = $item->getGroupId();
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

		foreach ($this->arParams['DATA'] as $item)
		{
			/** @var Access $item */
			if ($item->getGroupPrefix() == SocialNetwork::getDepartmentEntityPrefix())
			{
				$departmentIds[] = $item->getGroupId();
			}
		}

		if (!empty($departmentIds))
		{
			$this->arResult['AUX_DATA']['DEPARTMENTS'] = Department::getData($departmentIds);
		}
	}
}