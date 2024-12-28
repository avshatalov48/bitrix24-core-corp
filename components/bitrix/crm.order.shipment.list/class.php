<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	die();
}

use Bitrix\Crm\Order\Shipment;
use Bitrix\Crm\Service;
use Bitrix\Crm\Service\Container;
use Bitrix\Main;
use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class CCrmOrderShipmentListComponent extends \CBitrixComponent
{
	protected $userId = 0;
	protected $userPermissions;
	protected $errors = array();
	protected $isInternal = false;

	public function onPrepareComponentParams($arParams)
	{
		global  $APPLICATION;

		$arParams['PATH_TO_ORDER_SHIPMENT_SHOW'] = CrmCheckPath(
			'PATH_TO_ORDER_SHIPMENT_SHOW',
			$arParams['PATH_TO_ORDER_SHIPMENT_SHOW'] ?? '',
			$APPLICATION->GetCurPage() . '?order_id=#order_id#&show'
		);

		$arParams['PATH_TO_ORDER_SHIPMENT_EDIT'] = CrmCheckPath(
			'PATH_TO_ORDER_SHIPMENT_EDIT',
			$arParams['PATH_TO_ORDER_SHIPMENT_EDIT'] ?? '',
			$APPLICATION->GetCurPage() . '?order_id=#order_id#&edit'
		);

		$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath(
			'PATH_TO_USER_PROFILE',
			$arParams['PATH_TO_USER_PROFILE'] ?? '',
			'/company/personal/user/#user_id#/'
		);

		$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE'])
			? CSite::GetNameFormat(false)
			: str_replace(['#NOBR#', '#/NOBR#'], ['', ''], $arParams['NAME_TEMPLATE'] ?? '');

		return $arParams;
	}

	protected function init()
	{
		if (!CModule::IncludeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');

			return false;
		}

		if (!CModule::IncludeModule('currency'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');

			return false;
		}

		if (!CModule::IncludeModule('catalog'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG');

			return false;

		}

		if (!CModule::IncludeModule('sale'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_SALE');

			return false;
		}

		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();

		if (!\Bitrix\Crm\Order\Permissions\Order::checkReadPermission(0, $this->userPermissions))
		{
			$this->errors[] = new Main\Error(Loc::getMessage('CRM_PERMISSION_DENIED'));

			return false;
		}

		$this->userId = CCrmSecurityHelper::GetCurrentUserID();
		$this->isInternal = !empty($this->arParams['INTERNAL_FILTER']);
		CUtil::InitJSCore(array('ajax', 'tooltip'));

		return true;
	}

	protected function showErrors()
	{
		foreach ($this->errors as $error)
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

	protected function addUserInfoSelection($referenceFieldName, $prefix, array &$select, array &$runtime)
	{
		$runtime[] =
			new Main\Entity\ReferenceField('USER_'.$prefix,
				Main\UserTable::getEntity(),
				array(
					'=ref.ID' => 'this.'.$referenceFieldName,
				),
				array('join_type' => 'LEFT')
			);

		foreach (array('LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME') as $field)
		{
			$select[$prefix.'_'.$field] = 'USER_'.$prefix.'.'.$field;
		}
	}

	/*
	 *POST & GET actions processing
	 * and LocalRedirect
	 */
	protected function requestProcessing()
	{
		if(check_bitrix_sessid())
		{
			if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_REQUEST['action_'.$this->arResult['GRID_ID']]))
			{
				if ($_REQUEST['action_'.$this->arResult['GRID_ID']] == 'delete' && isset($_REQUEST['ID']))
				{
					$ids = array();

					if(is_array($_REQUEST['ID']))
					{
						$ids = $_REQUEST['ID'];
					}
					elseif(intval($_REQUEST['ID']) > 0)
					{
						$ids = array($_REQUEST['ID']);
					}

					unset($_REQUEST['ID']); // otherwise the filter will work

					foreach($ids as $id)
					{
						$id = (int)$id;

						if($id <= 0)
							continue;

						$shipment = \Bitrix\Crm\Order\Manager::getShipmentObject($id);

						if(!$shipment)
							continue;


						$hasDelPerm = \Bitrix\Crm\Order\Permissions\Order::checkDeletePermission($id, $this->userPermissions);

						if(!$hasDelPerm)
							continue;

						$order = $shipment->getCollection()->getOrder();
						$res = $shipment->delete();

						if(!$res->isSuccess())
						{
							foreach($res->getErrorMessages() as $error)
							{
								$this->arResult['ERRORS'][] = [
									'TITLE' => Loc::getMessage('CRM_SHIPMENT_DELETE_ERROR'),
									'TEXT' => $error
								];
							}

							continue;
						}

						$res = $order->save();

						if(!$res->isSuccess())
						{
							foreach($res->getErrorMessages() as $error)
							{
								$this->arResult['ERRORS'][] = [
									'TITLE' => Loc::getMessage('CRM_SHIPMENT_DELETE_ERROR'),
									'TEXT' => $error
								];
							}
						}
					}
				}

				if (!$this->arResult['IS_AJAX_CALL'])
					LocalRedirect('?'.$this->arParams['FORM_ID'].'_active_tab=tab_event');
			}
			else if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action_'.$this->arResult['GRID_ID']]))
			{
				if ($_GET['action_'.$this->arResult['GRID_ID']] == 'delete')
				{
					$id = (int)$_GET['ID'];

					if($id > 0)
					{
						if($shipment = \Bitrix\Crm\Order\Manager::getShipmentObject($id))
						{
							if($hasDelPerm = \Bitrix\Crm\Order\Permissions\Order::checkDeletePermission($id, $this->userPermissions))
							{
								$order = $shipment->getCollection()->getOrder();
								$res = $shipment->delete();

								if(!$res->isSuccess())
								{
									$res = $order->save();

									if(!$res->isSuccess())
									{
										foreach($res->getErrorMessages() as $error)
										{
											$this->arResult['ERRORS'][] = [
												'TITLE' => Loc::getMessage('CRM_SHIPMENT_DELETE_ERROR'),
												'TEXT' => $error
											];
										}
									}
								}
								else
								{
									foreach($res->getErrorMessages() as $error)
									{
										$this->arResult['ERRORS'][] = [
											'TITLE' => Loc::getMessage('CRM_SHIPMENT_DELETE_ERROR'),
											'TEXT' => $error
										];
									}
								}
							}
						}
					}

					unset($_GET['ID'], $_REQUEST['ID']); // otherwise the filter will work
				}

				if (!$this->arResult['IS_AJAX_CALL'])
					LocalRedirect($this->isInternal ? '?'.$this->arParams['FORM_ID'].'_active_tab='.$this->arResult['TAB_ID'] : '');
			}
		}
	}

	protected function getHeaders()
	{
		$result = array(
			array('id' => 'SHIPMENT_SUMMARY', 'name' => Loc::getMessage('CRM_COLUMN_SHIPMENT_SUMMARY'), 'sort' => 'account_number', 'default' => true, 'editable' => false),
			array('id' => 'DELIVERY_SERVICE', 'name' => Loc::getMessage('CRM_COLUMN_DELIVERY_SERVICE'), 'sort' => 'delivery_name', 'default' => true),
			array('id' => 'PRICE_DELIVERY_CURRENCY', 'name' => Loc::getMessage('CRM_COLUMN_PRICE_DELIVERY_CURRENCY'), 'sort' => 'price_delivery', 'default' => true, 'editable' => false),
			array('id' => 'ALLOW_DELIVERY', 'name' => Loc::getMessage('CRM_COLUMN_ALLOW_DELIVERY'), 'sort' => 'allow_delivery', 'default' => true, 'editable' => true),
			array('id' => 'DEDUCTED', 'name' => Loc::getMessage('CRM_COLUMN_DEDUCTED'), 'sort' => 'deducted', 'default' => true, 'editable' => false),
			array('id' => 'STATUS_ID', 'name' => Loc::getMessage('CRM_COLUMN_STATUS_ID'), 'sort' => 'status_id', 'default' => true, 'editable' => false),
			array('id' => 'ID', 'name' => Loc::getMessage('CRM_COLUMN_ID'), 'sort' => 'id', 'editable' => false, 'type' => 'int'),
			array('id' => 'ORDER_ID', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_ID'), 'sort' => 'order_id', 'default' => false, 'editable' => false),
			array('id' => 'DATE_INSERT', 'name' => Loc::getMessage('CRM_COLUMN_DATE_INSERT'), 'sort' => 'date_insert', 'editable' => false, 'type' => 'date', 'class' => 'date'),
			array('id' => 'CRM_COLUMN_DISCOUNT_PRICE', 'name' => Loc::getMessage('CRM_COLUMN_DISCOUNT_PRICE'), 'sort' => 'discount_price', 'editable' => false),
			array('id' => 'BASE_PRICE_DELIVERY', 'name' => Loc::getMessage('CRM_COLUMN_BASE_PRICE_DELIVERY'), 'sort' => 'base_price_delivery', 'editable' => false),
			array('id' => 'CUSTOM_PRICE_DELIVERY', 'name' => Loc::getMessage('CRM_COLUMN_CUSTOM_PRICE_DELIVERY'), 'sort' => false, 'editable' => false),
			array('id' => 'DATE_ALLOW_DELIVERY', 'name' => Loc::getMessage('CRM_COLUMN_DATE_ALLOW_DELIVERY'), 'sort' => false, 'editable' => false, 'type' => 'date', 'class' => 'date'),
			array('id' => 'EMP_ALLOW_DELIVERY', 'name' => Loc::getMessage('CRM_COLUMN_EMP_ALLOW_DELIVERY'), 'sort' => false, 'class' => 'username'),
			array('id' => 'DATE_DEDUCTED', 'name' => Loc::getMessage('CRM_COLUMN_DATE_DEDUCTED'), 'sort' => false, 'editable' => false, 'type' => 'date', 'class' => 'date'),
			array('id' => 'EMP_DEDUCTED', 'name' => Loc::getMessage('CRM_COLUMN_EMP_DEDUCTED'), 'sort' => false),
			array('id' => 'REASON_UNDO_DEDUCTED', 'name' => Loc::getMessage('CRM_COLUMN_REASON_UNDO_DEDUCTED'), 'sort' => false, 'editable' => false),
			array('id' => 'DELIVERY_DOC_NUM', 'name' => Loc::getMessage('CRM_COLUMN_DELIVERY_DOC_NUM'), 'sort' => false, 'editable' => false),
			array('id' => 'DELIVERY_DOC_DATE', 'name' => Loc::getMessage('CRM_COLUMN_DELIVERY_DOC_DATE'), 'sort' => 'false', 'editable' => false, 'type' => 'date', 'class' => 'date'),
			array('id' => 'TRACKING_NUMBER', 'name' => Loc::getMessage('CRM_COLUMN_TRACKING_NUMBER'),'sort' => false, 'editable' => false),
			array('id' => 'XML_ID', 'name' => 'XML_ID', 'sort' => false, 'editable' => false),
			array('id' => 'MARKED', 'name' => Loc::getMessage('CRM_COLUMN_MARKED'), 'sort' => 'marked', 'editable' => false),
			array('id' => 'DATE_MARKED', 'name' => Loc::getMessage('CRM_COLUMN_DATE_MARKED'), 'sort' => false, 'editable' => false),
			array('id' => 'EMP_MARKED', 'name' => Loc::getMessage('CRM_COLUMN_EMP_MARKED'), 'sort' => false, 'editable' => false),
			array('id' => 'REASON_MARKED', 'name' => Loc::getMessage('CRM_COLUMN_REASON_MARKED'), 'sort' => false, 'editable' => false),
			array('id' => 'CURRENCY', 'name' => Loc::getMessage('CRM_COLUMN_CURRENCY'), 'sort' => 'currency', 'editable' => false),
			array('id' => 'RESPONSIBLE', 'name' => Loc::getMessage('CRM_COLUMN_RESPONSIBLE'), 'sort' => 'responsible_id', 'editable' => false, 'class' => 'username'),
			array('id' => 'DATE_RESPONSIBLE_ID', 'name' => Loc::getMessage('CRM_COLUMN_DATE_RESPONSIBLE_ID'), 'sort' => 'date_responsible_id', 'editable' => false, 'type' => 'date', 'class' => 'date'),
			array('id' => 'COMMENTS', 'name' => Loc::getMessage('CRM_COLUMN_COMMENTS'), 'sort' => false, 'editable' => false),
			array('id' => 'TRACKING_STATUS', 'name' => Loc::getMessage('CRM_COLUMN_TRACKING_STATUS'), 'sort' => 'tracking_status', 'editable' => false),
			array('id' => 'TRACKING_DESCRIPTION', 'name' => Loc::getMessage('CRM_COLUMN_TRACKING_DESCRIPTION'), 'sort' => 'tracking_description', 'editable' => false),
		);

		return $result;
	}

	protected function createGlFilter($filter)
	{
		$result = array();
		$orderFields = Shipment::getAllFields();

		foreach($filter as $k => $v)
		{
			$name = preg_replace('/^\W+/', '', $k);

			if(isset($orderFields[$name]))
				$result[$k] = $v;
		}

		if (!isset($result['SYSTEM']))
		{
			$result['SYSTEM'] = 'N';
		}

		return $result;
	}

	/**
	 * @param array $filter
	 *
	 * @return int
	 */
	public function getCount(array $filter)
	{
		$filter = $this->createGlFilter($filter);
		$rowsCount = Shipment::getList(
			array(
				'filter' => $filter,
				'select' => array('CNT'),
				'runtime' => array(
					new \Bitrix\Main\Entity\ExpressionField('CNT', 'COUNT(1)'),
				)
			)
		)->fetch();

		return (int)$rowsCount['CNT'];
	}

	public function executeComponent()
	{
		global $APPLICATION, $USER;

		if(!$this->init())
		{
			$this->showErrors();
			return false;
		}

		$this->arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
		$this->arResult['PATH_TO_ORDER_SHIPMENT_LIST'] = $this->arParams['PATH_TO_ORDER_SHIPMENT_LIST'] = CrmCheckPath(
			'PATH_TO_ORDER_SHIPMENT_LIST',
			$this->arParams['PATH_TO_ORDER_SHIPMENT_LIST'] ?? '',
			$APPLICATION->GetCurPage()
		);

		$this->arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
		$this->arResult['SESSION_ID'] = bitrix_sessid();
		$this->arResult['NAVIGATION_CONTEXT_ID'] = $this->arParams['NAVIGATION_CONTEXT_ID'] ?? '';
		$this->arResult['PRESERVE_HISTORY'] = $this->arParams['PRESERVE_HISTORY'] ?? false;
		$this->arResult['STATUS_LIST'] = [];
		$this->arResult['ERRORS'] = [];

		$statusList = \Bitrix\Crm\Order\DeliveryStatus::getListInCrmFormat();
		foreach ($statusList as $status)
		{
			$this->arResult['STATUS_LIST'][$status['STATUS_ID']] = htmlspecialcharsbx($status['NAME']);
		}

		$this->arResult['ENABLE_SLIDER'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();
		$this->arResult['PATH_TO_ORDER_SHIPMENT_DELETE'] =  CHTTP::urlAddParams($this->arParams['PATH_TO_ORDER_SHIPMENT_LIST'], array('sessid' => bitrix_sessid()));
		$this->arResult['TIME_FORMAT'] = CCrmDateTimeHelper::getDefaultDateTimeFormat();

		$this->arResult['CALL_LIST_UPDATE_MODE'] = isset($_REQUEST['call_list_context']) && isset($_REQUEST['call_list_id']) && IsModuleInstalled('voximplant');
		$this->arResult['CALL_LIST_CONTEXT'] = (string)($_REQUEST['call_list_context'] ?? '');
		$this->arResult['CALL_LIST_ID'] = (int)($_REQUEST['call_list_id'] ?? 0);

		if($this->arResult['CALL_LIST_UPDATE_MODE'])
		{
			AddEventHandler('crm', 'onCrmOrderListItemBuildMenu', array('\Bitrix\Crm\CallList\CallList', 'handleOnCrmOrderListItemBuildMenu'));
		}

		$arSort = array();
		$runtime = array();
		$this->arResult['FORM_ID'] = $this->arParams['FORM_ID'] ?? '';
		$this->arResult['TAB_ID'] = $this->arParams['TAB_ID'] ?? '';
		$this->arResult['INTERNAL'] = $this->isInternal;

		if (!empty($this->arParams['INTERNAL_FILTER']) && is_array($this->arParams['INTERNAL_FILTER']))
		{
			if(empty($this->arParams['GRID_ID_SUFFIX']))
			{
				$this->arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null? mb_strtoupper($this->GetParent()->GetName()) : '';
			}
		}

		if (!empty($this->arParams['INTERNAL_SORT']) && is_array($this->arParams['INTERNAL_SORT']))
		{
			$arSort = $this->arParams['INTERNAL_SORT'];
		}

		$this->arResult['IS_EXTERNAL_FILTER'] = false;
		$this->arResult['GRID_ID'] = 'CRM_ORDER_SHIPMENT_SHIPMENT_LIST_V12'.($this->isInternal && !empty($this->arParams['GRID_ID_SUFFIX']) ? '_'.$this->arParams['GRID_ID_SUFFIX'] : '');
		$this->arResult['PERMS']['ADD'] = \Bitrix\Crm\Order\Permissions\Shipment::checkCreatePermission($this->userPermissions);
		$this->arResult['PERMS']['WRITE'] = \Bitrix\Crm\Order\Permissions\Shipment::checkUpdatePermission(0, $this->userPermissions);
		$this->arResult['PERMS']['DELETE'] = \Bitrix\Crm\Order\Permissions\Shipment::checkDeletePermission(0, $this->userPermissions);
		$this->arResult['AJAX_MODE'] = isset($this->arParams['AJAX_MODE']) ? $this->arParams['AJAX_MODE'] : ($this->arResult['INTERNAL'] ? 'N' : 'Y');
		$this->arResult['AJAX_ID'] = isset($this->arParams['AJAX_ID']) ? $this->arParams['AJAX_ID'] : '';
		$this->arResult['AJAX_OPTION_JUMP'] = isset($this->arParams['AJAX_OPTION_JUMP']) ? $this->arParams['AJAX_OPTION_JUMP'] : 'N';
		$this->arResult['AJAX_OPTION_HISTORY'] = isset($this->arParams['AJAX_OPTION_HISTORY']) ? $this->arParams['AJAX_OPTION_HISTORY'] : 'N';

		$this->arResult['HEADERS'] = $this->getHeaders();

		$filter = isset($this->arParams['INTERNAL_FILTER']) && is_array($this->arParams['INTERNAL_FILTER'])
			? $this->arParams['INTERNAL_FILTER']
			: array();

		$orderShipmentCount = (int)($this->arParams['ORDER_SHIPMENT_COUNT'] ?? 0);
		if ($orderShipmentCount <= 0)
		{
			$this->arParams['ORDER_SHIPMENT_COUNT'] = 20;
		}

		$arNavParams = array(
			'nPageSize' => $this->arParams['ORDER_SHIPMENT_COUNT']
		);

		$this->arResult['FILTER_PRESETS'] = array();
		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID'], $this->arResult['FILTER_PRESETS']);
		$arNavParams = $gridOptions->GetNavParams($arNavParams);
		$arNavParams['bShowAll'] = false;

		// POST & GET actions processing -->
		$this->requestProcessing();

		$_arSort = $gridOptions->GetSorting(array(
			'sort' => array('date_create' => 'desc'),
			'vars' => array('by' => 'by', 'order' => 'order')
		));

		$this->arResult['SORT'] = !empty($arSort) ? $arSort : $_arSort['sort'];
		$this->arResult['SORT_VARS'] = $_arSort['vars'];
		$visibleColumns = $gridOptions->GetVisibleColumns();

		if (empty($visibleColumns))
		{
			foreach ($this->arResult['HEADERS'] as $arHeader)
			{
				if (!empty($arHeader['default']))
				{
					$visibleColumns[] = $arHeader['id'];
				}
			}
		}

		$arSelect = array_intersect($visibleColumns, Shipment::getAllFields());

		// Fill in default values if empty
		if (empty($arSelect))
		{
			foreach ($this->arResult['HEADERS'] as $arHeader)
			{
				if ($arHeader['default'])
				{
					$arSelect[] = $arHeader['id'];
				}
			}

			$arSelect = array_intersect($arSelect, Shipment::getAllFields());
		}

		if(!in_array('ACCOUNT_NUMBER', $arSelect, true))
			$arSelect[] = 'ACCOUNT_NUMBER';

		if(!in_array('DATE_INSERT', $arSelect, true))
			$arSelect[] = 'DATE_INSERT';

		if(!in_array('REASON_CANCELED', $arSelect, true))
			$arSelect[] = 'REASON_CANCELED';

		foreach($visibleColumns as $item)
		{
			if(mb_substr($item, -9) == '_CURRENCY')
			{
				if(!in_array('CURRENCY', $arSelect))
					$arSelect[] = 'CURRENCY';

				$arSelect[] = mb_substr($item, 0, -9);
			}
		}

		if(in_array('RESPONSIBLE', $visibleColumns, true))
		{
			$arSelect[] = 'RESPONSIBLE_ID';
			$this->addUserInfoSelection('RESPONSIBLE_ID', 'RESPONSIBLE', $arSelect, $runtime);
		}

		if(in_array('EMP_ALLOW_DELIVERY', $visibleColumns, true))
		{
			$arSelect[] = 'EMP_ALLOW_DELIVERY_ID';
			$this->addUserInfoSelection('EMP_ALLOW_DELIVERY_ID', 'EMP_ALLOW_DELIVERY', $arSelect, $runtime);
		}

		if(in_array('EMP_DEDUCTED', $visibleColumns, true))
		{
			$arSelect[] = 'EMP_DEDUCTED_ID';
			$this->addUserInfoSelection('EMP_DEDUCTED_ID', 'EMP_DEDUCTED', $arSelect, $runtime);
		}

		if(in_array('EMP_RESPONSIBLE', $visibleColumns, true))
		{
			$arSelect[] = 'EMP_RESPONSIBLE_ID';
			$this->addUserInfoSelection('EMP_RESPONSIBLE_ID', 'EMP_RESPONSIBLE', $arSelect, $runtime);
		}

		if(in_array('EMP_MARKED', $visibleColumns, true))
		{
			$arSelect[] = 'EMP_MARKED_ID';
			$this->addUserInfoSelection('EMP_MARKED_ID', 'EMP_MARKED', $arSelect, $runtime);
		}

		// Always need to remove the menu items
		if (!in_array('STATUS_ID', $arSelect))
			$arSelect[] = 'STATUS_ID';

		// ID must present in select
		if(!in_array('ID', $arSelect))
			$arSelect[] = 'ID';

		$nTopCount = false;

		if($nTopCount > 0)
			$arNavParams['nTopCount'] = $nTopCount;

		// HACK: Make custom sort for RESPONSIBLE_BY field
		$arSort = $this->arResult['SORT'];

		if(isset($arSort['responsible_by']))
		{
			$arSort['responsible_by_last_name'] = $arSort['responsible_by'];
			$arSort['responsible_by_name'] = $arSort['responsible_by'];
			$arSort['responsible_by_login'] = $arSort['responsible_by'];
			unset($arSort['responsible_by']);
		}

		$arOptions = $arExportOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));

		if(isset($this->arParams['IS_EXTERNAL_CONTEXT']))
			$arOptions['IS_EXTERNAL_CONTEXT'] = $this->arParams['IS_EXTERNAL_CONTEXT'];

		//FIELD_OPTIONS
		$arSelect = array_unique($arSelect, SORT_STRING);

		$this->arResult['ORDER_SHIPMENT'] = array();
		$this->arResult['ORDER_SHIPMENT_ID'] = array();

		//region Navigation data initialization
		$pageNum = 0;
		$pageSize = (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $this->arParams['ORDER_SHIPMENT_COUNT']);
		$enableNextPage = false;

		if(isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y')
		{
			$pageNum = 1;
		}
		elseif($pageSize > 0 && isset($_REQUEST['page']))
		{
			$lastPageSelected = false;
			$pageNum = 1;
			if ((int)($_REQUEST['page'])>0)
			{
				$pageNum = (int)$_REQUEST['page'];
			}
			elseif ((int)($_REQUEST['page']) < 0)
			{
				$lastPageSelected = true;
			}

			$nav = new \Bitrix\Main\UI\PageNavigation('orderShipmentList');
			$nav->allowAllRecords(false)
				->setPageSize($pageSize);
			$count = $this->getCount($filter);
			$nav->setRecordCount($count);
			if ($lastPageSelected)
			{
				$pageNum = $nav->getPageCount();
			}
		}

		if($pageNum > 0)
		{
			if(!isset($_SESSION['CRM_PAGINATION_DATA']))
			{
				$_SESSION['CRM_PAGINATION_DATA'] = array();
			}
			$_SESSION['CRM_PAGINATION_DATA'][$this->arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum, 'PAGE_SIZE' => $pageSize);
		}
		else
		{
			if(!$this->isInternal
				&& !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
				&& isset($_SESSION['CRM_PAGINATION_DATA'])
				&& isset($_SESSION['CRM_PAGINATION_DATA'][$this->arResult['GRID_ID']])
			)
			{
				$paginationData = $_SESSION['CRM_PAGINATION_DATA'][$this->arResult['GRID_ID']];
				if(isset($paginationData['PAGE_NUM'])
					&& isset($paginationData['PAGE_SIZE'])
					&& $paginationData['PAGE_SIZE'] == $pageSize
				)
				{
					$pageNum = (int)$paginationData['PAGE_NUM'];
				}
			}

			if($pageNum <= 0)
			{
				$pageNum  = 1;
			}
		}
		//endregion

		if(!isset($arSort['nearest_activity']))
		{
			if (isset($arNavParams['nTopCount']))
			{
				$limit = $arNavParams['nTopCount'];
			}
			else
			{
				$limit = $pageSize + 1;
				$offset = $pageSize * ($pageNum - 1);
			}

			foreach($filter as $k => $v)
			{
				if (preg_match('/(.*)_from$/iu', $k, $arMatch))
				{
					\Bitrix\Crm\UI\Filter\Range::prepareFrom($filter, $arMatch[1], $v);
				}
				elseif (preg_match('/(.*)_to$/iu', $k, $arMatch))
				{
					if ($v != ''
							&& ($arMatch[1] == 'DATE_INSERT'
								|| $arMatch[1] == 'DATE_ALLOW_DELIVERY'
								|| $arMatch[1] == 'DATE_DEDUCTED'
								|| $arMatch[1] == 'DATE_RESPONSIBLE_ID'
								|| $arMatch[1] == 'DATE_MARKED')
							&& !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/u', $v))
					{
						$v = CCrmDateTimeHelper::SetMaxDayTime($v);
					}

					\Bitrix\Crm\UI\Filter\Range::prepareTo($filter, $arMatch[1], $v);
				}
			}

			$glFilter = $this->createGlFilter($filter);

			if(in_array('DELIVERY_SERVICE', $visibleColumns, true))
			{
				$arSelect['DELIVERY_SERVICE_NAME'] = 'DELIVERY.NAME';
				$arSelect['DELIVERY_SERVICE_LOGOTIP'] = 'DELIVERY.LOGOTIP';
			}

			$glFilter['SYSTEM'] = 'N';

			$glParams = array(
				'filter' => $glFilter,
				//'order' => $arSort,
				'select' => $arSelect
			);

			if (!empty($limit))
			{
				$glParams['limit'] = $limit;
			}

			if (!empty($offset))
			{
				$glParams['offset'] = $offset;
			}

			if(!empty($runtime))
			{
				$glParams['runtime'] = $runtime;
			}

			$dbResult = Shipment::getList($glParams);

			$qty = 0;
			while($arOrderShipment = $dbResult->fetch())
			{
				if($pageSize > 0 && ++$qty > $pageSize)
				{
					$enableNextPage = true;
					break;
				}

				$this->arResult['ORDER_SHIPMENT'][$arOrderShipment['ID']] = $arOrderShipment;
				$this->arResult['ORDER_SHIPMENT_ID'][$arOrderShipment['ID']] = $arOrderShipment['ID'];
			}

			//region Navigation data storing
			$this->arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
			$this->arResult['DB_FILTER'] = $filter;

			if(!isset($_SESSION['CRM_GRID_DATA']))
			{
				$_SESSION['CRM_GRID_DATA'] = array();
			}
			$_SESSION['CRM_GRID_DATA'][$this->arResult['GRID_ID']] = array('FILTER' => $filter);
			//endregion
		}
		else
		{
			$navListOptions = array_merge(
				$arOptions,
				array('QUERY_OPTIONS' => array('LIMIT' => $pageSize + 1, 'OFFSET' => $pageSize * ($pageNum - 1)))
			);

			$navDbResult = CCrmActivity::GetEntityList(
				CCrmOwnerType::Order,
				$this->userId,
				$arSort['nearest_activity'],
				$filter,
				false,
				$navListOptions
			);

			$qty = 0;
			while($arOrderShipment = $navDbResult->Fetch())
			{
				if($pageSize > 0 && ++$qty > $pageSize)
				{
					$enableNextPage = true;
					break;
				}

				$this->arResult['ORDER_SHIPMENT'][$arOrderShipment['ID']] = $arOrderShipment;
				$this->arResult['ORDER_SHIPMENT_ID'][$arOrderShipment['ID']] = $arOrderShipment['ID'];
			}

			//region Navigation data storing
			$this->arResult['PAGINATION'] = array('PAGE_NUM' => $pageNum, 'ENABLE_NEXT_PAGE' => $enableNextPage);
			$this->arResult['DB_FILTER'] = $filter;
			if(!isset($_SESSION['CRM_GRID_DATA']))
			{
				$_SESSION['CRM_GRID_DATA'] = array();
			}
			$_SESSION['CRM_GRID_DATA'][$this->arResult['GRID_ID']] = array('FILTER' => $filter);
			//endregion
		}

		$this->arResult['PAGINATION']['URL'] = $APPLICATION->GetCurPageParam('', array('apply_filter', 'clear_filter', 'save', 'page', 'sessid', 'internal'));
		$orderIds = $orderPermissions = array();

		foreach ($this->arResult['ORDER_SHIPMENT'] as $shipment)
		{
			if (isset($shipment['ORDER_ID']))
			{
				$orderIds[] = $shipment['ORDER_ID'];
			}
		}

		if (!empty($orderIds) && !(is_object($USER) && $USER->IsAdmin()))
		{
			$orderAttrs = \Bitrix\Crm\Order\Permissions\Order::getPermissionAttributes($orderIds);

			foreach ($orderIds as $orderId)
			{
				$orderPermissions[$orderId]['EDIT'] = \Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission(
					$orderId,
					$this->userPermissions,
					array('ENTITY_ATTRS' => $orderAttrs)
				);

				$orderPermissions[$orderId]['DELETE'] = \Bitrix\Crm\Order\Permissions\Order::checkDeletePermission(
					$orderId,
					$this->userPermissions,
					array('ENTITY_ATTRS' => $orderAttrs)
				);
			}
		}

		foreach($this->arResult['ORDER_SHIPMENT'] as &$arOrderShipment)
		{
			$entityID = $arOrderShipment['ID'];
			$arOrderShipment['DATE_INSERT'] = !empty($arOrderShipment['DATE_INSERT']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arOrderShipment['DATE_INSERT']), 'SHORT', SITE_ID)) : '';
			$arOrderShipment['DATE_ALLOW_DELIVERY'] = !empty($arOrderShipment['DATE_ALLOW_DELIVERY']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arOrderShipment['DATE_ALLOW_DELIVERY']), 'SHORT', SITE_ID)) : '';
			$arOrderShipment['DATE_DEDUCTED'] = !empty($arOrderShipment['DATE_DEDUCTED']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arOrderShipment['DATE_DEDUCTED']), 'SHORT', SITE_ID)) : '';
			$arOrderShipment['DATE_MARKED'] = !empty($arOrderShipment['DATE_MARKED']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arOrderShipment['DATE_MARKED']), 'SHORT', SITE_ID)) : '';
			$arOrderShipment['DATE_RESPONSIBLE_ID'] = !empty($arOrderShipment['DATE_RESPONSIBLE_ID']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arOrderShipment['DATE_RESPONSIBLE_ID']), 'SHORT', SITE_ID)) : '';

			$currencyID = $arOrderShipment['CURRENCY'] ?? CCrmCurrency::GetBaseCurrencyID();
			$arOrderShipment['CURRENCY'] = htmlspecialcharsbx($currencyID);
			$arOrderShipment['PATH_TO_ORDER_SHIPMENT_DETAILS'] = Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()->getShipmentDetailsLink(
				$entityID,
				Service\Sale\EntityLinkBuilder\Context::getShopAreaContext()
			);

			$arOrderShipment['PATH_TO_ORDER_SHIPMENT_DETAILS'] = CHTTP::urlAddParams(
				$arOrderShipment['PATH_TO_ORDER_SHIPMENT_DETAILS'] ?? '',
				['order_id' => (int)($this->arParams['INTERNAL_FILTER']['ORDER_ID'] ?? 0)]
			);

			$arOrderShipment['PATH_TO_ORDER_SHIPMENT_SHOW'] = $arOrderShipment['PATH_TO_ORDER_SHIPMENT_DETAILS'] ?? '';
			$arOrderShipment['PATH_TO_ORDER_SHIPMENT_EDIT'] = CCrmUrlUtil::AddUrlParams(
				$arOrderShipment['PATH_TO_ORDER_SHIPMENT_DETAILS'] ?? '',
				array('init_mode' => 'edit')
			);

			$arOrderShipment['PATH_TO_ORDER_SHIPMENT_DELETE'] =  CHTTP::urlAddParams(
				$this->isInternal
					? $APPLICATION->GetCurPage()
					: ($this->arParams['PATH_TO_ORDER_SHIPMENT_LIST'] ?? ''),
				array('action_'.$this->arResult['GRID_ID'] => 'delete', 'ID' => $entityID, 'sessid' => $this->arResult['SESSION_ID'])
			);

			$arOrderShipment['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_USER_PROFILE'] ?? '',
				array('user_id' => (int)($arOrderShipment['RESPONSIBLE_ID'] ?? 0))
			);

			$arOrderShipment['STATUS_ID'] = $arOrderShipment['STATUS_ID'] ?? '';

			$arOrderShipment['RESPONSIBLE'] = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'] ?? '',
				array(
					'LOGIN' => $arOrderShipment['RESPONSIBLE_LOGIN'] ?? '',
					'NAME' => $arOrderShipment['RESPONSIBLE_NAME'] ?? '',
					'LAST_NAME' => $arOrderShipment['RESPONSIBLE_LAST_NAME'] ?? '',
					'SECOND_NAME' => $arOrderShipment['RESPONSIBLE_SECOND_NAME'] ?? '',
				),
				true
			);

			$arOrderShipment['EMP_ALLOW_DELIVERY'] = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'] ?? '',
				array(
					'LOGIN' => $arOrderShipment['EMP_ALLOW_DELIVERY_LOGIN'] ?? '',
					'NAME' => $arOrderShipment['EMP_ALLOW_DELIVERY_NAME'] ?? '',
					'LAST_NAME' => $arOrderShipment['EMP_ALLOW_DELIVERY_LAST_NAME'] ?? '',
					'SECOND_NAME' => $arOrderShipment['EMP_ALLOW_DELIVERY_SECOND_NAME'] ?? '',
				),
				true
			);

			$arOrderShipment['EMP_DEDUCTED'] = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'] ?? '',
				array(
					'LOGIN' => $arOrderShipment['EMP_DEDUCTED_LOGIN'] ?? '',
					'NAME' => $arOrderShipment['EMP_DEDUCTED_NAME'] ?? '',
					'LAST_NAME' => $arOrderShipment['EMP_DEDUCTED_LAST_NAME'] ?? '',
					'SECOND_NAME' => $arOrderShipment['EMP_DEDUCTED_SECOND_NAME'] ?? '',
				),
				true
			);

			$arOrderShipment['EMP_MARKED'] = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'] ?? '',
				array(
					'LOGIN' => $arOrderShipment['EMP_MARKED_LOGIN'] ?? '',
					'NAME' => $arOrderShipment['EMP_MARKED_NAME'] ?? '',
					'LAST_NAME' => $arOrderShipment['EMP_MARKED_LAST_NAME'] ?? '',
					'SECOND_NAME' => $arOrderShipment['EMP_MARKED_SECOND_NAME'] ?? '',
				),
				true
			);

			$arOrderShipment['EMP_RESPONSIBLE'] = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'] ?? '',
				array(
					'LOGIN' => $arOrderShipment['EMP_RESPONSIBLE_LOGIN'] ?? '',
					'NAME' => $arOrderShipment['EMP_RESPONSIBLE_NAME'] ?? '',
					'LAST_NAME' => $arOrderShipment['EMP_RESPONSIBLE_LAST_NAME'] ?? '',
					'SECOND_NAME' => $arOrderShipment['EMP_RESPONSIBLE_SECOND_NAME'] ?? '',
				),
				true
			);

			$arOrderShipment['SHIPMENT_SUMMARY'] = Loc::getMessage(
				'CRM_SHIPMENT_SUMMARY',
				array(
					'#NUMBER#' => htmlspecialcharsbx($arOrderShipment['ACCOUNT_NUMBER']),
					'#DATE#' => $arOrderShipment['DATE_INSERT']
			));
			$arOrderShipment['PRICE_DELIVERY_CURRENCY'] = CCrmCurrency::MoneyToString(
				$arOrderShipment['PRICE_DELIVERY'] ?? 0.0,
				$arOrderShipment['CURRENCY']
			);
			$arOrderShipment['BASE_PRICE_DELIVERY'] = CCrmCurrency::MoneyToString(
				$arOrderShipment['BASE_PRICE_DELIVERY'] ?? 0.0,
				$arOrderShipment['CURRENCY']
			);

			if (
				isset($arOrderShipment['DELIVERY_SERVICE_LOGOTIP'])
				&& intval($arOrderShipment['DELIVERY_SERVICE_LOGOTIP']) > 0
			)
			{
				$arOrderShipment['DELIVERY_SERVICE_LOGOTIP'] = \CFile::GetPath($arOrderShipment['DELIVERY_SERVICE_LOGOTIP']);
			}

			foreach($arOrderShipment as $name => $field)
			{
				if ($name[0] !== '~')
				{
					$arOrderShipment['~'.$name] = $field;
				}
			}

			$arOrderShipment['DELETE'] = $arOrderShipment['EDIT'] = !$arOrderShipment['INTERNAL'];

			if (isset($arOrderShipment['ORDER_ID'], $orderPermissions[$arOrderShipment['ORDER_ID']]))
			{
				$arOrderShipment['EDIT'] = $orderPermissions[$arOrderShipment['ORDER_ID']]['EDIT'];
				$arOrderShipment['DELETE'] = $orderPermissions[$arOrderShipment['ORDER_ID']]['DELETE'];
			}
			$this->arResult['ORDER_SHIPMENT'][$entityID] = $arOrderShipment;
		}
		unset($arOrderShipment);

		$this->arResult['ENABLE_TOOLBAR'] = (isset($this->arParams['ENABLE_TOOLBAR']) && $this->arResult['PERMS']['ADD']) ? $this->arParams['ENABLE_TOOLBAR'] : false;

		if($this->arResult['ENABLE_TOOLBAR'])
		{
			$this->arResult['PATH_TO_ORDER_SHIPMENT_ADD'] = Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()->getShipmentDetailsLink(
				0,
				Service\Sale\EntityLinkBuilder\Context::getShopAreaContext()
			);

			$this->arResult['PATH_TO_ORDER_SHIPMENT_ADD'] = CHTTP::urlAddParams(
				$this->arResult['PATH_TO_ORDER_SHIPMENT_ADD'] ?? '',
				array('order_id' => (int)$this->arParams['INTERNAL_FILTER']['ORDER_ID'])
			);
		}

		$this->arResult['NEED_FOR_REBUILD_ORDER_SHIPMENT_ATTRS'] =
			$this->arResult['NEED_FOR_REBUILD_ORDER_SHIPMENT_SEMANTICS'] =
			$this->arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] =
			$this->arResult['NEED_FOR_BUILD_TIMELINE'] = false;

		if(!$this->isInternal)
		{
			if(COption::GetOptionString('crm', '~CRM_REBUILD_ORDER_SHIPMENT_SEARCH_CONTENT', 'N') === 'Y')
			{
				$this->arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = true;
			}

			$this->arResult['NEED_FOR_REFRESH_ACCOUNTING'] = \Bitrix\Crm\Agent\Accounting\OrderAccountSyncAgent::getInstance()->isEnabled();

			if(CCrmPerms::IsAdmin())
			{
				if(COption::GetOptionString('crm', '~CRM_REBUILD_ORDER_SHIPMENT_ATTR', 'N') === 'Y')
				{
					$this->arResult['PATH_TO_PRM_LIST'] = (string)Container::getInstance()->getRouter()->getPermissionsUrl();;
					$this->arResult['NEED_FOR_REBUILD_ORDER_SHIPMENT_ATTRS'] = true;
				}
				if(COption::GetOptionString('crm', '~CRM_REBUILD_ORDER_SHIPMENT_SEMANTICS', 'N') === 'Y')
				{
					$this->arResult['NEED_FOR_REBUILD_ORDER_SHIPMENT_SEMANTICS'] = true;
				}
			}
		}

		$this->IncludeComponentTemplate();

		include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.order/include/nav.php');

		return $this->arResult['ROWS_COUNT'] ?? null;
	}
}
