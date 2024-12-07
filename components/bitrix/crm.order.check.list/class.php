<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm;
use Bitrix\Crm\Order;
use Bitrix\Main;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\Cashbox;

Loc::loadMessages(__FILE__);

class CCrmOrderCheckListComponent extends \CBitrixComponent
{
	const GRID_ID = 'CRM_ORDER_CHECK_LIST';

	protected $userId = 0;
	protected $orderId = 0;
	/** @var Order\Order $order*/
	protected $order = null;
	protected $userPermissions;
	protected $errors = array();
	protected $isInternal = false;

	public function onPrepareComponentParams($params)
	{
		global  $APPLICATION;

		if (!$this->checkRequiredModules())
		{
			$this->showErrors();
			return null;
		}

		$params['PATH_TO_ORDER_CHECK_SHOW'] = $params['PATH_TO_ORDER_CHECK_SHOW'] ?? '';
		$params['PATH_TO_ORDER_CHECK_SHOW'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_SHOW',
			$params['PATH_TO_ORDER_CHECK_SHOW'] ?? '',
			$APPLICATION->GetCurPage() . '?check_id=#check_id#'
		);
		$params['PATH_TO_ORDER_CHECK_EDIT'] = $params['PATH_TO_ORDER_CHECK_EDIT'] ?? '';
		$params['PATH_TO_ORDER_CHECK_EDIT'] = CrmCheckPath(
			'PATH_TO_ORDER_CHECK_EDIT',
			$params['PATH_TO_ORDER_CHECK_EDIT'] ?? '',
			$APPLICATION->GetCurPage() . '?check_id=#check_id#'
		);
		$params['OWNER_ID'] = (int)($params['OWNER_ID'] ?? 0);
		$params['OWNER_TYPE'] = (int)($params['OWNER_TYPE'] ?? 0);

		return $params;
	}

	/**
	 * @return bool
	 */
	private function checkRequiredModules(): bool
	{
		if (!Loader::includeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');

			return false;
		}

		if (!Loader::includeModule('currency'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');
			return false;
		}

		if (!Loader::includeModule('catalog'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');

			return false;
		}

		if (!Loader::includeModule('sale'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_SALE');

			return false;
		}

		return true;
	}

	protected function init()
	{
		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();
		$this->orderId = $this->getOrderId();

		if ((int)$this->orderId <= 0)
		{
			$this->errors[] = new Main\Error(Loc::getMessage('CRM_ERROR_WRONG_ORDER_ID'));
			return false;
		}

		if (!\Bitrix\Crm\Order\Permissions\Order::checkReadPermission($this->orderId, $this->userPermissions))
		{
			$this->errors[] = new Main\Error(Loc::getMessage('CRM_PERMISSION_DENIED'));
			return false;
		}

		$this->order = Order\Order::load($this->orderId);
		if (!$this->order)
		{
			$this->errors[] = new Main\Error(Loc::getMessage('CRM_ERROR_WRONG_ORDER'));
			return false;
		}

		$this->arResult['PERM']['ADD'] = \Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($this->orderId, $this->userPermissions);
		if (!empty($this->arParams['EXTERNAL_ERRORS']))
		{
			foreach ($this->arParams['EXTERNAL_ERRORS'] as $errorMessage)
			{
				$this->errors[] = new Main\Error($errorMessage);
			}

			if (!empty($this->errors))
			{
				return false;
			}
		}

		$this->userId = CCrmSecurityHelper::GetCurrentUserID();
		$this->isInternal = !empty($this->arParams['INTERNAL_FILTER']);
		CUtil::InitJSCore(array('ajax', 'tooltip'));

		return true;
	}

	protected function showErrors()
	{
		foreach($this->errors as $error)
		{
			ShowError($error);
		}
	}

	protected function addErrors(array $errors)
	{
		$this->errors = array_merge($this->errors, $errors);
	}

	protected function  addError($error)
	{
		$this->errors[] = $error;
	}

	protected function getHeaders()
	{
		return [
			array('id' => 'TITLE', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_TITLE'), 'sort' => 'ID', 'default' => true, 'editable' => false),
			array('id' => 'CHECK_TYPE', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_TYPE'), 'sort' => 'TYPE', 'default' => true, 'editable' => false),
			array('id' => 'CHECK_STATUS', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_STATUS'), 'sort' => 'STATUS', 'default' => true, 'editable' => false),
			array('id' => 'CASHBOX_NAME', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_CASHBOX_ID'), 'sort' => 'CASHBOX_ID', 'default' => true, 'editable' => false),
			...(
				CCrmSaleHelper::isWithOrdersMode()
					? [
						array('id' => 'ORDER_ID', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_ORDER_ID'), 'sort' => 'ORDER_ID', 'default' => false, 'editable' => false),
					]
					: []
			),
			array('id' => 'DATE_CREATE', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_DATE_CREATE'), 'sort' => 'DATE_CREATE', 'default' => false, 'editable' => false),
			array('id' => 'FORMATTED_SUM', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_SUM'), 'sort' => 'SUM', 'default' => true, 'editable' => false),
			array('id' => 'LINK_PARAMS', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_LINK'), 'default' => true, 'editable' => false),
			array('id' => 'PAYMENT', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_PAYMENT_DESCR'), 'sort' => 'PAYMENT_ID', 'default' => true, 'editable' => false),
			array('id' => 'SHIPMENT', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_SHIPMENT_DESCR'), 'sort' => 'SHIPMENT_ID', 'default' => true, 'editable' => false),
			array('id' => 'PAYMENT_ID', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_PAYMENT_ID'), 'sort' => 'PAYMENT_ID', 'default' => false, 'editable' => false),
			array('id' => 'SHIPMENT_ID', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CHECK_SHIPMENT_ID'), 'sort' => 'SHIPMENT_ID', 'default' => false, 'editable' => false),
		];
	}

	public function executeComponent()
	{
		global $APPLICATION;

		if (!$this->init())
		{
			$this->showErrors();

			return false;
		}

		$this->arResult['CURRENT_USER_ID'] = "";

		$currentPage = $APPLICATION->GetCurPage();
		$this->arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
		$this->arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
		$this->arResult['AJAX_MODE'] = $this->arParams['AJAX_MODE'] ?? ($this->arResult['INTERNAL'] ? 'N' : 'Y');
		$this->arResult['AJAX_ID'] = $this->arParams['AJAX_ID'] ?? '';
		$this->arResult['AJAX_OPTION_JUMP'] = $this->arParams['AJAX_OPTION_JUMP'] ?? 'N';
		$this->arResult['AJAX_OPTION_HISTORY'] = $this->arParams['AJAX_OPTION_HISTORY'] ?? 'N';
		$this->arResult['SESSION_ID'] = bitrix_sessid();
		$this->arResult['NAVIGATION_CONTEXT_ID'] = $this->arParams['NAVIGATION_CONTEXT_ID'] ?? '';
		$this->arResult['ENABLE_SLIDER'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();

		$dbRes = Cashbox\Internals\CashboxTable::getList([
			'filter' => [
				'=ACTIVE' => 'Y',
				'!ID' => Cashbox\Cashbox1C::getId()
			]
		]);
		$this->arResult['ENABLE_TOOLBAR'] = (bool)$dbRes->fetch() && $this->arResult['PERM']['ADD'];
		$this->arResult['TIME_FORMAT'] = CCrmDateTimeHelper::getDefaultDateTimeFormat();
		$this->arResult['HEADERS'] = $this->getHeaders();

		$this->arResult['OWNER_TYPE'] = ($this->arParams['OWNER_TYPE'] > 0) ? $this->arParams['OWNER_TYPE'] : \CCrmOwnerType::Order;
		$this->arResult['OWNER_ID'] = (int)($this->arParams['OWNER_ID'] ?? 0);

		if ($this->arResult['ENABLE_TOOLBAR'])
		{
			$this->arResult['PATH_TO_ORDER_CHECK_ADD'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_ORDER_CHECK_EDIT'] ?? '',
				array('check_id' => 0)
			);

			$this->arResult['PATH_TO_ORDER_CHECK_ADD'] = CHTTP::urlAddParams(
				$this->arResult['PATH_TO_ORDER_CHECK_ADD'] ?? '',
				[
					'order_id' => $this->orderId,
					'owner_type' => $this->arResult['OWNER_TYPE'],
					'owner_id' => $this->arResult['OWNER_ID'],
				]
			);
		}

		/** @var \CBitrixComponent $this */
		$this->arResult['COMPONENT_ID'] = $this->randString();
		$this->arResult['DATE_FORMAT'] = Main\Type\Date::getFormat();
		$this->arResult['FORM_ID'] = $this->arParams['FORM_ID'] ?? '';
		$this->arResult['TAB_ID'] = $this->arParams['TAB_ID'] ?? '';
		$this->arResult['GRID_ID'] = self::GRID_ID;
		$this->arResult['HEADERS'] = $this->getHeaders();
		$resultPrepared = $this->prepareItems();
		$this->arResult = array_merge($this->arResult, $resultPrepared);

		if (!empty($this->arParams['SET_TITLE']))
		{
			$this->setTitle();
		}

		$this->IncludeComponentTemplate();

		return $this->arResult['ROWS_COUNT'] ?? null;
	}

	/**
	 * @param array $fields
	 *
	 * @return array
	 */
	protected function getRows($fields = array())
	{
		$rows = array();
		$checkIds = array();
		$checkTypeMap = Cashbox\CheckManager::getCheckTypeMap();
		$cashboxList = Cashbox\Manager::getListFromCache();
		$resultCheckData = Cashbox\Internals\CashboxCheckTable::getList($fields);

		while ($check = $resultCheckData->fetch())
		{
			/** @var \Bitrix\Sale\Cashbox\Check $checkClass */
			$checkClass = $checkTypeMap[$check['TYPE']];
			$check['CHECK_TYPE'] = class_exists($checkClass) ? $checkClass::getName() : '';
			$cashboxId = $check['CASHBOX_ID'];
			$check['CASHBOX_NAME'] = $cashboxList[$cashboxId]['NAME'];
			$check['FORMATTED_SUM'] = CCrmCurrency::MoneyToString($check['SUM'], $check['CURRENCY']);
			$checkIds[] = $check['ID'];

			$check['CASHBOX_IS_CHECKABLE'] = false;

			$cashbox = \Bitrix\Sale\Cashbox\Manager::getObjectById($check['CASHBOX_ID']);
			if ($check['STATUS'] === 'P')
			{
				if ($cashbox && $cashbox->isCheckable())
				{
					$check['CASHBOX_IS_CHECKABLE'] = $cashbox->isCheckable();
				}
			}

			if ($cashbox && $check['LINK_PARAMS'])
			{
				$check['URL'] = $cashbox->getCheckLink($check['LINK_PARAMS']);
			}

			$check['CHECK_STATUS'] = Loc::getMessage('CRM_ORDER_CASHBOX_STATUS_'.$check['STATUS']);
			if (isset($check['ERROR_MESSAGE']) && $check['STATUS'] === 'E')
			{
				$check['CHECK_STATUS'] .= ' (' . $check['ERROR_MESSAGE'] . ')';
			}
			$rows[$check['ID']] = $check;
		}

		if (!empty($checkIds))
		{
			$relatedDb = Cashbox\Internals\CheckRelatedEntitiesTable::getList(array(
				'filter' => array('=CHECK_ID' => $checkIds)
			));

			while ($related = $relatedDb->fetch())
			{
				$type = null;
				if ($related['ENTITY_TYPE'] === Cashbox\Internals\CheckRelatedEntitiesTable::ENTITY_TYPE_SHIPMENT)
				{
					$type = 'SHIPMENT';
				}
				elseif ($related['ENTITY_TYPE'] === Cashbox\Internals\CheckRelatedEntitiesTable::ENTITY_TYPE_PAYMENT)
				{
					$type = 'PAYMENT';
				}

				if (!empty($type))
				{
					$rows[$related['CHECK_ID']][$type][] = $related['ENTITY_ID'];
				}
			}
		}

		return $rows;
	}

	/**
	 * @param array $filter
	 *
	 * @return int
	 */
	public function getCount(array $filter)
	{
		$rowsCount = \Bitrix\Sale\Cashbox\Internals\CashboxCheckTable::getList(array(
			'select' => array('CNT'),
			'filter' => $filter,
			'group' => 'REF.CHECK_ID',
			'runtime' => array(
				new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)'),
				new \Bitrix\Main\Entity\ReferenceField(
					'REF',
					'Bitrix\Sale\Cashbox\Internals\CheckRelatedEntitiesTable',
					['=this.ID' => 'ref.CHECK_ID'],
					['join_type' => 'left']
				)
			)
		))->fetch();

		return (int)($rowsCount['CNT'] ?? 0);
	}

	/**
	 * @return int
	 */
	protected function getOrderId()
	{
		$orderId = null;

		if ($this->arParams['OWNER_TYPE'] === CCrmOwnerType::Order)
		{
			$orderId = $this->arParams['OWNER_ID'];
		}
		elseif ($this->arParams['OWNER_TYPE'] === CCrmOwnerType::OrderPayment)
		{
			$payment = Crm\Order\Manager::getPaymentObject((int)$this->arParams['OWNER_ID']);
			if ($payment)
			{
				$orderId = $payment->getField('ORDER_ID');
			}
		}

		return (int)$orderId;
	}

	/**
	 * @return array
	 */
	protected function prepareFilter()
	{
		$filter = array();

		if ($this->arParams['OWNER_TYPE'] === CCrmOwnerType::Order)
		{
			$filter['ORDER_ID'] = $this->orderId;
		}
		elseif ($this->arParams['OWNER_TYPE'] === CCrmOwnerType::OrderPayment)
		{
			$filter = array(
				'ORDER_ID' => $this->orderId,
				array(
					'LOGIC' => 'OR',
					array(
						'REF.ENTITY_ID' => $this->arParams['OWNER_ID'],
						'REF.ENTITY_TYPE' => Cashbox\Internals\CheckRelatedEntitiesTable::ENTITY_TYPE_PAYMENT
					),
					'PAYMENT_ID' => $this->arParams['OWNER_ID']
				)
			);
		}

		return $filter;
	}

	/**
	 * @return array
	 */
	public function prepareItems()
	{
		$result = [];
		$lastPageSelected = false;
		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID']);
		$navParams = array(
			'nPageSize' => $this->arParams['CHECK_COUNT']
		);
		$navParams = $gridOptions->GetNavParams($navParams);
		$navParams['bShowAll'] = false;
		$pageSize = (int)($navParams['nPageSize'] ?? $this->arParams['CHECK_COUNT']);
		$pageNum = 1;
		$requestPage = (int)($_REQUEST['page'] ?? 0);
		if ($requestPage > 0)
		{
			$pageNum = $requestPage;
		}
		elseif ($requestPage === -1)
		{
			$lastPageSelected = true;
		}

		if (!isset($_SESSION['CRM_PAGINATION_DATA']))
		{
			$_SESSION['CRM_PAGINATION_DATA'] = [];
		}

		$filter = $this->prepareFilter();

		$_SESSION['CRM_PAGINATION_DATA'][self::GRID_ID] = array('PAGE_NUM' => $pageNum, 'PAGE_SIZE' => $pageSize);
		$_SESSION['CRM_GRID_DATA'][self::GRID_ID] = array('FILTER' => $filter);

		$nav = new \Bitrix\Main\UI\PageNavigation('checkList');
		$nav->allowAllRecords(false)->setPageSize($pageSize);

		$count = $this->getCount($filter);

		$nav->setRecordCount($count);
		$result['ROWS_COUNT'] = $count;

		if ($lastPageSelected)
		{
			$pageNum = $nav->getPageCount();
		}

		$nav->setCurrentPage($pageNum);

		$this->arResult['PAGINATION'] = array(
			'PAGE_NUM' => $pageNum,
			'ENABLE_NEXT_PAGE' => ($nav->getPageCount() > $pageNum)
		);

		$sortData = $gridOptions->GetSorting(array(
			'sort' => array('ID' => 'desc')
		));

		$order = $sortData['sort'];

		$params = array(
			'filter' => $filter,
			'offset' => $nav->getOffset(),
			'limit' => $nav->getLimit(),
			'order' => $order
		);

		if ($this->arParams['OWNER_TYPE'] === CCrmOwnerType::OrderPayment)
		{
			$params['runtime'] = array(
				new \Bitrix\Main\Entity\ReferenceField(
					'REF',
					'Bitrix\Sale\Cashbox\Internals\CheckRelatedEntitiesTable',
					['=this.ID' => 'ref.CHECK_ID'],
					['join_type' => 'left']
				)
			);
		}

		$result['ROWS'] = $this->getRows($params);

		$result['NAV_OBJECT'] = $nav;

		$paymentCollection = $this->order->getPaymentCollection();
		foreach ($paymentCollection as $payment)
		{
			$paymentId = $payment->getId();
			$result['PAYMENT_LIST'][$paymentId] = array(
				'ACCOUNT_NUMBER' => $payment->getField('ACCOUNT_NUMBER'),
				'DATE_BILL' => $payment->getField('DATE_BILL'),
				'PAY_SYSTEM_NAME' => $payment->getField('PAY_SYSTEM_NAME')
			);
		}

		$shipmentCollection = $this->order->getShipmentCollection();
		foreach ($shipmentCollection as $shipment)
		{
			if ($shipment->isSystem())
			{
				continue;
			}

			$shipmentId = $shipment->getId();
			$result['SHIPMENT_LIST'][$shipmentId] = array(
				'ACCOUNT_NUMBER' => $shipment->getField('ACCOUNT_NUMBER'),
				'DATE_INSERT' => $shipment->getField('DATE_INSERT'),
				'DELIVERY_NAME' => $shipment->getField('DELIVERY_NAME'),
			);
		}

		return $result;
	}

	private function setTitle(): void
	{
		global $APPLICATION;

		if ($this->arParams['OWNER_TYPE'] !== CCrmOwnerType::OrderPayment)
		{
			return;
		}

		$payment = Crm\Order\Manager::getPaymentObject((int)$this->arParams['OWNER_ID']);
		if (!$payment)
		{
			return;
		}

		$APPLICATION->SetTitle(Loc::getMessage(
			'CRM_ORDER_CHECK_LIST_TITLE',
			[
				'#ACCOUNT_NUMBER#' => htmlspecialcharsbx($payment->getField('ACCOUNT_NUMBER')),
				'#DATE_BILL#' => FormatDate(
					$this->arResult['DATE_FORMAT'],
					MakeTimeStamp($payment->getField('DATE_BILL'))
				),
				'#PAY_SYSTEM_NAME#' => $payment->getPaymentSystemName(),
			]
		));
	}
}
