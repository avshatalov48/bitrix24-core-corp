<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Integration\Recyclebin\RecyclingManager;
use Bitrix\Crm\Restriction\AvailabilityManager;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

Main\Loader::includeModule('crm');
Main\Loader::includeModule('recyclebin');

class CCrmRecyclebinListComponent extends \CBitrixComponent
{
	private const TTL = 30;

	public function executeComponent()
	{
		$toolsManager = \Bitrix\Crm\Service\Container::getInstance()->getIntranetToolsManager();
		$isAvailable = $toolsManager->checkCrmAvailability();
		if (!$isAvailable)
		{
			print AvailabilityManager::getInstance()->getCrmInaccessibilityContent();

			return;
		}

		$this->arResult['GRID_ID'] = 'CRM_RECYCLE_BIN';
		$this->arResult['TTL'] = self::TTL;

		$this->arResult['ENTITY_TYPE_NAME'] = isset($this->arParams['~ENTITY_TYPE_NAME'])
			? $this->arParams['~ENTITY_TYPE_NAME'] : '';

		$this->arResult['ENTITY_TYPE_ID'] = \CCrmOwnerType::ResolveID($this->arResult['ENTITY_TYPE_NAME']);
		$this->arResult['RECYCLABLE_ENTITY_TYPE'] = RecyclingManager::resolveRecyclableEntityType(
			$this->arResult['ENTITY_TYPE_ID']
		);

		//region Presets
		$entityNames = Crm\Integration\Recyclebin\RecyclingManager::getEntityNames();
		unset($entityNames[\CCrmOwnerType::Activity]);

		$this->arResult['FILTER_PRESETS'] = array(
			'preset_crm_main_entities' => array(
				'name' => Loc::getMessage('CRM_RECYCLE_BIN_PRESET_MAIN_ENTITIES'),
				'default' => true,
				'fields' => array(
					'ENTITY_TYPE' => array_values($entityNames)
				)
			)
		);
		//endregion

		$this->arResult['USER_ID'] = \CCrmSecurityHelper::GetCurrentUserID();
		$this->arResult['PATH_TO_USER_PROFILE'] = isset($this->arParams['~PATH_TO_USER_PROFILE'])
			? $this->arParams['~PATH_TO_USER_PROFILE'] : '';

		$this->includeComponentTemplate();
	}
}