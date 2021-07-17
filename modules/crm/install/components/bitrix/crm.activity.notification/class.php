<?php

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Application;
use Bitrix\Crm\Integration\NotificationsManager;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

Loc::loadMessages(__FILE__);

/**
 * Class CrmActivityNotificationComponent
 */
class CrmActivityNotificationComponent extends \CBitrixComponent
{
	/** @var array */
	private $activity;

	/** @var int */
	private $messageId;

	/**
	 * @inheritDoc
	 */
	public function onPrepareComponentParams($arParams)
	{
		if (!\Bitrix\Main\Loader::includeModule('notifications'))
		{
			ShowError(Loc::getMessage('CRM_ACTIVITY_NOTIFICATION_NOTIFICATIONS_MODULE_IS_NOT_INSTALLED'));
			Application::getInstance()->terminate();
		}

		$this->activity = (
			isset($arParams['ACTIVITY'])
			&& is_array($arParams['ACTIVITY'])
		)
			? $arParams['ACTIVITY']
			: null;

		if (!$this->activity)
		{
			ShowError(Loc::getMessage('CRM_ACTIVITY_NOTIFICATION_ACTIVITY_NOT_FOUND'));
			Application::getInstance()->terminate();
		}

		$this->messageId = (
			isset($arParams['ACTIVITY']['ASSOCIATED_ENTITY_ID'])
			&& (int)$arParams['ACTIVITY']['ASSOCIATED_ENTITY_ID'] > 0
		)
			? (int)$arParams['ACTIVITY']['ASSOCIATED_ENTITY_ID']
			: 0;

		if (!$this->messageId)
		{
			ShowError(Loc::getMessage('CRM_ACTIVITY_NOTIFICATION_MESSAGE_NOT_FOUND'));
			Application::getInstance()->terminate();
		}
	}

	/**
	 * @inheritDoc
	 */
	public function executeComponent()
	{
		$this->arResult = NotificationsManager::getMessageByInfoId($this->messageId);
		
		$this->includeComponentTemplate();
	}
}
