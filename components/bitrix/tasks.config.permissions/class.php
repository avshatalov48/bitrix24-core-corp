<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage tasks
 * @copyright 2001-2021 Bitrix
 */

use Bitrix\Tasks\Access\Component\ConfigPermissions;

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
{
	die();
}

CBitrixComponent::includeComponentClass("bitrix:tasks.base");

class TasksConfigPermissions extends TasksBaseComponent
{
	public const
		STATUS_SUCCESS	= 'SUCCESS',
		STATUS_ERROR	= 'ERROR';

	protected static function checkRights(array $arParams, array $arResult, array $auxParams): ?\Bitrix\Tasks\Util\Error
	{
		$res = \Bitrix\Tasks\Access\TaskAccessController::can(
			\Bitrix\Tasks\Util\User::getId(),
			\Bitrix\Tasks\Access\ActionDictionary::ACTION_TASK_ADMIN
			)
			&& \Bitrix\Tasks\Integration\Bitrix24::checkFeatureEnabled(
				\Bitrix\Tasks\Integration\Bitrix24\FeatureDictionary::TASK_ACCESS_PERMISSIONS
			);

		if (!$res)
		{
			return new \Bitrix\Tasks\Util\Error('', 0, \Bitrix\Tasks\Util\Error::TYPE_FATAL);
		}

		return null;
	}

	protected function getData()
	{
		$configPermissions = new ConfigPermissions();

		$this->arResult['USER_GROUPS'] = $configPermissions->getUserGroups();
		$this->arResult['ACCESS_RIGHTS'] = $configPermissions->getAccessRights();
	}
}