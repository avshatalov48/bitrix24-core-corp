<?php
if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)
	die();

use Bitrix\Main\Localization\Loc;
use Bitrix\Crm\Component\ComponentError;

Loc::loadMessages(__FILE__);

class CCrmOrderProductDetailsComponent extends \CBitrixComponent
{
	protected function getErrorMessage($error)
	{
		if($error === ComponentError::ENTITY_NOT_FOUND)
		{
			return Loc::getMessage('CRM_ORDER_NOT_FOUND');
		}
		return ComponentError::getMessage($error);
	}

	public function executeComponent()
	{
		global $APPLICATION;
		//region Params
		$this->arResult['ENTITY_ID'] = isset($this->arParams['~ENTITY_ID']) ? (int)$this->arParams['~ENTITY_ID'] : 0;
		$this->arResult['ENTITY_TYPE'] = isset($this->arParams['~ENTITY_TYPE']) ? (int)$this->arParams['~ENTITY_TYPE'] : CCrmOwnerType::Order;

		if (!\Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($this->arResult['ENTITY_ID']))
		{
			ShowError($this->getErrorMessage(ComponentError::PERMISSION_DENIED));
			return;
		}

		if (!empty($this->arParams['BASKET_ID']))
		{
			$isProductCreation = false;
			if ($this->arParams['BASKET_ID'])
			{
				$this->arResult['ENTITY_DATA'] = $this->prepareEntityData();
				if (empty($this->arResult['ENTITY_DATA']))
				{
					$isProductCreation = true;
				}
			}
		}
		else
		{
			$isProductCreation = true;
		}

		if (empty($this->arResult['ENTITY_DATA']['CURRENCY']))
		{
			$this->arResult['ENTITY_DATA']['CURRENCY'] = $this->arParams['CURRENCY'];
		}

		$title = $isProductCreation ? Loc::getMessage('CRM_ORDER_PRODUCT_DETAILS_CREATE_TITLE') : Loc::getMessage('CRM_ORDER_PRODUCT_DETAILS_EDIT_TITLE');
		$this->arResult['IS_PRODUCT_CREATION'] = $isProductCreation;
		$APPLICATION->SetTitle($title);

		$this->guid = $this->arResult['GUID'] = isset($this->arParams['GUID'])
			? $this->arParams['GUID'] : "order_order_product_detail_{$this->arResult['ENTITY_ID']}";

		$this->arResult['EDITOR_CONFIG_ID'] = isset($this->arParams['EDITOR_CONFIG_ID'])
			? $this->arParams['EDITOR_CONFIG_ID'] : 'order_order_product_detail';

		$this->arResult['CONTEXT_ID'] = \CCrmOwnerType::OrderName.'_'.$this->arResult['ENTITY_ID'];

		$measureListItems = array('' => GetMessage('CRM_MEASURE_NOT_SELECTED'));
		$measures = \Bitrix\Crm\Measure::getMeasures(100);
		if (is_array($measures))
		{
			foreach ($measures as $measure)
				$measureListItems[$measure['ID']] = $measure['SYMBOL'];
			unset($measure);
		}
		unset($measures);

		$this->arResult['ENTITY_FIELDS'] = array(
			array(
				'name' => 'PRODUCT_ID',
				'title' => Loc::getMessage('CRM_ORDER_PRODUCT_DETAILS_CODE'),
				'type' => 'text',
				'editable' => true,
				'enabledMenu' => false,
				'transferable' => false
			),
			array(
				'name' => 'NAME',
				'title' => Loc::getMessage('CRM_ORDER_PRODUCT_DETAILS_NAME'),
				'type' => 'text',
				'required' => true,
				'editable' => true,
				'enabledMenu' => false,
				'transferable' => false
			),
			array(
				'name' => 'PRICE_WITH_CURRENCY',
				'title' => Loc::getMessage('CRM_ORDER_PRODUCT_DETAILS_PRICE'),
				'type' => 'money',
				'required' => true,
				'editable' => true,
				'data' => array(
					'affectedFields' => array('CURRENCY', 'PRICE'),
					'currency' => array(
						'name' => 'CURRENCY',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions(CCrmCurrencyHelper::PrepareListItems())
					),
					'amount' => 'PRICE',
					'formatted' => 'FORMATTED_PRICE',
					'formattedWithCurrency' => 'FORMATTED_PRICE_WITH_CURRENCY'
				)
			),
			array(
				'name' => 'QUANTITY',
				'title' => Loc::getMessage('CRM_ORDER_PRODUCT_DETAILS_QUANTITY'),
				'type' => 'order_quantity',
				'editable' => true,
				'required' => true,
				'data' => array(
					'affectedFields' => array('MEASURE_CODE', 'QUANTITY'),
					'measure' => array(
						'name' => 'MEASURE_CODE',
						'items'=> \CCrmInstantEditorHelper::PrepareListOptions($measureListItems)
					),
					'amount' => 'QUANTITY',
					'formatted' => 'FORMATTED_QUANTITY',
					'formattedWithCurrency' => 'FORMATTED_QUANTITY_WITH_MEASURE'
				)
			),
			array(
				'name' => 'CATALOG_XML_ID',
				'title' => Loc::getMessage('CRM_ORDER_PRODUCT_DETAILS_CATALOG_XML_ID'),
				'type' => 'text',
				'editable' => true,
				'enabledMenu' => false,
				'transferable' => false
			),
			array(
				'name' => 'PRODUCT_XML_ID',
				'title' => Loc::getMessage('CRM_ORDER_PRODUCT_DETAILS_PRODUCT_XML_ID'),
				'type' => 'text',
				'editable' => true,
				'enabledMenu' => false,
				'transferable' => false
			),
			array(
				'name' => 'WEIGHT',
				'title' => Loc::getMessage('CRM_ORDER_PRODUCT_DETAILS_WEIGHT'),
				'type' => 'text',
				'editable' => true,
				'enabledMenu' => false,
				'transferable' => false
			),
			array(
				'name' => 'PROPS',
				'type' => 'order_product_property',
				'editable' => true,
				'enabledMenu' => false,
				'transferable' => false
			)
		);
		$this->arResult['ENTITY_CONFIG'] = array(
			array(
				'name' => 'main',
				'title' => Loc::getMessage('CRM_ORDER_PRODUCT_DETAILS_SECTION_MAIN'),
				'type' => 'section',
				'data' => array(
					'showButtonPanel' => false
				),
				'elements' => array(
					array('name' => 'PRODUCT_ID'),
					array('name' => 'NAME'),
					array('name' => 'PRICE_WITH_CURRENCY'),
					array('name' => 'QUANTITY'),
					array('name' => 'WEIGHT'),
					array('name' => 'CATALOG_XML_ID'),
					array('name' => 'PRODUCT_XML_ID'),
					array('name' => 'PROPS'),
				)
			),
		);
		$this->arResult['ENTITY_CONTROLLERS'] = array(
			array(
				"name" => "ORDER_PRODUCT_CONTROLLER",
				"type" => "order_product_controller",
			)
		);

		$this->includeComponentTemplate();
	}

	protected function prepareEntityData()
	{
		$entityData = array();
		if (isset($_SESSION['ORDER_BASKET'][(int)$this->arParams['ORDER_ID']]['ITEMS'][$this->arParams['BASKET_ID']]))
		{
			$entityData = $_SESSION['ORDER_BASKET'][(int)$this->arParams['ORDER_ID']]['ITEMS'][$this->arParams['BASKET_ID']];
		}
		else
		{
			$orderId = (int)$this->arParams['ORDER_ID'];
			if ($orderId ===0 || !\Bitrix\Crm\Order\Permissions\Order::checkUpdatePermission($orderId, CCrmPerms::GetCurrentUserPermissions()))
			{
				return $entityData;
			}
			$order = \Bitrix\Crm\Order\Order::load($orderId);
			if (empty($order))
			{
				return $entityData;
			}

			$basket = $order->getBasket();
			$basketItem = $basket->getItemById((int)$this->arParams['BASKET_ID']);
			$entityData = $basketItem->getFieldValues();
			$propertyCollection = $basketItem->getPropertyCollection();
			$entityData['PROPS'] = $propertyCollection->getPropertyValues();
		}

		if (empty($entityData))
		{
			return array();
		}
		if (empty($entityData['CURRENCY']))
		{
			$entityData['CURRENCY'] = \CCrmCurrency::GetBaseCurrencyID();
		}

		$entityData['FORMATTED_PRICE'] = CCrmCurrency::MoneyToString($entityData['PRICE'], $entityData['CURRENCY'],'#');
		$entityData['FORMATTED_QUANTITY'] = $entityData['QUANTITY'];

		return $entityData;
	}

	protected function prepareFieldInfos()
	{
		return $this->arResult['ENTITY_FIELDS'];
	}
}
