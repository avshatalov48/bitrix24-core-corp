<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();
CModule::IncludeModule("crm");

use Bitrix\Crm\Color\PhaseColorScheme;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmEntityProgressBarComponent extends CBitrixComponent
{
	/** @var int */
	protected $userID = 0;
	/** @var string */
	protected $guid = '';
	/** @var string */
	protected $currentStepID = '';
	/** @var string */
	protected $currentSemanticID = '';
	/** @var string */
	protected $currentSemantics = '';
	/** @var string */
	protected $currentColor = '';
	/** @var string */
	protected $defaultBackgroundColor = "#d3d7dc";
	/** @var int */
	protected $entityTypeID = CCrmOwnerType::Undefined;
	/** @var int */
	protected $entityID = 0;
	/** @var array|null  */
	protected $extras = null;
	/** @var bool */
	protected $isReadOnly = false;
	/** @var string */
	protected $serviceUrl = "";
	public function executeComponent()
	{
		$this->userID = CCrmSecurityHelper::GetCurrentUserID();
		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID']) ? $this->arParams['GUID'] : 'entity_progress';

		$this->entityTypeID = isset($this->arParams['ENTITY_TYPE_ID'])
			? (int)$this->arParams['ENTITY_TYPE_ID'] : CCrmOwnerType::Undefined;
		$this->entityID = isset($this->arParams['ENTITY_ID']) ? (int)$this->arParams['ENTITY_ID'] : 0;

		$this->extras = isset($this->arParams['EXTRAS']) && is_array($this->arParams['EXTRAS'])
			? $this->arParams['EXTRAS'] : array();

		$this->isReadOnly = $this->arResult['READ_ONLY'] = isset($this->arParams['READ_ONLY'])
			? (bool)$this->arParams['READ_ONLY'] : false;

		$this->arResult['VERBOSE_MODE'] = isset($this->arParams['VERBOSE_MODE'])
			? (bool)$this->arParams['VERBOSE_MODE'] : false;

		//Entity field for progress state
		$this->arResult['ENTITY_FIELD_NAME'] = '';

		$this->currentStepID = '';
		if($this->entityTypeID === CCrmOwnerType::Lead)
		{
			$this->arResult['ENTITY_FIELD_NAME'] = 'STATUS_ID';
			$this->serviceUrl = '/bitrix/components/bitrix/crm.lead.list/list.ajax.php?'.bitrix_sessid_get();
			$dbResult = Bitrix\Crm\LeadTable::getList(
				array('select' => array('ID', 'STATUS_ID'), 'filter' => array('=ID' => $this->entityID))
			);
			$fields = $dbResult->fetch();
			if(is_array($fields))
			{
				if(isset($fields['STATUS_ID']))
				{
					$this->currentStepID = $fields['STATUS_ID'];
				}
			}
		}
		elseif($this->entityTypeID === CCrmOwnerType::Deal || $this->entityTypeID === CCrmOwnerType::DealRecurring)
		{
			$this->arResult['ENTITY_FIELD_NAME'] = 'STAGE_ID';
			$this->serviceUrl = '/bitrix/components/bitrix/crm.deal.list/list.ajax.php?'.bitrix_sessid_get();
			$dbResult = Bitrix\Crm\DealTable::getList(
				array('select' => array('ID', 'STAGE_ID', 'CATEGORY_ID'), 'filter' => array('=ID' => $this->entityID))
			);
			$fields = $dbResult->fetch();
			if(is_array($fields))
			{
				if(isset($fields['STAGE_ID']))
				{
					$this->currentStepID = $fields['STAGE_ID'];
				}
				if(isset($fields['CATEGORY_ID']))
				{
					$this->extras['CATEGORY_ID'] = $fields['CATEGORY_ID'];
				}
			}
		}
		else if($this->entityTypeID === CCrmOwnerType::Quote)
		{
			$this->arResult['ENTITY_FIELD_NAME'] = 'QUOTE_STATUS';
			$this->serviceUrl = '/bitrix/components/bitrix/crm.quote.list/list.ajax.php?'.bitrix_sessid_get();
			$dbResult = Bitrix\Crm\QuoteTable::getList(
				array('select' => array('ID', 'STATUS_ID'), 'filter' => array('=ID' => $this->entityID))
			);
			$fields = $dbResult->fetch();
			if(is_array($fields))
			{
				if(isset($fields['STATUS_ID']))
				{
					$this->currentStepID = $fields['STATUS_ID'];
				}
			}
		}
		else if($this->entityTypeID === CCrmOwnerType::Order)
		{
			$this->arResult['ENTITY_FIELD_NAME'] = 'STATUS_ID';
			$this->serviceUrl = '/bitrix/components/bitrix/crm.order.list/list.ajax.php?'.bitrix_sessid_get();

			$dbResult = Bitrix\Crm\Order\Order::getList(
				array('select' => array('ID', 'STATUS_ID'), 'filter' => array('=ID' => $this->entityID))
			);
			$fields = $dbResult->fetch();
			if(is_array($fields))
			{
				if(isset($fields['STATUS_ID']))
				{
					$this->currentStepID = $fields['STATUS_ID'];
				}
			}
		}
		else if($this->entityTypeID === CCrmOwnerType::OrderShipment)
		{
			$this->arResult['ENTITY_FIELD_NAME'] = 'ORDER_SHIPMENT_STATUS';
			$this->serviceUrl = '/bitrix/components/bitrix/crm.order.shipment.list/list.ajax.php?'.bitrix_sessid_get();

			$dbResult = \Bitrix\Crm\Order\Shipment::getList(
				array('select' => array('ID', 'STATUS_ID'), 'filter' => array('=ID' => $this->entityID))
			);
			$fields = $dbResult->fetch();
			if(is_array($fields))
			{
				if(isset($fields['STATUS_ID']))
				{
					$this->currentStepID = $fields['STATUS_ID'];
				}
			}
		}

		//region Conversion scheme
		$this->arResult['CAN_CONVERT'] = isset($this->arParams['~CAN_CONVERT'])
			? (bool)$this->arParams['~CAN_CONVERT'] : false;

		$conversionScheme = null;
		if(isset($this->arParams['CONVERSION_SCHEME']) && is_array($this->arParams['CONVERSION_SCHEME']))
		{
			$conversionScheme = array();
			if(isset($this->arParams['CONVERSION_SCHEME']['ORIGIN_URL']))
			{
				$conversionScheme['originUrl'] = $this->arParams['CONVERSION_SCHEME']['ORIGIN_URL'];
			}
			if(isset($this->arParams['CONVERSION_SCHEME']['SCHEME_NAME']))
			{
				$conversionScheme['schemeName'] =  $this->arParams['CONVERSION_SCHEME']['SCHEME_NAME'];
			}
			if(isset($this->arParams['CONVERSION_SCHEME']['SCHEME_CAPTION']))
			{
				$conversionScheme['schemeCaption'] =  $this->arParams['CONVERSION_SCHEME']['SCHEME_CAPTION'];
			}
			if(isset($this->arParams['CONVERSION_SCHEME']['SCHEME_DESCRIPTION']))
			{
				$conversionScheme['schemeDescription'] =  $this->arParams['CONVERSION_SCHEME']['SCHEME_DESCRIPTION'];
			}
		}
		$this->arResult['CONVERSION_SCHEME'] = $conversionScheme;
		$this->arResult['CONVERSION_TYPE_ID'] = isset($this->arParams['CONVERSION_TYPE_ID'])
			? (int)$this->arParams['CONVERSION_TYPE_ID'] : 0;
		//endregion

		$this->arResult['SERVICE_URL'] = $this->serviceUrl;
		$this->arResult['CURRENT_STEP_ID'] = $this->currentStepID;
		$this->arResult['STEP_INFO_TYPE_ID'] = "";
		$this->arResult['ENTITY_TYPE_ID'] = $this->entityTypeID;
		$this->arResult['ENTITY_ID'] = $this->entityID;
		//$this->arResult['EXTRAS'] = $this->extras;
		$this->arResult['TERMINATION_TITLE'] = Loc::getMessage('CRM_ENTITY_ED_PROG_CLOSE');

		$infos = array();
		$items = array();
		if($this->entityTypeID === CCrmOwnerType::Lead)
		{
			$this->currentSemanticID = \CCrmLead::GetSemanticID($this->currentStepID);
			$this->currentSemantics = \CCrmLead::GetStatusSemantics($this->currentStepID);

			$infos = \CCrmViewHelper::GetLeadStatusInfos();
			\CCrmViewHelper::PrepareLeadStatusInfoExtraParams($infos);
			$this->arResult['TERMINATION_TITLE'] = Loc::getMessage('CRM_ENTITY_ED_PROG_LEAD_CLOSE');
		}
		else if($this->entityTypeID === CCrmOwnerType::Deal || $this->entityTypeID === CCrmOwnerType::DealRecurring)
		{
			$categoryID = isset($this->extras['CATEGORY_ID']) ? (int)$this->extras['CATEGORY_ID'] : 0;
			$this->currentSemanticID = \CCrmDeal::GetSemanticID($this->currentStepID, $categoryID);
			$this->currentSemantics = \CCrmDeal::GetStageSemantics($this->currentStepID, $categoryID);

			$infos = CCrmViewHelper::GetDealStageInfos($categoryID);
			\CCrmViewHelper::PrepareDealStageExtraParams($infos, $categoryID);
			$this->arResult['TERMINATION_TITLE'] = Loc::getMessage('CRM_ENTITY_ED_PROG_DEAL_CLOSE');
			$this->arResult['STEP_INFO_TYPE_ID'] = "category_{$categoryID}";

			//Partial Editor Dialog Title is moved to BX.CrmDealStageManager.messages (please see \CCrmViewHelper::RenderDealStageSettings)
			//$this->arResult['CHECK_ERROR_TITLE'] = Loc::getMessage('CRM_ENTITY_ED_PROG_DEAL_CHECK_ERROR');
		}
		else if($this->entityTypeID === CCrmOwnerType::Quote)
		{
			$this->currentSemanticID = \CCrmQuote::GetSemanticID($this->currentStepID);
			$this->currentSemantics = \CCrmQuote::GetStatusSemantics($this->currentStepID);

			$infos = CCrmViewHelper::GetQuoteStatusInfos();
			\CCrmViewHelper::PrepareQuoteStatusInfoExtraParams($infos);
			$this->arResult['TERMINATION_TITLE'] = Loc::getMessage('CRM_ENTITY_ED_PROG_QUOTE_CLOSE');
		}
		else if($this->entityTypeID === CCrmOwnerType::Order)
		{
			$this->currentSemanticID = Bitrix\Crm\Order\OrderStatus::getSemanticID($this->currentStepID);
			$this->currentSemantics = Bitrix\Crm\Order\OrderStatus::getStatusSemantics($this->currentStepID);

			$infos = CCrmViewHelper::GetOrderStatusInfos();
			\CCrmViewHelper::PrepareOrderStatusInfoExtraParams($infos);
		}
		else if($this->entityTypeID === CCrmOwnerType::OrderShipment)
		{
			$this->currentSemanticID = Bitrix\Crm\Order\DeliveryStatus::getSemanticId($this->currentStepID);
			$this->currentSemantics = Bitrix\Crm\Order\DeliveryStatus::getStatusSemantics($this->currentStepID);

			$infos = CCrmViewHelper::GetOrderShipmentStatusInfos();
			\CCrmViewHelper::PrepareOrderShipmentStatusInfoExtraParams($infos);
			$this->arResult['TERMINATION_TITLE'] = Loc::getMessage('CRM_ENTITY_ED_PROG_ORDER_SHIPMENT_CLOSE');
		}

		/*
		else if($this->entityTypeID === CCrmOwnerType::Invoice)
		{
			$infos = CCrmViewHelper::GetInvoiceStatusInfos();
		}
		*/

		$this->arResult['CURRENT_SEMANTICS'] = $this->currentSemantics;

		$isPassed = true;

		if ($this->entityTypeID === CCrmOwnerType::DealRecurring)
		{
			$isPassed = false;
		}

		$infos = PhaseColorScheme::fillDefaultColors($infos);

		foreach($infos as $info)
		{
			$stepID = $info['STATUS_ID'];

			$name = $info['NAME'];
			$semanticID = isset($info['SEMANTICS']) ? $info['SEMANTICS'] : '';
			if($this->currentSemanticID !== Bitrix\Crm\PhaseSemantics::SUCCESS
				&& $semanticID === Bitrix\Crm\PhaseSemantics::SUCCESS
			)
			{
				$name = $this->arResult['TERMINATION_TITLE'];
			}

			$isVisible = true;
			if($this->currentSemanticID !== Bitrix\Crm\PhaseSemantics::FAILURE)
			{
				$isVisible = $info['SEMANTICS'] !== Bitrix\Crm\PhaseSemantics::FAILURE;
			}
			else
			{
				if($info['SEMANTICS'] === Bitrix\Crm\PhaseSemantics::SUCCESS)
				{
					$isVisible = false;
				}
				elseif($info['SEMANTICS'] === Bitrix\Crm\PhaseSemantics::FAILURE)
				{
					$isVisible = $stepID === $this->currentStepID;
				}
			}

			$color = isset($info['COLOR']) ? $info['COLOR'] : \CCrmViewHelper::PROCESS_COLOR;
			$items[] = array(
				'NAME' => $name,
				'STATUS_ID' => $stepID,
				'COLOR' => $color,
				'IS_PASSED' => $isPassed,
				'IS_VISIBLE' => $isVisible
			);

			if($stepID === $this->currentStepID)
			{
				$this->currentColor = $color;
				$isPassed = false;
			}
		}

		$this->arResult['ITEMS'] = $items;
		$this->arResult['CURRENT_COLOR'] = $this->currentColor;
		$this->arResult['DEFAULT_BACKGROUND_COLOR'] = $this->defaultBackgroundColor;
		$this->includeComponentTemplate();
	}
}