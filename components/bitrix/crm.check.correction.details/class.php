<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Crm;
use Bitrix\Main;
use Bitrix\Catalog;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Cashbox;
use Bitrix\Sale;
use Bitrix\Crm\Security\EntityAuthorization;
use Bitrix\Crm\Security\EntityPermissionType;
use Bitrix\Crm\Component\EntityDetails\ComponentMode;

Loc::loadMessages(__FILE__);

if (!Main\Loader::includeModule('sale'))
{
	ShowError(Loc::getMessage('SALE_MODULE_NOT_INSTALLED'));
	return;
}

Main\Loader::includeModule('crm');

class CCrmCheckCorrectionDetailsComponent extends Crm\Component\EntityDetails\BaseComponent
{
	public function getEntityTypeID()
	{
		return \CCrmOwnerType::CheckCorrection;
	}

	public function executeComponent()
	{
		$this->initComponentResult();

		$this->tryToDetectMode();
		$this->setPageTitle();

		if ($this->getErrors())
		{
			$this->showErrors();
			return;
		}

		$this->initEntityInfo();
		$this->prepareFieldInfos();
		$this->initEntityConfig();
		$this->registerViewEvent();

		$this->includeComponentTemplate();
	}

	protected function initEntityInfo()
	{
		global $APPLICATION;

		$this->arResult['ENTITY_INFO'] = [
			'ENTITY_ID' => $this->entityID,
			'ENTITY_TYPE_ID' => CCrmOwnerType::CheckCorrection,
			'ENTITY_TYPE_NAME' => CCrmOwnerType::CheckCorrectionName,
			'TITLE' => $APPLICATION->GetTitle(),
			'SHOW_URL' => CCrmOwnerType::GetEntityShowPath(CCrmOwnerType::CheckCorrection, $this->entityID, false),
		];
	}

	protected function initComponentResult()
	{
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;
		if ($this->arResult['ENTITY_ID'] > 0)
		{
			$this->arResult['READ_ONLY'] = true;
		}

		$this->setEntityID($this->arResult['ENTITY_ID']);

		$this->arResult['NAME_TEMPLATE'] = empty($this->arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(["#NOBR#","#/NOBR#"], ["",""], $this->arParams['NAME_TEMPLATE']);

		$this->arResult['EXTERNAL_CONTEXT_ID'] = $this->request->get('external_context');
		if($this->arResult['EXTERNAL_CONTEXT_ID'] === null)
		{
			$this->arResult['EXTERNAL_CONTEXT_ID'] = '';
		}

		$this->arResult['ACTION_URI'] = $this->arResult['POST_FORM_URI'] = POST_FORM_ACTION_URI;
		$this->arResult['DATE_FORMAT'] = Main\Type\Date::getFormat();
		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::CheckCorrectionName.'_'.$this->arResult['ENTITY_ID'];
		$this->arResult['CONTEXT_PARAMS'] = array(
			'NAME_TEMPLATE' => $this->arResult['NAME_TEMPLATE']
		);

		$this->arResult['EDITOR_CONFIG_ID'] = isset($this->arParams['EDITOR_CONFIG_ID'])
			? $this->arParams['EDITOR_CONFIG_ID'] : 'check_correction_details';

		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID'] : "check_correction_{$this->entityID}_details";

		$this->arResult['ENTITY_DATA'] = $this->prepareEntityData();
	}

	protected function initEntityConfig()
	{
		$this->arResult['ENTITY_CONFIG'] = [
			[
				'name' => 'main',
				'title' => Loc::getMessage('CRM_CHECK_CORRECTION_SECTION_MAIN'),
				'type' => 'section',
				'elements' => [
					['name' => 'ID'],
					['name' => 'CASHBOX_ID'],
					['name' => 'SUM_WITH_CURRENCY'],
					['name' => 'STATUS_NAME'],
					['name' => 'CHECK_LINK'],
					['name' => 'TYPE'],
					['name' => 'DATE_CREATE'],
					['name' => 'CORRECTION_TYPE'],
					['name' => 'DOCUMENT_NUMBER'],
					['name' => 'DOCUMENT_DATE'],
					['name' => 'DESCRIPTION'],
				]
			],
			[
				'name' => 'sum',
				'title' => Loc::getMessage('CRM_CHECK_CORRECTION_SECTION_PAYMENT'),
				'type' => 'section',
				'elements' => [
					['name' => 'CORRECTION_PAYMENT_CASH'],
					['name' => 'CORRECTION_PAYMENT_CASHLESS'],
				]
			],
		];

		$elements = [
			['name' => 'CORRECTION_VAT_NONE']
		];
		foreach ($this->getVatList() as $vat)
		{
			$rate = (int)$vat['RATE'];
			$elements[] = ['name' => 'CORRECTION_VAT_'.$rate];
		}

		$this->arResult['ENTITY_CONFIG'][] =
		[
			'name' => 'vat',
			'title' => Loc::getMessage('CRM_CHECK_CORRECTION_SECTION_VAT'),
			'type' => 'section',
			'elements' => $elements
		];
	}

	protected function setPageTitle()
	{
		global $APPLICATION;

		if ($this->mode === ComponentMode::CREATION)
		{
			$title = Loc::getMessage('CRM_CHECK_CORRECTION_ADD_TITLE');
		}
		else
		{
			$title = Loc::getMessage(
				'CRM_CHECK_CORRECTION_TITLE',
				[
					'#ID#' => $this->arResult['ENTITY_DATA']['ID'],
					'#DATE_CREATE#' => FormatDate(Main\Type\Date::getFormat(), MakeTimeStamp($this->arResult['ENTITY_DATA']['DATE_CREATE']))
				]
			);
		}

		$APPLICATION->SetTitle($title);
	}

	protected function registerViewEvent()
	{
		if (
			$this->entityID > 0
			&& \Bitrix\Crm\Settings\HistorySettings::getCurrent()->isViewEventEnabled()
		)
		{
			CCrmEvent::RegisterViewEvent(CCrmOwnerType::OrderCheck, $this->entityID, $this->userID);
		}
	}

	protected function prepareFieldInfos()
	{
		if (isset($this->arResult['ENTITY_FIELDS']))
		{
			return $this->arResult['ENTITY_FIELDS'];
		}

		$this->arResult['ENTITY_FIELDS'] = [
			[
				'name' => 'ID',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_ID'),
				'type' => 'text',
				'editable' => false
			],
			[
				'name' => 'CASHBOX_ID',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_CASHBOX_ID'),
				'type' => 'list',
				'editable' => $this->entityID == 0,
				'data' => [
					'items' =>  $this->getCorrectionCashboxList()
				],
				'required' => true
			],
			[
				'name' => 'STATUS_NAME',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_STATUS'),
				'type' => 'text',
				'editable' => false
			],
			[
				'name' => 'SUM_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_SUM'),
				'type' => 'money',
				'editable' => false,
				'data' => [
					'affectedFields' => ['CURRENCY', 'SUM'],
					'currency' => [
						'name' => 'CURRENCY',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					],
					'amount' => 'SUM',
					'formatted' => 'FORMATTED_SUM',
					'formattedWithCurrency' => 'FORMATTED_SUM_WITH_CURRENCY'
				]
			],
			[
				'name' => 'DATE_CREATE',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_DATE_CREATE'),
				'type' => 'datetime',
				'editable' => false,
				'data' => ['enableTime' => true]
			],
			[
				'name' => 'CHECK_LINK',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_LINK'),
				'type' => 'custom',
				'editable' => false,
				'data' =>  ['view' => 'CHECK_LINK']
			],
			[
				'name' => 'TYPE',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_TYPE'),
				'type' => 'list',
				'editable' => $this->entityID == 0,
				'data' => [
					'items' =>  $this->getCheckTypeMapList()
				],
				'required' => true
			],
			[
				'name' => 'CORRECTION_TYPE',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_CORRECTION_TYPE'),
				'type' => 'list',
				'editable' => $this->entityID == 0,
				'data' => [
					'items' =>  $this->getCorrectionTypeList()
				],
				'required' => true
			],
			[
				'name' => 'DOCUMENT_NUMBER',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_DOCUMENT_NUMBER'),
				'type' => 'text',
				'editable' => $this->entityID == 0,
				'required' => true
			],
			[
				'name' => 'DOCUMENT_DATE',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_DOCUMENT_DATE'),
				'editable' => $this->entityID == 0,
				'type' => 'datetime',
				'data' => ['enableTime' => false],
				'required' => true
			],
			[
				'name' => 'DESCRIPTION',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_DESCRIPTION'),
				'type' => 'text',
				'editable' => $this->entityID == 0,
				'required' => true
			],
			[
				'name' => 'CORRECTION_PAYMENT_CASH',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_CORRECTION_PAYMENT_CASH'),
				'type' => 'money',
				'editable' => $this->entityID == 0,
				'data' => [
					'affectedFields' => ['CURRENCY', 'SUM'],
					'currency' => [
						'name' => 'CURRENCY',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					],
					'amount' => 'CORRECTION_PAYMENT_CASH',
					'formatted' => 'FORMATTED_CASH',
					'formattedWithCurrency' => 'FORMATTED_CASH_WITH_CURRENCY'
				]
			],
			[
				'name' => 'CORRECTION_PAYMENT_CASHLESS',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_CORRECTION_PAYMENT_CASHLESS'),
				'type' => 'money',
				'editable' => $this->entityID == 0,
				'data' => [
					'affectedFields' => ['CURRENCY', 'SUM'],
					'currency' => [
						'name' => 'CURRENCY',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					],
					'amount' => 'CORRECTION_PAYMENT_CASHLESS',
					'formatted' => 'FORMATTED_CASHLESS',
					'formattedWithCurrency' => 'FORMATTED_CASHLESS_WITH_CURRENCY'
				]
			],
			[
				'name' => 'CORRECTION_VAT_NONE',
				'title' => Loc::getMessage('CRM_COLUMN_CHECK_CORRECTION_CORRECTION_VAT_NONE'),
				'type' => 'text',
				'editable' => $this->entityID == 0,
			]
		];

		foreach ($this->getVatList() as $vat)
		{
			$rate = (int)$vat['RATE'];
			$this->arResult['ENTITY_FIELDS'][] = [
				'name' => 'CORRECTION_VAT_'.$rate,
				'title' => $rate.'%',
				'type' => 'text',
				'editable' => $this->entityID == 0,
			];
		}

		return $this->arResult['ENTITY_FIELDS'];
	}

	public function getVatList()
	{
		if (Main\Loader::includeModule('catalog'))
		{
			$dbRes = Catalog\VatTable::getList([
				'select' => ['RATE'],
				'filter' => ['ACTIVE' => 'Y'],
				'order' => ['SORT' => 'ASC']
			]);

			return $dbRes->fetchAll();
		}

		return [];
	}

	protected function getCorrectionTypeList()
	{
		return [
			[
				'NAME' => Loc::getMessage('CRM_CORRECTION_CHECK_TYPE_SELF'),
				'VALUE' => Cashbox\CorrectionCheck::CORRECTION_TYPE_SELF
			],
			[
				'NAME' => Loc::getMessage('CRM_CORRECTION_CHECK_TYPE_INSTRUCTION'),
				'VALUE' => Cashbox\CorrectionCheck::CORRECTION_TYPE_INSTRUCTION
			],
		];
	}

	protected function getCorrectionCashboxList()
	{
		$result = [];

		foreach (Cashbox\Manager::getListFromCache() as $item)
		{
			if ($item['ACTIVE'] !== 'Y')
			{
				continue;
			}

			$cashbox = Cashbox\Manager::getObjectById($item['ID']);
			if ($cashbox->isCorrection())
			{
				$result[] = [
					'NAME' => $cashbox->getField('NAME'),
					'VALUE' => $cashbox->getField('ID'),
				];
			}
		}

		return $result;
	}

	protected function getCheckTypeMap()
	{
		return array_filter(
			Cashbox\CheckManager::getCheckTypeMap(),
			function ($checkClass)
			{
				return is_subclass_of($checkClass, Bitrix\Sale\Cashbox\CorrectionCheck::class);
			}
		);
	}

	public function getCheckTypes($selectedType = null)
	{
		$result = [
			'CHECK_TYPES' => []
		];

		$checkTypeMap = $this->getCheckTypeMap();

		/** @var Cashbox\Check $checkClass */
		foreach ($checkTypeMap as $id => $checkClass)
		{
			$result['CHECK_TYPES'][] = [
				'VALUE' => $id,
				'NAME' => $checkClass::getName()
			];
		}

		if (
			$selectedType === null
			|| !isset($checkTypeMap[$selectedType])
		)
		{
			$selectedType = $result['CHECK_TYPES'][0]['VALUE'];
			$result['CURRENT_TYPE_NAME'] = $result['CHECK_TYPES'][0]['NAME'];
		}

		$result['CURRENT_TYPE'] = $selectedType;

		return $result;
	}

	protected function getCheckTypeMapList()
	{
		$result = [];

		foreach ($this->getCheckTypeMap() as $type => $className)
		{
			$result[] = [
				'NAME' => $className::getName(),
				'VALUE' => $type,
			];
		}

		return $result;
	}

	protected function prepareEntityData()
	{
		if ($this->arResult['ENTITY_ID'] > 0)
		{
			return $this->extractEntityDataFromCheck();
		}
		elseif ($this->request->get('payment_id'))
		{
			return $this->extractEntityDataFromPayment();
		}

		return [];
	}

	protected function extractEntityDataFromCheck()
	{
		$check = Cashbox\CheckManager::getObjectById($this->arResult['ENTITY_ID']);
		if (!$check)
		{
			return [];
		}

		$data = [
			'ID' => $check->getField('ID'),
			'DATE_CREATE' => $check->getField('DATE_CREATE'),
			'CASHBOX_ID' => $check->getField('CASHBOX_ID'),
			'CHECK_TYPE' => $check::getName(),
			'FORMATTED_SUM_WITH_CURRENCY' => CCrmCurrency::MoneyToString(
				$check->getField('SUM'),
				$check->getField('CURRENCY'),
				''
			),
			'FORMATTED_SUM' => CCrmCurrency::MoneyToString(
				$check->getField('SUM'),
				$check->getField('CURRENCY'),
				'#'
			),
			'STATUS_NAME' => Loc::getMessage('CRM_ORDER_CASHBOX_STATUS_'.$check->getField('STATUS')),
			'CHECK_LINK' => ''
		];

		$url = $check->getUrl();
		if ($url)
		{
			$data['CHECK_LINK'] = CCrmViewHelper::RenderInfo(
				$url,
				Loc::getMessage('CRM_CHECK_CORRECTION_LINK'),
				'',
				['TARGET' => '_blank']
			);
		}

		return $data;
	}

	protected function extractEntityDataFromPayment()
	{
		$result = [
			'DOCUMENT_NUMBER' => '',
			'DOCUMENT_DATE' => new Main\Type\Date(),
			'DESCRIPTION' => Loc::getMessage('CRM_CASHBOX_CHECK_CORRECTION_DESCRIPTION'),
		];

		$isForAllRows = $this->request->get('is_for_all') === 'Y';
		if ($isForAllRows)
		{
			$filter = Sale\Helpers\Admin\Correction::getFilterValues();
			$filter = Sale\Helpers\Admin\Correction::prepareFilter($filter);
			$registry = Sale\Registry::getInstance(Sale\Registry::REGISTRY_TYPE_ORDER);
			$paymentClass = $registry->getPaymentClassName();
			$queryParams = Sale\Helpers\Admin\Correction::getPaymentSelectParams($filter);
			$queryResult = $paymentClass::getList($queryParams)->fetchAll();

			$paymentIdList = array_column($queryResult, 'ID');
		}
		else
		{
			$paymentIdList = $this->request->get('payment_id');
		}

		$repository = Sale\Repository\PaymentRepository::getInstance();

		if (!$paymentIdList)
		{
			return $result;
		}

		foreach ($paymentIdList as $paymentId)
		{
			$payment = $repository->getById((int)$paymentId);
			if ($payment)
			{
				$service = $payment->getPaySystem();
				if ($service)
				{
					if ($service->isCash())
					{
						if (!isset($result['CORRECTION_PAYMENT_CASH']))
						{
							$result['CORRECTION_PAYMENT_CASH'] = 0;
						}

						$result['CORRECTION_PAYMENT_CASH'] += $payment->getSum();
					}
					else
					{
						if (!isset($result['CORRECTION_PAYMENT_CASHLESS']))
						{
							$result['CORRECTION_PAYMENT_CASHLESS'] = 0;
						}

						$result['CORRECTION_PAYMENT_CASHLESS'] += $payment->getSum();
					}
				}
				else
				{
					$result['CORRECTION_PAYMENT_CASHLESS'] = $payment->getSum();
				}

				$result['CORRECTION_VAT_NONE'] += $payment->getSum();
			}
		}

		$result['FORMATTED_CASH_WITH_CURRENCY'] = CCrmCurrency::MoneyToString(
			$result['CORRECTION_PAYMENT_CASH'],
			$payment->getField('CURRENCY'),
			''
		);

		$result['FORMATTED_CASH'] = CCrmCurrency::MoneyToString(
			$result['CORRECTION_PAYMENT_CASH'],
			$payment->getField('CURRENCY'),
			'#'
		);

		$result['FORMATTED_CASHLESS_WITH_CURRENCY'] = CCrmCurrency::MoneyToString(
			$result['CORRECTION_PAYMENT_CASHLESS'],
			$payment->getField('CURRENCY'),
			''
		);

		$result['FORMATTED_CASHLESS'] = CCrmCurrency::MoneyToString(
			$result['CORRECTION_PAYMENT_CASHLESS'],
			$payment->getField('CURRENCY'),
			'#'
		);

		$result['CURRENCY'] = $payment->getField('CURRENCY');

		return $result;
	}

	protected function tryToDetectMode()
	{
		if($this->entityID <= 0)
		{
			if(!$this->checkEntityPermission(EntityPermissionType::UPDATE))
			{
				$this->addError(Loc::getMessage('CRM_PERMISSION_DENIED'));
				return false;
			}

			$this->mode = ComponentMode::CREATION;
		}
		else
		{
			if(!$this->checkEntityPermission(EntityPermissionType::READ))
			{
				$this->addError(Loc::getMessage('CRM_PERMISSION_DENIED'));
				return false;
			}

			$this->mode = ComponentMode::VIEW;
		}

		$this->arResult['COMPONENT_MODE'] = $this->mode;

		return true;
	}
	protected function checkEntityPermission($permissionTypeID)
	{
		return EntityAuthorization::checkPermission(
			$permissionTypeID,
			\CCrmOwnerType::Order
		);
	}
}