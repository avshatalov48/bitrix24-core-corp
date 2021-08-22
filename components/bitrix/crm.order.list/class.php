<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

define('PUBLIC_MODE', 1);

use Bitrix\Main;
use Bitrix\Crm\Order;
use Bitrix\Crm\Tracking;
use Bitrix\Crm\Product\Url;
use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Settings\LayoutSettings;
use \Bitrix\Main\Grid;
use Bitrix\Crm\Agent\Search\OrderSearchContentRebuildAgent;
use Bitrix\Iblock\Url\AdminPage\BuilderManager;


Loc::loadMessages(__FILE__);

class CCrmOrderListComponent extends \CBitrixComponent
{
	protected $userId = 0;
	protected $userPermissions;
	protected $errors = array();
	protected $isInternal = false;
	private $exportParams = [];

	/** @var null|\Bitrix\Iblock\Url\AdminPage\BaseBuilder  */
	private $urlBuilder = null;

	public function getEntityTypeId()
	{
		return \CCrmOwnerType::Order;
	}

	public function prepareParams($arParams)
	{
		global  $APPLICATION;

		$arParams['PATH_TO_ORDER_DETAILS'] = CrmCheckPath('PATH_TO_ORDER_DETAILS', $arParams['PATH_TO_ORDER_DETAILS'], $APPLICATION->GetCurPage().'?order_id=#order_id#&details');
		$arParams['PATH_TO_ORDER_SHOW'] = CrmCheckPath('PATH_TO_ORDER_SHOW', $arParams['PATH_TO_ORDER_SHOW'], $APPLICATION->GetCurPage().'?order_id=#order_id#&show');
		$arParams['PATH_TO_ORDER_EDIT'] = CrmCheckPath('PATH_TO_ORDER_EDIT', $arParams['PATH_TO_ORDER_EDIT'], $APPLICATION->GetCurPage().'?order_id=#order_id#&edit');
		$arParams['PATH_TO_QUOTE_EDIT'] = CrmCheckPath('PATH_TO_QUOTE_EDIT', $arParams['PATH_TO_QUOTE_EDIT'], $APPLICATION->GetCurPage().'?quote_id=#quote_id#&edit');
		$arParams['PATH_TO_INVOICE_EDIT'] = CrmCheckPath('PATH_TO_INVOICE_EDIT', $arParams['PATH_TO_INVOICE_EDIT'], $APPLICATION->GetCurPage().'?invoice_id=#invoice_id#&edit');
		$arParams['PATH_TO_COMPANY_SHOW'] = CrmCheckPath('PATH_TO_COMPANY_SHOW', $arParams['PATH_TO_COMPANY_SHOW'], $APPLICATION->GetCurPage().'?company_id=#company_id#&show');
		$arParams['PATH_TO_CONTACT_SHOW'] = CrmCheckPath('PATH_TO_CONTACT_SHOW', $arParams['PATH_TO_CONTACT_SHOW'], $APPLICATION->GetCurPage().'?contact_id=#contact_id#&show');
		$arParams['PATH_TO_USER_PROFILE'] = CrmCheckPath('PATH_TO_USER_PROFILE', $arParams['PATH_TO_USER_PROFILE'], '/company/personal/user/#user_id#/');
		$arParams['PATH_TO_BUYER_PROFILE'] = CrmCheckPath('PATH_TO_BUYER_PROFILE', $arParams['PATH_TO_BUYER_PROFILE'], '/shop/settings/sale_buyers_profile/?USER_ID=#user_id#&lang='.LANGUAGE_ID);
		$arParams['PATH_TO_USER_BP'] = CrmCheckPath('PATH_TO_USER_BP', $arParams['PATH_TO_USER_BP'], '/company/personal/bizproc/');
		$arParams['NAME_TEMPLATE'] = empty($arParams['NAME_TEMPLATE']) ? CSite::GetNameFormat(false) : str_replace(array("#NOBR#","#/NOBR#"), array("",""), $arParams["NAME_TEMPLATE"]);

		if ($this->isExportMode())
		{
			$this->prepareExportParams($arParams);
		}

		$arParams['BUILDER_CONTEXT'] = isset($arParams['BUILDER_CONTEXT']) ? $arParams['BUILDER_CONTEXT'] : '';
		if (
			$arParams['BUILDER_CONTEXT'] != Url\ShopBuilder::TYPE_ID
			&& $arParams['BUILDER_CONTEXT'] != Url\ProductBuilder::TYPE_ID
		)
		{
			$arParams['BUILDER_CONTEXT'] = Url\ShopBuilder::TYPE_ID;
		}

		return $arParams;
	}

	private function prepareExportParams($params)
	{
		$isStepperExport = (isset($params['STEXPORT_MODE']) && $params['STEXPORT_MODE'] === 'Y');
		$isExportAllFields = (isset($params['STEXPORT_INITIAL_OPTIONS']['EXPORT_ALL_FIELDS'])
			&& $params['STEXPORT_INITIAL_OPTIONS']['EXPORT_ALL_FIELDS'] === 'Y');
		$this->exportParams['STEXPORT_EXPORT_ALL_FIELDS'] = ($isStepperExport && $isExportAllFields) ? 'Y' : 'N';
		$this->exportParams['STEXPORT_MODE'] = $isStepperExport ? 'Y' : 'N';
		$this->exportParams['STEXPORT_TOTAL_ITEMS'] = max((int)$params['STEXPORT_TOTAL_ITEMS'], 0);
	}

	public function init() : bool
	{
		if(!CModule::IncludeModule('crm'))
		{
			$this->addError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED'));
			return false;
		}

		if(!CModule::IncludeModule('currency'))
		{
			$this->addError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CURRENCY'));
			return false;
		}

		if(!CModule::IncludeModule('catalog'))
		{
			$this->addError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED_CATALOG'));
			return false;
		}

		if (!CModule::IncludeModule('sale'))
		{
			$this->addError(Loc::getMessage('CRM_MODULE_NOT_INSTALLED_SALE'));
			return false;
		}

		$this->userPermissions = CCrmPerms::GetCurrentUserPermissions();

		if (!\Bitrix\Crm\Order\Permissions\Order::checkReadPermission(0, $this->userPermissions))
		{
			$this->addError(Loc::getMessage('CRM_PERMISSION_DENIED'));
			return false;
		}

		$this->userId = CCrmSecurityHelper::GetCurrentUserID();
		$this->isInternal = !empty($this->arParams['INTERNAL_FILTER']);
		CUtil::InitJSCore(array('ajax', 'tooltip'));

		$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
		if (!empty($this->arParams['EXPORT_TYPE']))
		{
			$exportType = (string)($this->arParams['EXPORT_TYPE']);
		}
		elseif ($request->get('type'))
		{
			$exportType = $request->get('type');
		}

		if (!empty($exportType))
		{
			$exportType = mb_strtolower(trim($exportType));
			switch ($exportType)
			{
				case 'csv':
				case 'excel':
					$this->exportParams['TYPE'] = $exportType;
					break;
			}

			if ($this->isExportMode() && !\Bitrix\Crm\Order\Permissions\Order::checkExportPermission($this->userPermissions))
			{
				$this->addError(Loc::getMessage('CRM_PERMISSION_DENIED'));
				return false;
			}
		}

		return true;
	}

	protected function initUrlBuilder(): bool
	{
		$manager = BuilderManager::getInstance();
		$this->urlBuilder = $manager->getBuilder($this->arParams['BUILDER_CONTEXT']);
		unset($manager);
		if ($this->urlBuilder === null)
		{
			$this->addError(Loc::getMessage('CRM_ERR_URL_BUILDER_ABSENT'));
			return false;
		}
		return true;
	}

	private function isExportMode()
	{
		return !empty($this->exportParams) && !empty($this->exportParams['TYPE']);
	}

	protected function showErrors()
	{
		$this->arResult['ERRORS'] = $this->errors;
		foreach($this->errors as $error)
			ShowError($error);
	}

	protected function addErrors(array $errors)
	{
		$this->errors = array_merge($this->errors, $errors);
	}

	protected function addError($error)
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

	protected function addActivitySelection(array &$select, array &$runtime)
	{
		$runtime[] =
			new Main\Entity\ReferenceField('C_USER_ACTIVITY',
				\Bitrix\Crm\UserActivityTable::getEntity(),
				array(
					'=ref.OWNER_ID' => 'this.ID',
					'=ref.OWNER_TYPE_ID' => new Main\DB\SqlExpression($this->getEntityTypeId()),
					'=ref.USER_ID' => new Main\DB\SqlExpression(0)
				),
				array('join_type' => 'LEFT')
			);

		$select['C_ACTIVITY_ID'] = 'C_USER_ACTIVITY.ACTIVITY_ID';
		$select['C_ACTIVITY_TIME'] = 'C_USER_ACTIVITY.ACTIVITY_TIME';

		$runtime[] =
			new Main\Entity\ReferenceField('C_ACTIVITY',
				\Bitrix\Crm\ActivityTable::getEntity(),
				array(
					'=ref.ID' => 'this.C_ACTIVITY_ID',
				),
				array('join_type' => 'LEFT')
			);

		$select['C_ACTIVITY_RESP_ID'] = 'C_ACTIVITY.RESPONSIBLE_ID';
		$select['C_ACTIVITY_SUBJECT'] = 'C_ACTIVITY.SUBJECT';

		$runtime[] =
			new Main\Entity\ReferenceField('C_USER',
				Main\UserTable::getEntity(),
				array(
					'=ref.ID' => 'this.C_ACTIVITY_RESP_ID',
				),
				array('join_type' => 'LEFT')
			);

		$select['C_ACTIVITY_RESP_LOGIN'] = 'C_USER.LOGIN';
		$select['C_ACTIVITY_RESP_NAME'] = 'C_USER.NAME';
		$select['C_ACTIVITY_RESP_LAST_NAME'] = 'C_USER.LAST_NAME';
		$select['C_ACTIVITY_RESP_SECOND_NAME'] = 'C_USER.SECOND_NAME';

		if($this->userId > 0)
		{
			$runtime[] =
				new Main\Entity\ReferenceField('USER_ACTIVITY',
					\Bitrix\Crm\UserActivityTable::getEntity(),
					array(
						'=ref.OWNER_ID' => 'this.ID',
						'=ref.OWNER_TYPE_ID' => new Main\DB\SqlExpression($this->getEntityTypeId()),
						'=ref.USER_ID' => new Main\DB\SqlExpression($this->userId)
					),
					array('join_type' => 'LEFT')
				);

			$select['USER_ACTIVITY_ID'] = 'USER_ACTIVITY.ACTIVITY_ID';
			$select['USER_ACTIVITY_TIME'] = 'USER_ACTIVITY.ACTIVITY_TIME';

			$runtime[] =
				new Main\Entity\ReferenceField('ACTIVITY',
					\Bitrix\Crm\ActivityTable::getEntity(),
					array(
						'=ref.ID' => 'this.USER_ACTIVITY_ID',
					),
					array('join_type' => 'LEFT')
				);

			$select['USER_ACTIVITY_SUBJECT'] = 'ACTIVITY.SUBJECT';
		}
	}

	/**
	 * @param array $runtime
	 */
	protected function addOrderDealRuntime(array &$runtime): void
	{
		$runtime[] = new Main\ORM\Fields\Relations\Reference('ORDER_DEAL',
			\Bitrix\Crm\Binding\OrderDealTable::getEntity(),
			['=ref.ORDER_ID' => 'this.ID',],
			['join_type' => 'LEFT',]
		);
	}

	protected function addActivityCounterFilter(array &$filter, array &$glFilter, array &$runtime)
	{
		if(is_array($filter['ACTIVITY_COUNTER']))
		{
			$counterTypeID = Bitrix\Crm\Counter\EntityCounterType::joinType(
				array_filter($filter['ACTIVITY_COUNTER'], 'is_numeric')
			);
		}
		else
		{
			$counterTypeID = (int)$filter['ACTIVITY_COUNTER'];
		}

		if(Bitrix\Crm\Counter\EntityCounterType::isDefined($counterTypeID))
		{
			$counterUserIDs = array();

			if(isset($filter['RESPONSIBLE_ID']))
			{
				if(is_array($filter['RESPONSIBLE_ID']))
				{
					$counterUserIDs = array_filter($filter['RESPONSIBLE_ID'], 'is_numeric');
				}
				elseif($filter['RESPONSIBLE_ID'] > 0)
				{
					$counterUserIDs[] = $filter['RESPONSIBLE_ID'];
				}
			}

			if(empty($counterUserIDs))
			{
				$counterUserIDs[] = $this->userId;
			}

			$counter = Bitrix\Crm\Counter\EntityCounterFactory::create(
				\CCrmOwnerType::Order,
				$counterTypeID,
				0
			);
			$activityFilterSql = $counter->getEntityListSqlExpression([
				'USER_IDS' => $counterUserIDs
			]);
			if (!empty($activityFilterSql))
			{
				if (isset($glFilter['@ID']))
				{
					$glFilter[] = [
						'@ID' => new Bitrix\Main\DB\SqlExpression($activityFilterSql)
					];
				}
				else
				{
					$glFilter['@ID'] =  new Bitrix\Main\DB\SqlExpression($activityFilterSql);
				}
			}
		}
	}

	/*
	 *POST & GET actions processing
	 * and LocalRedirect
	 */
	protected function requestProcessing($actionData, $filter)
	{
		if($actionData['ACTIVE'])
		{
			if ($actionData['METHOD'] == 'POST')
			{
				if($actionData['NAME'] == 'delete')
				{
					if ((isset($actionData['ID']) && is_array($actionData['ID'])) || $actionData['ALL_ROWS'])
					{
						$arFilterDel = array();

						if (!$actionData['ALL_ROWS'])
						{
							$arFilterDel = array('ID' => $actionData['ID']);
						}
						else
						{
							$arFilterDel += $filter;
						}

						$res = Bitrix\Crm\Order\Order::getList(array('filter' => $arFilterDel));

						while($order = $res->Fetch())
						{
							if(\Bitrix\Crm\Order\Permissions\Order::checkDeletePermission($order['ID'], $this->userPermissions))
							{
								try
								{
									$delRes = Order\Order::delete($order['ID']);
								}
								catch (Exception $e)
								{
									$delRes = Order\Order::deleteNoDemand($order['ID']);
								}

								if(!$delRes->isSuccess())
								{
									$this->addErrors($delRes->getErrorMessages());
								}
							}
							else
							{
								$this->addError(Loc::getMessage('CRM_PERMISSION_DENIED'));
							}
						}
					}
				}
				elseif($actionData['NAME'] == 'edit')
				{
					if(isset($actionData['FIELDS']) && is_array($actionData['FIELDS']))
					{
						foreach($actionData['FIELDS'] as $ID => $arSrcData)
						{
							if (!\Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($ID, $this->userPermissions))
								continue;

							$order = Order\Order::load($ID);

							if(!$order)
								continue;

							$arUpdateData = array();
							reset($this->arResult['HEADERS']);

							foreach ($this->arResult['HEADERS'] as $arHead)
								if (isset($arHead['editable']) && (is_array($arHead['editable']) || $arHead['editable'] === true) && isset($arSrcData[$arHead['id']]))
									$arUpdateData[$arHead['id']] = $arSrcData[$arHead['id']];

							$res = $order->setFields($arUpdateData);

							if(!$res->isSuccess())
								$this->addErrors($res->getErrorMessages());
						}
					}
				}
				elseif($actionData['NAME'] == 'set_status')
				{
					if($actionData['STATUS_ID'] <> '')
					{
						if((isset($actionData['ID']) && is_array($actionData['ID'])) || $actionData['ALL_ROWS'])
						{
							$statFilter = array();

							if (!$actionData['ALL_ROWS'])
								$statFilter = array('ID' => $actionData['ID']);
							else
								$statFilter += $filter;

							$reasonCanceled = isset($actionData['REASON_CANCELED']) ? $actionData['REASON_CANCELED'] : '';
							$res = Bitrix\Crm\Order\Order::getList(array('filter' => $statFilter));

							while($order = $res->fetch())
							{
								if(\Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($order['ID'], $this->userPermissions))
								{
									$setRes = Order\Manager::setOrderStatus($order['ID'], $actionData['STATUS_ID'], $reasonCanceled);

									if(!$setRes->isSuccess())
									{
										$this->addErrors($setRes->getErrorMessages());
									}
								}
							}
						}
					}
				}
				elseif($actionData['NAME'] == 'assign_to')
				{
					if((int)$actionData['RESPONSIBLE_BY_ID'] > 0)
					{
						if((isset($actionData['ID']) && is_array($actionData['ID'])) || $actionData['ALL_ROWS'])
						{
							$statFilter = array();

							if (!$actionData['ALL_ROWS'])
								$statFilter = array('ID' => $actionData['ID']);
							else
								$statFilter += $filter;

							$res = Bitrix\Crm\Order\Order::getList(array('filter' => $statFilter));

							while($orderFields = $res->fetch())
							{
								if(\Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($orderFields['ID'], $this->userPermissions))
								{
									$order = Order\Order::load($orderFields['ID']);

									if(!$order)
									{
										$this->addError(Loc::getMessage('CRM_ORDER_NOT_LOADED',['#ID#' => $orderFields['ID']]));
										continue;
									}

									$order->setFieldNoDemand(
										'RESPONSIBLE_ID',
										$actionData['RESPONSIBLE_BY_ID']
									);

									$order->save();
								}
							}
						}
					}
				}

				if (!$actionData['AJAX_CALL'])
				{
					LocalRedirect($this->arParams['PATH_TO_CURRENT_LIST']);
				}
			}
			else//if ($actionData['METHOD'] == 'GET')
			{
				if ($actionData['NAME'] == 'delete' && isset($actionData['ID']))
				{
					$ID = (int)$actionData['ID'];

					if(\Bitrix\Crm\Order\Permissions\Order::checkDeletePermission($ID, $this->userPermissions))
					{
						$res = Order\Order::delete($ID);

						if(!$res->isSuccess())
						{
							$this->addErrors($res->getErrorMessages());
						}
					}
					else
					{
						$this->addError(Loc::getMessage('CRM_PERMISSION_DENIED'));
					}
				}

				if (!$actionData['AJAX_CALL'])
				{
					if($this->isInternal)
					{
						LocalRedirect('?'.$this->arParams['FORM_ID'].'_active_tab=tab_order');
					}
					else
					{
						LocalRedirect($this->arParams['PATH_TO_CURRENT_LIST']);
					}
				}
			}
		}
	}

	protected function getHeaders()
	{
		$result = array(
			array('id' => 'ORDER_SUMMARY', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_SUMMARY'), 'sort' => 'ID', 'width' => 300, 'default' => true, 'editable' => false),
			array('id' => 'DATE_INSERT', 'name' => Loc::getMessage('CRM_COLUMN_DATE_INSERT'), 'default' => true, 'sort' => 'DATE_INSERT', 'editable' => false, 'type' => 'date', 'class' => 'date'),
			array('id' => 'STATUS_ID', 'name' => Loc::getMessage('CRM_COLUMN_STATUS_ID'), 'sort' => 'STATUS_ID', 'default' => true),
			array('id' => 'SUM', 'name' => Loc::getMessage('CRM_COLUMN_SUM'), 'sort' => 'PRICE', 'default' => true, 'editable' => false, 'align' => 'right'),
			array('id' => 'RESPONSIBLE_BY', 'name' => Loc::getMessage('CRM_COLUMN_RESPONSIBLE_BY'), 'default' => true, 'sort' => 'RESPONSIBLE_ID', 'editable' => false),
			array('id' => 'PAYED', 'name' => Loc::getMessage('CRM_COLUMN_PAYED'), 'sort' => 'PAYED', 'editable' => false, 'default' => true)
		);

		// Dont display activities in INTERNAL mode.
		if(!$this->isInternal)
		{
			$result[] = array(
				'id' => 'ACTIVITY_ID',
				'name' => Loc::getMessage('CRM_COLUMN_ACTIVITY'),
				'sort' => false, //'nearest_activity',
				'default' => true,
				'prevent_default' => false
			);
		}

		$result = array_merge(
			$result,
			array(
				array('id' => 'SOURCE', 'name' => Loc::getMessage('CRM_COLUMN_SOURCE'), 'default' => true, 'editable' => false),
				array('id' => 'USER', 'name' => Loc::getMessage('CRM_COLUMN_USER_ID'), 'sort' => 'USER_ID', 'editable' => false, 'default' => true),
				array('id' => 'ID', 'name' => Loc::getMessage('CRM_COLUMN_ID'), 'sort' => 'ID', 'editable' => false, 'type' => 'int'),
				array('id' => 'PERSON_TYPE_ID', 'name' => Loc::getMessage('CRM_COLUMN_PERSON_TYPE_ID'), 'sort' => 'PERSON_TYPE_ID', 'default' => false, 'editable' => false, 'class' => 'username'),
				array('id' => 'DATE_PAYED', 'name' => Loc::getMessage('CRM_COLUMN_DATE_PAYED'), 'sort' => 'DATE_PAYED', 'editable' => false, 'type' => 'date', 'class' => 'date'),
				array('id' => 'EMP_PAYED_ID', 'name' => Loc::getMessage('CRM_COLUMN_EMP_PAYED_ID'), 'sort' => 'EMP_PAYED_ID', 'editable' => false),
				array('id' => 'CANCELED', 'name' => Loc::getMessage('CRM_COLUMN_CANCELED'), 'sort' => 'CANCELED', 'editable' => false),
				array('id' => 'DATE_CANCELED', 'name' => Loc::getMessage('CRM_COLUMN_DATE_CANCELED'), 'sort' => 'DATE_CANCELED', 'editable' => false, 'type' => 'date', 'class' => 'date'),
				array('id' => 'EMP_CANCELED_ID', 'name' => Loc::getMessage('CRM_COLUMN_EMP_CANCELED_ID'), 'sort' => 'EMP_CANCELED_ID', 'editable' => false),
				array('id' => 'REASON_CANCELED', 'name' => Loc::getMessage('CRM_COLUMN_REASON_CANCELED'), 'sort' => 'REASON_CANCELED', 'editable' => false),
				array('id' => 'DATE_STATUS', 'name' => Loc::getMessage('CRM_COLUMN_DATE_STATUS'), 'sort' => 'DATE_STATUS', 'editable' => false, 'type' => 'date', 'class' => 'date'),
				array('id' => 'EMP_STATUS_ID', 'name' => Loc::getMessage('CRM_COLUMN_EMP_STATUS_ID'), 'sort' => 'EMP_STATUS_ID'),
				array('id' => 'PRICE_DELIVERY', 'name' => Loc::getMessage('CRM_COLUMN_PRICE_DELIVERY'), 'sort' => 'PRICE_DELIVERY', 'editable' => false),
				array('id' => 'ALLOW_DELIVERY', 'name' => Loc::getMessage('CRM_COLUMN_ALLOW_DELIVERY'), 'sort' => 'ALLOW_DELIVERY', 'default' => false, 'editable' => false),
				array('id' => 'DATE_ALLOW_DELIVERY', 'name' => Loc::getMessage('CRM_COLUMN_DATE_ALLOW_DELIVERY'), 'sort' => false, 'editable' => false, 'type' => 'date', 'class' => 'date'),
				array('id' => 'EMP_ALLOW_DELIVERY_ID', 'name' => Loc::getMessage('CRM_COLUMN_EMP_ALLOW_DELIVERY'), 'sort' => 'EMP_ALLOW_DELIVERY_ID', 'default' => false),
				array('id' => 'DEDUCTED', 'name' => Loc::getMessage('CRM_COLUMN_DEDUCTED'), 'sort' => 'DEDUCTED', 'editable' => false),
				array('id' => 'DATE_DEDUCTED', 'name' => Loc::getMessage('CRM_COLUMN_DATE_DEDUCTED'), 'sort' => false, 'editable' => false),
				array('id' => 'EMP_DEDUCTED_ID', 'name' => Loc::getMessage('CRM_COLUMN_EMP_DEDUCTED_ID'), 'sort' => false),
				array('id' => 'MARKED', 'name' => Loc::getMessage('CRM_COLUMN_MARKED'), 'sort' => 'MARKED', 'editable' => false),
				array('id' => 'RESERVED', 'name' => Loc::getMessage('CRM_COLUMN_RESERVED'), 'sort' => 'RESERVED', 'editable' => false),
				array('id' => 'CURRENCY', 'name' => Loc::getMessage('CRM_COLUMN_CURRENCY'), 'sort' => 'CURRENCY', 'editable' => false),
				array('id' => 'DISCOUNT_VALUE', 'name' => Loc::getMessage('CRM_COLUMN_DISCOUNT_VALUE'), 'sort' => false, 'editable' => false),
				array('id' => 'DATE_UPDATE', 'name' => Loc::getMessage('CRM_COLUMN_DATE_UPDATE'), 'sort' => 'DATE_UPDATE', 'editable' => false, 'type' => 'date', 'class' => 'date'),
				array('id' => 'COMMENTS', 'name' => Loc::getMessage('CRM_COLUMN_COMMENTS'), 'sort' => 'COMMENTS', 'editable' => false),
				array('id' => 'TAX_VALUE', 'name' => Loc::getMessage('CRM_COLUMN_TAX_VALUE'), 'sort' => 'TAX_VALUE', 'editable' => false),
				array('id' => 'SUM_PAID', 'name' => Loc::getMessage('CRM_COLUMN_SUM_PAID'), 'sort' => 'SUM_PAID', 'editable' => false),
				array('id' => 'CREATED_BY', 'name' => Loc::getMessage('CRM_COLUMN_CREATED_BY'), 'sort' => 'CREATED_BY', 'editable' => false),
				array('id' => 'ACCOUNT_NUMBER', 'name' => Loc::getMessage('CRM_COLUMN_ACCOUNT_NUMBER'), 'sort' => 'ACCOUNT_NUMBER', 'editable' => false),
				array('id' => 'ORDER_TOPIC', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_TOPIC2'), 'sort' => 'order_topic', 'default' => false, 'editable' => false),
				array('id' => 'BASKET', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_BASKET'), 'default' => false, 'editable' => false),
				array('id' => 'SHIPMENT', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_SHIPMENT'), 'default' => false, 'editable' => false),
				array('id' => 'PAYMENT', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_PAYMENT'), 'default' => false, 'editable' => false),
				array('id' => 'PROPS', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_PROPS'), 'default' => false, 'editable' => false),
				array('id' => 'CLIENT', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CLIENT'), 'default' => false, 'editable' => false),
				array('id' => 'COMPANY', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_COMPANY'), 'default' => false, 'editable' => false),
				array('id' => 'CONTACT', 'name' => Loc::getMessage('CRM_COLUMN_ORDER_CONTACT'), 'default' => false, 'editable' => false),
			)
		);

		Tracking\UI\Grid::appendColumns($result);

		return $result;
	}

	protected function createFilter()
	{
		$filter = array();

		if($this->isInternal)
		{
			if(is_array($this->arParams['INTERNAL_FILTER']))
			{
				$filter = $this->arParams['INTERNAL_FILTER'];
			}
		}
		else
		{
			$request = Bitrix\Main\Application::getInstance()->getContext()->getRequest();
			if($request->getQuery('from_analytics') === 'Y')
			{
				$boardId = $request->getQuery('board_id');
				$this->arResult['GRID_ID'] = 'report_' . $boardId . '_filter';
				$this->arResult['FILTER_PRESETS'] = [];
				$this->arResult['IS_EXTERNAL_FILTER'] = true;
			}

			$entityFilter = \Bitrix\Crm\Filter\Factory::createEntityFilter(
				new \Bitrix\Crm\Filter\OrderSettings(
					array('ID' => $this->arResult['GRID_ID'])
				)
			);
			$filterFields = $entityFilter->getFieldArrays();

			$filterOptions = new \Bitrix\Main\UI\Filter\Options($this->arResult['GRID_ID'], $this->arResult['FILTER_PRESETS']);
			$filter += $filterOptions->getFilter($filterFields);

			$effectiveFilterFieldIDs = $filterOptions->getUsedFields();

			if(empty($effectiveFilterFieldIDs))
			{
				$effectiveFilterFieldIDs = $entityFilter->getDefaultFieldIDs();
			}

			if(!in_array('ACTIVITY_COUNTER', $effectiveFilterFieldIDs, true))
			{
				$effectiveFilterFieldIDs[] = 'ACTIVITY_COUNTER';
			}

			Tracking\UI\Filter::appendEffectiveFields($effectiveFilterFieldIDs);

			foreach($effectiveFilterFieldIDs as $filterFieldID)
			{
				$filterField = $entityFilter->getField($filterFieldID);

				if($filterField)
				{
					$this->arResult['FILTER'][] = $filterField->toArray();
				}
			}
		}

		return $filter;
	}

	public function createGlFilter(array $filter, array &$runtime)
	{
		global $USER, $USER_FIELD_MANAGER;

		$result = array();
		$orderFields = array_flip(\Bitrix\Crm\Order\Order::getAllFields());

		$contactIds = [];
		$companyId = null;

		$filter = $this->formatUIFilter($filter);

		$searchRestriction = \Bitrix\Crm\Restriction\RestrictionManager::getSearchLimitRestriction();
		if(!$searchRestriction->isExceeded(CCrmOwnerType::Order))
		{
			Bitrix\Crm\Search\SearchEnvironment::convertEntityFilterValues(CCrmOwnerType::Order, $filter);
			if(isset($filter['SEARCH_CONTENT']))
			{
				$searchValue = $filter['SEARCH_CONTENT'];
				unset($filter['SEARCH_CONTENT']);
				if(is_string($searchValue))
				{
					$filter = array_merge($filter, $this->prepareSearchFilterValue($searchValue));
				}
			}
		}
		else
		{
			$this->arResult['LIVE_SEARCH_LIMIT_INFO'] = $searchRestriction->prepareStubInfo(
				array('ENTITY_TYPE_ID' => CCrmOwnerType::Order)
			);
		}

		$userType = new CCrmUserType($USER_FIELD_MANAGER, Order\Order::getUfId());
		$orderUserFields = $userType->GetFields();
		$propertyItterator = 0;
		foreach($filter as $k => $v)
		{
			$name = preg_replace('/^\W+/', '', $k);

			if ($name === 'COMPANY_ID')
			{
				$companyId = $v;
			}
			elseif ($name === 'ASSOCIATED_CONTACT_ID')
			{
				if(!is_array($v))
				{
					$contactIds = [$v];
				}
				else
				{
					$contactIds = $v;
				}
			}
			elseif ($name === 'SOURCE_ID')
			{
				$result['TRADING_PLATFORM.TRADING_PLATFORM_ID'] = $v;
			}
			elseif ($name === 'PAY_SYSTEM')
			{
				$result['PAYMENT.PAY_SYSTEM_ID'] = $v;
			}
			elseif ($name === 'DELIVERY_SERVICE')
			{
				$result['SHIPMENT.DELIVERY_ID'] = $v;
				$result['SHIPMENT.SYSTEM'] = 'N';
			}
			elseif($name === 'ASSOCIATED_DEAL_ID')
			{
				$result['=ORDER_DEAL.DEAL_ID'] = $v;
				$this->addOrderDealRuntime($runtime);
			}
			elseif($name === 'HAS_ASSOCIATED_DEAL')
			{
				$key = sprintf(
					'%s=ORDER_DEAL.ORDER_ID',
					($v === 'Y') ? '!' : ''
				);
				$result[$key] = null;
				$this->addOrderDealRuntime($runtime);
			}
			elseif($name === 'COUPON')
			{
				$result['=ORDER_COUPONS.COUPON'] = $v;
			}
			elseif($name === 'XML_ID')
			{
				$result['%XML_ID'] = $v;
			}
			elseif($name === 'SHIPMENT_TRACKING_NUMBER')
			{
				$result['%SHIPMENT.TRACKING_NUMBER'] = $v;
			}
			elseif($name === 'SHIPMENT_DELIVERY_DOC_DATE')
			{
				$docDateName = str_replace('SHIPMENT_DELIVERY_DOC_DATE', 'SHIPMENT.DELIVERY_DOC_DATE', $k);
				$result[$docDateName] = $v;
			}
			elseif($name === 'CHECK_PRINTED')
			{
				if ($v === 'Y')
				{
					$result['ORDER_CHECK_PRINTED.STATUS'] = 'Y';
				}
				else
				{
					$result[] = [
						'LOGIC' => 'OR',
						'=ORDER_CHECK_PRINTED.STATUS' => null,
						'@ORDER_CHECK_PRINTED.STATUS' => ['N', 'P', 'E']
					];
				}

				$runtime[] = new Main\ORM\Fields\Relations\Reference(
					'ORDER_CHECK_PRINTED',
					\Bitrix\Sale\Cashbox\Internals\CashboxCheckTable::getEntity(),
					['=ref.ORDER_ID' => 'this.ID',],
					['join_type' => 'LEFT',]
				);
			}
			elseif ($name === 'USER')
			{
				$buyerFilter = Main\UserUtils::getAdminSearchFilter([
					'FIND' => $v
				]);
				foreach ($buyerFilter as $key => $userFilterItem)
				{
					$key = str_replace('INDEX', 'USER.INDEX', $key);
					$result[$key] = $userFilterItem;
				}
			}
			if (preg_match("/^PROPERTY_/", $name))
			{
				$propertyId = (int)str_replace('PROPERTY_', '', $name);
				if ("PROPERTY_{$propertyId}" !== $name)
				{
					continue;
				}

				$propertyTableName = "PROPERTY_{$propertyItterator}";
				$propertyValueCode = str_replace($name, "{$propertyTableName}.VALUE", $k);

				if (preg_match('/^[A-Z]/',$propertyValueCode))
				{
					$propertyValueCode = "%{$propertyValueCode}";
				}

				$runtime[] =
					new Main\Entity\ReferenceField($propertyTableName,
						\Bitrix\Sale\Internals\OrderPropsValueTable::getEntity(),
						array(
							'=ref.ORDER_ID' => 'this.ID',
						),
						array('join_type' => 'inner')
					);

				$result[] = [
					"={$propertyTableName}.ORDER_PROPS_ID" => $propertyId,
					$propertyValueCode => $v
				];

				$propertyItterator++;
			}
			elseif (isset($orderUserFields[$name]) && mb_strpos($name, 'UF_') === 0)
			{
				$result[$k] = $v;
			}
			elseif (isset($orderFields[$name]))
			{
				$result[$k] = $v;
			}
		}

		if(isset($this->arParams['EXTERNAL_FILTER']['USER_ID']) && intval($this->arParams['EXTERNAL_FILTER']['USER_ID']) > 0)
		{
			$result['=USER_ID'] = intval($this->arParams['EXTERNAL_FILTER']['USER_ID']);
		}

		if(isset($filter['ACTIVITY_COUNTER']))
		{
			$this->addActivityCounterFilter($filter, $result, $runtime);
		}

		$contactCompanyFilter = $this->prepareContactCompanyFilter($companyId, $contactIds);
		if (!empty($contactCompanyFilter))
		{
			//caution! can be problem with filtering by companies from holding from sale module.
			unset($result['COMPANY_ID'], $result['=COMPANY_ID']);

			$result = array_merge($result, $contactCompanyFilter);
			$runtime[] =
				new Main\Entity\ReferenceField('CLIENT',
					\Bitrix\Crm\Binding\OrderContactCompanyTable::getEntity(),
					array(
						'=ref.ORDER_ID' => 'this.ID',
					),
					array('join_type' => 'LEFT')
				);
		}

		if (!(is_object($USER) && $USER->IsAdmin())
			&& (!array_key_exists('CHECK_PERMISSIONS', $filter) || $filter['CHECK_PERMISSIONS'] !== 'N')
		)
		{
			$permissionSql = \CCrmPerms::BuildSql(
				\CCrmOwnerType::OrderName,
				'',
				'READ',
				array('RAW_QUERY' => true, 'PERMS'=> \CCrmPerms::GetCurrentUserPermissions())
			);

			if($permissionSql <> '')
			{
				if (isset($result['@ID']))
				{
					$result[] = [
						'@ID' => new Bitrix\Main\DB\SqlExpression($permissionSql)
					];
				}
				else
				{
					$result['@ID'] = new Bitrix\Main\DB\SqlExpression($permissionSql);
				}
			}
		}

		Tracking\UI\Filter::buildOrmFilter($result, $filter, \CCrmOwnerType::Order, $runtime);

		return $result;
	}

	private function formatUIFilter(array $filter)
	{
		foreach($filter as $k => $v)
		{
			if (preg_match('/(.*)_from$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
			{
				\Bitrix\Crm\UI\Filter\Range::prepareFrom($filter, $arMatch[1], $v);
			}
			elseif (preg_match('/(.*)_to$/i'.BX_UTF_PCRE_MODIFIER, $k, $arMatch))
			{
				$dateFieldNames = ['DATE_INSERT', 'DATE_UPDATE', 'SHIPMENT_DELIVERY_DOC_DATE'];
				if ($v != '' && in_array($arMatch[1], $dateFieldNames) && !preg_match('/\d{1,2}:\d{1,2}(:\d{1,2})?$/'.BX_UTF_PCRE_MODIFIER, $v))
				{
					$v = CCrmDateTimeHelper::SetMaxDayTime($v);
				}

				\Bitrix\Crm\UI\Filter\Range::prepareTo($filter, $arMatch[1], $v);
			}
		}
		return $filter;
	}

	private function prepareSearchFilterValue($value)
	{
		$preparedFindFilter = [];
		$find = trim($value);
		if($find !== '')
		{
			$preparedFindFilter = \Bitrix\Crm\Search\SearchEnvironment::prepareEntityFilter(
				CCrmOwnerType::Order,
				array(
					'SEARCH_CONTENT' => \Bitrix\Crm\Search\SearchEnvironment::prepareSearchContent($find)
				)
			);
		}
		return $preparedFindFilter;
	}

	private function prepareContactCompanyFilter($companyId, array $contactIds)
	{
		$result = [];
		if(isset($this->arParams['EXTERNAL_FILTER']) && is_array($this->arParams['EXTERNAL_FILTER']))
		{
			if(isset($this->arParams['EXTERNAL_FILTER']['CONTACT_IDS']) && is_array($this->arParams['EXTERNAL_FILTER']['CONTACT_IDS']))
			{
				$contactIds = $this->arParams['EXTERNAL_FILTER']['CONTACT_IDS'];
			}
			if(isset($this->arParams['EXTERNAL_FILTER']['COMPANY_ID']))
			{
				$companyId = $this->arParams['EXTERNAL_FILTER']['COMPANY_ID'];
			}
		}

		if($companyId > 0 && !empty($contactIds))
		{
			$result[] = [
				'LOGIC' => 'OR',
				[
					'=CLIENT.ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'=CLIENT.ENTITY_ID' => $companyId,
				],
				[
					'=CLIENT.ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
					'=CLIENT.ENTITY_ID' => $contactIds,
				],
			];
		}
		elseif($companyId > 0)
		{
			$result['=CLIENT.ENTITY_ID'] = $companyId;
			$result['=CLIENT.ENTITY_TYPE_ID'] = CCrmOwnerType::Company;
		}
		elseif(!empty($contactIds))
		{
			$result['=CLIENT.ENTITY_ID'] = $contactIds;
			$result['=CLIENT.ENTITY_TYPE_ID'] = CCrmOwnerType::Contact;
		}

		return $result;
	}

	protected function getActionData()
	{
		//region Try to extract user action data
		// We have to extract them before call of CGridOptions::GetFilter() or the custom filter will be corrupted.
		$actionData = array(
			'METHOD' => $_SERVER['REQUEST_METHOD'],
			'ACTIVE' => false
		);

		if(check_bitrix_sessid())
		{
			$postAction = 'action_button_'.$this->arResult['GRID_ID'];
			$getAction = 'action_'.$this->arResult['GRID_ID'];
			//We need to check grid 'controls'
			$controls = isset($_POST['controls']) && is_array($_POST['controls']) ? $_POST['controls'] : array();
			if ($actionData['METHOD'] == 'POST' && (isset($controls[$postAction]) || isset($_POST[$postAction])))
			{
				CUtil::JSPostUnescape();

				$actionData['ACTIVE'] = true;

				if(isset($controls[$postAction]))
				{
					$actionData['NAME'] = $controls[$postAction];
				}
				else
				{
					$actionData['NAME'] = $_POST[$postAction];
					unset($_POST[$postAction], $_REQUEST[$postAction]);
				}

				$allRows = 'action_all_rows_'.$this->arResult['GRID_ID'];
				$actionData['ALL_ROWS'] = false;
				if(isset($controls[$allRows]))
				{
					$actionData['ALL_ROWS'] = $controls[$allRows] == 'Y';
				}
				elseif(isset($_POST[$allRows]))
				{
					$actionData['ALL_ROWS'] = $_POST[$allRows] == 'Y';
					unset($_POST[$allRows], $_REQUEST[$allRows]);
				}

				if(isset($_POST['rows']) && is_array($_POST['rows']))
				{
					$actionData['ID'] = $_POST['rows'];
				}
				elseif(isset($_POST['ID']))
				{
					$actionData['ID'] = $_POST['ID'];
					unset($_POST['ID'], $_REQUEST['ID']);
				}

				if(isset($_POST['FIELDS']))
				{
					$actionData['FIELDS'] = $_POST['FIELDS'];
					unset($_POST['FIELDS'], $_REQUEST['FIELDS']);
				}

				if(isset($_POST['ACTION_STATUS_ID']) || isset($controls['ACTION_STATUS_ID']))
				{
					if(isset($_POST['ACTION_STATUS_ID']))
					{
						$actionData['STATUS_ID'] = trim($_POST['ACTION_STATUS_ID']);
						unset($_POST['ACTION_STATUS_ID'], $_REQUEST['ACTION_STATUS_ID']);
					}
					else
					{
						$actionData['STATUS_ID'] = trim($controls['ACTION_STATUS_ID']);
					}
				}

				if(isset($_POST['ACTION_LID']) || isset($controls['ACTION_LID']))
				{
					if(isset($_POST['ACTION_LID']))
					{
						$actionData['LID'] = intval($_POST['ACTION_LID']);
						unset($_POST['ACTION_LID'], $_REQUEST['ACTION_LID']);
					}
					else
					{
						$actionData['LID'] = intval($controls['ACTION_LID']);
					}
				}

				if(isset($_POST['ACTION_RESPONSIBLE_BY_ID']) || isset($controls['ACTION_RESPONSIBLE_BY_ID']))
				{
					$responsibleById = 0;
					if(isset($_POST['ACTION_RESPONSIBLE_BY_ID']))
					{
						if(!is_array($_POST['ACTION_RESPONSIBLE_BY_ID']))
						{
							$responsibleById = intval($_POST['ACTION_RESPONSIBLE_BY_ID']);
						}
						elseif(count($_POST['ACTION_RESPONSIBLE_BY_ID']) > 0)
						{
							$responsibleById = intval($_POST['ACTION_RESPONSIBLE_BY_ID'][0]);
						}
						unset($_POST['ACTION_RESPONSIBLE_BY_ID'], $_REQUEST['ACTION_RESPONSIBLE_BY_ID']);
					}
					else
					{
						$responsibleById = (int)$controls['ACTION_RESPONSIBLE_BY_ID'];
					}

					$actionData['RESPONSIBLE_BY_ID'] = $responsibleById;
				}

				if(isset($_POST['ACTION_OPENED']) || isset($controls['ACTION_OPENED']))
				{
					if(isset($_POST['ACTION_OPENED']))
					{
						$actionData['OPENED'] = mb_strtoupper($_POST['ACTION_OPENED']) === 'Y' ? 'Y' : 'N';
						unset($_POST['ACTION_OPENED'], $_REQUEST['ACTION_OPENED']);
					}
					else
					{
						$actionData['OPENED'] = mb_strtoupper($controls['ACTION_OPENED']) === 'Y' ? 'Y' : 'N';
					}
				}

				$actionData['AJAX_CALL'] = $this->arResult['IS_AJAX_CALL'];
			}
			elseif ($actionData['METHOD'] == 'GET' && isset($_GET[$getAction]))
			{
				$actionData['ACTIVE'] = check_bitrix_sessid();

				$actionData['NAME'] = $_GET[$getAction];
				unset($_GET[$getAction], $_REQUEST[$getAction]);

				if(isset($_GET['ID']))
				{
					$actionData['ID'] = $_GET['ID'];
					unset($_GET['ID'], $_REQUEST['ID']);
				}

				$actionData['AJAX_CALL'] = $this->arResult['IS_AJAX_CALL'];
			}
		}

		return $actionData;
	}

	/**
	 * @param string $code
	 * @param Bitrix\Sale\TradingPlatform\Platform $class
	 * @return string
	 */
	protected function getTradingPlatformName($code, $class)
	{
		if($code == '' || !class_exists($class))
		{
			return '';
		}

		$tradingPlatform = $class::getInstanceByCode($code);
		return $tradingPlatform->getRealName();
	}

	/**
	 * @param array $arSort
	 * @param Grid\Options $gridOptions
	 * @param array $visibleColumns
	 * @param array $headers
	 * @return array
	 */
	protected function getSortFields(array $arSort, Grid\Options $gridOptions, array $visibleColumns, array $headers): array
	{
		$gridSort = $gridOptions->GetSorting(array(
			'sort' => array('ID' => 'desc'),
			'vars' => array('by' => 'by', 'order' => 'order')
		));

		$tmpSort = !empty($arSort) ? $arSort : $gridSort['sort'];
		$resultSort = [];

		foreach($headers as $header)
		{
			if(empty($header['sort']))
			{
				continue;
			}

			$sortColumn = $header['sort'];

			if(isset($tmpSort[$sortColumn]))
			{
				$resultSort[$sortColumn] = $tmpSort[$sortColumn];
				unset($tmpSort[$sortColumn]);

				if(empty($tmpSort[$sortColumn]))
				{
					break;
				}
			}
		}

		return [$resultSort, $gridSort['vars']];
	}

	public function executeComponent()
	{
		global $USER_FIELD_MANAGER, $APPLICATION, $USER;

		if(!$this->init())
		{
			$this->showErrors();
			return false;
		}

		$this->arParams = $this->prepareParams($this->arParams);

		if (!$this->initUrlBuilder())
		{
			$this->showErrors();
			return false;
		}

		$currentPage = $APPLICATION->GetCurPage();
		$this->arResult['CURRENT_USER_ID'] = CCrmSecurityHelper::GetCurrentUserID();
		$this->arResult['PATH_TO_ORDER_LIST'] = $this->arParams['PATH_TO_ORDER_LIST'] = CrmCheckPath('PATH_TO_ORDER_LIST', $this->arParams['PATH_TO_ORDER_LIST'], $APPLICATION->GetCurPage());
		$this->arResult['PATH_TO_ORDER_WIDGET'] = $this->arParams['PATH_TO_ORDER_WIDGET'] = CrmCheckPath('PATH_TO_ORDER_WIDGET', $this->arParams['PATH_TO_ORDER_WIDGET'], $APPLICATION->GetCurPage());
		$this->arResult['PATH_TO_ORDER_KANBAN'] = $this->arParams['PATH_TO_ORDER_KANBAN'] = CrmCheckPath('PATH_TO_ORDER_KANBAN', $this->arParams['PATH_TO_ORDER_KANBAN'], $currentPage);
		$this->arResult['PATH_TO_CURRENT_LIST'] = ($this->arParams['IS_RECURRING'] !== 'Y') ? $this->arParams['PATH_TO_ORDER_LIST'] : $this->arParams['PATH_TO_ORDER_RECUR'];
		$this->arResult['ADD_EVENT_NAME'] = $this->arParams['ADD_EVENT_NAME'] !== ''
			? preg_replace('/[^a-zA-Z0-9_]/', '', $this->arParams['ADD_EVENT_NAME']) : '';
		$this->arResult['IS_AJAX_CALL'] = isset($_REQUEST['AJAX_CALL']) || isset($_REQUEST['ajax_request']) || !!CAjax::GetSession();
		$this->arResult['SESSION_ID'] = bitrix_sessid();
		$this->arResult['NAVIGATION_CONTEXT_ID'] = isset($this->arParams['NAVIGATION_CONTEXT_ID']) ? $this->arParams['NAVIGATION_CONTEXT_ID'] : '';
		$this->arResult['PRESERVE_HISTORY'] = isset($this->arParams['PRESERVE_HISTORY']) ? $this->arParams['PRESERVE_HISTORY'] : false;
		$this->arResult['STATUS_LIST'] = [];

		$statusList = Order\OrderStatus::getListInCrmFormat();
		foreach ($statusList as $status)
		{
			$this->arResult['STATUS_LIST'][$status['STATUS_ID']] = htmlspecialcharsbx($status['NAME']);
		}

		$this->arResult['ENABLE_SLIDER'] = \Bitrix\Crm\Settings\LayoutSettings::getCurrent()->isSliderEnabled();

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

		$this->arResult['SALESCENTER_MODE'] = ($this->arParams['SALESCENTER_MODE'] === true);
		$this->arResult['CALL_LIST_UPDATE_MODE'] = isset($_REQUEST['call_list_context']) && isset($_REQUEST['call_list_id']) && IsModuleInstalled('voximplant');
		$this->arResult['CALL_LIST_CONTEXT'] = (string)$_REQUEST['call_list_context'];
		$this->arResult['CALL_LIST_ID'] = (int)$_REQUEST['call_list_id'];

		if($this->arResult['CALL_LIST_UPDATE_MODE'])
		{
			AddEventHandler('crm', 'onCrmOrderListItemBuildMenu', array('\Bitrix\Crm\CallList\CallList', 'handleOnCrmOrderListItemBuildMenu'));
		}

		\Bitrix\Crm\Order\Permissions\Order::prepareConversionPermissionFlags(0, $this->arResult, $this->userPermissions);

		if($this->arResult['CAN_CONVERT'])
		{
			$config = \Bitrix\Crm\Conversion\OrderConversionConfig::load();
			if($config === null)
			{
				$config = \Bitrix\Crm\Conversion\OrderConversionConfig::getDefault();
			}

			$this->arResult['CONVERSION_CONFIG'] = $config;
		}

		$arSort = array();
		$runtime = array();
		$this->arResult['FORM_ID'] = isset($this->arParams['FORM_ID']) ? $this->arParams['FORM_ID'] : '';
		$this->arResult['TAB_ID'] = isset($this->arParams['TAB_ID']) ? $this->arParams['TAB_ID'] : '';
		$this->arResult['INTERNAL'] = $this->isInternal;
		if (!empty($this->arParams['INTERNAL_FILTER']) && is_array($this->arParams['INTERNAL_FILTER']))
		{
			if(empty($this->arParams['GRID_ID_SUFFIX']))
			{
				$this->arParams['GRID_ID_SUFFIX'] = $this->GetParent() !== null? mb_strtoupper($this->GetParent()->GetName()) : '';
			}
		}

		if (!empty($this->arParams['INTERNAL_SORT']) && is_array($this->arParams['INTERNAL_SORT']))
			$arSort = $this->arParams['INTERNAL_SORT'];

		$this->arResult['IS_EXTERNAL_FILTER'] = false;
		$CCrmUserType = new CCrmUserType($USER_FIELD_MANAGER, Order\Order::getUfId());
		$this->arResult['GRID_ID'] = $this->combineGridIdentifier();
		$this->arResult['EVENT_LIST'] = CCrmStatus::GetStatusListEx('EVENT_TYPE');
		$this->arResult['FILTER'] = array();
		$this->arResult['FILTER_PRESETS'] = array();
		$this->arResult['PERMS']['ADD'] = \Bitrix\Crm\Order\Permissions\Order::checkCreatePermission($this->userPermissions);
		$this->arResult['PERMS']['WRITE'] = \Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission(0, $this->userPermissions);
		$this->arResult['PERMS']['DELETE'] = \Bitrix\Crm\Order\Permissions\Order::checkDeletePermission(0, $this->userPermissions);
		$this->arResult['AJAX_MODE'] = isset($this->arParams['AJAX_MODE']) ? $this->arParams['AJAX_MODE'] : ($this->arResult['INTERNAL'] ? 'N' : 'Y');
		$this->arResult['AJAX_ID'] = isset($this->arParams['AJAX_ID']) ? $this->arParams['AJAX_ID'] : '';
		$this->arResult['AJAX_OPTION_JUMP'] = isset($this->arParams['AJAX_OPTION_JUMP']) ? $this->arParams['AJAX_OPTION_JUMP'] : 'N';
		$this->arResult['AJAX_OPTION_HISTORY'] = isset($this->arParams['AJAX_OPTION_HISTORY']) ? $this->arParams['AJAX_OPTION_HISTORY'] : 'N';
		$this->arResult['EXTERNAL_SALES'] = CCrmExternalSaleHelper::PrepareListItems();


		//endregion Filter Presets Initialization
		$currentUserID = $this->arResult['CURRENT_USER_ID'];
		$currentUserName = CCrmViewHelper::GetFormattedUserName($currentUserID, $this->arParams['NAME_TEMPLATE']);
		$this->arResult['FILTER_PRESETS'] = array(
			'filter_in_work' => array(
				'name' => Loc::getMessage('CRM_PRESET_IN_WORK'),
				'default' => true,
				'fields' => array('STATUS_ID' => Order\OrderStatus::getSemanticProcessStatuses())
			),
			'filter_my' => array(
				'name' => Loc::getMessage('CRM_PRESET_MY'),
				'fields' => array(
					'RESPONSIBLE_ID_name' => $currentUserName,
					'RESPONSIBLE_ID' => $currentUserID,
					'STATUS_ID' => Order\OrderStatus::getSemanticProcessStatuses()
				)
			),
			'filter_won' => array(
				'name' => Loc::getMessage('CRM_PRESET_WON'),
				'fields' => array('STATUS_ID' =>  array(Order\OrderStatus::getFinalStatus()))
			)
		);

		$this->arResult['HEADERS'] = $this->getHeaders();
		$CCrmUserType->ListAddHeaders($this->arResult['HEADERS']);

		$actionData = $this->getActionData();

		// HACK: for clear filter by CREATED_BY, MODIFY_BY_ID and RESPONSIBLE_BY_ID
		if($_SERVER['REQUEST_METHOD'] === 'GET')
		{
			if(isset($_REQUEST['CREATED_BY_name']) && $_REQUEST['CREATED_BY_name'] === '')
			{
				$_REQUEST['CREATED_BY'] = $_GET['CREATED_BY'] = array();
			}

			if(isset($_REQUEST['RESPONSIBLE_BY_ID_name']) && $_REQUEST['RESPONSIBLE_BY_ID_name'] === '')
			{
				$_REQUEST['RESPONSIBLE_BY_ID'] = $_GET['RESPONSIBLE_BY_ID'] = array();
			}
		}

		if (intval($this->arParams['ORDER_COUNT']) <= 0)
			$this->arParams['ORDER_COUNT'] = 20;

		$arNavParams = array(
			'nPageSize' => $this->arParams['ORDER_COUNT']
		);

		$gridOptions = new \Bitrix\Main\Grid\Options($this->arResult['GRID_ID'], $this->arResult['FILTER_PRESETS']);
		$filter = $this->createFilter();

		$arNavParams = $gridOptions->GetNavParams($arNavParams);
		$arNavParams['bShowAll'] = false;
		$CCrmUserType->PrepareListFilterValues($this->arResult['FILTER'], $filter, $this->arResult['GRID_ID']);
		$USER_FIELD_MANAGER->AdminListAddFilter(Order\Order::getUfId(), $filter);

		// converts data from filter
		\Bitrix\Crm\UI\Filter\EntityHandler::internalize($this->arResult['FILTER'], $filter);

		$visibleColumns = $gridOptions->GetVisibleColumns();

		// Fill in default values if empty
		if (empty($visibleColumns))
		{
			foreach ($this->arResult['HEADERS'] as $arHeader)
			{
				if ($arHeader['default'])
				{
					$visibleColumns[] = $arHeader['id'];
				}
			}

			//Disable bizproc fields processing
			$this->arResult['ENABLE_BIZPROC'] = false;
		}
		else
		{
			//Check if bizproc fields selected
			$hasBizprocFields = false;

			foreach($visibleColumns as $key => &$fieldName)
			{
				if(mb_substr($fieldName, 0, 8) === 'BIZPROC_')
				{
					$hasBizprocFields = true;
					break;
				}
			}

			$this->arResult['ENABLE_BIZPROC'] = $hasBizprocFields;
			unset($fieldName);
		}

		[$this->arResult['SORT'], $this->arResult['SORT_VARS']] = $this->getSortFields(
			$arSort,
			$gridOptions,
			$visibleColumns,
			$this->arResult['HEADERS']
		);

		if ($CCrmUserType->NormalizeFields($visibleColumns))
		{
			$gridOptions->SetVisibleColumns($visibleColumns);
		}

		$columns = $visibleColumns;
		if ($this->isExportMode() && $this->exportParams['STEXPORT_EXPORT_ALL_FIELDS'] === 'Y')
		{
			$columns = array_column($this->arResult['HEADERS'], 'id');
		}
		$arSelect = array_intersect($columns, \Bitrix\Crm\Order\Order::getAllFields());

		$userFields = $CCrmUserType->GetFields();

		$ufColumns = [];
		if(is_array($userFields) && !empty($userFields))
		{
			if ($this->isExportMode() && $this->exportParams['STEXPORT_EXPORT_ALL_FIELDS'] === 'Y')
			{
				$ufColumns = array_keys($userFields);
			}
			else
			{
				$ufColumns = array_intersect(array_keys($userFields), $visibleColumns);
			}

			if(!empty($ufColumns))
			{
				$arSelect = array_merge($arSelect, $ufColumns);
			}
		}
		if ($this->isExportMode())
		{
			$this->exportParams['SELECTED_HEADERS'] = array_merge($columns, $ufColumns);
		}
		$arSelect[] = 'ORDER_TOPIC';

		$this->arResult['ENABLE_TASK'] = IsModuleInstalled('tasks');

		if($this->arResult['ENABLE_TASK'])
		{
			$this->arResult['TASK_CREATE_URL'] = CHTTP::urlAddParams(
				CComponentEngine::MakePathFromTemplate(
					COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
					array(
						'task_id' => 0,
						'user_id' => $this->userId
					)
				),
				array(
					'UF_CRM_TASK' => '#ENTITY_KEYS#',
					'ORDER_TOPIC' => urlencode(Loc::getMessage('CRM_TASK_TITLE_PREFIX')),
					'TAGS' => urlencode(Loc::getMessage('CRM_TASK_TAG')),
					'back_url' => urlencode($this->arParams['PATH_TO_ORDER_LIST'])
				)
			);
		}

		if(!in_array('ACCOUNT_NUMBER', $arSelect, true))
			$arSelect[] = 'ACCOUNT_NUMBER';

		if(!in_array('REASON_CANCELED', $arSelect, true))
			$arSelect[] = 'REASON_CANCELED';


		if(in_array('CREATED_BY', $arSelect, true))
		{
			$addictFields = array(
				'CREATED_BY_LOGIN' => 'CREATED_USER.LOGIN',
				'CREATED_BY_NAME'  => 'CREATED_USER.NAME',
				'CREATED_BY_LAST_NAME' => 'CREATED_USER.LAST_NAME',
				'CREATED_BY_SECOND_NAME'  => 'CREATED_USER.SECOND_NAME'
			);

			$arSelect = array_merge($arSelect, $addictFields);
			unset($addictFields);
		}
		if(in_array('USER_ID', $arSelect, true))
		{
			$addictFields = array(
				'USER_LOGIN' => 'USER.LOGIN',
				'USER_NAME'  => 'USER.NAME',
				'USER_LAST_NAME' => 'USER.LAST_NAME',
				'USER_SECOND_NAME'  => 'USER.SECOND_NAME'
			);

			$arSelect = array_merge($arSelect, $addictFields);
			unset($addictFields);
		}

		if(in_array('ACTIVITY_ID', $visibleColumns, true))
		{
			$this->addActivitySelection($arSelect, $runtime);
		}

		if(in_array('SUM', $visibleColumns, true))
		{
			$arSelect[] = 'PRICE';
			$arSelect[] = 'CURRENCY';
		}

		if(in_array('SOURCE', $visibleColumns, true))
		{
			$arSelect["TRADING_PLATFORM_CODE"] = 'TRADING_PLATFORM.TRADING_PLATFORM.CODE';
			$arSelect["TRADING_PLATFORM_CLASS"] = 'TRADING_PLATFORM.TRADING_PLATFORM.CLASS';
		}

		if(in_array('RESPONSIBLE_BY', $visibleColumns, true))
		{
			$arSelect[] = 'RESPONSIBLE_ID';
			$this->addUserInfoSelection('RESPONSIBLE_ID', 'RESPONSIBLE_BY', $arSelect, $runtime);
		}
		if(in_array('EMP_PAYED_ID', $visibleColumns, true))
		{
			$arSelect[] = 'EMP_PAYED_ID';
			$this->addUserInfoSelection('EMP_PAYED_ID', 'EMP_PAYED_ID', $arSelect, $runtime);
		}
		if(in_array('EMP_CANCELED_ID', $visibleColumns, true))
		{
			$arSelect[] = 'EMP_CANCELED_ID';
			$this->addUserInfoSelection('EMP_CANCELED_ID', 'EMP_CANCELED_ID', $arSelect, $runtime);
		}
		if(in_array('EMP_STATUS_ID', $visibleColumns, true))
		{
			$arSelect[] = 'EMP_STATUS_ID';
			$this->addUserInfoSelection('EMP_STATUS_ID', 'EMP_STATUS_ID', $arSelect, $runtime);
		}
		if(in_array('EMP_ALLOW_DELIVERY_ID', $visibleColumns, true))
		{
			$arSelect[] = 'EMP_ALLOW_DELIVERY_ID';
			$this->addUserInfoSelection('EMP_ALLOW_DELIVERY_ID', 'EMP_ALLOW_DELIVERY_ID', $arSelect, $runtime);
		}
		if(in_array('EMP_DEDUCTED_ID', $visibleColumns, true))
		{
			$arSelect[] = 'EMP_DEDUCTED_ID';
			$this->addUserInfoSelection('EMP_DEDUCTED_ID', 'EMP_DEDUCTED_ID', $arSelect, $runtime);
		}

		if(in_array('USER', $visibleColumns, true))
		{
			$arSelect[] = 'USER_ID';
			$this->addUserInfoSelection('USER_ID', 'USER', $arSelect, $runtime);
		}

		// Always need to remove the menu items
		if (!in_array('STATUS_ID', $arSelect))
			$arSelect[] = 'STATUS_ID';

		// For bizproc
		if (!in_array('RESPONSIBLE_ID', $arSelect))
			$arSelect[] = 'RESPONSIBLE_ID';

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

		if(isset($arSort['USER']))
		{
			$arSort['USER_ID'] = $arSort['USER'];
			unset($arSort['USER']);
		}

		$arOptions = $arExportOptions = array('FIELD_OPTIONS' => array('ADDITIONAL_FIELDS' => array()));
		if(in_array('ACTIVITY_ID', $arSelect, true))
		{
			$arOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
			$arExportOptions['FIELD_OPTIONS']['ADDITIONAL_FIELDS'][] = 'ACTIVITY';
		}

		if(isset($arSort['contact_full_name']))
		{
			$arSort['contact_last_name'] = $arSort['contact_full_name'];
			$arSort['contact_name'] = $arSort['contact_full_name'];
			unset($arSort['contact_full_name']);
		}
		if(isset($arSort['order_client']))
		{
			$arSort['contact_last_name'] = $arSort['order_client'];
			$arSort['contact_name'] = $arSort['order_client'];
			$arSort['company_title'] = $arSort['order_client'];
			unset($arSort['order_client']);
		}

		if(isset($this->arParams['IS_EXTERNAL_CONTEXT']))
		{
			$arOptions['IS_EXTERNAL_CONTEXT'] = $this->arParams['IS_EXTERNAL_CONTEXT'];
		}

		//FIELD_OPTIONS
		$arSelect = array_unique($arSelect, SORT_STRING);

		$this->arResult['ORDER'] = array();
		$this->arResult['ORDER_ID'] = array();
		$this->arResult['ORDER_UF'] = array();

		$glFilter = $this->createGlFilter($filter, $runtime);

		// POST & GET actions processing -->
		$this->requestProcessing($actionData, $glFilter);

		//region Navigation data initialization
		$nav = $this->getNavigation($arNavParams, $glFilter, $arSelect, $runtime);

		$glParams = array(
			'filter' => $glFilter,
			'order' => $arSort,
			'select' => $arSelect,
			'offset' => $nav->getOffset(),
			'limit' => $nav->getLimit()
		);

		if (is_array($glFilter))
		{
			$filterKeys = array_keys($glFilter);
			if (preg_grep("/^SHIPMENT./", $filterKeys) || preg_grep("/^PAYMENT./", $filterKeys))
			{
				$glParams['group'] = 'ID';
			}
		}

		if(!empty($runtime))
		{
			$glParams['runtime'] = $runtime;
		}

		$dbResult = Bitrix\Crm\Order\Order::getList($glParams);

		while($arOrder = $dbResult->fetch())
		{
			$this->arResult['ORDER'][$arOrder['ID']] = $arOrder;
			$this->arResult['ORDER_ID'][$arOrder['ID']] = $arOrder['ID'];
			$this->arResult['ORDER_UF'][$arOrder['ID']] = array();
		}


		$this->arResult['ROWS_COUNT'] = $nav->getRecordCount();
		$enableNextPage = ($nav->getPageCount() > $nav->getCurrentPage());
		if ($this->isExportMode())
		{
			$this->exportParams['STEXPORT_IS_FIRST_PAGE'] = 'N';
			$this->exportParams['STEXPORT_IS_LAST_PAGE'] = 'N';
			if ($nav->getCurrentPage() === 1)
			{
				$this->exportParams['STEXPORT_TOTAL_ITEMS'] = $nav->getRecordCount();
				$this->exportParams['STEXPORT_IS_FIRST_PAGE'] = 'Y';
			}
			elseif ($enableNextPage)
			{
				$this->exportParams['STEXPORT_IS_LAST_PAGE'] = 'Y';
			}
		}

		//region Navigation data storing
		$this->arResult['PAGINATION'] = array(
			'PAGE_NUM' => $nav->getCurrentPage(),
			'ENABLE_NEXT_PAGE' => $enableNextPage,
			'NAV_OBJECT' => $nav,
			//"SEF_MODE" => "Y",
			"SHOW_COUNT" => "N"
		);

		$this->arResult['DB_FILTER'] = $glFilter;

		if(!isset($_SESSION['CRM_GRID_DATA']))
		{
			$_SESSION['CRM_GRID_DATA'] = array();
		}
		$_SESSION['CRM_GRID_DATA'][$this->arResult['GRID_ID']] = array(
			'FILTER' => $glFilter,
			'SELECT' => $arSelect,
			'RUNTIME' => $runtime
		);
		//endregion

		$entityAttrs = \Bitrix\Crm\Order\Permissions\Order::getPermissionAttributes(array_keys($this->arResult['ORDER']));

		$this->arResult['PAGINATION']['URL'] = $APPLICATION->GetCurPageParam('', array('apply_filter', 'clear_filter', 'save', 'page', 'sessid', 'internal'));
		$now = time() + CTimeZone::GetOffset();
		$aclivitylessItems = array();

		$currencyList = \CCrmCurrencyHelper::PrepareListItems();
		$personTypes = \Bitrix\Crm\Order\PersonType::load(SITE_ID);

		$ordersIds = array_keys($this->arResult['ORDER']);

		$basketData = [];

		if(in_array('BASKET', $visibleColumns, true))
		{
			$basketData = $this->loadBasketData($ordersIds);
		}

		$shipmentData = [];

		if(in_array('SHIPMENT', $visibleColumns, true))
		{
			$shipmentData = $this->loadShipmentData($ordersIds);
		}

		$paymentData = [];

		if(in_array('PAYMENT', $visibleColumns, true))
		{
			$paymentData = $this->loadPaymentData($ordersIds);
		}


		$clientData = [];
		$needContactAndCompany = in_array('CLIENT', $visibleColumns, true);
		$needContact = $needContactAndCompany || in_array('CONTACT', $visibleColumns, true);
		$needCompany = $needContactAndCompany || in_array('COMPANY', $visibleColumns, true);

		if($needContact || $needCompany)
		{
			$clientData = $this->loadClientData($ordersIds, $needContact, $needCompany);
		}

		foreach($this->arResult['ORDER'] as &$arOrder)
		{
			$entityID = $arOrder['ID'];
			$arOrder['DATE_INSERT'] = !empty($arOrder['DATE_INSERT']) ? CCrmComponentHelper::TrimDateTimeString(ConvertTimeStamp(MakeTimeStamp($arOrder['DATE_INSERT']), 'FULL', SITE_ID)) : '';
			$currencyID =  isset($arOrder['CURRENCY']) ? $arOrder['CURRENCY'] : CCrmCurrency::GetBaseCurrencyID();
			$arOrder['CURRENCY'] = htmlspecialcharsbx($currencyList[$currencyID]);
			$arOrder['PATH_TO_ORDER_DETAILS'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_ORDER_DETAILS'],
				array('order_id' => $entityID)
			);

			$arOrder['PATH_TO_ORDER_SHOW'] = $arOrder['PATH_TO_ORDER_DETAILS'];
			$arOrder['PATH_TO_ORDER_EDIT'] = CCrmUrlUtil::AddUrlParams(
				$arOrder['PATH_TO_ORDER_DETAILS'],
				array('init_mode' => 'edit')
			);

			$arOrder['PATH_TO_ORDER_COPY'] =  CHTTP::urlAddParams(
				$arOrder['PATH_TO_ORDER_EDIT'],
				array('copy' => 1)
			);

			$arOrder['PATH_TO_ORDER_DELETE'] =  CHTTP::urlAddParams(
				$this->isInternal ? $APPLICATION->GetCurPage() : $this->arParams['PATH_TO_CURRENT_LIST'],
				array('action_'.$this->arResult['GRID_ID'] => 'delete', 'ID' => $entityID, 'sessid' => $this->arResult['SESSION_ID'])
			);

			$contactID = isset($arOrder['~CONTACT_ID']) ? intval($arOrder['~CONTACT_ID']) : 0;
			$arOrder['PATH_TO_CONTACT_SHOW'] = $contactID <= 0 ? ''
				: CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_CONTACT_SHOW'], array('contact_id' => $contactID));

			$arOrder['~CONTACT_FORMATTED_NAME'] = $contactID <= 0 ? ''
				: CCrmContact::PrepareFormattedName(
						array(
							'HONORIFIC' => isset($arOrder['~CONTACT_HONORIFIC']) ? $arOrder['~CONTACT_HONORIFIC'] : '',
							'NAME' => isset($arOrder['~CONTACT_NAME']) ? $arOrder['~CONTACT_NAME'] : '',
							'LAST_NAME' => isset($arOrder['~CONTACT_LAST_NAME']) ? $arOrder['~CONTACT_LAST_NAME'] : '',
							'SECOND_NAME' => isset($arOrder['~CONTACT_SECOND_NAME']) ? $arOrder['~CONTACT_SECOND_NAME'] : ''
						)
					);
			$arOrder['CONTACT_FORMATTED_NAME'] = htmlspecialcharsbx($arOrder['~CONTACT_FORMATTED_NAME']);

			$arOrder['~CONTACT_FULL_NAME'] = $contactID <= 0 ? ''
				: CCrmContact::GetFullName(
					array(
						'HONORIFIC' => isset($arOrder['~CONTACT_HONORIFIC']) ? $arOrder['~CONTACT_HONORIFIC'] : '',
						'NAME' => isset($arOrder['~CONTACT_NAME']) ? $arOrder['~CONTACT_NAME'] : '',
						'LAST_NAME' => isset($arOrder['~CONTACT_LAST_NAME']) ? $arOrder['~CONTACT_LAST_NAME'] : '',
						'SECOND_NAME' => isset($arOrder['~CONTACT_SECOND_NAME']) ? $arOrder['~CONTACT_SECOND_NAME'] : ''
					)
				);
			$arOrder['CONTACT_FULL_NAME'] = htmlspecialcharsbx($arOrder['~CONTACT_FULL_NAME']);

			$companyID = isset($arOrder['~COMPANY_ID']) ? intval($arOrder['~COMPANY_ID']) : 0;
			$arOrder['PATH_TO_COMPANY_SHOW'] = $companyID <= 0 ? ''
				: CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_COMPANY_SHOW'], array('company_id' => $companyID));

			$arOrder['PATH_TO_RESPONSIBLE_PROFILE'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_USER_PROFILE'],
				array('user_id' => $arOrder['RESPONSIBLE_ID'])
			);

			$arOrder['PATH_TO_USER_PROFILE'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_BUYER_PROFILE'],
				array('user_id' => $arOrder['USER_ID'])
			);
			$arOrder['PATH_TO_USER_BP'] = CComponentEngine::MakePathFromTemplate($this->arParams['PATH_TO_USER_BP'],
				array('user_id' => $this->userId)
			);

			$arOrder['USER_FORMATTED_NAME'] = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'],
				array(
					'LOGIN' => $arOrder['USER_LOGIN'],
					'NAME' => $arOrder['USER_NAME'],
					'LAST_NAME' => $arOrder['USER_LAST_NAME'],
					'SECOND_NAME' => $arOrder['USER_SECOND_NAME']
				),
				true
			);

			if ($arOrder['CREATED_BY'] > 0)
			{
				$arOrder['PATH_TO_USER_CREATOR'] = CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_BUYER_PROFILE'],
					array('user_id' => $arOrder['CREATED_BY'])
				);

				$arOrder['CREATED_BY_FORMATTED_NAME'] = CUser::FormatName(
					$this->arParams['NAME_TEMPLATE'],
					array(
						'LOGIN' => $arOrder['CREATED_BY_LOGIN'],
						'NAME' => $arOrder['CREATED_BY_NAME'],
						'LAST_NAME' => $arOrder['CREATED_BY_LAST_NAME'],
						'SECOND_NAME' => $arOrder['CREATED_BY_SECOND_NAME']
					),
					true
				);
			}

			if ($arOrder['EMP_PAYED_ID'] > 0)
			{
				$arOrder['PATH_TO_EMP_PAYED_ID'] = CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_BUYER_PROFILE'],
					array('user_id' => $arOrder['EMP_PAYED_ID'])
				);

				$arOrder['EMP_PAYED_ID_FORMATTED_NAME'] = CUser::FormatName(
					$this->arParams['NAME_TEMPLATE'],
					array(
						'LOGIN' => $arOrder['EMP_PAYED_ID_LOGIN'],
						'NAME' => $arOrder['EMP_PAYED_ID_NAME'],
						'LAST_NAME' => $arOrder['EMP_PAYED_ID_LAST_NAME'],
						'SECOND_NAME' => $arOrder['EMP_PAYED_ID_SECOND_NAME']
					),
					true
				);
			}
			if ($arOrder['EMP_CANCELED_ID'] > 0)
			{
				$arOrder['PATH_TO_EMP_CANCELED_ID'] = CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_BUYER_PROFILE'],
					array('user_id' => $arOrder['EMP_CANCELED_ID'])
				);

				$arOrder['EMP_CANCELED_ID_FORMATTED_NAME'] = CUser::FormatName(
					$this->arParams['NAME_TEMPLATE'],
					array(
						'LOGIN' => $arOrder['EMP_CANCELED_ID_LOGIN'],
						'NAME' => $arOrder['EMP_CANCELED_ID_NAME'],
						'LAST_NAME' => $arOrder['EMP_CANCELED_ID_LAST_NAME'],
						'SECOND_NAME' => $arOrder['EMP_CANCELED_ID_SECOND_NAME']
					),
					true
				);
			}
			if ($arOrder['EMP_STATUS_ID'] > 0)
			{
				$arOrder['PATH_TO_EMP_STATUS_ID'] = CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_BUYER_PROFILE'],
					array('user_id' => $arOrder['EMP_STATUS_ID'])
				);

				$arOrder['EMP_STATUS_ID_FORMATTED_NAME'] = CUser::FormatName(
					$this->arParams['NAME_TEMPLATE'],
					array(
						'LOGIN' => $arOrder['EMP_STATUS_ID_LOGIN'],
						'NAME' => $arOrder['EMP_STATUS_ID_NAME'],
						'LAST_NAME' => $arOrder['EMP_STATUS_ID_LAST_NAME'],
						'SECOND_NAME' => $arOrder['EMP_STATUS_ID_SECOND_NAME']
					),
					true
				);
			}
			if ($arOrder['EMP_ALLOW_DELIVERY_ID'] > 0)
			{
				$arOrder['PATH_TO_EMP_ALLOW_DELIVERY_ID'] = CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_BUYER_PROFILE'],
					array('user_id' => $arOrder['EMP_ALLOW_DELIVERY_ID'])
				);

				$arOrder['EMP_ALLOW_DELIVERY_ID_FORMATTED_NAME'] = CUser::FormatName(
					$this->arParams['NAME_TEMPLATE'],
					array(
						'LOGIN' => $arOrder['EMP_ALLOW_DELIVERY_ID_LOGIN'],
						'NAME' => $arOrder['EMP_ALLOW_DELIVERY_ID_NAME'],
						'LAST_NAME' => $arOrder['EMP_ALLOW_DELIVERY_ID_LAST_NAME'],
						'SECOND_NAME' => $arOrder['EMP_ALLOW_DELIVERY_ID_SECOND_NAME']
					),
					true
				);
			}
			if ($arOrder['EMP_DEDUCTED_ID'] > 0)
			{
				$arOrder['PATH_TO_EMP_DEDUCTED_ID'] = CComponentEngine::MakePathFromTemplate(
					$this->arParams['PATH_TO_BUYER_PROFILE'],
					array('user_id' => $arOrder['EMP_DEDUCTED_ID'])
				);

				$arOrder['EMP_DEDUCTED_ID_FORMATTED_NAME'] = CUser::FormatName(
					$this->arParams['NAME_TEMPLATE'],
					array(
						'LOGIN' => $arOrder['EMP_DEDUCTED_ID_LOGIN'],
						'NAME' => $arOrder['EMP_DEDUCTED_ID_NAME'],
						'LAST_NAME' => $arOrder['EMP_DEDUCTED_ID_LAST_NAME'],
						'SECOND_NAME' => $arOrder['EMP_DEDUCTED_ID_SECOND_NAME']
					),
					true
				);
			}

			$arOrder['STATUS_ID'] = isset($arOrder['STATUS_ID']) ? $arOrder['STATUS_ID'] : '';
			$arOrder['ORDER_STAGE_NAME'] = $arOrder['STATUS_ID'];
			$arOrder['PERSON_TYPE_ID'] = htmlspecialcharsbx($personTypes[$arOrder['PERSON_TYPE_ID']]['NAME']);

			//region Client info
			if($contactID > 0)
			{
				$arOrder['CONTACT_INFO'] = array(
					'ENTITY_TYPE_ID' => CCrmOwnerType::Contact,
					'ENTITY_ID' => $contactID
				);

				if(!CCrmContact::CheckReadPermission($contactID, $this->userPermissions))
				{
					$arOrder['CONTACT_INFO']['IS_HIDDEN'] = true;
				}
				else
				{
					$arOrder['CONTACT_INFO'] =
						array_merge(
							$arOrder['CONTACT_INFO'],
							array(
								'ORDER_TOPIC' => isset($arOrder['~CONTACT_FORMATTED_NAME']) ? $arOrder['~CONTACT_FORMATTED_NAME'] : ('['.$contactID.']'),
								'PREFIX' => "ORDER_{$arOrder['~ID']}",
								'DESCRIPTION' => isset($arOrder['~COMPANY_TITLE']) ? $arOrder['~COMPANY_TITLE'] : ''
							)
						);
				}
			}
			if($companyID > 0)
			{
				$arOrder['COMPANY_INFO'] = array(
					'ENTITY_TYPE_ID' => CCrmOwnerType::Company,
					'ENTITY_ID' => $companyID
				);

				if(!CCrmCompany::CheckReadPermission($companyID, $this->userPermissions))
				{
					$arOrder['COMPANY_INFO']['IS_HIDDEN'] = true;
				}
				else
				{
					$arOrder['COMPANY_INFO'] =
						array_merge(
							$arOrder['COMPANY_INFO'],
							array(
								'ORDER_TOPIC' => isset($arOrder['~COMPANY_TITLE']) ? $arOrder['~COMPANY_TITLE'] : ('['.$companyID.']'),
								'PREFIX' => "ORDER_{$arOrder['~ID']}"
							)
						);
				}
			}

			//endregion

			if(isset($arOrder['ACTIVITY_TIME']))
			{
				$time = MakeTimeStamp($arOrder['ACTIVITY_TIME']);
				$arOrder['ACTIVITY_EXPIRED'] = $time <= $now;
				$arOrder['ACTIVITY_IS_CURRENT_DAY'] = $arOrder['ACTIVITY_EXPIRED'] || CCrmActivity::IsCurrentDay($time);
			}

			if(isset($arOrder['TRADING_PLATFORM_CODE']))
			{
				$arOrder['SOURCE'] = htmlspecialcharsbx(
					$this->getTradingPlatformName(
						$arOrder['TRADING_PLATFORM_CODE'],
						$arOrder['TRADING_PLATFORM_CLASS']
				));
			}

			$originatorID = isset($arOrder['~ORIGINATOR_ID']) ? $arOrder['~ORIGINATOR_ID'] : '';
			if($originatorID !== '')
			{
				$arOrder['~ORIGINATOR_NAME'] = isset($this->arResult['EXTERNAL_SALES'][$originatorID])
					? $this->arResult['EXTERNAL_SALES'][$originatorID] : '';

				$arOrder['ORIGINATOR_NAME'] = htmlspecialcharsbx($arOrder['~ORIGINATOR_NAME']);
			}

			if ($this->arResult['ENABLE_TASK'])
			{
				$arOrder['PATH_TO_TASK_EDIT'] = CHTTP::urlAddParams(
					CComponentEngine::MakePathFromTemplate(COption::GetOptionString('tasks', 'paths_task_user_edit', ''),
						array('task_id' => 0, 'user_id' => $this->userId)
					),
					array(
						'UF_CRM_TASK' => "O_{$entityID}",
						'ORDER_TOPIC' => urlencode(Loc::getMessage('CRM_TASK_TITLE_PREFIX').' '),
						'TAGS' => urlencode(Loc::getMessage('CRM_TASK_TAG')),
						'back_url' => urlencode($this->arParams['PATH_TO_ORDER_LIST'])
					)
				);
			}

			if (IsModuleInstalled('sale'))
			{
				$arOrder['PATH_TO_QUOTE_ADD'] =
					CHTTP::urlAddParams(
						CComponentEngine::makePathFromTemplate(
							$this->arParams['PATH_TO_QUOTE_EDIT'],
							array('quote_id' => 0)
						),
						array('order_id' => $entityID)
					);
				$arOrder['PATH_TO_INVOICE_ADD'] =
					CHTTP::urlAddParams(
						CComponentEngine::makePathFromTemplate(
							$this->arParams['PATH_TO_INVOICE_EDIT'],
							array('invoice_id' => 0)
						),
						array('order' => $entityID)
					);
			}

			$arOrder['RESPONSIBLE_BY'] = CUser::FormatName(
				$this->arParams['NAME_TEMPLATE'],
				array(
					'LOGIN' => isset($arOrder['RESPONSIBLE_BY_LOGIN']) ? $arOrder['RESPONSIBLE_BY_LOGIN'] : '',
					'NAME' => isset($arOrder['RESPONSIBLE_BY_NAME']) ? $arOrder['RESPONSIBLE_BY_NAME'] : '',
					'LAST_NAME' => isset($arOrder['RESPONSIBLE_BY_LAST_NAME']) ? $arOrder['RESPONSIBLE_BY_LAST_NAME'] : '',
					'SECOND_NAME' => isset($arOrder['RESPONSIBLE_BY_SECOND_NAME']) ? $arOrder['RESPONSIBLE_BY_SECOND_NAME'] : ''
				),
				true
			);

			$arOrder['ORDER_SUMMARY'] = Loc::getMessage('CRM_ORDER_SUMMARY', array('#ORDER_NUMBER#' => $arOrder['ACCOUNT_NUMBER']));
			$arOrder['SUM'] = CCrmCurrency::MoneyToString($arOrder['PRICE'], $currencyID);
			$arOrder['SUM_PAID'] = CCrmCurrency::MoneyToString($arOrder['SUM_PAID'], $currencyID);
			$arOrder['DISCOUNT_VALUE'] = CCrmCurrency::MoneyToString($arOrder['DISCOUNT_VALUE'], $currencyID);
			$arOrder['PRICE_DELIVERY'] = CCrmCurrency::MoneyToString($arOrder['PRICE_DELIVERY'], $currencyID);
			$arOrder['TAX_VALUE'] = CCrmCurrency::MoneyToString($arOrder['TAX_VALUE'], $currencyID);

			// todo: order
			foreach($arOrder as $name => $field)
			{
				if ($name[0] !== '~')
				{
					$arOrder['~'.$name] = $field;
				}
			}

			$arOrder['DELETE'] = $arOrder['EDIT'] = !$arOrder['INTERNAL'];

			if (!(is_object($USER) && $USER->IsAdmin()))
			{
				$arOrder['EDIT'] = \Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission(
					$entityID,
					$this->userPermissions,
					array('ENTITY_ATTRS' => $entityAttrs)
				);

				$arOrder['DELETE'] = \Bitrix\Crm\Order\Permissions\Order::checkDeletePermission(
					$entityID,
					$this->userPermissions,
					array('ENTITY_ATTRS' => $entityAttrs)
				);
			}

			if(!empty( $basketData[$entityID]))
			{
				$arOrder['BASKET'] = $basketData[$entityID];
			}

			if(!empty( $shipmentData[$entityID]))
			{
				$arOrder['SHIPMENT'] = $shipmentData[$entityID];
			}

			if(!empty( $paymentData[$entityID]))
			{
				$arOrder['PAYMENT'] = $paymentData[$entityID];
			}

			if(!empty( $clientData[$entityID][\CCrmOwnerType::Company]))
			{
				$arOrder['COMPANY'] = $clientData[$entityID][\CCrmOwnerType::Company];
			}

			if(!empty( $clientData[$entityID][\CCrmOwnerType::Contact]))
			{
				$arOrder['CONTACT'] = $clientData[$entityID][\CCrmOwnerType::Contact];
			}

			if(isset($arOrder['CONTACT']))
			{
				$arOrder['CLIENT'] = $arOrder['CONTACT'];
			}
			elseif(isset($arOrder['COMPANY']))
			{
				$arOrder['CLIENT'] = $arOrder['COMPANY'];
			}

			if(in_array('PROPS', $visibleColumns, true))
			{
				$arOrder['PROPS'] = $this->loadPropsData($entityID);
			}

			$this->arResult['ORDER'][$entityID] = $arOrder;

			$userActivityID = isset($arOrder['USER_ACTIVITY_ID']) ? intval($arOrder['USER_ACTIVITY_ID']) : 0;
			$commonActivityID = isset($arOrder['C_ACTIVITY_ID']) ? intval($arOrder['C_ACTIVITY_ID']) : 0;
			if($userActivityID <= 0 && $commonActivityID <= 0)
			{
				$aclivitylessItems[] = $entityID;
			}

		}
		unset($arOrder);

		if(!empty($aclivitylessItems))
		{
			$waitingInfos = \Bitrix\Crm\Pseudoactivity\WaitEntry::getRecentInfos(CCrmOwnerType::Order, $aclivitylessItems);
			foreach($waitingInfos as $waitingInfo)
			{
				$entityID = (int)$waitingInfo['OWNER_ID'];
				if(isset($this->arResult['ORDER'][$entityID]))
				{
					$this->arResult['ORDER'][$entityID]['WAITING_TITLE'] = $waitingInfo['TITLE'];
				}
			}
		}

		$CCrmUserType->ListAddEnumFieldsValue(
			$this->arResult,
			$this->arResult['ORDER'],
			$this->arResult['ORDER_UF'],
			'<br />',
			false,
			array(
				'FILE_URL_TEMPLATE' =>
					'/bitrix/components/bitrix/crm.order.details/show_file.php?ownerId=#owner_id#&fieldName=#field_name#&fileId=#file_id#'
			)
		);

		$this->arResult['ENABLE_TOOLBAR'] = isset($this->arParams['ENABLE_TOOLBAR']) ? $this->arParams['ENABLE_TOOLBAR'] : false;
		if($this->arResult['ENABLE_TOOLBAR'])
		{
			$this->arResult['PATH_TO_ORDER_ADD'] = CComponentEngine::MakePathFromTemplate(
				$this->arParams['PATH_TO_ORDER_EDIT'],
				array('order_id' => 0)
			);

			$addParams = array();

			if($this->isInternal && isset($this->arParams['INTERNAL_CONTEXT']) && is_array($this->arParams['INTERNAL_CONTEXT']))
			{
				$internalContext = $this->arParams['INTERNAL_CONTEXT'];
				if(isset($internalContext['CONTACT_ID']))
				{
					$addParams['contact_id'] = $internalContext['CONTACT_ID'];
				}
				if(isset($internalContext['COMPANY_ID']))
				{
					$addParams['company_id'] = $internalContext['COMPANY_ID'];
				}
			}

			if(!empty($addParams))
			{
				$this->arResult['PATH_TO_ORDER_ADD'] = CHTTP::urlAddParams(
					$this->arResult['PATH_TO_ORDER_ADD'],
					$addParams
				);
			}
		}

		$this->arResult['NEED_FOR_REBUILD_ORDER_ATTRS'] =
			$this->arResult['NEED_FOR_REBUILD_ORDER_SEMANTICS'] =
			$this->arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] =
			$this->arResult['NEED_FOR_BUILD_TIMELINE'] = false;

		if(!$this->isInternal)
		{
			if(COption::GetOptionString('crm', '~CRM_REBUILD_ORDER_SEARCH_CONTENT', 'N') === 'Y')
			{
				$this->arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = true;
			}

			$this->arResult['NEED_FOR_REFRESH_ACCOUNTING'] = \Bitrix\Crm\Agent\Accounting\OrderAccountSyncAgent::getInstance()->isEnabled();

			/** @var OrderSearchContentRebuildAgent $agent */
			$agent = OrderSearchContentRebuildAgent::getInstance();
			$isAgentEnabled = $agent->isEnabled();
			if ($isAgentEnabled)
			{
				if (!$agent->isActive())
				{
					$agent->enable(false);
					$isAgentEnabled = false;
				}
			}
			$arResult['NEED_FOR_REBUILD_SEARCH_CONTENT'] = $isAgentEnabled;

			if(CCrmPerms::IsAdmin())
			{
				if(COption::GetOptionString('crm', '~CRM_REBUILD_ORDER_ATTR', 'N') === 'Y')
				{
					$this->arResult['PATH_TO_PRM_LIST'] = CComponentEngine::MakePathFromTemplate(COption::GetOptionString('crm', 'path_to_perm_list'));
					$this->arResult['NEED_FOR_REBUILD_ORDER_ATTRS'] = true;
				}
				if(COption::GetOptionString('crm', '~CRM_REBUILD_ORDER_SEMANTICS', 'N') === 'Y')
				{
					$this->arResult['NEED_FOR_REBUILD_ORDER_SEMANTICS'] = true;
				}
			}
		}

		if(!empty($this->errors))
		{
			$this->showErrors();
		}

		if ($this->isExportMode())
		{
			$this->arResult = array_merge($this->arResult, $this->exportParams);
			$this->IncludeComponentTemplate($this->exportParams['TYPE']);

			return array(
				'PROCESSED_ITEMS' => count($this->arResult['ORDER']),
				'TOTAL_ITEMS' => $this->arResult['STEXPORT_TOTAL_ITEMS']
			);
		}

		$this->IncludeComponentTemplate();
		include_once($_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/crm.order/include/nav.php');
		return $this->arResult['ROWS_COUNT'];
	}

	/**
	 * @return string
	 */
	protected function combineGridIdentifier()
	{
		if(isset($this->arParams['GRID_ID']) && is_string($this->arParams['GRID_ID']) && !empty($this->arParams['GRID_ID']))
		{
			return $this->arParams['GRID_ID'];
		}
		return 'CRM_ORDER_LIST_V12'.($this->isInternal && !empty($this->arParams['GRID_ID_SUFFIX']) ? '_'.$this->arParams['GRID_ID_SUFFIX'] : '');
	}

	/**
	 * @param array $arNavParams
	 * @param array $glFilter
	 * @param array $arSelect
	 * @param array $runtime
	 *
	 * @return Main\UI\PageNavigation
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 */
	private function getNavigation(array $arNavParams, array $glFilter, array $arSelect, array $runtime)
	{
		$pageNum = 0;
		if ($this->isExportMode() &&  (int)$this->arParams['STEXPORT_PAGE_SIZE'] > 0)
		{
			$pageSize = (int)$this->arParams['STEXPORT_PAGE_SIZE'];
		}
		else
		{
			$pageSize = (int)(isset($arNavParams['nPageSize']) ? $arNavParams['nPageSize'] : $this->arParams['ORDER_COUNT']);
		}

		$res = Bitrix\Crm\Order\Order::getList(array(
			'filter' => $glFilter,
			'select' => $arSelect,
			'count_total' => true,
			'runtime' => $runtime)
		);

		$total = $res->getCount();

		if (isset($_REQUEST['apply_filter']) && $_REQUEST['apply_filter'] === 'Y')
		{
			$pageNum = 1;
		}
		elseif ($pageSize > 0 && (isset($this->arParams['PAGE_NUMBER']) || isset($_REQUEST['page'])))
		{
			$pageNum = (int)$this->arParams['PAGE_NUMBER'] > 0 ? (int)$this->arParams['PAGE_NUMBER'] : (int)$_REQUEST['page'];
			if ($pageNum < 0)
			{
				//Backward mode
				$offset = -($pageNum + 1);
				$pageNum = (int)(ceil($total / $pageSize)) - $offset;
				if ($pageNum <= 0)
				{
					$pageNum = 1;
				}
			}
		}

		if ($pageNum > 0)
		{
			if (!isset($_SESSION['CRM_PAGINATION_DATA']))
			{
				$_SESSION['CRM_PAGINATION_DATA'] = array();
			}
			$_SESSION['CRM_PAGINATION_DATA'][$this->arResult['GRID_ID']] = array('PAGE_NUM' => $pageNum, 'PAGE_SIZE' => $pageSize);
		}
		else
		{
			if (!$this->isInternal
				&& !(isset($_REQUEST['clear_nav']) && $_REQUEST['clear_nav'] === 'Y')
				&& isset($_SESSION['CRM_PAGINATION_DATA'])
				&& isset($_SESSION['CRM_PAGINATION_DATA'][$this->arResult['GRID_ID']])
			)
			{
				$paginationData = $_SESSION['CRM_PAGINATION_DATA'][$this->arResult['GRID_ID']];
				if (isset($paginationData['PAGE_NUM'])
					&& isset($paginationData['PAGE_SIZE'])
					&& $paginationData['PAGE_SIZE'] == $pageSize
				)
				{
					$pageNum = (int)$paginationData['PAGE_NUM'];
				}
			}

			if ($pageNum <= 0)
			{
				$pageNum = 1;
			}
		}
		//endregion

		$nav = new Main\UI\PageNavigation("crm-order-list");
		$nav->allowAllRecords(true)
			->setPageSize($pageSize)
			->setCurrentPage($pageNum)
			->setRecordCount($total)
			->initFromUri();
		return $nav;
	}

	protected function loadClientData(array $orderIds, bool $needContact, bool $needCompany): array
	{
		if(empty($orderIds) || (!$needCompany && !$needContact))
		{
			return [];
		}

		$result = [];
		$runtime = [];
		$select = ['*'];

		if($needCompany)
		{
			$runtime[] = new Main\Entity\ReferenceField(
				'COMPANY',
				\Bitrix\Crm\CompanyTable::class,
				[
					'=this.ENTITY_ID' => 'ref.ID',
					'=this.ENTITY_TYPE_ID' => new Main\DB\SqlExpression('?i', \CCrmOwnerType::Company)
				],
				[
					'join_type' => 'LEFT'
				]
			);

			$select['COMPANY_TITLE'] = 'COMPANY.TITLE';
		}

		if($needContact)
		{
			$runtime[] = new Main\Entity\ReferenceField(
				'CONTACT',
				\Bitrix\Crm\ContactTable::class,
				[
					'=this.ENTITY_ID' => 'ref.ID',
					'=this.ENTITY_TYPE_ID' => new Main\DB\SqlExpression('?i', \CCrmOwnerType::Contact)
				],
				[
					'join_type' => 'LEFT'
				]
			);

			$select['CONTACT_FULL_NAME'] = 'CONTACT.FULL_NAME';
			$select['CONTACT_COMPANY_TITLE'] = 'CONTACT.COMPANY.TITLE';
		}

		$res = Order\ContactCompanyCollection::getList([
			'filter' => [
				'=ORDER_ID' => $orderIds,
				'=IS_PRIMARY' => 'Y',
			],
			'runtime' => $runtime,
			'select' => $select
		]);

		while ($row = $res->fetch())
		{
			$item = [
				'ENTITY_TYPE_ID' => $row['ENTITY_TYPE_ID'],
				'ENTITY_ID' => $row['ENTITY_ID'],
				'PREFIX' => 'ORDER_'.$row['ORDER_ID'],
			];

			if((int)$row['ENTITY_TYPE_ID'] === \CCrmOwnerType::Contact)
			{
				$item['TITLE'] = $row['CONTACT_FULL_NAME'];
				$item['DESCRIPTION'] = $row['CONTACT_COMPANY_TITLE'];
			}
			elseif((int)$row['ENTITY_TYPE_ID'] === \CCrmOwnerType::Company)
			{
				$item['TITLE'] = $row['COMPANY_TITLE'];
			}
			else
			{
				continue;
			}

			if(!isset($result[$row['ORDER_ID']]))
			{
				$result[$row['ORDER_ID']] = [];
			}

			$result[$row['ORDER_ID']][$row['ENTITY_TYPE_ID']] = $item;
		}

		return $result;
	}

	protected function loadPaymentData(array $orderIds): array
	{
		if(empty($orderIds))
		{
			return [];
		}

		$result = [];
		$urlTemplate = '/shop/orders/payment/details/#PAYMENT_ID#/';

		$res = Order\Payment::getList(array(
			'order' => array('ID' => 'ASC'),
			'filter' => array('ORDER_ID' => $orderIds)
		));

		while($item = $res->fetch())
		{
			$item['URL'] = str_replace(
				'#PAYMENT_ID#',
				$item['ID'],
				$urlTemplate
			);

			$item['SUM'] = SaleFormatCurrency($item["SUM"], $item["CURRENCY"]);
			$result[$item['ORDER_ID']][] = $item;
		}

		return $result;
	}

	protected function loadShipmentData(array $orderIds): array
	{
		if(empty($orderIds))
		{
			return [];
		}

		$result = [];
		$urlTemplate = '/shop/orders/shipment/details/#SHIPMENT_ID#/?order_id=#ORDER_ID#';

		$dbItemsList = Order\Shipment::getList([
			'order' => ['ID' => 'ASC'],
			'filter' => ['ORDER_ID' => $orderIds, '!=SYSTEM' => 'Y'],
			'select' => [
				'*',
				'ORDER_SITE_ID' => 'ORDER.LID'
			]
		]);

		while ($item = $dbItemsList->fetch())
		{
			$item['DELIVERY_NAME'] = htmlspecialcharsbx($item['DELIVERY_NAME']);
			$item['STATUS'] = $this->getShipmentStatus($item['STATUS_ID'], LANGUAGE_ID);
			$item['PRICE_DELIVERY'] = SaleFormatCurrency($item["PRICE_DELIVERY"], $item["CURRENCY"]);
			$item['URL'] = str_replace(
				['#SHIPMENT_ID#', '#ORDER_ID#'],
				[$item['ID'], $item['ORDER_ID']],
				$urlTemplate
			);
			$item['WEIGHT'] = $this->getReadableWeight((float)$item['WEIGHT'], 1, $item['ORDER_SITE_ID']);
			$result[$item['ORDER_ID']][] = $item;
		}

		return $result;
	}

	protected function getShipmentStatus(string $statusId, string $lang)
	{
		if($statusId == '')
		{
			return '';
		}

		static $data = null;

		if($data === null)
		{
			$data = $this->loadShipmentStatusData($lang);
		}

		return isset($data[$statusId]) ? $data[$statusId] : '';
	}

	protected function loadShipmentStatusData(string $lang)
	{
		if($lang == '')
		{
			return [];
		}

		$result = [];

		$dbRes = Order\DeliveryStatus::getList([
			'select' => ['ID', 'NAME' => 'Bitrix\Sale\Internals\StatusLangTable:STATUS.NAME'],
			'filter' => [
				'=Bitrix\Sale\Internals\StatusLangTable:STATUS.LID' => $lang
			],
		]);

		while ($shipmentStatus = $dbRes->fetch())
		{
			$result[$shipmentStatus["ID"]] = $shipmentStatus["NAME"] . " [" . $shipmentStatus["ID"] . "]";
		}

		return $result;
	}

	/**
	 * @param int[] $orderIds
	 * @return array
	 * @throws Main\ArgumentException
	 * @throws Main\ObjectPropertyException
	 * @throws Main\SystemException
	 * Notice: we suggest that iblock module was included with catalog module
	 */
	protected function loadBasketData(array $orderIds): array
	{
		if(empty($orderIds))
		{
			return [];
		}

		$result = [];
		$basketItems = [];
		$dbItemsList = Order\Basket::getList(array(
			'order' => ['ID' => 'ASC'],
			'filter' => ['=ORDER_ID' => $orderIds],
			'select' => [
				'*',
				'ORDER_SITE_ID' => 'ORDER.LID',
				'PRODUCT__ID' => 'PRODUCT.ID',
				'IBLOCK_ID' => 'PRODUCT.IBLOCK.IBLOCK_ID',
				'IBLOCK_SECTION_ID' => 'PRODUCT.IBLOCK.IBLOCK_SECTION_ID'
			]
		));

		while ($item = $dbItemsList->fetch())
		{
			$item['NAME'] = htmlspecialcharsbx($item['NAME']);

			$item['EDIT_PAGE_URL'] = '';

			if((int)$item['IBLOCK_ID'] > 0 && (int)$item['PRODUCT__ID'] > 0)
			{
				$this->urlBuilder->setIblockId((int)$item['IBLOCK_ID']);
				$item['EDIT_PAGE_URL'] = $this->urlBuilder->getElementDetailUrl(
					(int)$item['PRODUCT__ID'],
					[
						'find_section_section' => (int)$item['IBLOCK_SECTION_ID'] > 0 ? (int)$item['IBLOCK_SECTION_ID'] : 0,
						'WF' => 'Y'
					]
				);
			}

			$item['PRICE'] = SaleFormatCurrency($item['PRICE'], $item['CURRENCY']);
			$measure = isset($arItem["MEASURE_NAME"]) ? $item["MEASURE_NAME"] : Loc::getMessage("CRM_ORDER_LIST_DEFAULT_MEASURE");
			$item['WEIGHT'] = $this->getReadableWeight((float)$item['WEIGHT'], (float)$item['QUANTITY'], $item['ORDER_SITE_ID']);
			$item["QUANTITY"] = htmlspecialcharsbx(Order\BasketItem::formatQuantity($item["QUANTITY"])) . " " . htmlspecialcharsbx($measure);
			$item["PROPS"] = [];
			$basketItems[$item['ID']] = $item;
		}

		if (!empty($basketItems))
		{
			$basketItemsIds = array_keys($basketItems);
			$propertyItemRaw = Order\BasketPropertyItem::getList([
				'filter' => [
					'=BASKET_ID' => $basketItemsIds,
					"!CODE" => ["CATALOG.XML_ID", "PRODUCT.XML_ID"]
				]
			]);
			while($propertyItem = $propertyItemRaw->fetch())
			{
				$basketItems[$propertyItem['BASKET_ID']]['PROPS'][] = $propertyItem;
			}

			foreach($basketItems as $item)
			{
				if(!is_array($result[$item['ORDER_ID']]))
				{
					$result[$item['ORDER_ID']] = [];
				}

				$result[$item['ORDER_ID']][] = $item;
			}
		}

		return $result;
	}

	protected function getReadableWeight(float $weight, float $quantity, string $orderSiteId)
	{
		if($weight <= 0)
		{
			return '';
		}

		$siteData = $this->getSiteData($orderSiteId);

		$result = $weight;

		if((float)$siteData['WEIGHT_KOEF'] > 0)
		{
			$result = (float)($weight / $siteData['WEIGHT_KOEF']);
		}

		if((float)($quantity) > 0)
		{
			$result *= $quantity;
		}

		$weightUnit = isset($siteData['WEIGHT_UNIT']) ? ' '.$siteData['WEIGHT_UNIT'] : '';
		return htmlspecialcharsbx(roundEx($result, SALE_WEIGHT_PRECISION).$weightUnit);
	}

	protected function getSiteData(string $siteId)
	{
		static $data = null;

		if($data === null)
		{
			$data = $this->loadSiteData();
		}

		return $data[$siteId] ? $data[$siteId] : [];
	}

	protected function loadSiteData()
	{
		$result = [];
		$dbRes = Main\SiteTable::getList();

		while ($row = $dbRes->fetch())
		{
			$serverName = $row['SERVER_NAME'];

			if($serverName == '')
			{
				if(defined('SITE_SERVER_NAME') && SITE_SERVER_NAME <> '')
					$serverName = SITE_SERVER_NAME;
				else
					$serverName = \Bitrix\Main\Config\Option::get('main', 'server_name', '');
			}

			$result[$row['LID']] = [
				'SERVER_NAME' => $serverName,
				'WEIGHT_UNIT' => htmlspecialcharsbx(\Bitrix\Main\Config\Option::get('sale', 'weight_unit', '', $row['LID'])),
				'WEIGHT_KOEF' => htmlspecialcharsbx(\Bitrix\Main\Config\Option::get('sale', 'weight_koef', 1, $row['LID']))
			];
		}

		return $result;
	}

	//todo: for all by one query
	protected function loadPropsData(int $orderId)
	{
		if($orderId <= 0)
		{
			return [];
		}

		if(!($propOrder = $this->loadOrder($orderId)))
		{
			return [];
		}

		if(!($collection = $propOrder->getPropertyCollection()))
		{
			return [];
		}

		$result = [];

		foreach ($collection->getGroups() as $group)
		{
			$items = [];

			/** @var Order\PropertyValue $property */
			foreach ($collection->getPropertiesByGroupId($group['ID']) as $property)
			{
				if(!($propertyValue = $property->getValue()))
				{
					continue;
				}

				$items[] = [
					'NAME' => htmlspecialcharsbx($property->getName()),
					'VALUE' => $property->getViewHtml()
				];
			}

			if(!empty($items))
			{
				$result[] = [
					'NAME' => htmlspecialcharsbx($group['NAME']),
					'ITEMS' => $items
				];
			}
		}

		return $result;
	}

	protected function getOrder(int $orderId)
	{
		$orders = [];

		if(!isset($orders[$orderId]))
		{
			$orders[$orderId] = $this->loadOrder($orderId);
		}

		return $orders[$orderId];

	}

	protected function loadOrder(int $orderId)
	{
		return Order\Order::load($orderId);
	}
}
?>

