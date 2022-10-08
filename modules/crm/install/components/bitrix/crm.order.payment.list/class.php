<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main;
use Bitrix\Crm\Order\Payment;
use Bitrix\Crm\Order\Manager;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Settings\LayoutSettings;
use Bitrix\Crm\WebForm\Manager as WebFormManager;
use Bitrix\Crm\Service;

Loc::loadMessages(__FILE__);

class CCrmOrderPaymentListComponent extends \CBitrixComponent
{
	protected $userId = 0;
	protected $userPermissions;
	protected $errors = array();
	protected $isInternal = false;

	public function onPrepareComponentParams($arParams)
	{
		global $APPLICATION;

		$arParams['PATH_TO_ORDER_PAYMENT_SHOW'] = CrmCheckPath('PATH_TO_ORDER_PAYMENT_SHOW', $arParams['PATH_TO_ORDER_PAYMENT_SHOW'], $APPLICATION->GetCurPage().'?payment_id=#payment_id#&show');
		$arParams['PATH_TO_ORDER_PAYMENT_EDIT'] = CrmCheckPath('PATH_TO_ORDER_PAYMENT_EDIT', $arParams['PATH_TO_ORDER_PAYMENT_EDIT'], $APPLICATION->GetCurPage().'?payment_id=#payment_id#&edit');
		$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
		$arParams['PATH_TO_BUYER_PROFILE'] = CrmCheckPath('PATH_TO_BUYER_PROFILE', $arParams['PATH_TO_BUYER_PROFILE'], 'shop/settings/sale_buyers_profile/?USER_ID=#user_id#');
		$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);
		$arParams['SHOW_ROW_CHECKBOXES'] = empty($arParams['SHOW_ROW_CHECKBOXES']) ? false : $arParams['SHOW_ROW_CHECKBOXES'];
		$arParams['SALESCENTER_MODE'] = empty($arParams['SALESCENTER_MODE']) ? false : $arParams['SALESCENTER_MODE'];

		return $arParams;
	}

	protected function init()
	{
		if(!CModule::IncludeModule('crm'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED');
			return false;
		}

		if(!CModule::IncludeModule('currency'))
		{
			$this->errors[] = Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY');
			return false;
		}

		if(!CModule::IncludeModule('catalog'))
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
		foreach($this->errors as $error)
			ShowError($error);
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

		foreach(array('LOGIN', 'NAME', 'LAST_NAME', 'SECOND_NAME') as $field)
			$select[$prefix.'_'.$field] = 'USER_'.$prefix.'.'.$field;
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
				if ($_REQUEST['action_'.$this->arResult['GRID_ID']] == 'delete')
				{
					$ids = array();

					if(isset($_REQUEST['ID']))
					{
						if(is_array($_REQUEST['ID']))
						{
							$ids = $_REQUEST['ID'];
						}
						elseif(intval($_REQUEST['ID']) > 0)
						{
							$ids = array($_REQUEST['ID']);
						}

						unset($_REQUEST['ID']); // otherwise the filter will work
					}

					foreach($ids as $id)
					{
						$id = (int)$id;

						if($id <= 0)
							continue;

						$hasDelPerm = \Bitrix\Crm\Order\Permissions\Order::checkDeletePermission($id, $this->userPermissions);

						if(!$hasDelPerm)
							continue;

						$payment = Manager::getPaymentObject($id);

						if(!$payment)
							continue;

						$order = $payment->getCollection()->getOrder();
						$res = $payment->delete();

						if(!$res->isSuccess())
						{
							foreach($res->getErrorMessages() as $error)
							{
								$this->arResult['ERRORS'][] = [
									'TITLE' => Loc::getMessage('CRM_PAYMENT_DELETE_ERROR'),
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
									'TITLE' => Loc::getMessage('CRM_PAYMENT_DELETE_ERROR'),
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
						if($payment = Manager::getPaymentObject($id))
						{
							if($hasDelPerm = \Bitrix\Crm\Order\Permissions\Order::checkDeletePermission($id, $this->userPermissions))
							{
								$order = $payment->getCollection()->getOrder();
								$res = $payment->delete();

								if($res->isSuccess())
								{
									$res = $order->save();

									if(!$res->isSuccess())
									{
										foreach($res->getErrorMessages() as $error)
										{
											$this->arResult['ERRORS'][] = [
												'TITLE' => Loc::getMessage('CRM_PAYMENT_DELETE_ERROR'),
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
											'TITLE' => Loc::getMessage('CRM_PAYMENT_DELETE_ERROR'),
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
			array('id' => 'PAYMENT_SUMMARY', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_PAYMENT_SUMMARY'), 'sort' => 'id', 'default' => true, 'editable' => false),
			array('id' => 'ID', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_ID'), 'sort' => 'id', 'editable' => false, 'type' => 'int'),
			array('id' => 'ORDER_ID', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_ORDER_ID'), 'sort' => 'order_id', 'default' => false, 'editable' => false),
			array('id' => 'ACCOUNT_NUMBER', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_ACCOUNT_NUMBER'), 'sort' => 'account_number', 'default' => false, 'editable' => false),
			array('id' => 'DATE_BILL', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_DATE_BILL'), 'sort' => 'date_bill', 'editable' => false, 'class' => 'date'),
			array('id' => 'USER_ID', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_USER_ID'), 'sort' => 'user_id', 'editable' => false),
			array('id' => 'PAY_SYSTEM_FULL', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_PAY_SYSTEM_FULL'), 'sort' => 'pay_system_id', 'default' => true),
			array('id' => 'PAY_SYSTEM_NAME', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_PAY_SYSTEM_NAME'), 'sort' => 'pay_system_mane', 'default' => false),
			array('id' => 'SUM', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_SUM'), 'sort' => 'sum', 'default' => true, 'editable' => false),
			array('id' => 'CURRENCY', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_CURRENCY'), 'sort' => 'currency', 'default' => false, 'editable' => false),
			array('id' => 'PAID', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_PAID'), 'sort' => 'paid', 'editable' => false, 'default' => true),
			array('id' => 'DATE_PAID', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_DATE_PAID'), 'sort' => 'date_paid', 'editable' => false, 'type' => 'date', 'class' => 'date'),
			array('id' => 'PAY_VOUCHER_NUM', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_PAY_VOUCHER_NUM'), 'sort' => 'pay_voucher_num', 'default' => false, 'editable' => false),
			array('id' => 'RESPONSIBLE', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_RESPONSIBLE'), 'sort' => 'responsible_id', 'editable' => false, 'class' => 'username'),
			array('id' => 'DATE_PAY_BEFORE', 'name' => Loc::getMessage('CRM_PAYMENT_HEADER_DATE_PAY_BEFORE'), 'sort' => 'date_pay_before', 'editable' => false, 'class' => 'date'),
		);

		return $result;
	}

	protected function createGlFilter($filter)
	{
		$result = array();
		$orderFields = Payment::getAllFields();

		foreach($filter as $k => $v)
		{
			$name = preg_replace('/^\W+/', '', $k);

			if(isset($orderFields[$name]))
				$result[$k] = $v;
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
		$rowsCount = Payment::getList(
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
		global $APPLICATION;

		if(!$this->init())
		{
			$this->showErrors();
			return false;
		}

		$this->arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
		$this->arResult['PATH_TO_ORDER_PAYMENT_LIST'] = $this->arParams['PATH_TO_ORDER_PAYMENT_LIST'] = CrmCheckPath('PATH_TO_ORDER_PAYMENT_LIST', $this->arParams['PATH_TO_ORDER_PAYMENT_LIST'], $APPLICATION->GetCurPage());
		$this->arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
		$this->arResult['SESSION_ID'] = bitrix_sessid();
		$this->arResult['NAVIGATION_CONTEXT_ID'] = isset($this->arParams['NAVIGATION_CONTEXT_ID']) ? $this->arParams['NAVIGATION_CONTEXT_ID'] : '';
		$this->arResult['PRESERVE_HISTORY'] = isset($this->arParams['PRESERVE_HISTORY']) ? $this->arParams['PRESERVE_HISTORY'] : false;
		$this->arResult['STATUS_LIST'] = [];
		$this->arResult['ERRORS'] = [];

		$statusList = \Bitrix\Crm\Order\DeliveryStatus::getListInCrmFormat();
		foreach ($statusList as $status)
		{
			$this->arResult['STATUS_LIST'][$status['STATUS_ID']] = htmlspecialcharsbx($status['NAME']);
		}

		$this->arResult['ENABLE_SLIDER'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();
		$this->arResult['PATH_TO_ORDER_PAYMENT_DELETE'] =  CHTTP::urlAddParams($this->arParams['PATH_TO_ORDER_PAYMENT_LIST'], array('sessid' => bitrix_sessid()));

		if(LayoutSettings::getCurrent()->isSimpleTimeFormatEnabled())
		{
			$this->arResult['TIME_FORMAT'] = array(
				'tommorow' => 'tommorow',
				's' => 'sago',
				'i' => 'iago',
				'H3' => 'Hago',
				'today' => 'today',
				'yesterday' => 'yesterday',
				//'d7' => 'dago',
				'-' => Main\Type\DateTime::convertFormatToPhp(FORMAT_DATE)
			);
		}
		else
		{
			$this->arResult['TIME_FORMAT'] = preg_replace('/:s$/', '', Main\Type\DateTime::convertFormatToPhp(FORMAT_DATETIME));
		}

		$this->arResult['CALL_LIST_UPDATE_MODE'] = isset($_REQUEST['call_list_context']) && isset($_REQUEST['call_list_id']) && IsModuleInstalled('voximplant');
		$this->arResult['CALL_LIST_CONTEXT'] = (string)$_REQUEST['call_list_context'];
		$this->arResult['CALL_LIST_ID'] = (int)$_REQUEST['call_list_id'];

		if($this->arResult['CALL_LIST_UPDATE_MODE'])
		{
			AddEventHandler('crm', 'onCrmOrderListItemBuildMenu', array('\Bitrix\Crm\CallList\CallList', 'handleOnCrmOrderListItemBuildMenu'));
		}

		$sort = array();
		$runtime = array();
		$this->arResult['FORM_ID'] = isset($this->arParams['FORM_ID']) ? $this->arParams['FORM_ID'] : '';
		$this->arResult['TAB_ID'] = isset($this->arParams['TAB_ID']) ? $this->arParams['TAB_ID'] : '';
		$this->arResult['INTERNAL'] = $this->isInternal;
		$this->arResult['ORDER_ID'] = isset($this->arParams['ORDER_ID']) && intval($this->arParams['ORDER_ID']) > 0 ? $this->arParams['ORDER_ID'] : false;

		if (!empty($this->arParams['INTERNAL_FILTER']) && is_array($this->arParams['INTERNAL_FILTER']))
		{
			if(empty($this->arParams['GRID_ID_SUFFIX']))
			{
				$this->arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null? mb_strtoupper($this->GetParent()->GetName()) : '';
			}
		}

		if (!empty($this->arParams['INTERNAL_SORT']) && is_array($this->arParams['INTERNAL_SORT']))
		{
			$sort = $this->arParams['INTERNAL_SORT'];
		}


		$this->arResult['IS_EXTERNAL_FILTER'] = false;
		$this->arResult['GRID_ID'] = 'CRM_ORDER_PAYMENT_LIST_V12'.($this->isInternal && !empty($this->arParams['GRID_ID_SUFFIX']) ? '_'.$this->arParams['GRID_ID_SUFFIX'] : '');

		// Please, uncomment if required
		//$this->arResult['CURRENCY_LIST'] = CCrmCurrencyHelper::PrepareListItems();

		$this->arResult['WEBFORM_LIST'] = WebFormManager::getListNames();
		/** ToDo Change checking orderId */
		$this->arResult['PERMS']['ADD'] = \Bitrix\Crm\Order\Permissions\Payment::checkCreatePermission($this->userPermissions);
		$this->arResult['PERMS']['WRITE'] = \Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($this->arResult['ORDER_ID'], $this->userPermissions);
		$this->arResult['PERMS']['DELETE'] = \Bitrix\Crm\Order\Permissions\Order::checkDeletePermission($this->arResult['ORDER_ID'], $this->userPermissions);

		$this->arResult['AJAX_MODE'] = isset($this->arParams['AJAX_MODE']) ? $this->arParams['AJAX_MODE'] : ($this->arResult['INTERNAL'] ? 'N' : 'Y');
		$this->arResult['AJAX_ID'] = isset($this->arParams['AJAX_ID']) ? $this->arParams['AJAX_ID'] : '';
		$this->arResult['AJAX_OPTION_JUMP'] = isset($this->arParams['AJAX_OPTION_JUMP']) ? $this->arParams['AJAX_OPTION_JUMP'] : 'N';
		$this->arResult['AJAX_OPTION_HISTORY'] = isset($this->arParams['AJAX_OPTION_HISTORY']) ? $this->arParams['AJAX_OPTION_HISTORY'] : 'N';

		$this->arResult['HEADERS'] = $this->getHeaders();

		$filter = isset($this->arParams['INTERNAL_FILTER']) && is_array($this->arParams['INTERNAL_FILTER'])
			? $this->arParams['INTERNAL_FILTER'] : array();

		if (intval($this->arParams['ORDER_PAYMENT_COUNT']) <= 0)
		{
			$this->arParams['ORDER_PAYMENT_COUNT'] = 20;
		}

		$arNavParams = array(
			'nPageSize' => $this->arParams['ORDER_PAYMENT_COUNT']
		);

		$this->arResult['FILTER_PRESETS'] = array();
		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID'], $this->arResult['FILTER_PRESETS']);
		$arNavParams = $gridOptions->GetNavParams($arNavParams);
		$arNavParams['bShowAll'] = false;

		// POST & GET actions processing -->
		$this->requestProcessing();

		$gridSort = $gridOptions->GetSorting(array(
			'sort' => array('ID' => 'desc'),
			'vars' => array('by' => 'by', 'order' => 'order')
		));
		$this->arResult['SORT'] = !empty($sort) ? $sort : $gridSort['sort'];
		$this->arResult['SORT_VARS'] = $gridSort['vars'];

		$visibleColumns = $gridOptions->GetVisibleColumns();
		if (empty($visibleColumns))
		{
			foreach ($this->arResult['HEADERS'] as $headers)
			{
				if ($headers['default'])
				{
					$visibleColumns[] = $headers['id'];
				}
			}
		}

		$selectFields = array_intersect($visibleColumns, Payment::getAllFields());

		if (!in_array('ACCOUNT_NUMBER', $selectFields, true))
		{
			$selectFields[] = 'ACCOUNT_NUMBER';
		}


		if (in_array('SUM', $selectFields, true))
		{
			$selectFields[] = 'CURRENCY';
		}

		if (!in_array('ID', $selectFields))
		{
			$selectFields[] = 'ID';
		}

		if (!in_array('DATE_BILL', $selectFields, true))
		{
			$selectFields[] = 'DATE_BILL';
		}

		if (in_array('RESPONSIBLE', $visibleColumns, true))
		{
			$selectFields[] = 'RESPONSIBLE_ID';
			$this->addUserInfoSelection('RESPONSIBLE_ID', 'RESPONSIBLE', $selectFields, $runtime);
		}

		if (in_array('PAY_SYSTEM_FULL', $visibleColumns, true))
		{
			$selectFields['PAY_SYSTEM_LOGOTIP'] = 'PAY_SYSTEM.LOGOTIP';
			$selectFields[] = 'PAY_SYSTEM_ID';
			$selectFields['PAY_SYSTEM_HANDLER'] = 'PAY_SYSTEM.ACTION_FILE';
			if (!in_array('PAY_SYSTEM_NAME', $selectFields, true))
			{
				$selectFields[] = 'PAY_SYSTEM_NAME';
			}
		}


		$nTopCount = false;

		if($nTopCount > 0)
		{
			$arNavParams['nTopCount'] = $nTopCount;
		}

		// HACK: Make custom sort for RESPONSIBLE_BY field
		$sort = $this->arResult['SORT'];

		if (isset($sort['responsible_by']))
		{
			$sort['responsible_by_last_name'] = $sort['responsible_by'];
			$sort['responsible_by_name'] = $sort['responsible_by'];
			$sort['responsible_by_login'] = $sort['responsible_by'];
			unset($sort['responsible_by']);
		}

		if (in_array('USER_ID', $visibleColumns, true) || isset($sort['user_id']))
		{
			$selectFields['USER_ID'] = 'ORDER.USER_ID';
			$selectFields['USER_NAME'] = 'ORDER.USER.NAME';
			$selectFields['USER_SECOND_NAME'] = 'ORDER.USER.SECOND_NAME';
			$selectFields['USER_LAST_NAME'] = 'ORDER.USER.LAST_NAME';
			$selectFields['USER_LOGIN'] = 'ORDER.USER.LOGIN';
			$selectFields['USER_EMAIL'] = 'ORDER.USER.EMAIL';
		}

		if (isset($sort['user_id']))
		{
			$sort['USER_LAST_NAME'] = $sort['user_id'];
			$sort['USER_NAME'] = $sort['user_id'];
			unset($sort['user_id']);
		}

		$arOptions = $arExportOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));

		if(isset($this->arParams['IS_EXTERNAL_CONTEXT']))
		{
			$arOptions['IS_EXTERNAL_CONTEXT'] = $this->arParams['IS_EXTERNAL_CONTEXT'];
		}

		//FIELD_OPTIONS
		$selectFields = array_unique($selectFields, SORT_STRING);

		$this->arResult['ORDER_PAYMENT'] = array();
		$this->arResult['ORDER_PAYMENT_ID'] = array();

		//region Navigation data initialization
		$pageNum = 0;
		$pageSize = (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $this->arParams['ORDER_PAYMENT_COUNT']);
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

			$nav = new \Bitrix\Main\UI\PageNavigation("orderPaymentList");
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

		if (!isset($sort['nearest_activity']))
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

			foreach ($filter as $k => $v)
			{
				if (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
				{
					\Bitrix\Crm\UI\Filter\Range::prepareFrom($filter, $arMatch[1], $v);
				}
				elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
				{
					if ($v != ''
							&& ($arMatch[1] == 'DATE_BILL'
								|| $arMatch[1] == 'DATE_RESPONSIBLE_ID')
							&& !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
					{
						$v = CCrmDateTimeHelper::SetMaxDayTime($v);
					}

					\Bitrix\Crm\UI\Filter\Range::prepareTo($filter, $arMatch[1], $v);
				}
			}

			$glFilter = $this->createGlFilter($filter);

			$glParams = array(
				'filter' => $glFilter,
				'order' => $sort,
				'select' => $selectFields
			);

			if (!empty($runtime))
			{
				$glParams['runtime'] = $runtime;
			}

			if (!empty($limit))
			{
				$glParams['limit'] = $limit;
			}

			if (!empty($offset))
			{
				$glParams['offset'] = $offset;
			}

			$dbResult = Payment::getList($glParams);

			$qty = 0;
			while($payment = $dbResult->fetch())
			{
				if($pageSize > 0 && ++$qty > $pageSize)
				{
					$enableNextPage = true;
					break;
				}

				$this->arResult['ORDER_PAYMENT'][$payment['ID']] = $payment;
				$this->arResult['ORDER_PAYMENT_ID'][$payment['ID']] = $payment['ID'];
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
				$sort['nearest_activity'],
				$filter,
				false,
				$navListOptions
			);

			$qty = 0;
			while($payment = $navDbResult->Fetch())
			{
				if($pageSize > 0 && ++$qty > $pageSize)
				{
					$enableNextPage = true;
					break;
				}

				$this->arResult['ORDER_PAYMENT'][$payment['ID']] = $payment;
				$this->arResult['ORDER_PAYMENT_ID'][$payment['ID']] = $payment['ID'];
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

		foreach($this->arResult['ORDER_PAYMENT'] as &$payment)
		{
			$entityID = $payment['ID'];
			$payment['DATE_BILL'] = !empty($payment['DATE_BILL']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($payment['DATE_BILL']), 'SHORT', SITE_ID)) : '';
			$payment['DATE_PAID'] = !empty($payment['DATE_PAID']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($payment['DATE_PAID']), 'SHORT', SITE_ID)) : '';
			$payment['DATE_RESPONSIBLE_ID'] = !empty($payment['DATE_RESPONSIBLE_ID']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($payment['DATE_RESPONSIBLE_ID']), 'SHORT', SITE_ID)) : '';

			$currencyID =  isset($payment['CURRENCY']) ? $payment['CURRENCY'] : CCrmCurrency::GetBaseCurrencyID();
			$payment['CURRENCY'] = htmlspecialcharsbx($currencyID);
			$payment['PAY_SYSTEM_NAME'] = htmlspecialcharsbx($payment['PAY_SYSTEM_NAME']);
			$payment['PATH_TO_ORDER_PAYMENT_DETAILS'] = Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
				->getPaymentDetailsLink(
					$entityID,
					Service\Sale\EntityLinkBuilder\Context::getShopAreaContext()
				);
			$payment['PATH_TO_ORDER_PAYMENT_SHOW'] = $payment['PATH_TO_ORDER_PAYMENT_DETAILS'];
			$payment['PATH_TO_ORDER_PAYMENT_EDIT'] = CCrmUrlUtil::AddUrlParams(
				$payment['PATH_TO_ORDER_PAYMENT_DETAILS'],
				array('init_mode' => 'edit')
			);

			$payment['PATH_TO_ORDER_PAYMENT_DELETE'] =  CHTTP::urlAddParams(
				$this->isInternal ? $APPLICATION->GetCurPage() : $this->arParams['PATH_TO_ORDER_PAYMENT_LIST'],
				array('action_'.$this->arResult['GRID_ID'] => 'delete', 'ID' => $entityID, 'sessid' => $this->arResult['SESSION_ID'])
			);

			$payment['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_USER_PROFILE'],
				array('user_id' => $payment['RESPONSIBLE_ID'])
			);

			$payment['RESPONSIBLE'] = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($payment['RESPONSIBLE_LOGIN']) ? $payment['RESPONSIBLE_LOGIN'] : '',
					'NAME' => isset($payment['RESPONSIBLE_NAME']) ? $payment['RESPONSIBLE_NAME'] : '',
					'LAST_NAME' => isset($payment['RESPONSIBLE_LAST_NAME']) ? $payment['RESPONSIBLE_LAST_NAME'] : '',
					'SECOND_NAME' => isset($payment['RESPONSIBLE_SECOND_NAME']) ? $payment['RESPONSIBLE_SECOND_NAME'] : ''
				),
				true
			);

			$payment['PAYMENT_SUMMARY'] = Loc::getMessage('CRM_PAYMENT_NAME', array(
				'#ACCOUNT_NUMBER#' => htmlspecialcharsbx($payment['ACCOUNT_NUMBER']),
				'#DATE_BILL#' => $payment['DATE_BILL']
			));

			if((int)$payment['USER_ID'] > 0)
			{
				$payment['BUYER_FORMATTED_NAME'] = \CUser::FormatName(
					\CSite::getNameFormat(false),
					array(
						'LOGIN' => $payment['USER_LOGIN'],
						'NAME' => $payment['USER_NAME'],
						'LAST_NAME' => $payment['USER_LAST_NAME'],
						'SECOND_NAME' => $payment['USER_SECOND_NAME']
					),
					true,
					true
				);

				$payment['PATH_TO_BUYER'] = CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_BUYER_PROFILE'],
					array('user_id' => $payment['USER_ID'])
				);
			}

			// todo: order
			foreach($payment as $name => $field)
			{
				if ($name[0] !== '~')
				{
					$payment['~'.$name] = $field;
				}
			}

			$payment['DELETE'] = $payment['EDIT'] = !$payment['INTERNAL'];
			$this->arResult['ORDER_PAYMENT'][$entityID] = $payment;
		}
		unset($payment);

		$this->arResult['ENABLE_TOOLBAR'] = (isset($this->arParams['ENABLE_TOOLBAR']) && $this->arResult['PERMS']['ADD']) ? $this->arParams['ENABLE_TOOLBAR'] : false;

		if($this->arResult['ENABLE_TOOLBAR'])
		{
			$this->arResult['PATH_TO_ORDER_PAYMENT_ADD'] = Service\Sale\EntityLinkBuilder\EntityLinkBuilder::getInstance()
				->getPaymentDetailsLink(
					0,
					Service\Sale\EntityLinkBuilder\Context::getShopAreaContext()
				);

			$this->arResult['PATH_TO_ORDER_PAYMENT_ADD'] = CHTTP::urlAddParams(
				$this->arResult['PATH_TO_ORDER_PAYMENT_ADD'],
				array('order_id' => (int)$this->arParams['INTERNAL_FILTER']['ORDER_ID'])
			);
		}

		$this->arResult['NEED_FOR_REBUILD_ORDER_PAYMENT_ATTRS'] =
			$this->arResult['NEED_FOR_REBUILD_ORDER_PAYMENT_SEMANTICS'] =
			$this->arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] =
			$this->arResult['NEED_FOR_BUILD_TIMELINE'] = false;

		if(!$this->isInternal)
		{
			if(COption::GetOptionString('crm', '~CRM_REBUILD_ORDER_PAYMENT_SEARCH_CONTENT', 'N') === 'Y')
			{
				$this->arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = true;
			}

			$this->arResult['NEED_FOR_REFRESH_ACCOUNTING'] = \Bitrix\Crm\Agent\Accounting\OrderAccountSyncAgent::getInstance()->isEnabled();

			if(CCrmPerms::IsAdmin())
			{
				if(COption::GetOptionString('crm', '~CRM_REBUILD_ORDER_PAYMENT_ATTR', 'N') === 'Y')
				{
					$this->arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_perm_list'));
					$this->arResult['NEED_FOR_REBUILD_ORDER_PAYMENT_ATTRS'] = true;
				}
				if(COption::GetOptionString('crm', '~CRM_REBUILD_ORDER_PAYMENT_SEMANTICS', 'N') === 'Y')
				{
					$this->arResult['NEED_FOR_REBUILD_ORDER_PAYMENT_SEMANTICS'] = true;
				}
			}
		}

		$this->IncludeComponentTemplate();
		include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.order/include/nav.php');
		return $this->arResult['ROWS_COUNT'];
	}
}
?>

