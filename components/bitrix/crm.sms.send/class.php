<?php

use Bitrix\Crm\Integration\SmsManager;
use Bitrix\Main\Localization\Loc;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class CCrmSmsSendComponent extends CBitrixComponent
{
	protected $entityTypeId;
	protected $entityId;

	public function onPrepareComponentParams($arParams)
	{
		$arParams = parent::onPrepareComponentParams($arParams);

		$this->entityTypeId = $arParams['ENTITY_TYPE_ID'];
		$this->entityId = $arParams['ENTITY_ID'];

		return $arParams;
	}

	public function executeComponent()
	{
		\Bitrix\Main\Loader::includeModule('crm');

		Loc::loadLanguageFile(__FILE__);
		if(!SmsManager::canUse())
		{
			ShowError(Loc::getMessage('CRM_SMS_SEND_COMPONENT_NOT_AVAILABLE'));
			return;
		}

		$this->arResult = SmsManager::getEditorConfig($this->entityTypeId, $this->entityId);
		$this->arResult['text'] = $this->arParams['TEXT'];
		$this->arResult['containerId'] = 'sms_send_'.randString(10);
		$this->arResult['serviceUrl'] = "/bitrix/components/bitrix/crm.timeline/ajax.php?&site=".SITE_ID."&".bitrix_sessid_get();
		$this->arResult['ownerTypeId'] = $this->entityTypeId;
		$this->arResult['ownerId'] = $this->entityId;
		$this->arResult['providerId'] = $this->arParams['PROVIDER_ID'] ?? null;
		$this->arResult['isProviderFixed'] = (string)($this->arParams['IS_PROVIDER_FIXED'] ?? null) === 'Y';

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('CRM_SMS_SEND_COMPONENT_TITLE'));

		$this->includeComponentTemplate();
	}
}