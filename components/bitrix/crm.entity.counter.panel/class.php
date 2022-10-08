<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Counter\EntityCounter;
use Bitrix\Crm\Counter\EntityCounterType;
use Bitrix\Crm\Counter\EntityCounterFactory;
use Bitrix\Crm\Security\EntityAuthorization;

Loc::loadMessages(__FILE__);

class CCrmEntityCounterPanelComponent extends CBitrixComponent
{
	/** @var int */
	protected $userID = 0;
	/** @var string */
	protected $guid = '';
	/** @var string */
	protected $entityTypeName = '';
	/** @var int */
	protected $entityTypeID = \CCrmOwnerType::Undefined;
	/** @var array */
	protected $extras = array();
	/** @var string  */
	protected $entityListUrl = '';
	/** @var array */
	protected $errors = array();
	/** @var bool */
	protected $isVisible = true;
	/** @var bool  */
	protected $recalculate = false;

	public function executeComponent()
	{
		$this->initialize();
		if($this->isVisible)
		{
			foreach($this->errors as $message)
			{
				ShowError($message);
			}
			$this->includeComponentTemplate();
		}
	}
	protected function initialize()
	{
		if (!Bitrix\Main\Loader::includeModule('crm'))
		{
			$this->errors[] = GetMessage('CRM_MODULE_NOT_INSTALLED');
			return;
		}

		$this->userID = $this->arResult['USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
		$dbUsers = \CUser::GetList(
			'last_name',
			'asc',
			array('ID' => $this->userID),
			array('FIELDS' => array('ID', 'NAME', 'SECOND_NAME', 'LAST_NAME', 'LOGIN', 'TITLE'))
		);

		$userFields = $dbUsers->Fetch();
		$this->arResult['USER_NAME'] =  is_array($userFields)
			? \CUser::FormatName(\CSite::GetNameFormat(false), $userFields) : "[{$this->userID}]";

		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID']) ? $this->arParams['GUID'] : 'counter_panel';
		if(isset($this->arParams['ENTITY_TYPE_NAME']))
		{
			$this->entityTypeName = $this->arParams['ENTITY_TYPE_NAME'];
		}
		$this->entityTypeID = CCrmOwnerType::ResolveID($this->entityTypeName);
		if(!CCrmOwnerType::IsDefined($this->entityTypeID))
		{
			$this->errors[] = GetMessage('CRM_COUNTER_ENTITY_TYPE_NOT_DEFINED');
			return;
		}

		if(isset($this->arParams['EXTRAS']) && is_array($this->arParams['EXTRAS']))
		{
			$this->extras = $this->arParams['EXTRAS'];
		}

		if(!EntityAuthorization::checkReadPermission($this->entityTypeID, 0, null, $this->extras))
		{
			$this->isVisible = false;
		}

		if(isset($this->arParams['PATH_TO_ENTITY_LIST']))
		{
			$this->entityListUrl = $this->arParams['PATH_TO_ENTITY_LIST'];
		}

		$this->recalculate = isset($_REQUEST['recalc']) && mb_strtoupper($_REQUEST['recalc']) === 'Y';

		$data = array();
		$codes = array();
		$total = 0;
		$allSupportedTypes = EntityCounterType::getAllSupported($this->entityTypeID, true);
		foreach($allSupportedTypes as $typeID)
		{
			if(
				EntityCounterType::isGroupingForArray($typeID, $allSupportedTypes)
			)
			{
				$codes[] = EntityCounter::prepareCode($this->entityTypeID, $typeID, $this->extras);
				continue;
			}

			$counter = EntityCounterFactory::create($this->entityTypeID, $typeID, $this->userID, $this->extras);
			$code = $counter->getCode();
			$value = $counter->getValue($this->recalculate);
			$data[$code] = array(
				'TYPE_ID' => $typeID,
				'TYPE_NAME' => EntityCounterType::resolveName($typeID),
				'CODE' => $code,
				'VALUE' => $value,
				'URL' => $counter->prepareDetailsPageUrl($this->entityListUrl)
			);
			$total += $value;
			$codes[] = $code;
		}

		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		$this->arResult['EXTRAS'] = $this->extras;
		$this->arResult['TOTAL'] = $total;
		$this->arResult['CODES'] = $codes;
		$this->arResult['DATA'] = $data;
		$this->arResult['ENTITY_NUMBER_DECLENSIONS'] = \Bitrix\Crm\MessageHelper::getEntityNumberDeclensionMessages($this->entityTypeID);
		$this->arResult['ENTITY_PLURALS'] = \Bitrix\Crm\MessageHelper::getEntityPluralMessages($this->entityTypeID);
	}
}
